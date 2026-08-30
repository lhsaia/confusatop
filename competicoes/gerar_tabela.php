<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Login required']));
}

if($_SESSION['emTestes'] ?? false){
    die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem gerar tabelas de competições.']));
}

$idCompeticao = intval($_POST['id']);
$tipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0; // 0: Misto, 1: Mata-mata, 2: Round-robin

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$compInfo = $competicao->readInfo($idCompeticao);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die(json_encode(array("success" => false, "error" => "Você não tem permissão para gerar a tabela desta competição.")));
}

$options = $competicao->getOptions($idCompeticao);
$isSlots = (isset($options['sorteio']) && intval($options['sorteio']) == 2);
$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;

// Clean existing games
$competicao->limparJogos($idCompeticao);

// Load teams from MariaDB
$stmt = $competicao->carregarListaTimes($idCompeticao);
$teams = [];
if($stmt){
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        if(isset($row['has_team']) && ($row['has_team'] == '1' || $row['has_team'] === 1)){
            $teamId = (isset($row['id_time_portal']) && intval($row['id_time_portal']) > 0) ? intval($row['id_time_portal']) : intval($row['codigo_time']);
            if($teamId > 0 && !in_array($teamId, $teams)){
                $teams[] = $teamId;
            }
        }
    }
}

// Fallback: Se tiver menos de 2 times marcados no MariaDB, buscar no SQLite da competição diretamente
if(count($teams) < 2){
    $teams = [];
    $db3File = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
    if(file_exists($db3File)){
        $liteDb = new SQLiteDatabase();
        $liteDb->fileName = $db3File;
        $sdbTmp = $liteDb->getConnection();
        if($sdbTmp){
            $stmtClube = $sdbTmp->query("SELECT ID FROM clube");
            if($stmtClube){
                while($rC = $stmtClube->fetch(PDO::FETCH_ASSOC)){
                    $cId = intval($rC['ID']);
                    if($cId > 0 && !in_array($cId, $teams)){
                        $teams[] = $cId;
                    }
                }
            }
        }
    }
}

if(!$isSlots && count($teams) < 2){
    die(json_encode(['success' => false, 'error' => 'É necessário confirmar pelo menos 2 times na competição (na tela Lista de Times) para gerar a tabela automaticamente. Times confirmados encontrados: ' . count($teams)]));
}

// Get SQLite connection for referees and stadiums
$liteDatabase = new SQLiteDatabase();
$liteDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
$ldb = $liteDatabase->getConnection();

