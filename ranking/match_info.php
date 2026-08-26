<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Informações da Partida";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "match_info";
$extra_css = 'home_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

if(isset($_GET['match_id'])){
    $match_id = (int)$_GET['match_id'];
} else {
    $match_id = 0;
}

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$usuario = new Usuario($db);
$jogo = new Jogo($db);

$results = $jogo->getSingleMatchInfo($match_id);

?>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="match-info-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">sports_soccer</span>
                    Detalhes da Partida
                </h2>
                <h3 class="ranking-card-date"><?php echo htmlspecialchars($results['competition_name'] ?? 'Partida Internacional'); ?><?php echo isset($results['fase']) ? ' - ' . htmlspecialchars($results['fase']) : ''; ?></h3>
            </div>
            
            <div style="display:flex; align-items:center; gap:8px;">
                <?php if(isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1): ?>
                    <a href="criar_jogo.php?match_id=<?php echo $match_id; ?>" class="ranking-nav-link active" style="padding: 8px 16px;">
                        <span class="material-symbols-outlined nav-icon" style="font-size:1.1rem;">edit</span>
                        <span>Editar Partida</span>
                    </a>
                <?php endif; ?>
                <a href="javascript:history.back()" class="ranking-nav-link" style="padding: 8px 16px;">
                    <span class="material-symbols-outlined nav-icon">arrow_back</span>
                    <span>Voltar</span>
                </a>
            </div>
        </div>

        <!-- Placar -->
        <div id="match-info-header">
            <div class="match-team-block team-left">
                <img id="match-info-team-1-flag" src="/images/bandeiras/<?php echo $results['timeA_bandeira']; ?>" alt="<?php echo $results['timeA_nome']; ?>">
                <a href="./teamstatus.php?team=<?php echo $results['timeA_id']; ?>" class="team-link">
                    <span id="match-info-team-1-name"><?php echo $results['timeA_nome']; ?></span>
                </a>
            </div>

            <div class="match-score-center">
                <span id="match-info-team-1-score"><?php echo $results['timeA_gols']; ?></span>
                <?php if(isset($results['timeA_penaltis'], $results['timeB_penaltis']) && $results['timeA_penaltis'] !== null && $results['timeB_penaltis'] !== null && $results['timeA_penaltis'] !== '' && $results['timeB_penaltis'] !== ''): ?>
                    <span class="penalty-indicator">(<?php echo $results['timeA_penaltis']; ?>)</span>
                <?php endif; ?>
                
                <span id="match-info-x-mark">×</span>
                
                <?php if(isset($results['timeA_penaltis'], $results['timeB_penaltis']) && $results['timeA_penaltis'] !== null && $results['timeB_penaltis'] !== null && $results['timeA_penaltis'] !== '' && $results['timeB_penaltis'] !== ''): ?>
                    <span class="penalty-indicator">(<?php echo $results['timeB_penaltis']; ?>)</span>
                <?php endif; ?>
                <span id="match-info-team-2-score"><?php echo $results['timeB_gols']; ?></span>
            </div>

            <div class="match-team-block team-right">
                <img id="match-info-team-2-flag" src="/images/bandeiras/<?php echo $results['timeB_bandeira']; ?>" alt="<?php echo $results['timeB_nome']; ?>">
                <a href="./teamstatus.php?team=<?php echo $results['timeB_id']; ?>" class="team-link">
                    <span id="match-info-team-2-name"><?php echo $results['timeB_nome']; ?></span>
                </a>
            </div>
        </div>

        <!-- Metadados da Partida -->
        <div id="match-info-base">
            <div class="match-meta-pill">
                <span class="material-symbols-outlined">emoji_events</span>
                <span><?php echo $results['competition_name']; ?><?php echo isset($results['fase']) ? ' (' . $results['fase'] . ')' : ''; ?></span>
            </div>

            <?php if(!empty($results['estadio'])): ?>
                <div class="match-meta-pill">
                    <span class="material-symbols-outlined">stadium</span>
                    <span><?php echo $results['estadio']; ?></span>
                </div>
            <?php endif; ?>

            <div class="match-meta-pill">
                <span class="material-symbols-outlined">event</span>
                <span><?php echo date("d/m/Y", strtotime($results['data'])); ?></span>
            </div>

            <?php if(!empty($results['nome_arbitro'])): ?>
                <div class="match-meta-pill">
                    <span class="material-symbols-outlined">sports</span>
                    <span>Árbitro: <?php echo $results['nome_arbitro']; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Linha do Tempo de Eventos -->
        <div id="match-info-events">
            <h4 class="events-title">
                <span class="material-symbols-outlined">timer</span>
                Eventos e Lances da Partida
            </h4>

            <?php
            $stmt = $jogo->getSingleMatchEvents($match_id);
            $hasEvents = false;

            while ($event = $stmt->fetch(PDO::FETCH_ASSOC)){
                $hasEvents = true;
                extract($event);
                $player_name_event_a = "";
                $player_id_event_a = 0;
                $minute_event_a = "";
                $player_name_event_b = "";
                $player_id_event_b = 0;
                $minute_event_b = "";

                if($minutos === null || $minutos === '') {
                    $minutos_corrigidos = "-";
                } else if($tempo == 1 && $minutos > 45){
                    $minutos_corrigidos = "45+" . ($minutos - 45);
                } else if($tempo == 2 && $minutos > 90){
                    $minutos_corrigidos = "90+" . ($minutos - 90);
                } else {
                    $minutos_corrigidos = $minutos . "'";
                }

                if(!empty($results['timeA_id']) && $results['timeA_id'] == $id_time){
                    $player_name_event_a = stripslashes($nome_jogador);
                    $player_id_event_a = $id_jogador;
                    $minute_event_a = $minutos_corrigidos;
                } else if(!empty($results['timeB_id']) && $results['timeB_id'] == $id_time){
                    $player_name_event_b = stripslashes($nome_jogador);
                    $player_id_event_b = $id_jogador;
                    $minute_event_b = $minutos_corrigidos;
                } else {
                    $player_name_event_a = stripslashes($nome_jogador);
                    $player_id_event_a = $id_jogador;
                    $minute_event_a = $minutos_corrigidos;
                }

                switch ($tipo) {
                    case 1:
                        $iconClass = 'goal';
                        $iconName = 'sports_soccer';
                        break;
                    case 2:
                        $iconClass = 'yellow-card';
                        $iconName = 'square';
                        break;
                    case 3:
                        $iconClass = 'red-card';
                        $iconName = 'square';
                        break;
                    case 4:
                        $iconClass = 'own-goal';
                        $iconName = 'sports_soccer';
                        break;
                    default:
                        $iconClass = 'goal';
                        $iconName = 'sports_soccer';
                        break;
                }

                echo "<div class='match-event-unit'>";
                    echo "<div class='event-col-left'>";
                        if($player_id_event_a != 0){
                            echo "<a href='/ligas/playerstatus.php?player={$player_id_event_a}'>{$player_name_event_a}</a>";
                        } else {
                            echo "<span>{$player_name_event_a}</span>";
                        }
                        if(!empty($minute_event_a)){
                            echo "<span class='match-event-minute'>{$minute_event_a}</span>";
                        }
                    echo "</div>";

                    echo "<div class='event-col-center'>";
                        echo "<span class='material-symbols-outlined match-event-icon {$iconClass}'>{$iconName}</span>";
                    echo "</div>";

                    echo "<div class='event-col-right'>";
                        if(!empty($minute_event_b)){
                            echo "<span class='match-event-minute'>{$minute_event_b}</span>";
                        }
                        if($player_id_event_b != 0){
                            echo "<a href='/ligas/playerstatus.php?player={$player_id_event_b}'>{$player_name_event_b}</a>";
                        } else {
                            echo "<span>{$player_name_event_b}</span>";
                        }
                    echo "</div>";
                echo "</div>";
            }

            if(!$hasEvents){
                echo "<p style='text-align: center; color: #94a3b8; padding: 1.5rem; margin: 0; font-size: 0.9rem;'>Nenhum evento registrado nesta partida.</p>";
            }
            ?>
        </div>

        <!-- Seção de Escalações das Seleções -->
        <?php
        $stmtEsc = $jogo->getSingleMatchLineup($match_id);
        $teams = [
            1 => ['titular'=>[], 'reserva'=>[], 'tecnico'=>[], 'nome'=>$results['timeA_nome'], 'bandeira'=>$results['timeA_bandeira']],
            2 => ['titular'=>[], 'reserva'=>[], 'tecnico'=>[], 'nome'=>$results['timeB_nome'], 'bandeira'=>$results['timeB_bandeira']]
        ];

        $allPlayers = $stmtEsc ? $stmtEsc->fetchAll(PDO::FETCH_ASSOC) : [];
        $t1_id = (int)$results['timeA_id'];
        $t2_id = (int)$results['timeB_id'];

        foreach ($allPlayers as $p) {
            $pTeamId = (int)($p['id_time'] ?? 0);
            $tKey = 0;

            if ($t1_id > 0 && $pTeamId === $t1_id) {
                $tKey = 1;
            } elseif ($t2_id > 0 && $pTeamId === $t2_id) {
                $tKey = 2;
            } elseif (isset($p['lado']) && ($p['lado'] == 'A' || $p['lado'] == '1')) {
                $tKey = 1;
            } elseif (isset($p['lado']) && ($p['lado'] == 'B' || $p['lado'] == '2')) {
                $tKey = 2;
            }

            // Fallback por contagem se o time não estiver preenchido
            if ($tKey === 0) {
                if ((count($teams[1]['titular']) + count($teams[1]['reserva']) + count($teams[1]['tecnico'])) < 18) {
                    $tKey = 1;
                } else {
                    $tKey = 2;
                }
            }

            if ($p['posicao'] == 'S' && $p['id_jogador'] == 0 && (stripos($p['nome_jogador'], 'Tecnico') !== false || stripos($p['nome_jogador'], 'Técnico') !== false)) {
                $p['posicao'] = 'T';
            }

            if ($p['posicao'] == 'T') {
                $teams[$tKey]['tecnico'][] = $p;
            } elseif ($p['titular'] == 1) {
                $teams[$tKey]['titular'][] = $p;
            } else {
                $teams[$tKey]['reserva'][] = $p;
            }
        }

        $hasLineupData = (!empty($teams[1]['titular']) || !empty($teams[1]['reserva']) || !empty($teams[2]['titular']) || !empty($teams[2]['reserva']));
        ?>

        <?php if($hasLineupData): ?>
        <link rel="stylesheet" href="/css/jogos_clubes_redesign.css?v=<?php echo $css_versao; ?>">
        <div style="margin-top: 2rem;">
            <h4 class="events-title" style="margin-bottom: 1.25rem;">
                <span class="material-symbols-outlined" style="color:#0284c7;">groups</span>
                Escalações das Seleções
            </h4>

            <div class="lineups-grid">
                <?php 
                if (!function_exists('renderNationalPlayerRow')) {
                    function renderNationalPlayerRow($p) {
                        $posDisplay = ($p['posicao'] == '0' || empty($p['posicao'])) ? '-' : $p['posicao'];
                        $isGK = (isset($p['ordem_posicao']) && $p['ordem_posicao'] == 1);
                        $gkIcon = $isGK ? '<span class="material-symbols-outlined" title="Goleiro" style="font-size:0.85rem; margin-right:3px; vertical-align:middle; color:#0284c7;">sports_handball</span>' : '';
                        
                        $subOut = (!empty($p['saida_minuto']) || (isset($p['saida_minuto']) && $p['saida_minuto'] === 0)) ? '<span class="sub-tag-out" title="Substituído">&#9660; '.$p['saida_minuto'].'\'</span>' : '';
                        $subIn = (!empty($p['entrada_minuto']) || (isset($p['entrada_minuto']) && $p['entrada_minuto'] === 0)) ? '<span class="sub-tag-in" title="Entrou em campo">&#9650; '.$p['entrada_minuto'].'\'</span>' : '';

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
                        <img src="/images/bandeiras/<?php echo $teamData['bandeira']; ?>" style="width:30px; height:20px; object-fit:cover; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.1);" alt="<?php echo $teamData['nome']; ?>">
                        <h3 class="lineup-team-title" style="margin:0; border:none; padding:0;"><?php echo $teamData['nome']; ?></h3>
                    </div>
                    
                    <span class="lineup-section-subtitle">Titulares</span>
                    <?php foreach($teamData['titular'] as $p): 
                        echo renderNationalPlayerRow($p);
                    endforeach; ?>
                    <?php if(empty($teamData['titular'])) echo "<div class='player-item-row' style='color:#94a3b8;'>Nenhum titular registrado</div>"; ?>

                    <?php if(count($teamData['reserva']) > 0): ?>
                        <span class="lineup-section-subtitle">Reservas</span>
                        <?php foreach($teamData['reserva'] as $p): 
                            echo renderNationalPlayerRow($p);
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
        <?php endif; ?>
    </div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
