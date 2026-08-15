<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Login required']));
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

if(count($teams) < 2){
    die(json_encode(['success' => false, 'error' => 'É necessário confirmar pelo menos 2 times na competição (na tela Lista de Times) para gerar a tabela. Times confirmados encontrados: ' . count($teams)]));
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

$options = $competicao->getOptions($idCompeticao);
$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;

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
    if(!$ldb) return 0;
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
    if ($estadios_times == 1) {
        return getStadiumForTeam($ldb, $teamId);
    } else {
        return count($estadios) > 0 ? $estadios[array_rand($estadios)]['ID'] : 0;
    }
}

// Default date
$currentDate = date("Y-m-d H:i:s");

if($tipo == 2) { // Round-robin (Pontos Corridos)
    $numTeams = count($teams);
    
    // Berger Algorithm implementation from Hexacolor Scheduler 1.2
    // Sort array randomly/uniformly first to map double sort
    shuffle($teams);
    
    $pivot = null;
    $teamsToDraw = $teams;
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
        
        // Rotate (deslocar N/2 elementos para o final)
        $rotationFactor = (int)($numTeams / 2);
        if ($numTeams % 2 == 1) {
            $rotationFactor++;
        }
        for ($r = 0; $r < $rotationFactor; $r++) {
            $temp = array_shift($teamsToDraw);
            array_push($teamsToDraw, $temp);
        }
    }
    
    // Inserir jogos no banco. Supondo 2 turnos (Ida e Volta) se configurado ou 1 turno.
    // Vamos ler a opção de ida e volta do banco de dados (golfora / finalunica indicam se há ida e volta em competições,
    // mas por padrão para pontos corridos vamos gerar Ida e Volta)
    $roundsLimit = 2; // Ida e Volta padrão para Pontos Corridos no portal
    
    $totalRoundIndex = 0;
    for ($r = 1; $r <= $roundsLimit; $r++) {
        $shouldInvert = ($r % 2 == 0);
        foreach ($baseSchedule as $mdayIndex => $matchdayMatches) {
            foreach ($matchdayMatches as $match) {
                $home = $shouldInvert ? $match['away'] : $match['home'];
                $away = $shouldInvert ? $match['home'] : $match['away'];
                
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($totalRoundIndex * 3) . " days"));
                
                $competicao->inserirJogo($idCompeticao, $home, $away, 2, $arbId, $estId, $dateMatch, "false", null);
            }
            $totalRoundIndex++;
        }
    }
} else if ($tipo == 1) { // Mata-mata
    shuffle($teams);
    $numTeams = count($teams);
    
    // Determinar fase inicial baseada na quantidade de times
    $fase = 3; // Oitavas
    if($numTeams <= 16 && $numTeams > 8) $fase = 3; // Oitavas
    else if($numTeams <= 8 && $numTeams > 4) $fase = 4; // Quartas
    else if($numTeams <= 4 && $numTeams > 2) $fase = 5; // Semifinal
    else if($numTeams <= 2) $fase = 8; // Final
    
    for($i = 0; $i < $numTeams; $i += 2){
        if(isset($teams[$i+1])){
            $home = $teams[$i];
            $away = $teams[$i+1];
            $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
            $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
            $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . floor($i/2) . " days"));
            $competicao->inserirJogo($idCompeticao, $home, $away, $fase, $arbId, $estId, $dateMatch, "true", null);
        }
    }
} else if ($tipo == 0) { // Misto (Grupos + Mata-Mata)
    shuffle($teams);
    $totalTeams = count($teams);
    $teamsPerGroup = 4;
    $numGroups = ceil($totalTeams / $teamsPerGroup);
    
    for($g = 0; $g < $numGroups; $g++){
        $groupTeams = array_slice($teams, $g * $teamsPerGroup, $teamsPerGroup);
        $groupName = chr(65 + $g); // A, B, C...
        $n = count($groupTeams);
        if($n < 2) continue;
        
        // Berger para grupos de qualquer tamanho
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
                    continue; // Bye
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
                    $team2 = $teamsToDraw[$size - 1 - j];
                }
                $matchday[] = ['home' => $team1, 'away' => $team2];
            }
            $groupSchedule[] = $matchday;
            
            // Rotate
            $rotationFactor = (int)($n / 2);
            if ($n % 2 == 1) {
                $rotationFactor++;
            }
            for ($r = 0; $r < $rotationFactor; $r++) {
                $temp = array_shift($teamsToDraw);
                array_push($teamsToDraw, $temp);
            }
        }
        
        // Inserir jogos do grupo (1 turno simples por padrão em fase de grupos)
        foreach ($groupSchedule as $mdayIndex => $matchdayMatches) {
            foreach ($matchdayMatches as $match) {
                $home = $match['home'];
                $away = $match['away'];
                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($mdayIndex * 3) . " days"));
                
                $competicao->inserirJogo($idCompeticao, $home, $away, 2, $arbId, $estId, $dateMatch, "false", $groupName);
            }
        }
    }
}
$countQuery = $db->prepare("SELECT COUNT(*) as total FROM competicao_jogos WHERE competicao = :id");
$countQuery->bindParam(':id', $idCompeticao);
$countQuery->execute();
$resCount = $countQuery->fetch(PDO::FETCH_ASSOC);
$totalGerados = $resCount ? intval($resCount['total']) : 0;

echo json_encode(['success' => true, 'total_jogos' => $totalGerados, 'teams_count' => count($teams), 'tipo' => $tipo]);
?>
