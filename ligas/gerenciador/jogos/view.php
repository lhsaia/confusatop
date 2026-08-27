<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Detalhes da Partida";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = "home_redesign";
$extra_css = "jogos_clubes_redesign";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_GET['match_id'])){
    $match_id = (int)$_GET['match_id'];
} elseif(isset($_GET['id'])) {
    $match_id = (int)$_GET['id'];
} else {
    echo "<div class='clubes-container'><div class='clubes-card'>ID da partida não fornecido.</div></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
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
    echo "<div class='clubes-container'><div class='clubes-card' style='text-align:center; padding:3rem;'>Partida não encontrada.</div></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
    exit;
}

?>

<div class="clubes-container">
    <div class="clubes-card">
        
        <div class="match-view-header">
            <a href="index.php" class="btn-clubes-secondary">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_back</span>
                <span>Voltar para Lista</span>
            </a>

            <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <a href="editar.php?match_id=<?php echo $match_id; ?>" class="btn-clubes-primary">
                    <span class="material-symbols-outlined" style="font-size:1.1rem;">edit</span>
                    <span>Editar Partida</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Placar Principal -->
        <div class="match-scoreboard-card">
            <div class="scoreboard-team">
                <img class="scoreboard-crest" src="/images/escudos/<?php echo $results['timeA_bandeira'] ?? '0.png'; ?>" alt="<?php echo $results['timeA_nome']; ?>">
                <?php if(!empty($results['timeA_id'])): ?>
                    <a href="/times/team_presentation_magazine.php?team=<?php echo $results['timeA_id']; ?>" class="scoreboard-team-name"><?php echo $results['timeA_nome']; ?></a>
                <?php else: ?>
                    <span class="scoreboard-team-name"><?php echo $results['timeA_nome']; ?></span>
                <?php endif; ?>
            </div>

            <div class="scoreboard-score-center">
                <div class="scoreboard-score-text">
                    <?php echo $results['timeA_gols']; ?> × <?php echo $results['timeB_gols']; ?>
                </div>
                <?php if($results['timeA_penaltis'] !== null && $results['timeB_penaltis'] !== null && ($results['timeA_penaltis'] + $results['timeB_penaltis'] != 0)): ?>
                    <span class="scoreboard-penalty">Pênaltis: <?php echo $results['timeA_penaltis']; ?> × <?php echo $results['timeB_penaltis']; ?></span>
                <?php endif; ?>
            </div>

            <div class="scoreboard-team">
                <img class="scoreboard-crest" src="/images/escudos/<?php echo $results['timeB_bandeira'] ?? '0.png'; ?>" alt="<?php echo $results['timeB_nome']; ?>">
                <?php if(!empty($results['timeB_id'])): ?>
                    <a href="/times/team_presentation_magazine.php?team=<?php echo $results['timeB_id']; ?>" class="scoreboard-team-name"><?php echo $results['timeB_nome']; ?></a>
                <?php else: ?>
                    <span class="scoreboard-team-name"><?php echo $results['timeB_nome']; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metadados da Partida -->
        <div class="match-meta-bar">
            <?php if(!empty($results['competition_name'])): ?>
                <div class="meta-pill">
                    <span class="material-symbols-outlined">emoji_events</span>
                    <span>
                        <?php echo $results['competition_name']; ?> 
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
                            $faseId = (int)($results['phase'] ?? 0);
                            if(isset($faseMap[$faseId]) && $faseId != 0) echo " ({$faseMap[$faseId]})";
                            else if(!empty($results['phase'])) echo " ({$results['phase']})";
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if(!empty($results['data'])): ?>
                <div class="meta-pill">
                    <span class="material-symbols-outlined">event</span>
                    <span><?php echo date("d/m/Y", strtotime($results['data'])); ?></span>
                </div>
            <?php endif; ?>

            <?php if(!empty($results['estadio'])): ?>
                <div class="meta-pill">
                    <span class="material-symbols-outlined">stadium</span>
                    <span><?php echo $results['estadio']; ?></span>
                </div>
            <?php endif; ?>

            <?php if(!empty($results['nome_arbitro'])): ?>
                <div class="meta-pill">
                    <span class="material-symbols-outlined">sports</span>
                    <span>Árbitro: <?php echo $results['nome_arbitro']; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Linha do Tempo de Eventos -->
        <div class="match-events-box">
            <h4 class="events-box-title">
                <span class="material-symbols-outlined" style="color:#0284c7;">timer</span>
                Eventos e Lances da Partida
            </h4>

            <?php
            $stmtEvents = $db->prepare("SELECT * FROM jogos_clube_eventos WHERE id_jogo = ? ORDER BY tempo ASC, minutos ASC");
            $stmtEvents->execute([$match_id]);
            $allEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

            if(count($allEvents) > 0):
                foreach ($allEvents as $event):
                    extract($event);
                    switch ((int)$tipo) {
                        case 1: $icon = '<span class="material-symbols-outlined event-icon-badge goal">sports_soccer</span>'; break;
                        case 2: $icon = '<span class="material-symbols-outlined event-icon-badge yellow-card">square</span>'; break;
                        case 3: $icon = '<span class="material-symbols-outlined event-icon-badge red-card">square</span>'; break;
                        case 4: $icon = '<span class="material-symbols-outlined event-icon-badge own-goal" title="Gol Contra">sports_soccer</span>'; break; 
                        default: $icon = '<span class="material-symbols-outlined event-icon-badge">sports_soccer</span>';
                    }
                    
                    if($minutos === null || $minutos === '') {
                        $min_display = "-";
                    } else if($minutos > 90 && $tempo == 2) {
                        $min_display = "90+" . ($minutos-90) . "'";
                    } else if($minutos > 45 && $tempo == 1) {
                        $min_display = "45+" . ($minutos-45) . "'";
                    } else {
                        $min_display = $minutos . "'";
                    }
                    
                    $alignment = ($id_time == $results['timeA_id'] || $nome_time == $results['timeA_nome']) ? 'left' : 'right';
                    $rowStyle = ($alignment == 'left') ? 'flex-direction: row;' : 'flex-direction: row-reverse; text-align: right;';
                    
                    $playerNameDisplay = stripslashes($nome_jogador ?? '');
                    if(!empty($id_jogador) && $id_jogador > 0) {
                        $playerNameDisplay = '<a href="/ligas/playerstatus.php?player='.$id_jogador.'" class="player-link-clean"><strong>'.$playerNameDisplay.'</strong></a>';
                    } else {
                        $playerNameDisplay = '<strong>'.$playerNameDisplay.'</strong>';
                    }
            ?>
                <div class="event-item-row" style="<?php echo $rowStyle; ?>">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="event-minute-badge"><?php echo $min_display; ?></span>
                        <div><?php echo $icon; ?></div>
                        <div><?php echo $playerNameDisplay; ?></div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <p style="text-align:center; color:#94a3b8; padding:1.5rem; margin:0; font-size:0.9rem;">Sem eventos registrados nesta partida.</p>
            <?php endif; ?>
        </div>

        <!-- Escalações -->
        <div class="lineups-grid">
            <?php
                $queryEscalacao = "SELECT jce.*, p.ID as ordem_posicao 
                                   FROM jogos_clube_escalacao jce 
                                   LEFT JOIN posicoes p ON jce.posicao = p.Sigla 
                                   WHERE jce.id_partida = :id_jogo";
                $stmtEsc = $db->prepare($queryEscalacao);
                $stmtEsc->bindParam(':id_jogo', $match_id);
                $stmtEsc->execute();
                $allPlayers = $stmtEsc->fetchAll(PDO::FETCH_ASSOC);

                $idA = (int)($results['timeA_id'] ?? 0);
                $idB = (int)($results['timeB_id'] ?? 0);

                $teams = [
                    1 => ['titular' => [], 'reserva' => [], 'tecnico' => [], 'nome' => $results['timeA_nome'], 'escudo' => $results['timeA_bandeira'] ?? '0.png'],
                    2 => ['titular' => [], 'reserva' => [], 'tecnico' => [], 'nome' => $results['timeB_nome'], 'escudo' => $results['timeB_bandeira'] ?? '0.png']
                ];

                foreach($allPlayers as $player) {
                    $pTeamId = (int)($player['id_time'] ?? 0);
                    $tKey = 0;
                    
                    if($idA > 0 && $pTeamId === $idA) {
                        $tKey = 1;
                    } elseif($idB > 0 && $pTeamId === $idB) {
                        $tKey = 2;
                    } elseif(!empty($player['nome_time']) && $player['nome_time'] === $results['timeA_nome']) {
                        $tKey = 1;
                    } elseif(!empty($player['nome_time']) && $player['nome_time'] === $results['timeB_nome']) {
                        $tKey = 2;
                    } else {
                        $tKey = (count($teams[1]['titular']) + count($teams[1]['reserva']) + count($teams[1]['tecnico']) < 18) ? 1 : 2;
                    }

                    if($player['posicao'] == 'T') {
                        $teams[$tKey]['tecnico'][] = $player;
                    } elseif($player['titular'] == 1) {
                        $teams[$tKey]['titular'][] = $player;
                    } else {
                        $teams[$tKey]['reserva'][] = $player;
                    }
                }

                $sortPositions = function($a, $b) {
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

                foreach([1, 2] as $tKey) {
                    usort($teams[$tKey]['titular'], $sortPositions);
                    usort($teams[$tKey]['reserva'], $sortReserves);
                }

                if (!function_exists('renderClubPlayerRow')) {
                    function renderClubPlayerRow($p) {
                        $posDisplay = ($p['posicao'] == '0' || empty($p['posicao'])) ? '-' : $p['posicao'];
                        $isGK = (isset($p['ordem_posicao']) && $p['ordem_posicao'] == 1);
                        $gkIcon = $isGK ? '<span class="material-symbols-outlined" title="Goleiro" style="font-size:0.85rem; margin-right:3px; vertical-align:middle; color:#0284c7;">sports_handball</span>' : '';
                        
                        $subOut = !empty($p['saida_minuto']) ? '<span class="sub-tag-out" title="Substituído">&#9660; '.$p['saida_minuto'].'\'</span>' : '';
                        $subIn = !empty($p['entrada_minuto']) ? '<span class="sub-tag-in" title="Entrou em campo">&#9650; '.$p['entrada_minuto'].'\'</span>' : '';

                        $pName = stripslashes($p['nome_jogador'] ?? '');
                        if(isset($p['id_jogador']) && $p['id_jogador'] > 0) {
                             $pName = '<a href="/ligas/playerstatus.php?player='.$p['id_jogador'].'" class="player-link-clean">'.$pName.'</a>';
                        }

                        return '
                        <div class="player-item-row">
                            <div>
                                <span class="player-pos-tag">'.$posDisplay.'</span>
                                '.$gkIcon.'
                                '.$pName.'
                            </div>
                            <div>
                                '.$subOut.'
                                '.$subIn.'
                            </div>
                        </div>';
                    }
                }

                foreach([1, 2] as $tKey): 
                    $teamData = $teams[$tKey];
                ?>
                <div class="lineup-card">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                        <img src="/images/escudos/<?php echo $teamData['escudo']; ?>" class="team-crest" style="width:30px; height:30px;" alt="<?php echo $teamData['nome']; ?>">
                        <h3 class="lineup-team-title" style="margin:0; border:none; padding:0;"><?php echo $teamData['nome']; ?></h3>
                    </div>
                    
                    <span class="lineup-section-subtitle">Titulares</span>
                    <?php foreach($teamData['titular'] as $p): 
                        echo renderClubPlayerRow($p);
                    endforeach; ?>
                    <?php if(empty($teamData['titular'])) echo "<div class='player-item-row' style='color:#94a3b8;'>Nenhum titular registrado</div>"; ?>

                    <?php if(count($teamData['reserva']) > 0): ?>
                        <span class="lineup-section-subtitle">Reservas</span>
                        <?php foreach($teamData['reserva'] as $p): 
                            echo renderClubPlayerRow($p);
                        endforeach; ?>
                    <?php endif; ?>

                    <?php if(count($teamData['tecnico']) > 0): ?>
                        <span class="lineup-section-subtitle">Comissão Técnica</span>
                        <?php foreach($teamData['tecnico'] as $p): 
                            $coachName = stripslashes($p['nome_jogador'] ?? '');
                            if(isset($p['id_jogador']) && $p['id_jogador'] > 0) {
                                 $coachName = '<a href="/ligas/playerstatus.php?player='.$p['id_jogador'].'" class="player-link-clean">'.$coachName.'</a>';
                            }
                        ?>
                            <div class="player-item-row">
                                <div>
                                    <span class="player-pos-tag" style="background:#f1f5f9; color:#475569;">T</span>
                                    <span><?php echo $coachName; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
        </div>

    </div>
</div>

<?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php"); ?>
