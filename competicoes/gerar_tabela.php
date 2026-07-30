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
    if ($numTeams % 2 != 0) {
        $teams[] = null; // Bye
        $numTeams++;
    }

    $rounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;

    for ($r = 0; $r < $rounds; $r++) {
        for ($m = 0; $m < $matchesPerRound; $m++) {
            $home = $teams[$m];
            $away = $teams[$numTeams - 1 - $m];

            if ($home !== null && $away !== null) {
                if ($m == 0 && $r % 2 == 1) {
                    $temp = $home;
                    $home = $away;
                    $away = $temp;
                }

                $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                
                $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($r * 3) . " days"));
                
                // Fase 2: Fase Única / Rodadas
                $competicao->inserirJogo($idCompeticao, $home, $away, 2, $arbId, $estId, $dateMatch, "false", null);
            }
        }
        $lastTeam = array_pop($teams);
        array_splice($teams, 1, 0, [$lastTeam]);
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
        
        // Round robin dentro de cada grupo com espaçamento correto de rodadas
        // Em um grupo de 4 times, temos 3 rodadas.
        // Rodada 1: 1 v 2, 3 v 4
        // Rodada 2: 1 v 3, 2 v 4
        // Rodada 3: 1 v 4, 2 v 3
        // Cada rodada com 3 dias de diferença.
        if ($n == 4) {
            $rodadas = [
                1 => [[0, 1], [2, 3]],
                2 => [[0, 2], [1, 3]],
                3 => [[0, 3], [1, 2]]
            ];
            foreach ($rodadas as $rNum => $confrontos) {
                foreach ($confrontos as $cIdx => $pair) {
                    $home = $groupTeams[$pair[0]];
                    $away = $groupTeams[$pair[1]];
                    $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                    $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                    // 3 dias de intervalo por rodada
                    $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . (($rNum - 1) * 3) . " days"));
                    
                    $competicao->inserirJogo($idCompeticao, $home, $away, 1, $arbId, $estId, $dateMatch, "false", $groupName);
                }
            }
        } else {
            // Fallback genérico se o grupo não tiver exatamente 4 times
            $matchCount = 0;
            for($i = 0; $i < $n; $i++){
                for($j = $i + 1; $j < $n; $j++){
                    $matchCount++;
                    $home = $groupTeams[$i];
                    $away = $groupTeams[$j];
                    $arbId = count($arbitros) > 0 ? $arbitros[array_rand($arbitros)]['ID'] : 0;
                    $estId = getStadiumForMatch($ldb, $home, $estadios_times, $estadios);
                    $dateMatch = date("Y-m-d 16:00:00", strtotime($currentDate . " +" . ($matchCount * 3) . " days"));
                    
                    $competicao->inserirJogo($idCompeticao, $home, $away, 1, $arbId, $estId, $dateMatch, "false", $groupName);
                }
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
