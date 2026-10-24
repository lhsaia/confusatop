<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Próximos Jogos & Escalações";
$css_filename = "home_redesign";
$aux_css = "competicoes_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    $database = new Database();
    $db = $database->getConnection();
    $idUsuario = (int)$_SESSION['user_id'];

    // Função auxiliar para resolução de nome, escudo e dono do time
    // O ownership do time está estritamente associado ao PAÍS (paises.dono), seja via clube.Pais ou competicao_times.pais_time (times importados/vagas)
    function resolveTeamInfo($j, $side, &$sqliteCache, $idUsuario) {
        $tId = intval($j["time{$side}_id"]);
        $tNome = trim((string)($j["time{$side}_nome"] ?? ''));
        $cNameraw = trim((string)($j["c{$side}_nome"] ?? ''));
        $cEscudoraw = trim((string)($j["c{$side}_escudo"] ?? ''));
        $paisDono = intval($j["time{$side}_dono"] ?? 0);
        $paisNome = trim((string)($j["time{$side}_pais_nome"] ?? ''));
        $paisBandeira = trim((string)($j["time{$side}_bandeira"] ?? ''));
        $compId = intval($j['comp_id']);
        $isDono = ($paisDono === (int)$idUsuario);

        $nomeFinal = "";
        $escudoFinal = "0.png";

        if ($tId > 0 && !empty($cNameraw) && $cNameraw !== 'Sem clube') {
            $nomeFinal = $cNameraw;
            $escudoFinal = $cEscudoraw ?: '0.png';
        } elseif ($tId < 0) {
            // Buscar no SQLite da competição se existir
            if (!isset($sqliteCache[$compId])) {
                $sqliteCache[$compId] = [];
                $dbPath = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/{$compId}-database.db3";
                if (file_exists($dbPath)) {
                    try {
                        $sdb = new PDO("sqlite:" . $dbPath);
                        $st = $sdb->query("SELECT ID, Nome, Escudo FROM clube");
                        if ($st) {
                            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                                $escTratado = str_replace('../../images/escudos/', '', (string)($r['Escudo'] ?? ''));
                                $sqliteCache[$compId][intval($r['ID'])] = [
                                    'nome' => $r['Nome'] ?? '',
                                    'escudo' => $escTratado ?: '0.png'
                                ];
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }

            if (isset($sqliteCache[$compId][$tId])) {
                $nomeFinal = $sqliteCache[$compId][$tId]['nome'];
                $escudoFinal = $sqliteCache[$compId][$tId]['escudo'];
            } elseif (isset($sqliteCache[$compId][abs($tId)])) {
                $nomeFinal = $sqliteCache[$compId][abs($tId)]['nome'];
                $escudoFinal = $sqliteCache[$compId][abs($tId)]['escudo'];
            } elseif (!empty($paisNome)) {
                $nomeFinal = $paisNome . " (Vaga " . abs($tId) . ")";
                $escudoFinal = $paisBandeira ? '../bandeiras/' . $paisBandeira : '0.png';
            } else {
                $nomeFinal = !empty($tNome) ? $tNome : "Time #" . abs($tId);
            }
        } else {
            // tId == 0
            if (!empty($tNome)) {
                $nomeFinal = $tNome;
            } else {
                $nomeFinal = "A definir";
            }
        }

        return [
            'id' => $tId,
            'nome' => $nomeFinal,
            'escudo' => $escudoFinal,
            'is_dono' => $isDono,
            'pais_nome' => $paisNome
        ];
    }

    // Query para obter todos os jogos pendentes de times sob comando do usuário (seja em ligas próprias ou competições de terceiros)
    // O ownership do time está estritamente associado ao PAÍS (paises.dono), seja via clube.Pais ou competicao_times.pais_time (times importados/vagas)
    $query = "
        SELECT j.id as match_id, j.competicao_id as comp_id, cl.nome as comp_nome, cl.ano as comp_ano, cl.dono as comp_dono, cl.tipo as comp_tipo,
               u_dono.nome as comp_dono_nome,
               j.timeA_id, j.timeA_nome,
               j.timeB_id, j.timeB_nome,
               j.data, j.fase, j.grupo, f.nome as fase_nome,

               -- Dono e País Time A (resolvido por clube ou competicao_times)
               COALESCE(pctA.dono, pA.dono) as timeA_dono,
               COALESCE(pctA.nome, pA.nome) as timeA_pais_nome,
               COALESCE(pctA.bandeira, pA.bandeira) as timeA_bandeira,
               cA.Nome as cA_nome, cA.Escudo as cA_escudo,

               -- Dono e País Time B (resolvido por clube ou competicao_times)
               COALESCE(pctB.dono, pB.dono) as timeB_dono,
               COALESCE(pctB.nome, pB.nome) as timeB_pais_nome,
               COALESCE(pctB.bandeira, pB.bandeira) as timeB_bandeira,
               cB.Nome as cB_nome, cB.Escudo as cB_escudo,

               -- Check if time A has injured or suspended players
               (SELECT COUNT(*) FROM contratos_jogador cj 
                INNER JOIN jogador jog ON cj.jogador = jog.ID 
                LEFT JOIN competicao_suspensos cs ON cs.id_jogador = jog.ID AND cs.id_competicao = j.competicao_id AND cs.suspenso = 1
                WHERE cj.clube = j.timeA_id AND cj.tipoContrato = 0 
                  AND ((jog.lesionado_ate IS NOT NULL AND jog.lesionado_ate >= CURDATE()) OR cs.suspenso = 1)
               ) as desfalques_timeA,
               
               -- Check if time B has injured or suspended players
               (SELECT COUNT(*) FROM contratos_jogador cj 
                INNER JOIN jogador jog ON cj.jogador = jog.ID 
                LEFT JOIN competicao_suspensos cs ON cs.id_jogador = jog.ID AND cs.id_competicao = j.competicao_id AND cs.suspenso = 1
                WHERE cj.clube = j.timeB_id AND cj.tipoContrato = 0 
                  AND ((jog.lesionado_ate IS NOT NULL AND jog.lesionado_ate >= CURDATE()) OR cs.suspenso = 1)
               ) as desfalques_timeB

        FROM jogos_clube j
        INNER JOIN competicao_lista cl ON j.competicao_id = cl.id
        LEFT JOIN usuarios u_dono ON cl.dono = u_dono.id

        -- Time A
        LEFT JOIN clube cA ON j.timeA_id = cA.ID AND j.timeA_id > 0
        LEFT JOIN paises pA ON cA.Pais = pA.id
        LEFT JOIN competicao_times ctA ON ctA.id_competicao = j.competicao_id 
             AND (
                 (j.timeA_id > 0 AND ctA.id_time_portal = j.timeA_id) 
                 OR (j.timeA_id < 0 AND ctA.codigo_time = ABS(j.timeA_id))
                 OR (j.timeA_id = 0 AND j.timeA_nome IS NOT NULL AND j.timeA_nome != '' AND (ctA.slot = j.timeA_nome OR ctA.slot = TRIM(REPLACE(j.timeA_nome, 'Slot ', ''))))
             )
        LEFT JOIN paises pctA ON ctA.pais_time = pctA.id

        -- Time B
        LEFT JOIN clube cB ON j.timeB_id = cB.ID AND j.timeB_id > 0
        LEFT JOIN paises pB ON cB.Pais = pB.id
        LEFT JOIN competicao_times ctB ON ctB.id_competicao = j.competicao_id 
             AND (
                 (j.timeB_id > 0 AND ctB.id_time_portal = j.timeB_id) 
                 OR (j.timeB_id < 0 AND ctB.codigo_time = ABS(j.timeB_id))
                 OR (j.timeB_id = 0 AND j.timeB_nome IS NOT NULL AND j.timeB_nome != '' AND (ctB.slot = j.timeB_nome OR ctB.slot = TRIM(REPLACE(j.timeB_nome, 'Slot ', ''))))
             )
        LEFT JOIN paises pctB ON ctB.pais_time = pctB.id

        LEFT JOIN fase f ON j.fase = f.id
        WHERE j.simulador_interno = 1
          AND (j.tipo_times = 0 OR j.tipo_times IS NULL)
          AND (
              j.status = 0
              OR (j.timeA_penaltis IS NULL AND DATE_ADD(j.data, INTERVAL 120 MINUTE) > NOW())
              OR (j.timeA_penaltis IS NOT NULL AND DATE_ADD(j.data, INTERVAL 150 MINUTE) > NOW())
          )
          AND (
              COALESCE(pctA.dono, pA.dono) = :user_id_a 
              OR 
              COALESCE(pctB.dono, pB.dono) = :user_id_b
          )
        ORDER BY j.data ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id_a' => $idUsuario,
        ':user_id_b' => $idUsuario
    ]);
    $jogosRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqliteCache = [];
    $competicoesUnicas = [];
    $jogos = [];
    $totalJogosOutros = 0;
    $totalJogosProprios = 0;
    $totalJogosInternacionais = 0;

    foreach ($jogosRaw as $j) {
        $cId = (int)$j['comp_id'];
        $isCompOutro = ((int)$j['comp_dono'] !== $idUsuario);
        $isInternacional = (!isset($j['comp_tipo']) || (int)$j['comp_tipo'] === 0);

        if (!isset($competicoesUnicas[$cId])) {
            $competicoesUnicas[$cId] = [
                'id' => $cId,
                'nome' => $j['comp_ano'] . " - " . $j['comp_nome'],
                'is_outro' => $isCompOutro,
                'is_internacional' => $isInternacional,
                'dono_nome' => $j['comp_dono_nome'] ?? '',
                'qtd' => 0
            ];
        }
        $competicoesUnicas[$cId]['qtd']++;

        if ($isCompOutro) {
            $totalJogosOutros++;
        } else {
            $totalJogosProprios++;
        }

        if ($isInternacional) {
            $totalJogosInternacionais++;
        }

        $j['resolved_teamA'] = resolveTeamInfo($j, 'A', $sqliteCache, $idUsuario);
        $j['resolved_teamB'] = resolveTeamInfo($j, 'B', $sqliteCache, $idUsuario);
        $j['is_comp_outro'] = $isCompOutro;
        $j['is_internacional'] = $isInternacional;

        $jogos[] = $j;
    }
?>

<main class="redesign-container" style="max-width: 1000px; padding-top: 40px; padding-bottom: 40px;">
    <div class="propostas-card">
        <h2 class="propostas-title" style="margin-bottom: 10px !important;">Próximos Jogos & Escalações</h2>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px;">Aqui estão os próximos jogos de times sob seu comando (em ligas próprias ou organizadas por terceiros). Jogos com desfalques ativos (jogadores suspensos ou lesionados) estão realçados em vermelho.</p>
        
        <?php if (empty($jogos)): ?>
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 4rem; margin-bottom: 15px;">sports_soccer</span>
                <p>Nenhum jogo pendente encontrado para os seus times em competições ativas.</p>
            </div>
        <?php else: ?>
            <!-- Barra de Filtros e Abas -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <!-- Filtros por tipo de competição (Pills) -->
                <div style="display: flex; flex-wrap: wrap; gap: 8px;" id="filter-pills-container">
                    <button type="button" class="btn-filter-pill active" data-filter="all" style="background: #0284c7; color: #fff; border: 1px solid #0284c7; border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        Todos os Jogos (<?php echo count($jogos); ?>)
                    </button>
                    <button type="button" class="btn-filter-pill" data-filter="internacionais" style="background: rgba(16, 185, 129, 0.08); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        🌍 Apenas Internacionais (<?php echo $totalJogosInternacionais; ?>)
                    </button>
                    <button type="button" class="btn-filter-pill" data-filter="outros" style="background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        🌐 Competições de Outros (<?php echo $totalJogosOutros; ?>)
                    </button>
                    <button type="button" class="btn-filter-pill" data-filter="proprias" style="background: rgba(0, 0, 0, 0.04); color: #475569; border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 20px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        🏆 Minhas Competições (<?php echo $totalJogosProprios; ?>)
                    </button>
                </div>

                <!-- Filtro por competição específica -->
                <div style="min-width: 240px; flex-shrink: 0;">
                    <select id="select-filter-comp" style="width: 100%; padding: 7px 12px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.12); background: #ffffff; color: #1e293b; font-size: 0.85rem; font-weight: 500; outline: none; cursor: pointer;">
                        <option value="all">Todas as competições (<?php echo count($competicoesUnicas); ?>)</option>
                        <?php foreach ($competicoesUnicas as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['nome']) . " (" . $c['qtd'] . ")" . ($c['is_outro'] ? " [Org: " . htmlspecialchars($c['dono_nome']) . "]" : ""); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="matches-list-container" style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($jogos as $j): 
                    $tA = $j['resolved_teamA'];
                    $tB = $j['resolved_teamB'];
                    $isDonoA = $tA['is_dono'];
                    $isDonoB = $tB['is_dono'];
                    $isCompOutro = $j['is_comp_outro'];
                    $isInternacional = $j['is_internacional'];
                    
                    $temDesfalqueA = ($isDonoA && $j['desfalques_timeA'] > 0);
                    $temDesfalqueB = ($isDonoB && $j['desfalques_timeB'] > 0);
                    $destacarJogo = ($temDesfalqueA || $temDesfalqueB);
                    
                    $cardBorder = $destacarJogo ? '1px solid rgba(239, 68, 68, 0.4)' : ($isCompOutro ? '1px solid rgba(245, 158, 11, 0.35)' : '1px solid rgba(0, 0, 0, 0.08)');
                    $cardBg = $destacarJogo ? 'rgba(239, 68, 68, 0.04)' : ($isCompOutro ? 'rgba(254, 243, 199, 0.15)' : 'rgba(248, 250, 252, 0.8)');
                ?>
                    <div class="match-item-card" 
                         data-comp-id="<?php echo $j['comp_id']; ?>" 
                         data-is-outro="<?php echo $isCompOutro ? '1' : '0'; ?>"
                         data-is-internacional="<?php echo $isInternacional ? '1' : '0'; ?>"
                         style="background: <?php echo $cardBg; ?>; border: <?php echo $cardBorder; ?>; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                         onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 15px rgba(0,0,0,0.06)';"
                         onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                        
                        <!-- Left Info: Comp e Data -->
                        <div style="flex: 1; min-width: 220px;">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; background: rgba(2, 132, 199, 0.1); color: #0284c7; padding: 3px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($j['comp_ano'] . " - " . $j['comp_nome']); ?>
                                </span>
                                <?php if ($isInternacional): ?>
                                    <span style="font-size: 0.72rem; background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); padding: 2px 7px; border-radius: 4px; font-weight: 600;" title="Competição Internacional">
                                        🌍 Internacional
                                    </span>
                                <?php endif; ?>
                                <?php if ($isCompOutro): ?>
                                    <span style="font-size: 0.72rem; background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25); padding: 2px 7px; border-radius: 4px; font-weight: 600;" title="Competição organizada por outro treinador">
                                        🌐 Org: <?php echo htmlspecialchars($j['comp_dono_nome'] ?: 'Outro Usuário'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 8px;">
                                📅 <?php echo date('d/m/Y H:i', strtotime($j['data'])); ?> | 
                                🏆 <?php echo htmlspecialchars($j['fase_nome'] ?: 'Partida'); ?><?php echo !empty($j['grupo']) ? " (Grupo " . $j['grupo'] . ")" : ""; ?>
                            </div>
                        </div>

                        <!-- Center Info: Placar / Confronto -->
                        <div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex: 2; min-width: 300px;">
                            <!-- Time A -->
                            <div style="display: flex; flex-direction: column; align-items: center; width: 130px; text-align: center;">
                                <img src="/images/escudos/<?php echo $tA['escudo']; ?>" style="height: 35px; width: auto; object-fit: contain; margin-bottom: 5px;">
                                <span style="font-weight: <?php echo $isDonoA ? 'bold' : 'normal'; ?>; color: <?php echo $isDonoA ? '#0284c7' : '#334155'; ?>; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($tA['nome']); ?>
                                </span>
                                <?php if ($isDonoA): ?>
                                    <span style="font-size: 0.68rem; color: #0284c7; font-weight: 600; margin-top: 2px;">(Seu time)</span>
                                <?php endif; ?>
                                <?php if ($temDesfalqueA): ?>
                                    <span style="color: #ef4444; font-size: 0.72rem; font-weight: 600; margin-top: 4px; background: rgba(239, 68, 68, 0.1); padding: 1px 5px; border-radius: 3px;">
                                        ⚠️ <?php echo $j['desfalques_timeA']; ?> Desfalque(s)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <span style="font-weight: bold; color: #64748b; font-size: 1.2rem;">VS</span>

                            <!-- Time B -->
                            <div style="display: flex; flex-direction: column; align-items: center; width: 130px; text-align: center;">
                                <img src="/images/escudos/<?php echo $tB['escudo']; ?>" style="height: 35px; width: auto; object-fit: contain; margin-bottom: 5px;">
                                <span style="font-weight: <?php echo $isDonoB ? 'bold' : 'normal'; ?>; color: <?php echo $isDonoB ? '#0284c7' : '#334155'; ?>; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($tB['nome']); ?>
                                </span>
                                <?php if ($isDonoB): ?>
                                    <span style="font-size: 0.68rem; color: #0284c7; font-weight: 600; margin-top: 2px;">(Seu time)</span>
                                <?php endif; ?>
                                <?php if ($temDesfalqueB): ?>
                                    <span style="color: #ef4444; font-size: 0.72rem; font-weight: 600; margin-top: 4px; background: rgba(239, 68, 68, 0.1); padding: 1px 5px; border-radius: 3px;">
                                        ⚠️ <?php echo $j['desfalques_timeB']; ?> Desfalque(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Info: Ações de Escalação -->
                        <div style="display: flex; flex-direction: column; gap: 8px; justify-content: center; align-items: flex-end; min-width: 150px;">
                            <?php if ($isDonoA && $tA['id'] != 0): ?>
                                <a href="/competicoes/escalacao_jogo.php?comp=<?php echo $j['comp_id']; ?>&team=<?php echo $tA['id']; ?>&jogo=<?php echo $j['match_id']; ?>" 
                                   style="display: inline-flex; align-items: center; gap: 5px; background: #0284c7; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s;"
                                   onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                                    📋 Escalar <?php echo htmlspecialchars($tA['nome']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($isDonoB && $tB['id'] != 0): ?>
                                <a href="/competicoes/escalacao_jogo.php?comp=<?php echo $j['comp_id']; ?>&team=<?php echo $tB['id']; ?>&jogo=<?php echo $j['match_id']; ?>" 
                                   style="display: inline-flex; align-items: center; gap: 5px; background: #0284c7; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s;"
                                   onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                                    📋 Escalar <?php echo htmlspecialchars($tB['nome']); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mensagem quando nenhum jogo bater com o filtro selecionado -->
            <div id="no-filtered-matches" style="display: none; text-align: center; padding: 40px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 10px; color: #94a3b8;">filter_alt_off</span>
                <p>Nenhum jogo encontrado para os filtros selecionados.</p>
            </div>
        <?php endif; ?>

<script>
$(document).ready(function() {
    var activePill = 'all';

    function applyFilters() {
        var selectedComp = $('#select-filter-comp').val();
        var visibleCount = 0;

        $('.match-item-card').each(function() {
            var card = $(this);
            var compId = card.data('comp-id');
            var isOutro = card.data('is-outro') == '1';

            var matchPill = true;
            if (activePill === 'internacionais') {
                matchPill = (card.data('is-internacional') == '1');
            } else if (activePill === 'outros') {
                matchPill = isOutro;
            } else if (activePill === 'proprias') {
                matchPill = !isOutro;
            }

            var matchComp = (selectedComp === 'all' || selectedComp == compId);

            if (matchPill && matchComp) {
                card.show();
                visibleCount++;
            } else {
                card.hide();
            }
        });

        if (visibleCount === 0) {
            $('#no-filtered-matches').fadeIn(150);
        } else {
            $('#no-filtered-matches').hide();
        }
    }

    // Clique nas abas / pills
    $('.btn-filter-pill').on('click', function() {
        $('.btn-filter-pill').each(function() {
            var pill = $(this);
            var f = pill.data('filter');
            if (f === 'all') {
                pill.css({ background: 'rgba(0, 0, 0, 0.04)', color: '#475569', borderColor: 'rgba(0, 0, 0, 0.08)' });
            } else if (f === 'internacionais') {
                pill.css({ background: 'rgba(16, 185, 129, 0.08)', color: '#059669', borderColor: 'rgba(16, 185, 129, 0.25)' });
            } else if (f === 'outros') {
                pill.css({ background: 'rgba(245, 158, 11, 0.1)', color: '#d97706', borderColor: 'rgba(245, 158, 11, 0.3)' });
            } else {
                pill.css({ background: 'rgba(0, 0, 0, 0.04)', color: '#475569', borderColor: 'rgba(0, 0, 0, 0.08)' });
            }
            pill.removeClass('active');
        });

        var current = $(this);
        current.addClass('active');
        activePill = current.data('filter');

        if (activePill === 'all') {
            current.css({ background: '#0284c7', color: '#fff', borderColor: '#0284c7' });
        } else if (activePill === 'internacionais') {
            current.css({ background: '#059669', color: '#fff', borderColor: '#059669' });
        } else if (activePill === 'outros') {
            current.css({ background: '#d97706', color: '#fff', borderColor: '#d97706' });
        } else {
            current.css({ background: '#0284c7', color: '#fff', borderColor: '#0284c7' });
        }

        applyFilters();
    });

    // Mudança no select de competição
    $('#select-filter-comp').on('change', function() {
        applyFilters();
    });
});
</script>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
               onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para Minha Área
            </a>
        </div>
    </div>
</main>

<?php
} else {
    echo "<div style='padding:50px; color:#fff;'>Por favor faça o login para acessar esta página.</div>";
}
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