$arbitros = [];
try {
    if($ldb){
        $arbitro_obj = new TrioArbitragem($ldb);
        $stmtArbitros = $arbitro_obj->carregarListaArbitrosSqlite();
        if($stmtArbitros){
            $arbitros = $stmtArbitros->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch(Exception $e) {
    $arbitros = [];
}

$estadios = [];
try {
    if($ldb){
        $stmtEstadios = $ldb->query("SELECT ID FROM estadio");
        if($stmtEstadios){
            $estadios = $stmtEstadios->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch(Exception $e) {
    $estadios = [];
}

function getStadiumForTeam($ldb, $teamId) {
    if(!$ldb || !is_numeric($teamId) || intval($teamId) <= 0) return 0;
    try {
        $query = "SELECT Estadio FROM clube WHERE ID = :id";
        $stmt = $ldb->prepare($query);
        $stmt->bindParam(":id", $teamId);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($res && isset($res['Estadio'])) ? intval($res['Estadio']) : 0;
    } catch(Exception $e) {
        return 0;
    }
}

function getStadiumForMatch($ldb, $teamId, $estadios_times, $estadios) {
    if ($estadios_times == 1 && is_numeric($teamId) && intval($teamId) > 0) {
        return getStadiumForTeam($ldb, $teamId);
    } else {
        return count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0;
    }
}

function getNeutralStadiumForMatch($ldb, $teamA_id, $teamB_id, $estadios, &$usedStadiumsInPhase) {
    if (empty($estadios)) return 0;

    $estadioA = getStadiumForTeam($ldb, $teamA_id);
    $estadioB = getStadiumForTeam($ldb, $teamB_id);

    // 1. Filtrar estádios que não pertencem nem ao time A nem ao time B
    $disponiveis = [];
    foreach ($estadios as $est) {
        $eId = intval($est['ID']);
        if ($eId > 0 && $eId != $estadioA && $eId != $estadioB) {
            $disponiveis[] = $eId;
        }
    }

    // Se não sobrar nenhum estádio exclusivo, usa todos exceto os dos times
    if (empty($disponiveis)) {
        foreach ($estadios as $est) {
            $disponiveis[] = intval($est['ID']);
        }
    }

    // 2. Tentar selecionar um estádio que ainda não foi usado nesta fase
    $naoUsados = array_values(array_diff($disponiveis, $usedStadiumsInPhase));
    if (!empty($naoUsados)) {
        $escolhido = $naoUsados[array_rand($naoUsados)];
    } else {
        // Se todos os disponíveis já foram usados pelo menos uma vez na fase, reseta ou pega dos disponíveis
        $escolhido = $disponiveis[array_rand($disponiveis)];
    }

    $usedStadiumsInPhase[] = $escolhido;
    return $escolhido;
}

// Default date
$currentDate = date("Y-m-d H:i:s");

$assignedSlotTeams = [];
if ($isSlots) {
    $stSlots = $db->prepare("SELECT slot, id_time_portal, codigo_time, has_team FROM competicao_times WHERE id_competicao = :id AND slot IS NOT NULL AND slot != ''");
    $stSlots->bindParam(':id', $idCompeticao);
    $stSlots->execute();
    while($rSlot = $stSlots->fetch(PDO::FETCH_ASSOC)){
        $sName = trim($rSlot['slot']);
        if($sName !== ''){
            if(!empty($rSlot['id_time_portal']) && intval($rSlot['id_time_portal']) > 0){
                $assignedSlotTeams[$sName] = intval($rSlot['id_time_portal']);
            } else if($rSlot['has_team'] == 1 || $rSlot['has_team'] == '1'){
                $assignedSlotTeams[$sName] = -1 * abs(intval($rSlot['codigo_time']));
            }
        }
    }
}

if($tipo == 2) { // Round-robin (Pontos Corridos)
    $roundsLimit = (isset($options['turnos_pontos_corridos']) && intval($options['turnos_pontos_corridos']) >= 1 && intval($options['turnos_pontos_corridos']) <= 4) 
                   ? intval($options['turnos_pontos_corridos']) 
                   : 2;

    if ($isSlots) {
        $numTeams = (isset($options['numero_times']) && intval($options['numero_times']) >= 2) ? intval($options['numero_times']) : (count($teams) >= 2 ? count($teams) : 8);
        $drawTeams = [];
        for ($s = 1; $s <= $numTeams; $s++) {
            $drawTeams[] = "Slot " . $s;
        }
    } else {
        $numTeams = count($teams);
        shuffle($teams);
        $drawTeams = $teams;
    }
    
    $pivot = null;
    $teamsToDraw = $drawTeams;
    if ($numTeams % 2 == 0) {
        $pivot = array_shift($teamsToDraw);
    }
    
    $matchdays = ($numTeams % 2 == 0) ? $numTeams - 1 : $numTeams;
    $baseSchedule = [];
    
    for ($i = 0; $i < $matchdays; $i++) {
        $matchday = [];
        $size = count($teamsToDraw);
        $half = (int)($size / 2);
        
        for ($j = 0; $j <= $half; $j++) {
            if ($numTeams % 2 == 1 && $j == $half) {
                continue; // Bye
            }
            $team1 = $teamsToDraw[$j];
            if ($numTeams % 2 == 0 && $j == $half) {
                if ($i % 2 == 1) {
                    $team2 = $pivot;
                } else {
                    $team2 = $team1;
                    $team1 = $pivot;
                }
            } else {
                $team2 = $teamsToDraw[$size - 1 - $j];
            }
            $matchday[] = ['home' => $team1, 'away' => $team2];
        }
        $baseSchedule[] = $matchday;
        
        // Rotate
        $rotationFactor = (int)($numTeams / 2);
        if ($numTeams % 2 == 1) {
            $rotationFactor++;
        }
        for ($r = 0; $r < $rotationFactor; $r++) {
            $temp = array_shift($teamsToDraw);
            array_push($teamsToDraw, $temp);
        }
    }
    
    // Inserir jogos no banco conforme o número de turnos escolhido
    $totalRoundIndex = 0;
    for ($r = 1; $r <= $roundsLimit; $r++) {
        $shouldInvert = ($r % 2 == 0);
        foreach ($baseSchedule as $mdayIndex => $matchdayMatches) {
            foreach ($matchdayMatches as $match) {
                $home = $shouldInvert ? $match['away'] : $match['home'];
                $away = $shouldInvert ? $match['home'] : $match['away'];
                
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($totalRoundIndex * 3) . " days"));
                
                if ($isSlots) {
                    $tA_id = isset($assignedSlotTeams[$home]) ? $assignedSlotTeams[$home] : 0;
                    $tA_nome = ($tA_id == 0) ? $home : null;
                    $tB_id = isset($assignedSlotTeams[$away]) ? $assignedSlotTeams[$away] : 0;
                    $tB_nome = ($tB_id == 0) ? $away : null;
                    $estId = ($tA_id > 0) ? getStadiumForMatch($ldb, $tA_id, $estadios_times, $estadios) : (count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0);
                    $competicao->inserirJogo($idCompeticao, $tA_id, $tB_id, 2, $arbId, $estId, $dateMatch, "false", null, null, $tA_nome, $tB_nome);
                } else {
                    $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                    $competicao->inserirJogo($idCompeticao, $home, $away, 2, $arbId, $estId, $dateMatch, "false", null);
                }
            }
            $totalRoundIndex++;
        }
    }
} else if ($tipo == 1) { // Mata-mata
    if ($isSlots) {
        $numTeams = (isset($options['numero_times']) && intval($options['numero_times']) >= 2) ? intval($options['numero_times']) : (count($teams) >= 2 ? count($teams) : 8);
        $drawTeams = [];
        for ($s = 1; $s <= $numTeams; $s++) {
            $drawTeams[] = "Slot " . $s;
        }
    } else {
        shuffle($teams);
        $numTeams = count($teams);
        $drawTeams = $teams;
    }
    
    // 1. Determinar tamanho padrão da chave (potência de 2 mais próxima acima)
    $bracketSize = 2;
    if ($numTeams > 32) {
        $bracketSize = 64;
    } else if ($numTeams > 16) {
        $bracketSize = 32;
    } else if ($numTeams > 8) {
        $bracketSize = 16;
    } else if ($numTeams > 4) {
        $bracketSize = 8;
    } else if ($numTeams > 2) {
        $bracketSize = 4;
    } else {
        $bracketSize = 2;
    }

    // Determinar fase inicial baseada no tamanho da chave
    $faseMapSize = [
        64 => 10, // 32-avos-de-final
        32 => 9,  // 16-avos-de-final
        16 => 3,  // Oitavas
        8  => 4,  // Quartas
        4  => 5,  // Semifinal
        2  => 8   // Final
    ];
    $fase = $faseMapSize[$bracketSize] ?? 3;

    // Calcular quantidade de BYEs (times que avançam direto para a fase seguinte)
    $numByes = $bracketSize - $numTeams;
    $usedStadiumsInPhase = [];

    if ($numByes > 0 && $bracketSize > 2) {
        // Sorteio dos times que ganham BYE: embaralha a lista antes de separar
        shuffle($drawTeams);
        $teamsWithBye = array_slice($drawTeams, 0, $numByes);
        $teamsPlayingFirstRound = array_slice($drawTeams, $numByes);
        $numMatchesRound1 = count($teamsPlayingFirstRound) / 2;

        // Inserir os jogos da primeira fase
        for ($i = 0; $i < count($teamsPlayingFirstRound); $i += 2) {
            if (isset($teamsPlayingFirstRound[$i+1])) {
                $home = $teamsPlayingFirstRound[$i];
                $away = $teamsPlayingFirstRound[$i+1];
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . floor($i/2) . " days"));

                if ($isSlots) {
                    $tA_id = isset($assignedSlotTeams[$home]) ? $assignedSlotTeams[$home] : 0;
                    $tA_nome = ($tA_id == 0) ? $home : null;
                    $tB_id = isset($assignedSlotTeams[$away]) ? $assignedSlotTeams[$away] : 0;
                    $tB_nome = ($tB_id == 0) ? $away : null;
                    $estId = getNeutralStadiumForMatch($ldb, $tA_id, $tB_id, $estadios, $usedStadiumsInPhase);
                    $competicao->inserirJogo($idCompeticao, $tA_id, $tB_id, $fase, $arbId, $estId, $dateMatch, "true", null, null, $tA_nome, $tB_nome);
                } else {
                    $estId = getNeutralStadiumForMatch($ldb, $home, $away, $estadios, $usedStadiumsInPhase);
                    $competicao->inserirJogo($idCompeticao, $home, $away, $fase, $arbId, $estId, $dateMatch, "true", null);
                }
            }
        }
    } else {
        // Chave perfeita sem BYEs (ex: 64, 32, 16, 8, 4, 2)
        shuffle($drawTeams);
        for ($i = 0; $i < $numTeams; $i += 2) {
            if (isset($drawTeams[$i+1])) {
                $home = $drawTeams[$i];
                $away = $drawTeams[$i+1];
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . floor($i/2) . " days"));
                
                if ($isSlots) {
                    $tA_id = isset($assignedSlotTeams[$home]) ? $assignedSlotTeams[$home] : 0;
                    $tA_nome = ($tA_id == 0) ? $home : null;
                    $tB_id = isset($assignedSlotTeams[$away]) ? $assignedSlotTeams[$away] : 0;
                    $tB_nome = ($tB_id == 0) ? $away : null;
                    $estId = getNeutralStadiumForMatch($ldb, $tA_id, $tB_id, $estadios, $usedStadiumsInPhase);
                    $competicao->inserirJogo($idCompeticao, $tA_id, $tB_id, $fase, $arbId, $estId, $dateMatch, "true", null, null, $tA_nome, $tB_nome);
                } else {
                    $estId = getNeutralStadiumForMatch($ldb, $home, $away, $estadios, $usedStadiumsInPhase);
                    $competicao->inserirJogo($idCompeticao, $home, $away, $fase, $arbId, $estId, $dateMatch, "true", null);
                }
            }
        }
    }
} else if ($tipo == 0) { // Misto (Grupos + Mata-Mata)
    $numGroups = (isset($options['num_grupos']) && intval($options['num_grupos']) > 0) ? intval($options['num_grupos']) : 4;
    $teamsPerGroup = (isset($options['times_por_grupo']) && intval($options['times_por_grupo']) > 0) ? intval($options['times_por_grupo']) : 4;
    $tipoPreliminar = isset($options['tipo_preliminar']) ? intval($options['tipo_preliminar']) : 1; // 0=ida, 1=ida e volta
    $capacidadeGrupos = $numGroups * $teamsPerGroup;

    if ($isSlots) {
        $totalTeams = (isset($options['numero_times']) && intval($options['numero_times']) >= $capacidadeGrupos) ? intval($options['numero_times']) : $capacidadeGrupos;
        $preliminarDateOffset = 0;
        
        if ($totalTeams > $capacidadeGrupos) {
            $excedente = $totalTeams - $capacidadeGrupos;
            $numTimesPreliminar = $excedente * 2;
            
            for ($p = 0; $p < $numTimesPreliminar; $p += 2) {
                $pHome = "P" . ($p + 1);
                $pAway = "P" . ($p + 2);
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $dateMatch1 = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($preliminarDateOffset * 3) . " days"));
                
                $tA_id = isset($assignedSlotTeams[$pHome]) ? $assignedSlotTeams[$pHome] : 0;
                $tA_nome = ($tA_id == 0) ? $pHome : null;
                $tB_id = isset($assignedSlotTeams[$pAway]) ? $assignedSlotTeams[$pAway] : 0;
                $tB_nome = ($tB_id == 0) ? $pAway : null;
                $estId1 = ($tA_id > 0) ? getStadiumForMatch($ldb, $tA_id, $estadios_times, $estadios) : (count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0);
                
                $competicao->inserirJogo($idCompeticao, $tA_id, $tB_id, 1, $arbId, $estId1, $dateMatch1, "false", "P", null, $tA_nome, $tB_nome);
                
                if ($tipoPreliminar == 1) {
                    $estIdVolta = ($tB_id > 0) ? getStadiumForMatch($ldb, $tB_id, $estadios_times, $estadios) : (count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0);
                    $dateMatch2 = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . (($preliminarDateOffset + 1) * 3) . " days"));
                    $competicao->inserirJogo($idCompeticao, $tB_id, $tA_id, 1, $arbId, $estIdVolta, $dateMatch2, "false", "P", null, $tB_nome, $tA_nome);
                }
                $preliminarDateOffset += ($tipoPreliminar == 1 ? 2 : 1);
            }
        }
        
        $groupDateBaseOffset = $preliminarDateOffset + 1;
        
        for($g = 0; $g < $numGroups; $g++){
            $groupName = chr(65 + $g); // A, B, C...
            $groupSlots = [];
            for ($k = 1; $k <= $teamsPerGroup; $k++) {
                $groupSlots[] = $groupName . $k;
            }
            $n = count($groupSlots);
            if ($n < 2) continue;
            
            $pivot = null;
            $teamsToDraw = $groupSlots;
            if ($n % 2 == 0) {
                $pivot = array_shift($teamsToDraw);
            }
            
            $matchdays = ($n % 2 == 0) ? $n - 1 : $n;
            $groupSchedule = [];
            
            for ($i = 0; $i < $matchdays; $i++) {
                $matchday = [];
                $size = count($teamsToDraw);
                $half = (int)($size / 2);
                
                for ($j = 0; $j <= $half; $j++) {
                    if ($n % 2 == 1 && $j == $half) {
                        continue;
                    }
                    $team1 = $teamsToDraw[$j];
                    if ($n % 2 == 0 && $j == $half) {
                        if ($i % 2 == 1) {
                            $team2 = $pivot;
                        } else {
                            $team2 = $team1;
                            $team1 = $pivot;
                        }
                    } else {
                        $team2 = $teamsToDraw[$size - 1 - $j];
                    }
                    $matchday[] = ['home' => $team1, 'away' => $team2];
                }
                $groupSchedule[] = $matchday;
                
                $rotationFactor = (int)($n / 2);
                if ($n % 2 == 1) {
                    $rotationFactor++;
                }
                for ($r = 0; $r < $rotationFactor; $r++) {
                    $temp = array_shift($teamsToDraw);
                    array_push($teamsToDraw, $temp);
                }
            }
            
            foreach ($groupSchedule as $mdayIndex => $matchdayMatches) {
                foreach ($matchdayMatches as $match) {
                    $home = $match['home'];
                    $away = $match['away'];
                    $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                    $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . (($groupDateBaseOffset + $mdayIndex) * 3) . " days"));
                    
                    $tA_id = isset($assignedSlotTeams[$home]) ? $assignedSlotTeams[$home] : 0;
                    $tA_nome = ($tA_id == 0) ? $home : null;
                    $tB_id = isset($assignedSlotTeams[$away]) ? $assignedSlotTeams[$away] : 0;
                    $tB_nome = ($tB_id == 0) ? $away : null;
                    $estId = ($tA_id > 0) ? getStadiumForMatch($ldb, $tA_id, $estadios_times, $estadios) : (count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0);
                    
                    $competicao->inserirJogo($idCompeticao, $tA_id, $tB_id, 2, $arbId, $estId, $dateMatch, "false", $groupName, null, $tA_nome, $tB_nome);
                }
            }
        }
    } else {
        shuffle($teams);
        $totalTeams = count($teams);
        $teamsForGroups = $teams;
        $preliminarDateOffset = 0;
        
        if ($totalTeams > $capacidadeGrupos) {
            $excedente = $totalTeams - $capacidadeGrupos;
            $numTimesPreliminar = $excedente * 2;
            
            $timesPreliminar = array_slice($teams, 0, $numTimesPreliminar);
            $timesGarantidos = array_slice($teams, $numTimesPreliminar);
            
            for ($p = 0; $p < $numTimesPreliminar; $p += 2) {
                if (isset($timesPreliminar[$p+1])) {
                    $pHome = $timesPreliminar[$p];
                    $pAway = $timesPreliminar[$p+1];
                    
                    $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                    $estId = getStadiumForMatch($ldb, $pHome, $estadios_times, $estadios);
                    $dateMatch1 = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($preliminarDateOffset * 3) . " days"));
                    
                    $competicao->inserirJogo($idCompeticao, $pHome, $pAway, 1, $arbId, $estId, $dateMatch1, "false", "P");
                    
                    if ($tipoPreliminar == 1) {
                        $estIdVolta = getStadiumForMatch($ldb, $pAway, $estadios_times, $estadios);
                        $dateMatch2 = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . (($preliminarDateOffset + 1) * 3) . " days"));
                        $competicao->inserirJogo($idCompeticao, $pAway, $pHome, 1, $arbId, $estIdVolta, $dateMatch2, "false", "P");
                    }
                    $preliminarDateOffset += ($tipoPreliminar == 1 ? 2 : 1);
                }
            }
            
            $vencedoresPreliminarSlots = [];
            for ($v = 1; $v <= $excedente; $v++) {
                $idxP = ($v - 1) * 2;
                $vencedoresPreliminarSlots[] = $timesPreliminar[$idxP];
            }
            $teamsForGroups = array_merge($timesGarantidos, $vencedoresPreliminarSlots);
        }
        
        $groupDateBaseOffset = $preliminarDateOffset + 1;
        
        for($g = 0; $g < $numGroups; $g++){
            $groupTeams = array_slice($teamsForGroups, $g * $teamsPerGroup, $teamsPerGroup);
            $groupName = chr(65 + $g); // A, B, C...
            $n = count($groupTeams);
            if($n < 2) continue;
            
            $pivot = null;
            $teamsToDraw = $groupTeams;
            if ($n % 2 == 0) {
                $pivot = array_shift($teamsToDraw);
            }
            
            $matchdays = ($n % 2 == 0) ? $n - 1 : $n;
            $groupSchedule = [];
            
            for ($i = 0; $i < $matchdays; $i++) {
                $matchday = [];
                $size = count($teamsToDraw);
                $half = (int)($size / 2);
                
                for ($j = 0; $j <= $half; $j++) {
                    if ($n % 2 == 1 && $j == $half) {
                        continue;
                    }
                    $team1 = $teamsToDraw[$j];
                    if ($n % 2 == 0 && $j == $half) {
                        if ($i % 2 == 1) {
                            $team2 = $pivot;
                        } else {
                            $team2 = $team1;
                            $team1 = $pivot;
                        }
                    } else {
                        $team2 = $teamsToDraw[$size - 1 - $j];
                    }
                    $matchday[] = ['home' => $team1, 'away' => $team2];
                }
                $groupSchedule[] = $matchday;
                
                $rotationFactor = (int)($n / 2);
                if ($n % 2 == 1) {
                    $rotationFactor++;
                }
                for ($r = 0; $r < $rotationFactor; $r++) {
                    $temp = array_shift($teamsToDraw);
                    array_push($teamsToDraw, $temp);
                }
            }
            
            foreach ($groupSchedule as $mdayIndex => $matchdayMatches) {
                foreach ($matchdayMatches as $match) {
                    $home = $match['home'];
                    $away = $match['away'];
                    $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                    $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                    $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . (($groupDateBaseOffset + $mdayIndex) * 3) . " days"));
                    
                    $competicao->inserirJogo($idCompeticao, $home, $away, 2, $arbId, $estId, $dateMatch, "false", $groupName);
                }
            }
        }
    }
}

$countQuery = $db->prepare("SELECT COUNT(*) as total FROM jogos_clube WHERE competicao_id = :id AND simulador_interno = 1");
$countQuery->bindParam(':id', $idCompeticao);
$countQuery->execute();
$resCount = $countQuery->fetch(PDO::FETCH_ASSOC);
$totalGerados = $resCount ? intval($resCount['total']) : 0;

echo json_encode(['success' => true, 'total_jogos' => $totalGerados, 'teams_count' => count($teams), 'tipo' => $tipo, 'is_slots' => $isSlots]);
?>
