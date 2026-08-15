<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Detalhes da Partida";
// Reuse existing styles
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "match_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_GET['match_id'])){
    $match_id = $_GET['match_id'];
} else {
    echo "ID da partida não fornecido.";
    exit;
}

// Database Connection
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

$results = $jogo->getSingleMatchInfo($match_id);

if(!$results) {
    echo "<div id='ranking-container'><div id='ranking'>Partida não encontrada.</div></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
    exit;
}

?>

<style>
    /* Custom styles for the manager view */
    #ranking-container { 
        padding: 20px; 
        background: #f9f9f9; 
        padding-bottom: 300px; /* Large padding to prevent fixed footer overlap */
        height: auto !important; 
        float: none !important;
        overflow: visible !important;
    }
    #ranking { 
        background: white; 
        padding: 20px; 
        border-radius: 8px; 
        max-width: 1000px; 
        margin: 0 auto; 
        box-shadow: 0 0 10px rgba(0,0,0,0.1); 
    }
    
    /* Fixed Footer as requested */
    #bottom-bar {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: auto !important;
        
        margin-top: 0;
        z-index: 999;
        background-color: #1A1469; /* Ensure background is opaque */
    }


    
    /* Header/Scoreboard */
    .match-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 0; border-bottom: 1px solid #eee; }
    .team-block { text-align: center; width: 35%; }
    .team-flag { width: 60px; height: auto; display: block; margin: 0 auto 10px; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
    .team-name { font-size: 1.2em; font-weight: bold; }
    .score-block { text-align: center; width: 30%; font-size: 3em; font-weight: bold; color: #333; }
    .penalty-score { font-size: 0.4em; color: #666; display: block; margin-top: -10px; }
    
    /* Info Bar */
    .match-details-bar { display: flex; justify-content: space-around; background: #f5f5f5; padding: 10px; margin: 20px 0; border-radius: 4px; font-size: 0.9em; color: #555; }
    .detail-item i { margin-right: 5px; }

    /* Events */
    .events-container { margin: 20px 0; }
    .event-row { display: flex; align-items: center; padding: 5px 0; border-bottom: 1px dashed #eee; }
    .event-time { width: 50px; text-align: center; font-weight: bold; color: #888; font-size: 0.9em; }
    .event-icon { width: 30px; text-align: center; }
    .event-desc { flex: 1; }
    .goal { color: green; }
    .own-goal { color: darkred; }
    .yellow-card { color: #f4d03f; }
    .red-card { color: #e74c3c; }

    /* Lineups */
    .lineups-container { display: flex; justify-content: space-between; margin-top: 30px; }
    .lineup { width: 48%; }
    .lineup h3 { border-bottom: 2px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; font-size: 1.1em; color: #444; }
    .player-row { padding: 4px 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; }
    .player-pos { width: 30px; font-weight: bold; color: #999; font-size: 0.8em; }
    .player-name { flex: 1; }
    .sub-info { font-size: 0.8em; color: #e67e22; margin-left: 5px; }

    .nav-back { margin-bottom: 15px; }
</style>

<div id="ranking-container">
    <div id="ranking">
        
        <div class="nav-back d-flex justify-content-between">
            <a href="index.php" class="btn btn-sm btn-secondary">&larr; Voltar para Lista</a>
            <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] ): ?>
                <a href="editar.php?match_id=<?php echo $match_id; ?>" class="btn btn-sm btn-info text-white"><span class="material-symbols-outlined">edit</span> Editar Partida</a>
            <?php endif; ?>
        </div>

        <div class="match-header">
            <div class="team-block">
                <img class="team-flag" src="/images/escudos/<?php echo $results['timeA_bandeira']; ?>">
                <div class="team-name">
                    <?php 
                    if($results['timeA_id']) {
                        echo '<a href="/times/team_presentation.php?team='.$results['timeA_id'].'" style="text-decoration:none; color:inherit;">'.$results['timeA_nome'].'</a>';
                    } else {
                        echo $results['timeA_nome'];
                    }
                    ?>
                </div>
            </div>
            <div class="score-block">
                <?php echo $results['timeA_gols']; ?> - <?php echo $results['timeB_gols']; ?>
                <?php if($results['timeA_penaltis'] !== null && $results['timeB_penaltis'] !== null && ($results['timeA_penaltis'] + $results['timeB_penaltis'] != 0)): ?>
                    <span class="penalty-score">(<?php echo $results['timeA_penaltis']; ?> - <?php echo $results['timeB_penaltis']; ?> pen)</span>
                <?php endif; ?>
            </div>
            <div class="team-block">
                <img class="team-flag" src="/images/escudos/<?php echo $results['timeB_bandeira']; ?>">
                <div class="team-name">
                    <?php 
                    if($results['timeB_id']) {
                        echo '<a href="/times/team_presentation.php?team='.$results['timeB_id'].'" style="text-decoration:none; color:inherit;">'.$results['timeB_nome'].'</a>';
                    } else {
                        echo $results['timeB_nome'];
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="match-details-bar">
            <?php if($results['competition_name']): ?>
                <div class="detail-item"><span class="material-symbols-outlined">emoji_events</span> <?php echo $results['competition_name']; ?> 
                <?php 
                    $faseMap = [
                        0 => 'N/A',
                        1 => 'Fase pré',
                        2 => 'Fase de grupos',
                        3 => 'Oitavas-de-final',
                        4 => 'Quartas-de-final',
                        5 => 'Semi-final',
                        6 => 'Disputa de terceiro lugar',
                        7 => 'Repescagem',
                        8 => 'Final'
                    ];
                    $faseId = (int)$results['phase'];
                    if(isset($faseMap[$faseId]) && $faseId != 0) echo " ({$faseMap[$faseId]})";
                    else if($results['phase']) echo " ({$results['phase']})";
                ?></div>
            <?php endif; ?>
            <?php if($results['data']): ?>
                <div class="detail-item"><span class="material-symbols-outlined">calendar_today</span> <?php echo date("d/m/Y", strtotime($results['data'])); ?></div>
            <?php endif; ?>
            <?php if($results['estadio']): ?>
                <div class="detail-item"><span class="material-symbols-outlined">location_on</span> <?php echo $results['estadio']; ?></div>
            <?php endif; ?>
            <?php if($results['nome_arbitro']): ?>
                <div class="detail-item"><span class="material-symbols-outlined">person</span> <?php echo $results['nome_arbitro']; ?></div>
            <?php endif; ?>
        </div>

        <div class="events-container">
            <h4>Eventos</h4>
            <?php
            $stmtEvents = $jogo->getSingleMatchEvents($match_id);
            if($stmtEvents->rowCount() > 0):
                while ($event = $stmtEvents->fetch(PDO::FETCH_ASSOC)):
                    extract($event);
                    // Match visual icons
                    switch ($tipo) {
                        case 1: $icon = '<span class="material-symbols-outlined goal">sports_soccer</span>'; break;
                        case 2: $icon = '<span class="material-symbols-outlined yellow-card">stop</span>'; break;
                        case 3: $icon = '<span class="material-symbols-outlined red-card">stop</span>'; break;
                        case 4: $icon = '<span class="material-symbols-outlined own-goal" title="Gol Contra">sports_soccer</span>'; break; 
                        default: $icon = '';
                    }
                    
                    if($minutos > 90 && $tempo == 2) { $min_display = "90+" . ($minutos-90); }
                    else if($minutos > 45 && $tempo == 1) { $min_display = "45+" . ($minutos-45); }
                    else { $min_display = $minutos . "'"; }
                    
                    // Determine which team the event belongs to relative to display
                    // Note: id_time in eventos must match time_id in match info
                    $alignment = ($id_time == $results['timeA_id']) ? 'left' : 'right';
                    $rowStyle = ($alignment == 'left') ? 'flex-direction: row;' : 'flex-direction: row-reverse; text-align: right;';
                    
                    $playerNameDisplay = stripslashes($nome_jogador);
                    if($id_jogador) {
                        $playerNameDisplay = '<a href="/ligas/playerstatus.php?player='.$id_jogador.'" style="text-decoration:none; color:inherit;">'.$playerNameDisplay.'</a>';
                    }
            ?>
                <div class="event-row" style="<?php echo $rowStyle; ?>">
                    <div class="event-time"><?php echo $min_display; ?></div>
                    <div class="event-icon"><?php echo $icon; ?></div>
                    <div class="event-desc">
                        <strong><?php echo $playerNameDisplay; ?></strong>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="text-align:center; color:#999;">Sem eventos registrados.</p>
            <?php endif; ?>
        </div>

        <div class="lineups-container">
            <?php
                // Fetch Lineups with Position Order
                $queryEscalacao = "SELECT jce.*, p.ID as ordem_posicao 
                                   FROM jogos_clube_escalacao jce 
                                   LEFT JOIN posicoes p ON jce.posicao = p.Sigla 
                                   WHERE jce.id_partida = :id_jogo";
                $stmtEsc = $db->prepare($queryEscalacao);
                $stmtEsc->bindParam(':id_jogo', $match_id);
                $stmtEsc->execute();

                $idA = $results['timeA_id'] ? $results['timeA_id'] : 0;
                $idB = $results['timeB_id'] ? $results['timeB_id'] : 0;

                $teams = [
                    $idA => ['titular' => [], 'reserva' => [], 'tecnico' => [], 'nome' => $results['timeA_nome']],
                    $idB => ['titular' => [], 'reserva' => [], 'tecnico' => [], 'nome' => $results['timeB_nome']]
                ];

                while($player = $stmtEsc->fetch(PDO::FETCH_ASSOC)) {
                    $teamId = $player['id_time'] ? $player['id_time'] : 0; // Normalize row ID too if needed
                    
                    // Fallback using loose check or mapped ID
                    if(!isset($teams[$teamId])) {
                         // Try to match 0 vs "" or similar just in case specific type mismatch, but the normalization above should handle it.
                         continue;
                    }

                    if($player['posicao'] == 'T') {
                        $teams[$teamId]['tecnico'][] = $player;
                    } elseif($player['titular'] == 1) {
                        $teams[$teamId]['titular'][] = $player;
                    } else {
                        $teams[$teamId]['reserva'][] = $player;
                    }
                }

                // Sorting Function
                $sortPositions = function($a, $b) {
                     // null safe comparison for ordem_posicao
                     $posA = isset($a['ordem_posicao']) ? $a['ordem_posicao'] : 999;
                     $posB = isset($b['ordem_posicao']) ? $b['ordem_posicao'] : 999;
                     return $posA - $posB;
                };

                $sortReserves = function($a, $b) use ($sortPositions) {
                    $a_entered = !empty($a['entrada_minuto']);
                    $b_entered = !empty($b['entrada_minuto']);

                    if ($a_entered && !$b_entered) return -1;
                    if (!$a_entered && $b_entered) return 1;
                    
                    return $sortPositions($a, $b);
                };

                foreach($teams as $id => $team) {
                    usort($teams[$id]['titular'], $sortPositions);
                    usort($teams[$id]['reserva'], $sortReserves);
                }

                // Helper to render player row
                function renderPlayerRow($p) {
                    $posDisplay = ($p['posicao'] == '0') ? '-' : $p['posicao'];
                    $isGK = (isset($p['ordem_posicao']) && $p['ordem_posicao'] == 1);
                    $gkIcon = $isGK ? '<span class="material-symbols-outlined" title="Goleiro" style="font-size:0.8em; margin-right:3px;">sports_handball</span> ' : '';
                    
                    // Updated Icons for Sub In/Out
                    $subOut = $p['saida_minuto'] ? '<span class="sub-info" style="color:red; font-size:1.1em;" title="Saiu">&#9660; '.$p['saida_minuto'].'\'</span>' : '';
                    $subIn = (isset($p['entrada_minuto']) && $p['entrada_minuto']) ? '<span class="sub-info" style="color:green; font-size:1.1em;" title="Entrou">&#9650; '.$p['entrada_minuto'].'\'</span>' : '';

                    $pName = stripslashes($p['nome_jogador']);
                    if(isset($p['id_jogador']) && $p['id_jogador']) {
                         $pName = '<a href="/ligas/playerstatus.php?player='.$p['id_jogador'].'" style="text-decoration:none; color:inherit;">'.$pName.'</a>';
                    }

                    return '
                    <div class="player-row">
                        <span class="player-pos">'.$posDisplay.'</span>
                        <span class="player-name">
                            '.$gkIcon.'
                            '.$pName.'
                            '.$subOut.'
                            '.$subIn.'
                        </span>
                    </div>';
                }
            ?>

            <?php foreach($teams as $teamId => $teamData): 
                  // Ensure we print Time A first then Time B to match header order
                  if($teamId != $results['timeA_id'] && $teamId != $results['timeB_id']) continue; // skip anomalies
                  // We need to output in specific order: A (left), B (right). 
                  // The loop order depends on array keys. Let's restart loop structure manually below to be safe.
            endforeach; ?>
            
            <?php 
            // Explicit Display Order
            $displayOrder = [$idA, $idB];
            foreach($displayOrder as $teamId): 
                $teamData = $teams[$teamId] ?? ['titular'=>[], 'reserva'=>[], 'tecnico'=>[], 'nome'=>''];
            ?>
            <div class="lineup">
                <h3><?php echo $teamData['nome']; ?></h3>
                
                <strong>Titulares</strong>
                <?php foreach($teamData['titular'] as $p): 
                    echo renderPlayerRow($p);
                endforeach; ?>
                <?php if(empty($teamData['titular'])) echo "<div class='player-row'>-</div>"; ?>

                <?php if(count($teamData['reserva']) > 0): ?>
                    <br><strong>Reservas</strong>
                    <?php foreach($teamData['reserva'] as $p): 
                        echo renderPlayerRow($p);
                    endforeach; ?>
                <?php endif; ?>

                <?php if(count($teamData['tecnico']) > 0): ?>
                    <br><strong>Comissão Técnica</strong>
                    <?php foreach($teamData['tecnico'] as $p): 
                        $coachName = stripslashes($p['nome_jogador']);
                        if(isset($p['id_jogador']) && $p['id_jogador']) {
                             $coachName = '<a href="/ligas/playerstatus.php?player='.$p['id_jogador'].'" style="text-decoration:none; color:inherit;">'.$coachName.'</a>';
                        }
                    ?>
                        <div class="player-row">
                            <span class="player-pos">T</span>
                            <span class="player-name"><?php echo $coachName; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>

    </div>
</div>

<?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php"); ?>
