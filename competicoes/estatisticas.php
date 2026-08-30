<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$idCompeticao = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

    $database = new Database();
    $db = $database->getConnection();
    
    $competicao = new Competicao_clube($db);
    $info = $competicao->readInfo($idCompeticao);
    
    if (!$info) {
        die("Competição não encontrada.");
    }
    
    $nome_competicao = $info['nome'];
    $ano_competicao = $info['ano'];
    $logo_competicao = $info['logo'];
    $sede_competicao = $info['sede'];
    $federacao_nome = $info['federacao'];
    
    $optionsComp = $competicao->getOptions($idCompeticao);
    $tipoCompeticao = isset($optionsComp['tipocompeticao']) ? (int)$optionsComp['tipocompeticao'] : 0;
    $numTeamsComp = isset($optionsComp['numero_times']) ? (int)$optionsComp['numero_times'] : 0;

    // Conectar ao SQLite da competição
    $compDatabase = new SQLiteDatabase();
    $compDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
    $cdb = $compDatabase->getConnection();
    
    if (!$cdb) {
        die("Banco de dados da competição não disponível.");
    }
    
    // 1. Carregar Clubes do SQLite
    $stmtClubes = $cdb->query("SELECT ID, Nome, TresLetras, Escudo FROM clube");
    $clubes = [];
    while ($row = $stmtClubes->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['Escudo'])) {
            $row['Escudo'] = basename($row['Escudo']);
        }
        $clubes[$row['ID']] = $row;
    }

    // Carregar vagas / slots atribuídos do MariaDB
    $assignedSlotTeams = [];
    $stmtTimesSlots = $competicao->carregarListaTimes($idCompeticao);
    while ($rSlot = $stmtTimesSlots->fetch(PDO::FETCH_ASSOC)) {
        $sName = !empty($rSlot['slot']) ? $rSlot['slot'] : ("Slot " . $rSlot['codigo_time']);
        if (!empty($rSlot['id_time_portal']) && intval($rSlot['id_time_portal']) > 0) {
            $assignedSlotTeams[$sName] = intval($rSlot['id_time_portal']);
        } else if ($rSlot['has_team'] == 1 || $rSlot['has_team'] == '1') {
            $assignedSlotTeams[$sName] = -1 * abs(intval($rSlot['codigo_time']));
        }
    }
    
    // 2. Carregar Jogadores do SQLite
    $stmtJogadores = $cdb->query("SELECT ID, Nome, Nivel FROM jogador");
    $jogadoresMap = [];
    while ($row = $stmtJogadores->fetch(PDO::FETCH_ASSOC)) {
        $jogadoresMap[$row['ID']] = $row;
    }
    
    // 3. Mapear Jogador -> Clube pelo Elenco no SQLite
    $stmtElenco = $cdb->query("SELECT * FROM elenco");
    $jogadorClubeMap = [];
    while ($row = $stmtElenco->fetch(PDO::FETCH_ASSOC)) {
        $clubeId = $row['Clube'];
        for ($i = 1; $i <= 23; $i++) {
            $pId = $row['Jogador' . $i];
            if ($pId) {
                $jogadorClubeMap[$pId] = $clubeId;
            }
        }
    }
    
    // 4. Carregar Jogos da Competição
    $stmtJogos = $db->prepare("SELECT id, timeA_id, timeA_nome, timeA_gols, timeB_id, timeB_nome, timeB_gols, timeA_penaltis, timeB_penaltis, data, status, fase, grupo, path 
                               FROM jogos_clube 
                               WHERE competicao_id = :idComp 
                                 AND simulador_interno = 1
                               ORDER BY data ASC, id ASC");
    $stmtJogos->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtJogos->execute();
    $jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Carregar todos os jogos da Fase 2 para mapear a estrutura de Grupos e Clubes
    $stmtFase2Jogos = $db->prepare("SELECT timeA_id, timeB_id, grupo 
                                    FROM jogos_clube 
                                    WHERE competicao_id = :idComp 
                                      AND simulador_interno = 1 
                                      AND fase = 2");
    $stmtFase2Jogos->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtFase2Jogos->execute();
    $allFase2 = $stmtFase2Jogos->fetchAll(PDO::FETCH_ASSOC);

    // Detectar quais grupos existem na Fase 2
    $gruposDetectados = [];
    $clubeGrupoMap = [];
    foreach ($allFase2 as $j) {
        $g = trim($j['grupo'] ?? '');
        if ($g !== '') {
            if (!in_array($g, $gruposDetectados)) {
                $gruposDetectados[] = $g;
            }
            if (!empty($j['timeA_id']) && !isset($clubeGrupoMap[$j['timeA_id']])) $clubeGrupoMap[$j['timeA_id']] = $g;
            if (!empty($j['timeB_id']) && !isset($clubeGrupoMap[$j['timeB_id']])) $clubeGrupoMap[$j['timeB_id']] = $g;
        }
    }
    sort($gruposDetectados);
    $temGrupos = !empty($gruposDetectados);
    
    // Inicializar tabela por grupo (ou tabela única se não houver grupos)
    $tabelaPorGrupo = [];
    if ($temGrupos) {
        foreach ($gruposDetectados as $g) {
            $tabelaPorGrupo[$g] = [];
        }
        foreach ($clubeGrupoMap as $idC => $g) {
            if ($g !== '' && isset($clubes[$idC])) {
                $c = $clubes[$idC];
                $tabelaPorGrupo[$g][$idC] = [
                    'id' => $idC,
                    'nome' => $c['Nome'],
                    'escudo' => $c['Escudo'],
                    'sigla' => $c['TresLetras'],
                    'jogos' => 0,
                    'pontos' => 0,
                    'vitorias' => 0,
                    'empates' => 0,
                    'derrotas' => 0,
                    'gp' => 0,
                    'gc' => 0,
                    'sg' => 0
                ];
            }
        }
    } else {
        $tabelaPorGrupo[''] = [];
        foreach ($clubes as $idC => $c) {
            $tabelaPorGrupo[''][$idC] = [
                'id' => $idC,
                'nome' => $c['Nome'],
                'escudo' => $c['Escudo'],
                'sigla' => $c['TresLetras'],
                'jogos' => 0,
                'pontos' => 0,
                'vitorias' => 0,
                'empates' => 0,
                'derrotas' => 0,
                'gp' => 0,
                'gc' => 0,
                'sg' => 0
            ];
        }
    }
    
    $fasesKnockout = [];
    
    foreach ($jogos as $j) {
        $dataJogo = !empty($j['data']) ? strtotime($j['data']) : time();
        $temPen = ($j['timeA_penaltis'] !== null && $j['timeA_penaltis'] !== '');
        $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
        $jaTerminou = (time() >= ($dataJogo + $duracaoSegundos));

        // Se já foi simulado e o tempo real da partida terminou
        if ($j['status'] == 1 && $jaTerminou) {
            $idA = (int)$j['timeA_id'];
            $idB = (int)$j['timeB_id'];
            $golsA = (int)$j['timeA_gols'];
            $golsB = (int)$j['timeB_gols'];
            
            // Fase de grupos entra na classificação
            if ($j['fase'] == 2) {
                $g = trim($j['grupo'] ?? '');
                if ($temGrupos) {
                    if ($g === '' || !isset($tabelaPorGrupo[$g])) {
                        $g = $clubeGrupoMap[$idA] ?? ($clubeGrupoMap[$idB] ?? '');
                    }
                } else {
                    $g = '';
                }
                
                if (isset($tabelaPorGrupo[$g][$idA]) && isset($tabelaPorGrupo[$g][$idB])) {
                    $tabelaPorGrupo[$g][$idA]['jogos']++;
                    $tabelaPorGrupo[$g][$idB]['jogos']++;
                    $tabelaPorGrupo[$g][$idA]['gp'] += $golsA;
                    $tabelaPorGrupo[$g][$idB]['gp'] += $golsB;
                    $tabelaPorGrupo[$g][$idA]['gc'] += $golsB;
                    $tabelaPorGrupo[$g][$idB]['gc'] += $golsA;
                    
                    if ($golsA > $golsB) {
                        $tabelaPorGrupo[$g][$idA]['pontos'] += 3;
                        $tabelaPorGrupo[$g][$idA]['vitorias']++;
                        $tabelaPorGrupo[$g][$idB]['derrotas']++;
                    } elseif ($golsB > $golsA) {
                        $tabelaPorGrupo[$g][$idB]['pontos'] += 3;
                        $tabelaPorGrupo[$g][$idB]['vitorias']++;
                        $tabelaPorGrupo[$g][$idA]['derrotas']++;
                    } else {
                        $tabelaPorGrupo[$g][$idA]['pontos'] += 1;
                        $tabelaPorGrupo[$g][$idB]['pontos'] += 1;
                        $tabelaPorGrupo[$g][$idA]['empates']++;
                        $tabelaPorGrupo[$g][$idB]['empates']++;
                    }
                }
            }
        }
        
        // Separar todos os jogos de mata-mata (agendados e concluídos)
        if ($j['fase'] > 2) {
            $fasesKnockout[$j['fase']][] = $j;
        }
    }
    
    // Atualizar saldo de gols e ordenar cada grupo
    foreach ($tabelaPorGrupo as $g => $grupoTab) {
        foreach ($grupoTab as $idC => $c) {
            $tabelaPorGrupo[$g][$idC]['sg'] = $tabelaPorGrupo[$g][$idC]['gp'] - $tabelaPorGrupo[$g][$idC]['gc'];
        }
        usort($tabelaPorGrupo[$g], function($a, $b) {
            if ($a['pontos'] != $b['pontos']) return $b['pontos'] - $a['pontos'];
            if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] - $a['vitorias'];
            if ($a['sg'] != $b['sg']) return $b['sg'] - $a['sg'];
            return $b['gp'] - $a['gp'];
        });
    }
    ksort($tabelaPorGrupo);
    
    // 5. Escanear arquivos de partidas e consolidar estatísticas de jogadores
    $playerStats = [];
    $nomeComposto = $ano_competicao . " - " . $nome_competicao;
    $dirPartidas = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada";
    
    if (is_dir($dirPartidas)) {
        $files = scandir($dirPartidas);
        foreach ($files as $f) {
            if (pathinfo($f, PATHINFO_EXTENSION) === 'hyj') {
                $content = @file_get_contents($dirPartidas . "/" . $f);
                if ($content !== false) {
                    $json = json_decode($content);
                    if ($json) {
                        // Processar Time 1
                        if (isset($json->time1->jogadores)) {
                            foreach ($json->time1->jogadores as $pj) {
                                $pid = (int)$pj->idJogador;
                                if ($pid > 0) {
                                    if (!isset($playerStats[$pid])) {
                                        $playerStats[$pid] = ['gols' => 0, 'assistencias' => 0, 'amarelos' => 0, 'vermelhos' => 0, 'partidas' => 0];
                                    }
                                    $playerStats[$pid]['gols'] += (int)$pj->gols;
                                    $playerStats[$pid]['assistencias'] += (int)$pj->assistencias;
                                    $playerStats[$pid]['amarelos'] += (int)$pj->amarelos;
                                    $playerStats[$pid]['vermelhos'] += (int)$pj->vermelhos;
                                    if ((int)$pj->minutos > 0) {
                                        $playerStats[$pid]['partidas']++;
                                    }
                                }
                            }
                        }
                        // Processar Time 2
                        if (isset($json->time2->jogadores)) {
                            foreach ($json->time2->jogadores as $pj) {
                                $pid = (int)$pj->idJogador;
                                if ($pid > 0) {
                                    if (!isset($playerStats[$pid])) {
                                        $playerStats[$pid] = ['gols' => 0, 'assistencias' => 0, 'amarelos' => 0, 'vermelhos' => 0, 'partidas' => 0];
                                    }
                                    $playerStats[$pid]['gols'] += (int)$pj->gols;
                                    $playerStats[$pid]['assistencias'] += (int)$pj->assistencias;
                                    $playerStats[$pid]['amarelos'] += (int)$pj->amarelos;
                                    $playerStats[$pid]['vermelhos'] += (int)$pj->vermelhos;
                                    if ((int)$pj->minutos > 0) {
                                        $playerStats[$pid]['partidas']++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 6. Consultar Departamento Médico (Lesionados) no MySQL
    // Obter todos os jogadores com lesão ativa (na tabela jogador ou competicao_suspensos)
    $lesionados = [];
    if (!empty($jogadoresMap)) {
        $pIds = array_keys($jogadoresMap);
        $inClause = implode(',', $pIds);
        try {
            $stmtLes = $db->prepare("SELECT val.id, GREATEST(COALESCE(j.lesionado_ate, '1970-01-01'), COALESCE(cs.lesionado_ate, '1970-01-01')) AS lesionado_ate
                                     FROM (
                                         SELECT id FROM jogador WHERE id IN ($inClause) AND lesionado_ate >= CURDATE()
                                         UNION
                                         SELECT id_jogador AS id FROM competicao_suspensos WHERE id_competicao = :idComp AND id_jogador IN ($inClause) AND lesionado_ate >= CURDATE()
                                     ) val
                                     LEFT JOIN jogador j ON val.id = j.id
                                     LEFT JOIN competicao_suspensos cs ON val.id = cs.id_jogador AND cs.id_competicao = :idComp2");
            $stmtLes->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
            $stmtLes->bindParam(':idComp2', $idCompeticao, PDO::PARAM_INT);
            $stmtLes->execute();
            $lesRows = $stmtLes->fetchAll(PDO::FETCH_ASSOC);
            foreach ($lesRows as $l) {
                $pid = (int)$l['id'];
                if (isset($jogadoresMap[$pid])) {
                    $clubeId = $jogadorClubeMap[$pid] ?? 0;
                    $lesionados[] = [
                        'id' => $pid,
                        'nome' => $jogadoresMap[$pid]['Nome'],
                        'clube' => $clubes[$clubeId]['Nome'] ?? 'Sem clube',
                        'escudo' => $clubes[$clubeId]['Escudo'] ?? '',
                        'retorno' => date('d/m/Y', strtotime($l['lesionado_ate']))
                    ];
                }
            }
        } catch (Exception $e) {}
    }
    
    // 7. Consultar Suspensos da Competição no MySQL
    $stmtSus = $db->prepare("SELECT cs.id_jogador, cs.suspenso 
                             FROM competicao_suspensos cs 
                             WHERE cs.id_competicao = :idComp AND cs.suspenso = 1");
    $stmtSus->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtSus->execute();
    $suspRows = $stmtSus->fetchAll(PDO::FETCH_ASSOC);
    $suspensos = [];
    foreach ($suspRows as $s) {
        $pid = (int)$s['id_jogador'];
        if (isset($jogadoresMap[$pid])) {
            $clubeId = $jogadorClubeMap[$pid] ?? 0;
            $suspensos[] = [
                'id' => $pid,
                'nome' => $jogadoresMap[$pid]['Nome'],
                'clube' => $clubes[$clubeId]['Nome'] ?? 'Sem clube',
                'escudo' => $clubes[$clubeId]['Escudo'] ?? ''
            ];
        }
    }
    
    // 8. Consultar Cartões Amarelos Acumulados da Competição no MySQL
    $stmtAmarelos = $db->prepare("SELECT cs.id_jogador, cs.cartoes_amarelos 
                                 FROM competicao_suspensos cs 
                                 WHERE cs.id_competicao = :idComp AND cs.cartoes_amarelos > 0 
                                 ORDER BY cs.cartoes_amarelos DESC");
    $stmtAmarelos->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtAmarelos->execute();
    $amarelosRows = $stmtAmarelos->fetchAll(PDO::FETCH_ASSOC);
    $cartoesAmarelosAcumulados = [];
    foreach ($amarelosRows as $ar) {
        $pid = (int)$ar['id_jogador'];
        if (isset($jogadoresMap[$pid])) {
            $clubeId = $jogadorClubeMap[$pid] ?? 0;
            $cartoesAmarelosAcumulados[] = [
                'id' => $pid,
                'nome' => $jogadoresMap[$pid]['Nome'],
                'clube' => $clubes[$clubeId]['Nome'] ?? 'Sem clube',
                'escudo' => $clubes[$clubeId]['Escudo'] ?? '',
                'amarelos' => (int)$ar['cartoes_amarelos']
            ];
        }
    }
    
    // Preparar listas para a aba de estatísticas
    $artilharia = [];
    $assistencias = [];
    $cartoes = [];
    
    foreach ($playerStats as $pid => $stats) {
        if (!isset($jogadoresMap[$pid])) continue;
        $clubeId = $jogadorClubeMap[$pid] ?? 0;
        
        $pInfo = [
            'id' => $pid,
            'nome' => $jogadoresMap[$pid]['Nome'],
            'clube' => $clubes[$clubeId]['Nome'] ?? 'Sem clube',
            'escudo' => $clubes[$clubeId]['Escudo'] ?? '',
            'partidas' => $stats['partidas'],
            'valor' => 0
        ];
        
        if ($stats['gols'] > 0) {
            $pInfo['valor'] = $stats['gols'];
            $artilharia[] = $pInfo;
        }
        if ($stats['assistencias'] > 0) {
            $pInfo['valor'] = $stats['assistencias'];
            $assistencias[] = $pInfo;
        }
        if ($stats['amarelos'] > 0 || $stats['vermelhos'] > 0) {
            $pInfo['amarelos'] = $stats['amarelos'];
            $pInfo['vermelhos'] = $stats['vermelhos'];
            $cartoes[] = $pInfo;
        }
    }
    
    // Ordenar listas
    usort($artilharia, function($a, $b) { return $b['valor'] - $a['valor']; });
    usort($assistencias, function($a, $b) { return $b['valor'] - $a['valor']; });
    usort($cartoes, function($a, $b) { 
        if ($a['vermelhos'] != $b['vermelhos']) return $b['vermelhos'] - $a['vermelhos'];
        return $b['amarelos'] - $a['amarelos'];
    });
    
    // Limitar tops a 15
    $artilharia = array_slice($artilharia, 0, 15);
    $assistencias = array_slice($assistencias, 0, 15);
    $cartoes = array_slice($cartoes, 0, 15);

    $page_title = "Estatísticas - " . $nome_competicao;
    $css_filename = "home_redesign";
    $aux_css = "estatisticas_redesign";
    $css_login = 'login';
    $css_versao = date('h:i:s');
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
?>

<main class="propostas-container">
    <div class="propostas-card">
        <h2 class="propostas-title"><?php echo $nome_competicao . " " . $ano_competicao; ?> - Estatísticas</h2>
        
        <!-- Abas -->
        <div class="stats-tabs">
            <button class="stats-tab-btn active" data-tab="tab-classificacao">
                <span class="material-symbols-outlined">table_chart</span> Classificação
            </button>
            <button class="stats-tab-btn" data-tab="tab-chave">
                <span class="material-symbols-outlined">account_tree</span> Chave de Mata-Mata
            </button>
            <button class="stats-tab-btn" data-tab="tab-jogadores">
                <span class="material-symbols-outlined">sports_soccer</span> Jogadores
            </button>
            <button class="stats-tab-btn" data-tab="tab-departamento">
                <span class="material-symbols-outlined">medical_services</span> DM & Suspensões
            </button>
        </div>
        
        <!-- Conteúdo 1: Classificação -->
        <div class="tab-content active" id="tab-classificacao">
            <?php
            if ($temGrupos):
                $firstGroup = true;
            ?>
                <!-- Sub-abas de Grupos -->
                <div class="group-tabs">
                    <?php foreach (array_keys($tabelaPorGrupo) as $gKey): ?>
                        <?php if (trim($gKey) === '') continue; ?>
                        <button class="group-tab-btn <?php echo $firstGroup ? 'active' : ''; ?>" data-group="grupo-<?php echo htmlspecialchars($gKey); ?>">
                            Grupo <?php echo htmlspecialchars($gKey); ?>
                        </button>
                        <?php $firstGroup = false; ?>
                    <?php endforeach; ?>
                </div>
                <?php
                $firstGroup = true;
                foreach ($tabelaPorGrupo as $gKey => $grupoTab):
                    if (trim($gKey) === '') continue;
                ?>
                <div class="group-content <?php echo $firstGroup ? 'active' : ''; ?>" id="grupo-<?php echo htmlspecialchars($gKey); ?>">
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Time</th>
                                    <th>P</th>
                                    <th>J</th>
                                    <th>V</th>
                                    <th>E</th>
                                    <th>D</th>
                                    <th>GP</th>
                                    <th>GC</th>
                                    <th>SG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pos = 1;
                                $totalGrupo = count($grupoTab);
                                foreach ($grupoTab as $team):
                                    $posClass = '';
                                    if ($pos <= 2) $posClass = 'zone-g4'; // top 2 avançam
                                    if ($pos >= $totalGrupo - 1 && $totalGrupo > 4) $posClass = 'zone-relegation';
                                ?>
                                <tr>
                                    <td class="pos-col <?php echo $posClass; ?>"><?php echo $pos++; ?>º</td>
                                    <td class="team-col">
                                        <div class="team-cell">
                                            <img class="team-logo" src="/images/escudos/<?php echo $team['escudo'] ? $team['escudo'] : '0.png'; ?>" alt="Escudo" />
                                            <span><?php echo htmlspecialchars($team['nome']); ?></span>
                                        </div>
                                    </td>
                                    <td class="pts-col"><?php echo $team['pontos']; ?></td>
                                    <td><?php echo $team['jogos']; ?></td>
                                    <td><?php echo $team['vitorias']; ?></td>
                                    <td><?php echo $team['empates']; ?></td>
                                    <td><?php echo $team['derrotas']; ?></td>
                                    <td><?php echo $team['gp']; ?></td>
                                    <td><?php echo $team['gc']; ?></td>
                                    <td><?php echo ($team['sg'] > 0 ? '+' : '') . $team['sg']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php $firstGroup = false; endforeach; ?>
            <?php else: /* Pontos corridos - tabela única */ ?>
                <div class="stats-table-wrapper">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th class="pos-col">#</th>
                                <th style="text-align: left;">Time</th>
                                <th>P</th>
                                <th>J</th>
                                <th>V</th>
                                <th>E</th>
                                <th>D</th>
                                <th>GP</th>
                                <th>GC</th>
                                <th>SG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $singleTab = reset($tabelaPorGrupo) ?: [];
                            $pos = 1;
                            $totalTabela = count($singleTab);
                            foreach ($singleTab as $team):
                                $posClass = '';
                                if ($pos <= 4) $posClass = 'zone-g4';
                                if ($pos > $totalTabela - 4 && $totalTabela > 8) $posClass = 'zone-relegation';
                            ?>
                            <tr>
                                <td class="pos-col <?php echo $posClass; ?>"><?php echo $pos++; ?>º</td>
                                <td class="team-col">
                                    <div class="team-cell">
                                        <img class="team-logo" src="/images/escudos/<?php echo $team['escudo'] ? $team['escudo'] : '0.png'; ?>" alt="Escudo" />
                                        <span><?php echo htmlspecialchars($team['nome']); ?></span>
                                    </div>
                                </td>
                                <td class="pts-col"><?php echo $team['pontos']; ?></td>
                                <td><?php echo $team['jogos']; ?></td>
                                <td><?php echo $team['vitorias']; ?></td>
                                <td><?php echo $team['empates']; ?></td>
                                <td><?php echo $team['derrotas']; ?></td>
                                <td><?php echo $team['gp']; ?></td>
                                <td><?php echo $team['gc']; ?></td>
                                <td><?php echo ($team['sg'] > 0 ? '+' : '') . $team['sg']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Conteúdo 2: Chave de Mata-Mata -->
        <div class="tab-content" id="tab-chave">
            <div class="bracket-phases-container">
                <?php 
                if (empty($fasesKnockout)): 
                    echo "<p style='text-align:center; padding: 2rem; color: #64748b;'>Nenhuma partida de mata-mata registrada.</p>";
                else:
                    // Ordenar fases na ordem cronológica de disputa do mata-mata
                    $faseOrder = [10 => 1, 9 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 8 => 7];
                    uksort($fasesKnockout, function($a, $b) use ($faseOrder) {
                        $orderA = $faseOrder[$a] ?? (100 + $a);
                        $orderB = $faseOrder[$b] ?? (100 + $b);
                        return $orderA - $orderB;
                    });
                    // Determinar se há BYEs no chaveamento de mata-mata
                    $primeiraFaseId = array_key_first($fasesKnockout);
                    $listaByesPrimeiraFase = [];
                    if ($tipoCompeticao == 1 && !empty($primeiraFaseId) && !empty($fasesKnockout[$primeiraFaseId])) {
                        // Coletar todos os times que estão jogando na primeira fase
                        $jogandoPrimeiraFaseIds = [];
                        $jogandoPrimeiraFaseNomes = [];
                        foreach ($fasesKnockout[$primeiraFaseId] as $p) {
                            if (!empty($p['timeA_id']) && (int)$p['timeA_id'] > 0) $jogandoPrimeiraFaseIds[(int)$p['timeA_id']] = true;
                            if (!empty($p['timeA_nome'])) $jogandoPrimeiraFaseNomes[trim($p['timeA_nome'])] = true;
                            if (!empty($p['timeB_id']) && (int)$p['timeB_id'] > 0) $jogandoPrimeiraFaseIds[(int)$p['timeB_id']] = true;
                            if (!empty($p['timeB_nome'])) $jogandoPrimeiraFaseNomes[trim($p['timeB_nome'])] = true;
                        }

                        // Comparar com todos os participantes/slots da competição
                        if (!empty($assignedSlotTeams)) {
                            foreach ($assignedSlotTeams as $sName => $cId) {
                                $cIdInt = (int)$cId;
                                $isNoJogo = false;
                                if ($cIdInt > 0 && isset($jogandoPrimeiraFaseIds[$cIdInt])) $isNoJogo = true;
                                if (isset($jogandoPrimeiraFaseNomes[$sName])) $isNoJogo = true;

                                if (!$isNoJogo) {
                                    $clubeObj = ($cIdInt > 0 && isset($clubes[$cIdInt])) ? $clubes[$cIdInt] : null;
                                    $nomeBye = $clubeObj ? $clubeObj['Nome'] : $sName;
                                    $escudoBye = $clubeObj ? ($clubeObj['Escudo'] ?? '0.png') : '0.png';

                                    $listaByesPrimeiraFase[] = [
                                        'slot' => $sName,
                                        'nome' => $nomeBye,
                                        'escudo' => $escudoBye
                                    ];
                                }
                            }
                        } else if (!empty($clubes)) {
                            foreach ($clubes as $cId => $cObj) {
                                if (!isset($jogandoPrimeiraFaseIds[$cId])) {
                                    $listaByesPrimeiraFase[] = [
                                        'slot' => $cObj['Nome'],
                                        'nome' => $cObj['Nome'],
                                        'escudo' => $cObj['Escudo'] ?? '0.png'
                                    ];
                                }
                            }
                        }
                    }

                    foreach ($fasesKnockout as $faseId => $partidasFase):
                        $nomeFase = "Fase " . $faseId;
                        if ($faseId == 10) $nomeFase = "32-avos de Final";
                        if ($faseId == 9) $nomeFase = "16-avos de Final";
                        if ($faseId == 3) $nomeFase = "Oitavas de Final";
                        if ($faseId == 4) $nomeFase = "Quartas de Final";
                        if ($faseId == 5) $nomeFase = "Semifinal";
                        if ($faseId == 6) $nomeFase = "Decisão do 3º Lugar";
                        if ($faseId == 8) $nomeFase = "Final";
                ?>
                    <div class="bracket-phase">
                        <h3 class="bracket-phase-title"><?php echo $nomeFase; ?></h3>
                        <div class="bracket-matches-grid">
                            <?php 
                            // Na primeira fase, se existirem BYEs, exibi-los
                            if ($faseId == $primeiraFaseId && !empty($listaByesPrimeiraFase)):
                                foreach ($listaByesPrimeiraFase as $byeItem):
                            ?>
                                <div class="bracket-bye-card">
                                    <div class="bracket-match-row">
                                        <span class="bracket-team">
                                            <img class="team-logo" src="/images/escudos/<?php echo $byeItem['escudo']; ?>" alt="" />
                                            <?php echo htmlspecialchars($byeItem['nome']); ?>
                                        </span>
                                        <span class="bracket-bye-badge">BYE</span>
                                    </div>
                                    <div class="bracket-match-info" style="color: #0284c7; font-weight: 500;">
                                        Classificado direto para a próxima fase
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            endif;
                            ?>

                            <?php foreach ($partidasFase as $partida): 
                                $tA = $clubes[$partida['timeA_id']] ?? null;
                                $tB = $clubes[$partida['timeB_id']] ?? null;
                                $nomeTimeA = $tA['Nome'] ?? ($partida['timeA_nome'] ?? 'Indefinido');
                                $nomeTimeB = $tB['Nome'] ?? ($partida['timeB_nome'] ?? 'Indefinido');
                                
                                $dtMatch = !empty($partida['data']) ? strtotime($partida['data']) : time();
                                $temPenaltis = ($partida['status'] == 1 && $partida['timeA_penaltis'] !== null && $partida['timeB_penaltis'] !== null);
                                $durMatchSec = $temPenaltis ? (150 * 60) : (120 * 60);
                                $matchTerminou = (time() >= ($dtMatch + $durMatchSec));
                                $podeRevelar = ($partida['status'] == 1 && $matchTerminou);

                                $gA = $podeRevelar ? $partida['timeA_gols'] : '-';
                                $gB = $podeRevelar ? $partida['timeB_gols'] : '-';
                                
                                $pA = ($podeRevelar && $temPenaltis) ? (int)$partida['timeA_penaltis'] : null;
                                $pB = ($podeRevelar && $temPenaltis) ? (int)$partida['timeB_penaltis'] : null;

                                $winA = "";
                                $winB = "";
                                if ($podeRevelar) {
                                    if ($gA > $gB) {
                                        $winA = "winner";
                                    } elseif ($gB > $gA) {
                                        $winB = "winner";
                                    } elseif ($temPenaltis) {
                                        if ($pA > $pB) $winA = "winner";
                                        elseif ($pB > $pA) $winB = "winner";
                                    }
                                }
                            ?>
                                <div class="bracket-match-card">
                                    <div class="bracket-match-row <?php echo $winA; ?>">
                                        <span class="bracket-team">
                                            <img class="team-logo" src="/images/escudos/<?php echo $tA['Escudo'] ?? '0.png'; ?>" alt="" />
                                            <?php echo htmlspecialchars($nomeTimeA); ?>
                                        </span>
                                        <span class="bracket-score">
                                            <?php echo $gA; ?>
                                            <?php if ($temPenaltis): ?>
                                                <small style="font-size: 0.8rem; color: #38bdf8; font-weight: 700; margin-left: 2px;">(<?php echo $pA; ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="bracket-match-row <?php echo $winB; ?>">
                                        <span class="bracket-team">
                                            <img class="team-logo" src="/images/escudos/<?php echo $tB['Escudo'] ?? '0.png'; ?>" alt="" />
                                            <?php echo htmlspecialchars($nomeTimeB); ?>
                                        </span>
                                        <span class="bracket-score">
                                            <?php echo $gB; ?>
                                            <?php if ($temPenaltis): ?>
                                                <small style="font-size: 0.8rem; color: #38bdf8; font-weight: 700; margin-left: 2px;">(<?php echo $pB; ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="bracket-match-info">
                                        <?php if($podeRevelar && $temPenaltis): ?>
                                            <span style="color: #0284c7; font-weight: 600; font-size: 0.78rem;">Pên: <?php echo $pA . " × " . $pB; ?></span>
                                        <?php elseif(!empty($partida['data'])): ?>
                                            <span><?php echo date('d/m/Y H:i', strtotime($partida['data'])); ?></span>
                                        <?php endif; ?>
                                        <?php if($partida['grupo']): ?>
                                            <span> • Grupo <?php echo htmlspecialchars($partida['grupo']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                endif; 
                ?>
            </div>
        </div>
        
        <!-- Conteúdo 3: Estatísticas de Jogadores -->
        <div class="tab-content" id="tab-jogadores">
            <div class="grid-stats-cards">
                <!-- Artilharia -->
                <div class="stats-subcard">
                    <h3 class="stats-subcard-title">
                        <span class="material-symbols-outlined" style="color: #fbbf24;">workspace_premium</span>
                        Artilharia
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Jogador</th>
                                    <th>Time</th>
                                    <th>Gols</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($artilharia)): ?>
                                    <tr><td colspan="3">Nenhum gol registrado.</td></tr>
                                <?php else: foreach ($artilharia as $art): ?>
                                    <tr>
                                        <td class="player-col"><?php echo htmlspecialchars($art['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $art['escudo'] ? $art['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($art['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="stats-count-badge"><?php echo $art['valor']; ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Assistências -->
                <div class="stats-subcard">
                    <h3 class="stats-subcard-title">
                        <span class="material-symbols-outlined" style="color: #34d399;">volunteer_activism</span>
                        Assistências
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Jogador</th>
                                    <th>Time</th>
                                    <th>Assists</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assistencias)): ?>
                                    <tr><td colspan="3">Nenhuma assistência registrada.</td></tr>
                                <?php else: foreach ($assistencias as $ass): ?>
                                    <tr>
                                        <td class="player-col"><?php echo htmlspecialchars($ass['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $ass['escudo'] ? $ass['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($ass['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="stats-count-badge" style="background:#34d399;"><?php echo $ass['valor']; ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo 4: DM & Suspensões -->
        <div class="tab-content" id="tab-departamento">
            <div class="grid-stats-cards">
                <!-- Suspensões -->
                <div class="stats-subcard">
                    <h3 class="stats-subcard-title" style="color: #ef4444;">
                        <span class="material-symbols-outlined">gavel</span>
                        Suspensos
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Jogador</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($suspensos)): ?>
                                    <tr><td colspan="3">Nenhum jogador suspenso.</td></tr>
                                <?php else: foreach ($suspensos as $sus): ?>
                                    <tr>
                                        <td class="player-col"><?php echo htmlspecialchars($sus['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $sus['escudo'] ? $sus['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($sus['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="stats-badge suspenso">Suspenso</span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Departamento Médico -->
                <div class="stats-subcard">
                    <h3 class="stats-subcard-title" style="color: #d97706;">
                        <span class="material-symbols-outlined">medical_services</span>
                        Departamento Médico
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Jogador</th>
                                    <th>Time</th>
                                    <th>Retorno</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lesionados)): ?>
                                    <tr><td colspan="3">Nenhum jogador lesionado.</td></tr>
                                <?php else: foreach ($lesionados as $les): ?>
                                    <tr>
                                        <td class="player-col"><?php echo htmlspecialchars($les['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $les['escudo'] ? $les['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($les['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="stats-badge lesionado"><?php echo $les['retorno']; ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cartões Amarelos -->
                <div class="stats-subcard">
                    <h3 class="stats-subcard-title" style="color: #fbbf24;">
                        <span class="material-symbols-outlined">warning</span>
                        Cartões Amarelos
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Jogador</th>
                                    <th>Time</th>
                                    <th>Cartões</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cartoesAmarelosAcumulados)): ?>
                                    <tr><td colspan="3">Nenhum cartão amarelo acumulado.</td></tr>
                                <?php else: foreach ($cartoesAmarelosAcumulados as $am): ?>
                                    <tr>
                                        <td class="player-col"><?php echo htmlspecialchars($am['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $am['escudo'] ? $am['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($am['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                <?php for ($i = 0; $i < $am['amarelos']; $i++): ?>
                                                    <span style="display: inline-block; width: 10px; height: 14px; background: #fbbf24; border-radius: 2px; border: 1px solid rgba(0,0,0,0.2);"></span>
                                                <?php endfor; ?>
                                                <span style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-left: 2px;">(<?php echo $am['amarelos']; ?>)</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top: 30px;">
            <a href="competitionstatus.php?id=<?php echo $idCompeticao; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para a Competição
            </a>
        </div>
    </div>
</main>

<script>
$(document).ready(function($){
    // Alternar abas principais
    $('.stats-tab-btn').on('click', function(){
        var tabId = $(this).data('tab');
        
        $('.stats-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Alternar sub-abas de grupos
    $(document).on('click', '.group-tab-btn', function(){
        var groupId = $(this).data('group');
        var $container = $(this).closest('#tab-classificacao');
        
        $container.find('.group-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $container.find('.group-content').removeClass('active');
        $container.find('#' + groupId).addClass('active');
    });
});
</script>

<?php
} else {
    echo "Usuário, por favor refaça o login!";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
