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
                    <?php 
                        $statusJogo = isset($results['status']) ? (int)$results['status'] : 1;
                        $dtJogo = !empty($results['data']) ? strtotime($results['data']) : time();
                        $temPen = ($results['timeA_penaltis'] !== null && $results['timeA_penaltis'] !== '');
                        $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
                        $jaTerminou = (time() >= ($dtJogo + $duracaoSegundos));

                        if ($statusJogo === 0 || (!$jaTerminou && !empty($results['simulador_interno']))) {
                            echo "VS";
                        } else {
                            $gA = ($results['timeA_gols'] !== null) ? (int)$results['timeA_gols'] : 0;
                            $gB = ($results['timeB_gols'] !== null) ? (int)$results['timeB_gols'] : 0;
                            echo "{$gA} × {$gB}";
                        }
                    ?>
                </div>
                <?php if($statusJogo === 1 && ($jaTerminou || empty($results['simulador_interno'])) && $results['timeA_penaltis'] !== null && $results['timeB_penaltis'] !== null && ($results['timeA_penaltis'] != '' || $results['timeB_penaltis'] != '')): ?>
                    <span class="scoreboard-penalty">Pênaltis: <?php echo (int)$results['timeA_penaltis']; ?> × <?php echo (int)$results['timeB_penaltis']; ?></span>
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
                                8 => 'Final',
                                9 => '16-avos-de-final',
                                10 => '32-avos-de-final'
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
            $baseRoot = (isset($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : dirname(__DIR__, 3));

            // 1. Carregar súmula .hyl se disponível
            $hylData = null;
            if (!empty($results['path'])) {
                $cleanPath = basename($results['path'], '.hyl');
                $hylFiles = glob($baseRoot . "/competicoes/hexacolor/Partidas/*/*/" . $cleanPath . ".hyl");
                if (empty($hylFiles)) {
                    $hylFiles = glob($baseRoot . "/full hexa suite/Hexacolor YMT/Partidas/*/*/" . $cleanPath . ".hyl");
                }
                if (!empty($hylFiles) && file_exists($hylFiles[0])) {
                    $hylData = json_decode(file_get_contents($hylFiles[0]), true);
                }
            }

            // 2. Carregar mapa de jogadores do SQLite da competição (se for simulador interno)
            $sqlitePlayersMap = [];
            if (!empty($results['competicao_id'])) {
                $dbPath = $baseRoot . "/competicoes/databases/" . (int)$results['competicao_id'] . "-database.db3";
                if (file_exists($dbPath)) {
                    try {
                        $cdb = new PDO("sqlite:" . $dbPath);
                        $stmtSqJogs = $cdb->query("SELECT ID, Nome, Posicao FROM jogador");
                        if ($stmtSqJogs) {
                            while ($sqRow = $stmtSqJogs->fetch(PDO::FETCH_ASSOC)) {
                                $sqlitePlayersMap[(int)$sqRow['ID']] = $sqRow;
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }

            // 3. Mapeamento de nomes do .hyl
            $hylPlayerNames = [];
            if ($hylData) {
                foreach (array_merge($hylData['escalacaoTime1'] ?? [], $hylData['escalacaoTime2'] ?? []) as $hp) {
                    $hpId = (int)($hp['id'] ?? 0);
                    $hpNome = trim((string)($hp['nome'] ?? ''));
                    if ($hpId > 0 && $hpNome !== '') {
                        $hylPlayerNames[$hpId] = $hpNome;
                    }
                }
            }

            // 4. Função universal de resolução de jogador
            $resolverJogador = function($pId) use ($sqlitePlayersMap, $hylPlayerNames, $db) {
                $pId = (int)$pId;
                if ($pId <= 0) return ['nome' => '', 'posicao' => ''];
                
                $nome = '';
                $pos = '';
                
                if (isset($hylPlayerNames[$pId]) && $hylPlayerNames[$pId] !== '') {
                    $nome = $hylPlayerNames[$pId];
                }
                
                if (isset($sqlitePlayersMap[$pId])) {
                    if (empty($nome)) $nome = $sqlitePlayersMap[$pId]['Nome'];
                    $pos = $sqlitePlayersMap[$pId]['Posicao'] ?? '';
                }
                
                if (empty($nome)) {
                    try {
                        $stmtMy = $db->prepare("SELECT Nome, Posicao FROM jogador WHERE ID = ? LIMIT 1");
                        $stmtMy->execute([$pId]);
                        if ($myRow = $stmtMy->fetch(PDO::FETCH_ASSOC)) {
                            $nome = $myRow['Nome'];
                            if (empty($pos)) $pos = $myRow['Posicao'] ?? '';
                        }
                    } catch (\Throwable $e) {}
                }
                
                return ['nome' => $nome, 'posicao' => $pos];
            };

            // 5. Carregar Eventos da partida
            $stmtEvents = $db->prepare("SELECT * FROM jogos_clube_eventos WHERE id_jogo = ? ORDER BY tempo ASC, minutos ASC");
            $stmtEvents->execute([$match_id]);
            $allEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

            // Se a tabela de eventos estiver vazia mas tivermos o .hyl, popula os eventos a partir do .hyl
            if (empty($allEvents) && $hylData && !empty($hylData['eventos']) && is_array($hylData['eventos'])) {
                foreach ($hylData['eventos'] as $ev) {
                    $tipoEvStr = $ev['tipoEvento'] ?? '';
                    $tipoEvento = 0;
                    switch ($tipoEvStr) {
                        case 'gol':       $tipoEvento = 1; break;
                        case 'amarelo':   $tipoEvento = 2; break;
                        case 'vermelho':  $tipoEvento = 3; break;
                        case 'golContra': $tipoEvento = 4; break;
                    }

                    // Ignorar eventos ocorridos durante disputa de pênaltis pós-jogo (tempo > 4)
                    $tempoRaw = isset($ev['tempo']) ? (int)$ev['tempo'] : 1;
                    if ($tempoRaw > 4) {
                        continue;
                    }

                    if ($tipoEvento > 0) {
                        $pId = (int)($ev['idJogador'] ?? 0);
                        $infoJog = $resolverJogador($pId);
                        $teamNum = (int)($ev['time'] ?? 1);
                        $idTm = ($teamNum === 2) ? (int)$results['timeB_id'] : (int)$results['timeA_id'];
                        $nomeTm = ($teamNum === 2) ? $results['timeB_nome'] : $results['timeA_nome'];
                        $minuto = isset($ev['minutos']) ? (int)$ev['minutos'] : null;
                        $tempo = $tempoRaw;
                        if ($minuto !== null && $minuto > 45 && $tempo == 1) {
                            $tempo = 2;
                        }

                        $allEvents[] = [
                            'id_jogo' => $match_id,
                            'tempo' => $tempo,
                            'minutos' => $minuto,
                            'tipo' => $tipoEvento,
                            'id_jogador' => $pId,
                            'nome_jogador' => $infoJog['nome'],
                            'id_time' => $idTm,
                            'nome_time' => $nomeTm
                        ];

                        try {
                            $stmtInsEv = $db->prepare("INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmtInsEv->execute([$match_id, $tempo, $minuto, $tipoEvento, $pId, mb_substr($infoJog['nome'], 0, 40), $idTm, $nomeTm]);
                        } catch (\Throwable $e) {}
                    }
                }
            }

            // Preencher nomes faltantes em eventos
            foreach ($allEvents as &$event) {
                if (empty($event['nome_jogador']) && !empty($event['id_jogador'])) {
                    $infoJog = $resolverJogador($event['id_jogador']);
                    if (!empty($infoJog['nome'])) {
                        $event['nome_jogador'] = $infoJog['nome'];
                    }
                }
            }
            unset($event);

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
                    
                    $isTeamA = ($id_time == $results['timeA_id'] || $id_time == 1 || $nome_time == $results['timeA_nome']);
                    $alignment = $isTeamA ? 'left' : 'right';
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

                // Se a tabela de escalação estiver vazia mas tivermos o .hyl, carrega a escalação do .hyl
                if (empty($allPlayers) && $hylData) {
                    $idA = (int)($results['timeA_id'] ?? 0);
                    $idB = (int)($results['timeB_id'] ?? 0);

                    foreach ($hylData['escalacaoTime1'] ?? [] as $idx => $p) {
                        $pId = (int)($p['id'] ?? 0);
                        $infoJog = $resolverJogador($pId);
                        $pNome = !empty($p['nome']) ? trim((string)$p['nome']) : $infoJog['nome'];
                        $pPos = !empty($p['posicao']) ? $p['posicao'] : $infoJog['posicao'];
                        $pTitular = isset($p['titular']) ? (int)$p['titular'] : ($idx < 11 ? 1 : 0);

                        $allPlayers[] = [
                            'id_partida' => $match_id,
                            'id_time' => $idA,
                            'nome_time' => $results['timeA_nome'],
                            'id_jogador' => $pId,
                            'nome_jogador' => $pNome,
                            'posicao' => $pPos,
                            'titular' => $pTitular,
                            'numero' => $idx + 1,
                            'entrada_minuto' => null,
                            'saida_minuto' => null
                        ];

                        try {
                            $stmtInsEsc = $db->prepare("INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_tempo, entrada_minuto, saida_tempo, saida_minuto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0)");
                            $stmtInsEsc->execute([$match_id, $idA, $results['timeA_nome'], $pPos, $idx + 1, $pId, $pNome, $pTitular]);
                        } catch (\Throwable $e) {}
                    }

                    foreach ($hylData['escalacaoTime2'] ?? [] as $idx => $p) {
                        $pId = (int)($p['id'] ?? 0);
                        $infoJog = $resolverJogador($pId);
                        $pNome = !empty($p['nome']) ? trim((string)$p['nome']) : $infoJog['nome'];
                        $pPos = !empty($p['posicao']) ? $p['posicao'] : $infoJog['posicao'];
                        $pTitular = isset($p['titular']) ? (int)$p['titular'] : ($idx < 11 ? 1 : 0);

                        $allPlayers[] = [
                            'id_partida' => $match_id,
                            'id_time' => $idB,
                            'nome_time' => $results['timeB_nome'],
                            'id_jogador' => $pId,
                            'nome_jogador' => $pNome,
                            'posicao' => $pPos,
                            'titular' => $pTitular,
                            'numero' => $idx + 1,
                            'entrada_minuto' => null,
                            'saida_minuto' => null
                        ];

                        try {
                            $stmtInsEsc = $db->prepare("INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_tempo, entrada_minuto, saida_tempo, saida_minuto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0)");
                            $stmtInsEsc->execute([$match_id, $idB, $results['timeB_nome'], $pPos, $idx + 1, $pId, $pNome, $pTitular]);
                        } catch (\Throwable $e) {}
                    }
                }

                // Preencher e persistir nomes vazios em $allPlayers
                foreach ($allPlayers as &$player) {
                    if (empty($player['nome_jogador']) && !empty($player['id_jogador'])) {
                        $pId = (int)$player['id_jogador'];
                        $infoJog = $resolverJogador($pId);

                        if (!empty($infoJog['nome'])) {
                            $player['nome_jogador'] = $infoJog['nome'];
                            if (empty($player['posicao']) && !empty($infoJog['posicao'])) {
                                $player['posicao'] = $infoJog['posicao'];
                            }
                            try {
                                $stmtUpEsc = $db->prepare("UPDATE jogos_clube_escalacao SET nome_jogador = ? WHERE id_partida = ? AND id_jogador = ?");
                                $stmtUpEsc->execute([$infoJog['nome'], $match_id, $pId]);
                            } catch (\Throwable $e) {}
                        }
                    }
                }
                unset($player);

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
                    } elseif($pTeamId === 1) {
                        $tKey = 1;
                    } elseif($pTeamId === 2) {
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
                                 $coachName = '<a href="/ligas/coachstatus.php?coach='.$p['id_jogador'].'" class="player-link-clean">'.$coachName.'</a>';
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
