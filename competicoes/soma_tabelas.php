<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: /index.php");
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$idComp1 = isset($_GET['comp1']) ? (int)$_GET['comp1'] : 0;
$idComp2 = isset($_GET['comp2']) ? (int)$_GET['comp2'] : 0;

// Carregar lista de todas as competições para os selects
$todasCompeticoes = [];
$stmtAll = $db->query("SELECT id, nome, ano, sede, logo FROM competicao_lista ORDER BY ano DESC, nome ASC");
if ($stmtAll) {
    while ($r = $stmtAll->fetch(PDO::FETCH_ASSOC)) {
        $todasCompeticoes[] = $r;
    }
}

// Carregar clubes cadastrados no portal para nomes e escudos atualizados
$clubesPortal = [];
try {
    $stmtTimes = $db->query("SELECT id, nome as Nome, sigla as TresLetras, escudo as Escudo FROM time");
    if ($stmtTimes) {
        while ($pTime = $stmtTimes->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($pTime['Escudo'])) {
                $pTime['Escudo'] = basename($pTime['Escudo']);
            }
            $clubesPortal[(int)$pTime['id']] = $pTime;
        }
    }
} catch (\Throwable $e) {}

$infoComp1 = $idComp1 > 0 ? $competicaoObj->readInfo($idComp1) : null;
$infoComp2 = $idComp2 > 0 ? $competicaoObj->readInfo($idComp2) : null;

// Função auxiliar para processar uma competição
function processarDadosCompeticao($compId, $db, $clubesPortal, $infoComp) {
    if ($compId <= 0 || !$infoComp) {
        return null;
    }

    $nomeComp = $infoComp['nome'] ?? '';
    $anoComp = $infoComp['ano'] ?? '';

    $db3File = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $compId . "-database.db3";
    $cdb = null;
    if (file_exists($db3File)) {
        $sDatabase = new SQLiteDatabase();
        $sDatabase->fileName = $db3File;
        $cdb = $sDatabase->getConnection();
    }

    // Carregar clubes do SQLite local
    $clubesLocal = $clubesPortal;
    if ($cdb) {
        try {
            $stmtC = $cdb->query("SELECT ID, Nome, TresLetras, Escudo FROM clube");
            if ($stmtC) {
                while ($rc = $stmtC->fetch(PDO::FETCH_ASSOC)) {
                    $cid = (int)$rc['ID'];
                    if (!isset($clubesLocal[$cid])) {
                        if (!empty($rc['Escudo'])) {
                            $rc['Escudo'] = basename($rc['Escudo']);
                        }
                        $clubesLocal[$cid] = $rc;
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    // Carregar jogadores do SQLite local
    $jogadoresMap = [];
    $jogadorClubeMap = [];
    if ($cdb) {
        try {
            $stmtJ = $cdb->query("SELECT ID, Nome, Nivel FROM jogador");
            if ($stmtJ) {
                while ($rj = $stmtJ->fetch(PDO::FETCH_ASSOC)) {
                    $jogadoresMap[(int)$rj['ID']] = $rj;
                }
            }
            $stmtEl = $cdb->query("SELECT * FROM elenco");
            if ($stmtEl) {
                while ($rel = $stmtEl->fetch(PDO::FETCH_ASSOC)) {
                    $clubeId = (int)$rel['Clube'];
                    for ($i = 1; $i <= 23; $i++) {
                        $pid = (int)($rel['Jogador' . $i] ?? 0);
                        if ($pid > 0) {
                            $jogadorClubeMap[$pid] = $clubeId;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    // Carregar jogos encerrados
    $stmtJogos = $db->prepare("SELECT id, timeA_id, timeA_nome, timeA_gols, timeB_id, timeB_nome, timeB_gols, timeA_penaltis, timeB_penaltis, data, status, fase, grupo, path 
                               FROM jogos_clube 
                               WHERE competicao_id = :idComp AND simulador_interno = 1 
                               ORDER BY data ASC, id ASC");
    $stmtJogos->execute([':idComp' => $compId]);
    $jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

    // Mapeamento dos jogos por path
    $jogosPorPath = [];
    foreach ($jogos as $j) {
        $pBasename = !empty($j['path']) ? basename($j['path']) : '';
        if ($pBasename !== '') {
            $jogosPorPath[$pBasename] = $j;
        }
    }

    // Tabela de clubes da competição
    $tabela = [];

    foreach ($jogos as $j) {
        $dataJogo = !empty($j['data']) ? strtotime($j['data']) : time();
        $temPen = ($j['timeA_penaltis'] !== null && $j['timeA_penaltis'] !== '');
        $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
        $jaTerminou = (time() >= ($dataJogo + $duracaoSegundos));

        if ($j['status'] == 1 && $jaTerminou) {
            $idA = (int)$j['timeA_id'];
            $idB = (int)$j['timeB_id'];
            $golsA = (int)$j['timeA_gols'];
            $golsB = (int)$j['timeB_gols'];

            // Contabilizar fase de pontos corridos / grupos
            if ($j['fase'] == 2) {
                foreach ([$idA, $idB] as $idC) {
                    if (!isset($tabela[$idC])) {
                        $c = $clubesLocal[$idC] ?? ['Nome' => 'Time ' . $idC, 'Escudo' => '0.png', 'TresLetras' => ''];
                        $tabela[$idC] = [
                            'id' => $idC,
                            'nome' => $c['Nome'],
                            'escudo' => $c['Escudo'] ?: '0.png',
                            'sigla' => $c['TresLetras'] ?? '',
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

                $tabela[$idA]['jogos']++;
                $tabela[$idB]['jogos']++;
                $tabela[$idA]['gp'] += $golsA;
                $tabela[$idB]['gp'] += $golsB;
                $tabela[$idA]['gc'] += $golsB;
                $tabela[$idB]['gc'] += $golsA;

                if ($golsA > $golsB) {
                    $tabela[$idA]['pontos'] += 3;
                    $tabela[$idA]['vitorias']++;
                    $tabela[$idB]['derrotas']++;
                } elseif ($golsB > $golsA) {
                    $tabela[$idB]['pontos'] += 3;
                    $tabela[$idB]['vitorias']++;
                    $tabela[$idA]['derrotas']++;
                } else {
                    $tabela[$idA]['pontos'] += 1;
                    $tabela[$idB]['pontos'] += 1;
                    $tabela[$idA]['empates']++;
                    $tabela[$idB]['empates']++;
                }
            }
        }
    }

    foreach ($tabela as $idC => $row) {
        $tabela[$idC]['sg'] = $tabela[$idC]['gp'] - $tabela[$idC]['gc'];
    }

    // Processar súmulas .hyj de jogadores
    $playerStats = [];
    $nomeComposto = $anoComp . " - " . $nomeComp;
    $dirPartidas = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada";

    if (is_dir($dirPartidas)) {
        $files = scandir($dirPartidas);
        foreach ($files as $f) {
            if (pathinfo($f, PATHINFO_EXTENSION) === 'hyj') {
                $baseFilename = pathinfo($f, PATHINFO_FILENAME);
                $jogoCorrespondente = $jogosPorPath[$baseFilename] ?? null;

                if ($jogoCorrespondente) {
                    $dataJogo = !empty($jogoCorrespondente['data']) ? strtotime($jogoCorrespondente['data']) : time();
                    $temPen = ($jogoCorrespondente['timeA_penaltis'] !== null && $jogoCorrespondente['timeA_penaltis'] !== '');
                    $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
                    $jaTerminou = (time() >= ($dataJogo + $duracaoSegundos));
                    if ((int)$jogoCorrespondente['status'] !== 1 || !$jaTerminou) {
                        continue;
                    }
                }

                $content = @file_get_contents($dirPartidas . "/" . $f);
                if ($content !== false) {
                    $json = json_decode($content);
                    if ($json) {
                        foreach (['time1', 'time2'] as $tKey) {
                            if (isset($json->$tKey->jogadores)) {
                                foreach ($json->$tKey->jogadores as $pj) {
                                    $pid = (int)$pj->idJogador;
                                    if ($pid > 0) {
                                        if (!isset($playerStats[$pid])) {
                                            $playerStats[$pid] = [
                                                'id' => $pid,
                                                'nome' => $pj->nomeJogador ?? ($jogadoresMap[$pid]['Nome'] ?? 'Jogador ' . $pid),
                                                'clubeId' => $jogadorClubeMap[$pid] ?? 0,
                                                'gols' => 0,
                                                'assistencias' => 0,
                                                'amarelos' => 0,
                                                'vermelhos' => 0,
                                                'partidas' => 0,
                                                'soma_notas' => 0,
                                                'jogos_com_nota' => 0
                                            ];
                                        }
                                        $playerStats[$pid]['gols'] += (int)($pj->gols ?? 0);
                                        $playerStats[$pid]['assistencias'] += (int)($pj->assistencias ?? 0);
                                        $playerStats[$pid]['amarelos'] += (int)($pj->amarelos ?? 0);
                                        $playerStats[$pid]['vermelhos'] += (int)($pj->vermelhos ?? 0);
                                        if ((int)($pj->minutos ?? 0) > 0) {
                                            $playerStats[$pid]['partidas']++;
                                        }
                                        if (isset($pj->nota) && is_numeric($pj->nota) && floatval($pj->nota) > 0) {
                                            $playerStats[$pid]['soma_notas'] += floatval($pj->nota);
                                            $playerStats[$pid]['jogos_com_nota']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return [
        'info' => $infoComp,
        'clubes' => $clubesLocal,
        'jogadoresMap' => $jogadoresMap,
        'tabela' => $tabela,
        'playerStats' => $playerStats
    ];
}

$dadosComp1 = processarDadosCompeticao($idComp1, $db, $clubesPortal, $infoComp1);
$dadosComp2 = processarDadosCompeticao($idComp2, $db, $clubesPortal, $infoComp2);

// Consolidação Acumulada
$tabelaAcumulada = [];
$playersAcumulados = [];

if ($dadosComp1 || $dadosComp2) {
    $listaFontes = array_filter([$dadosComp1, $dadosComp2]);

    // 1. Consolidar Tabela
    foreach ($listaFontes as $fonte) {
        foreach ($fonte['tabela'] as $idC => $row) {
            if (!isset($tabelaAcumulada[$idC])) {
                $tabelaAcumulada[$idC] = [
                    'id' => $idC,
                    'nome' => $row['nome'],
                    'escudo' => $row['escudo'],
                    'sigla' => $row['sigla'],
                    'jogos' => 0,
                    'pontos' => 0,
                    'vitorias' => 0,
                    'empates' => 0,
                    'derrotas' => 0,
                    'gp' => 0,
                    'gc' => 0,
                    'sg' => 0,
                    'comp1_pontos' => 0,
                    'comp1_jogos' => 0,
                    'comp2_pontos' => 0,
                    'comp2_jogos' => 0
                ];
            }

            $tabelaAcumulada[$idC]['jogos'] += $row['jogos'];
            $tabelaAcumulada[$idC]['pontos'] += $row['pontos'];
            $tabelaAcumulada[$idC]['vitorias'] += $row['vitorias'];
            $tabelaAcumulada[$idC]['empates'] += $row['empates'];
            $tabelaAcumulada[$idC]['derrotas'] += $row['derrotas'];
            $tabelaAcumulada[$idC]['gp'] += $row['gp'];
            $tabelaAcumulada[$idC]['gc'] += $row['gc'];
            $tabelaAcumulada[$idC]['sg'] += $row['sg'];

            if ($fonte === $dadosComp1) {
                $tabelaAcumulada[$idC]['comp1_pontos'] = $row['pontos'];
                $tabelaAcumulada[$idC]['comp1_jogos'] = $row['jogos'];
            } else if ($fonte === $dadosComp2) {
                $tabelaAcumulada[$idC]['comp2_pontos'] = $row['pontos'];
                $tabelaAcumulada[$idC]['comp2_jogos'] = $row['jogos'];
            }
        }
    }

    // Ordenar tabela acumulada: Pontos > Vitórias > Saldo de Gols > Gols Pró
    usort($tabelaAcumulada, function($a, $b) {
        if ($a['pontos'] != $b['pontos']) return $b['pontos'] - $a['pontos'];
        if ($a['vitorias'] != $b['vitorias']) return $b['vitorias'] - $a['vitorias'];
        if ($a['sg'] != $b['sg']) return $b['sg'] - $a['sg'];
        return $b['gp'] - $a['gp'];
    });

    // 2. Consolidar Jogadores
    foreach ($listaFontes as $fonte) {
        foreach ($fonte['playerStats'] as $pid => $pStats) {
            $clubeId = $pStats['clubeId'];
            $clubeInfo = $fonte['clubes'][$clubeId] ?? ($clubesPortal[$clubeId] ?? null);

            if (!isset($playersAcumulados[$pid])) {
                $playersAcumulados[$pid] = [
                    'id' => $pid,
                    'nome' => $pStats['nome'],
                    'clube' => $clubeInfo['Nome'] ?? 'Sem clube',
                    'escudo' => $clubeInfo['Escudo'] ?? '0.png',
                    'gols' => 0,
                    'assistencias' => 0,
                    'amarelos' => 0,
                    'vermelhos' => 0,
                    'partidas' => 0,
                    'soma_notas' => 0,
                    'jogos_com_nota' => 0,
                    'nota_media' => 0.0
                ];
            }

            $playersAcumulados[$pid]['gols'] += $pStats['gols'];
            $playersAcumulados[$pid]['assistencias'] += $pStats['assistencias'];
            $playersAcumulados[$pid]['amarelos'] += $pStats['amarelos'];
            $playersAcumulados[$pid]['vermelhos'] += $pStats['vermelhos'];
            $playersAcumulados[$pid]['partidas'] += $pStats['partidas'];
            $playersAcumulados[$pid]['soma_notas'] += $pStats['soma_notas'];
            $playersAcumulados[$pid]['jogos_com_nota'] += $pStats['jogos_com_nota'];
        }
    }

    // Calcular notas médias consolidadas
    foreach ($playersAcumulados as $pid => $pData) {
        if ($pData['jogos_com_nota'] > 0) {
            $playersAcumulados[$pid]['nota_media'] = round($pData['soma_notas'] / $pData['jogos_com_nota'], 2);
        }
    }
}

// Rankings individuais agregados
$rankingGols = array_filter($playersAcumulados, function($p) { return $p['gols'] > 0; });
usort($rankingGols, function($a, $b) { return $b['gols'] - $a['gols']; });
$rankingGols = array_slice($rankingGols, 0, 15);

$rankingAssists = array_filter($playersAcumulados, function($p) { return $p['assistencias'] > 0; });
usort($rankingAssists, function($a, $b) { return $b['assistencias'] - $a['assistencias']; });
$rankingAssists = array_slice($rankingAssists, 0, 15);

$rankingCartoes = array_filter($playersAcumulados, function($p) { return $p['amarelos'] > 0 || $p['vermelhos'] > 0; });
usort($rankingCartoes, function($a, $b) {
    if ($a['vermelhos'] != $b['vermelhos']) return $b['vermelhos'] - $a['vermelhos'];
    return $b['amarelos'] - $a['amarelos'];
});
$rankingCartoes = array_slice($rankingCartoes, 0, 15);

$rankingNotas = array_filter($playersAcumulados, function($p) { return $p['jogos_com_nota'] >= 2 && $p['nota_media'] > 0; });
usort($rankingNotas, function($a, $b) {
    if ($b['nota_media'] == $a['nota_media']) return $b['partidas'] - $a['partidas'];
    return ($b['nota_media'] > $a['nota_media']) ? 1 : -1;
});
$rankingNotas = array_slice($rankingNotas, 0, 15);

$page_title = "Soma de Tabelas & Dashboard Agregado (Apertura / Clausura)";
$css_filename = "home_redesign";
$aux_css = "estatisticas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="header-actions-container">
            <h2 class="propostas-title">
                <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">calculate</span>
                Soma de Tabelas & Dashboard Agregado
            </h2>
            <div class="header-buttons-wrapper">
                <?php if ($idComp1 > 0 && $idComp2 > 0): ?>
                    <button type="button" class="btn-action-primary" id="btn-salvar-visao-agrupada" style="background: linear-gradient(135deg, #6366f1, #0284c7); box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);" title="Salvar esta visão agregada para aparecer fixada na sua lista de competições">
                        <span class="material-symbols-outlined">bookmark_add</span>
                        <span>Salvar Visão Agrupada</span>
                    </button>
                <?php endif; ?>
                <a href="/competicoes/index.php" class="btn-action-primary" style="background:#475569;" title="Voltar ao índice de competições">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span>Competições</span>
                </a>
            </div>
        </div>

        <!-- Seletor de Competições -->
        <div style="background: rgba(2, 132, 199, 0.04); border: 1px solid rgba(2, 132, 199, 0.15); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;">
            <form method="GET" action="soma_tabelas.php" id="form-soma-tabelas" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 260px;">
                    <label for="select-comp1" style="display: block; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 0.9rem; color: #0369a1; margin-bottom: 6px;">
                        Competição 1 (ex: Torneo Apertura / 1º Turno)
                    </label>
                    <select name="comp1" id="select-comp1" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-family: 'Montserrat', sans-serif; font-size: 0.9rem; color: #0f172a;" onchange="this.form.submit()">
                        <option value="0">-- Selecione a primeira competição --</option>
                        <?php foreach ($todasCompeticoes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($idComp1 == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome'] . ' (' . $c['ano'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; justify-content: center; padding-bottom: 10px;">
                    <span class="material-symbols-outlined" style="font-size: 1.8rem; color: #0284c7;">add_circle</span>
                </div>

                <div style="flex: 1; min-width: 260px;">
                    <label for="select-comp2" style="display: block; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 0.9rem; color: #0369a1; margin-bottom: 6px;">
                        Competição 2 (ex: Torneo Clausura / 2º Turno)
                    </label>
                    <select name="comp2" id="select-comp2" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-family: 'Montserrat', sans-serif; font-size: 0.9rem; color: #0f172a;" onchange="this.form.submit()">
                        <option value="0">-- Selecione a segunda competição --</option>
                        <?php foreach ($todasCompeticoes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($idComp2 == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome'] . ' (' . $c['ano'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-action-primary" style="height: 42px; padding: 0 20px; font-size: 0.95rem;">
                        <span class="material-symbols-outlined">sync</span>
                        <span>Somar Tabelas</span>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($idComp1 <= 0 && $idComp2 <= 0): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 3.5rem; color: #cbd5e1; margin-bottom: 10px; display: block;">sports_score</span>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; margin-bottom: 6px; color: #334155;">Selecione duas competições acima</h3>
                <p style="font-size: 0.9rem; max-width: 500px; margin: 0 auto;">Escolha os torneios (como Apertura e Clausura) para consolidar a tabela geral anual, artilharia unificada e notas médias dos elencos.</p>
            </div>
        <?php else: ?>

            <!-- Abas de Navegação -->
            <div class="stats-tabs">
                <button class="stats-tab-btn active" data-tab="tab-acumulada">
                    <span class="material-symbols-outlined">table_chart</span> Tabela Acumulada
                </button>
                <button class="stats-tab-btn" data-tab="tab-artilharia">
                    <span class="material-symbols-outlined">workspace_premium</span> Artilharia & Gols
                </button>
                <button class="stats-tab-btn" data-tab="tab-assistencias">
                    <span class="material-symbols-outlined">volunteer_activism</span> Assistências
                </button>
                <button class="stats-tab-btn" data-tab="tab-notas">
                    <span class="material-symbols-outlined">star</span> Melhores Médias
                </button>
                <button class="stats-tab-btn" data-tab="tab-disciplina">
                    <span class="material-symbols-outlined">warning</span> Disciplina (Cartões)
                </button>
            </div>

            <!-- Aba 1: Tabela Acumulada -->
            <div class="tab-content active" id="tab-acumulada">
                <?php if (empty($tabelaAcumulada)): ?>
                    <p style="text-align: center; padding: 2rem; color: #64748b;">Nenhuma partida de pontos corridos / fase de grupos concluída nas competições selecionadas.</p>
                <?php else: ?>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Clube</th>
                                    <th>P</th>
                                    <th>J</th>
                                    <th>V</th>
                                    <th>E</th>
                                    <th>D</th>
                                    <th>GP</th>
                                    <th>GC</th>
                                    <th>SG</th>
                                    <th>% Apr</th>
                                    <?php if ($infoComp1): ?>
                                        <th style="background: rgba(2, 132, 199, 0.08); color: #0284c7;" title="Pontos no 1º Torneio">P (<?php echo htmlspecialchars($infoComp1['nome']); ?>)</th>
                                    <?php endif; ?>
                                    <?php if ($infoComp2): ?>
                                        <th style="background: rgba(16, 185, 129, 0.08); color: #059669;" title="Pontos no 2º Torneio">P (<?php echo htmlspecialchars($infoComp2['nome']); ?>)</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $pos = 1;
                                $tot = count($tabelaAcumulada);
                                foreach ($tabelaAcumulada as $team):
                                    $aprov = ($team['jogos'] > 0) ? round(($team['pontos'] / ($team['jogos'] * 3)) * 100, 1) : 0;
                                    $posClass = '';
                                    if ($pos <= 4) $posClass = 'zone-g4';
                                    elseif ($pos > $tot - 4 && $tot > 8) $posClass = 'zone-relegation';
                                ?>
                                    <tr>
                                        <td class="pos-col <?php echo $posClass; ?>"><?php echo $pos++; ?>º</td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $team['escudo'] ? $team['escudo'] : '0.png'; ?>" alt="" />
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
                                        <td style="font-weight: 600; color: #475569;"><?php echo $aprov; ?>%</td>
                                        <?php if ($infoComp1): ?>
                                            <td style="font-weight: 600; color: #0284c7; background: rgba(2, 132, 199, 0.03);"><?php echo $team['comp1_pontos']; ?></td>
                                        <?php endif; ?>
                                        <?php if ($infoComp2): ?>
                                            <td style="font-weight: 600; color: #059669; background: rgba(16, 185, 129, 0.03);"><?php echo $team['comp2_pontos']; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Aba 2: Artilharia Acumulada -->
            <div class="tab-content" id="tab-artilharia">
                <div class="stats-subcard" style="max-width: 850px; margin: 0 auto;">
                    <h3 class="stats-subcard-title">
                        <span class="material-symbols-outlined" style="color: #fbbf24;">workspace_premium</span>
                        Artilharia Geral Consolidada
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Jogador</th>
                                    <th style="text-align: left;">Clube</th>
                                    <th>Partidas</th>
                                    <th>Gols</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rankingGols)): ?>
                                    <tr><td colspan="5">Nenhum gol registrado nas competições.</td></tr>
                                <?php else: $pPos = 1; foreach ($rankingGols as $art): ?>
                                    <tr>
                                        <td class="pos-col"><?php echo $pPos++; ?>º</td>
                                        <td class="player-col" style="text-align: left; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($art['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $art['escudo'] ? $art['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($art['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $art['partidas']; ?></td>
                                        <td><span class="stats-count-badge"><?php echo $art['gols']; ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Aba 3: Assistências Acumuladas -->
            <div class="tab-content" id="tab-assistencias">
                <div class="stats-subcard" style="max-width: 850px; margin: 0 auto;">
                    <h3 class="stats-subcard-title">
                        <span class="material-symbols-outlined" style="color: #34d399;">volunteer_activism</span>
                        Líderes de Assistências Consolidados
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Jogador</th>
                                    <th style="text-align: left;">Clube</th>
                                    <th>Partidas</th>
                                    <th>Assistências</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rankingAssists)): ?>
                                    <tr><td colspan="5">Nenhuma assistência registrada nas competições.</td></tr>
                                <?php else: $pPos = 1; foreach ($rankingAssists as $ass): ?>
                                    <tr>
                                        <td class="pos-col"><?php echo $pPos++; ?>º</td>
                                        <td class="player-col" style="text-align: left; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($ass['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $ass['escudo'] ? $ass['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($ass['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $ass['partidas']; ?></td>
                                        <td><span class="stats-count-badge" style="background: #34d399;"><?php echo $ass['assistencias']; ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Aba 4: Melhores Médias / Notas -->
            <div class="tab-content" id="tab-notas">
                <div class="stats-subcard" style="max-width: 850px; margin: 0 auto;">
                    <h3 class="stats-subcard-title">
                        <span class="material-symbols-outlined" style="color: #0284c7;">star</span>
                        Melhores Médias de Atuação (Mínimo de 2 jogos avaliados)
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Jogador</th>
                                    <th style="text-align: left;">Clube</th>
                                    <th>Jogos Avaliados</th>
                                    <th>Nota Média</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rankingNotas)): ?>
                                    <tr><td colspan="5">Nenhuma nota registrada ou número de partidas insuficiente.</td></tr>
                                <?php else: $pPos = 1; foreach ($rankingNotas as $not): ?>
                                    <tr>
                                        <td class="pos-col"><?php echo $pPos++; ?>º</td>
                                        <td class="player-col" style="text-align: left; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($not['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $not['escudo'] ? $not['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($not['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $not['jogos_com_nota']; ?></td>
                                        <td>
                                            <span class="stats-count-badge" style="background: #0284c7; font-size: 0.95rem; font-weight: 700;">
                                                <?php echo number_format($not['nota_media'], 2, ',', '.'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Aba 5: Disciplina / Cartões -->
            <div class="tab-content" id="tab-disciplina">
                <div class="stats-subcard" style="max-width: 850px; margin: 0 auto;">
                    <h3 class="stats-subcard-title" style="color: #ef4444;">
                        <span class="material-symbols-outlined">warning</span>
                        Cartões Acumulados
                    </h3>
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th class="pos-col">#</th>
                                    <th style="text-align: left;">Jogador</th>
                                    <th style="text-align: left;">Clube</th>
                                    <th>Amarelos</th>
                                    <th>Vermelhos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rankingCartoes)): ?>
                                    <tr><td colspan="5">Nenhum cartão registrado nas competições.</td></tr>
                                <?php else: $pPos = 1; foreach ($rankingCartoes as $cart): ?>
                                    <tr>
                                        <td class="pos-col"><?php echo $pPos++; ?>º</td>
                                        <td class="player-col" style="text-align: left; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($cart['nome']); ?></td>
                                        <td class="team-col">
                                            <div class="team-cell">
                                                <img class="team-logo" src="/images/escudos/<?php echo $cart['escudo'] ? $cart['escudo'] : '0.png'; ?>" alt="" />
                                                <span><?php echo htmlspecialchars($cart['clube']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 3px; font-weight: 600; color: #d97706;">
                                                <span style="display: inline-block; width: 10px; height: 14px; background: #fbbf24; border-radius: 2px;"></span>
                                                <?php echo $cart['amarelos']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 3px; font-weight: 600; color: #dc2626;">
                                                <span style="display: inline-block; width: 10px; height: 14px; background: #ef4444; border-radius: 2px;"></span>
                                                <?php echo $cart['vermelhos']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>

    <!-- Modal Salvar Visão Agrupada -->
    <div id="modalSalvarVisao" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(9, 13, 22, 0.6); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center;">
        <div style="position:relative; top:50%; transform:translateY(-50%); margin:0 auto; background:rgba(255, 255, 255, 0.98); border-radius:18px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 20px 40px rgba(0,0,0,0.25); max-width:460px; width:92%; padding:25px; box-sizing:border-box;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid rgba(0,0,0,0.06); padding-bottom:10px;">
                <h3 style="margin:0; font-family:'Outfit', sans-serif; font-size:1.2rem; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="color:#6366f1;">bookmark_add</span>
                    Salvar Visão Agrupada
                </h3>
                <span id="btn-fechar-modal-salvar" style="cursor:pointer; color:#64748b; font-size:1.4rem;" class="material-symbols-outlined">close</span>
            </div>

            <p style="font-size:0.86rem; color:#64748b; margin-top:0; margin-bottom:15px; line-height:1.4;">
                Ao salvar, esta visão agrupada aparecerá em destaque na sua lista de competições para você acessar diretamente a qualquer momento.
            </p>

            <div style="margin-bottom:20px;">
                <label for="input-nome-visao" style="display:block; font-size:0.84rem; font-weight:600; color:#1e293b; margin-bottom:6px;">
                    Nome da Visão Agrupada:
                </label>
                <?php
                $sugestaoNome = ($infoComp1 && $infoComp2) 
                    ? "{$infoComp1['nome']} + {$infoComp2['nome']} ({$infoComp1['ano']})" 
                    : "Tabela Acumulada Anual";
                ?>
                <input type="text" id="input-nome-visao" value="<?php echo htmlspecialchars($sugestaoNome); ?>" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Montserrat', sans-serif; font-size:0.9rem; color:#0f172a; box-sizing:border-box;"/>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" id="btn-cancelar-salvar-visao" style="padding:8px 16px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; font-weight:600; font-size:0.85rem; color:#475569; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-salvar-visao" style="padding:8px 20px; background:linear-gradient(135deg, #6366f1, #0284c7); border:none; border-radius:8px; font-weight:600; font-size:0.85rem; color:#fff; cursor:pointer; display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:1.1rem;">save</span>
                    Salvar na Lista
                </button>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gerenciador de Abas
    const tabs = document.querySelectorAll('.stats-tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            const target = this.getAttribute('data-tab');
            const targetEl = document.getElementById(target);
            if (targetEl) {
                targetEl.classList.add('active');
            }
        });
    });

    // Salvar Visão Agrupada
    const btnSalvar = document.getElementById('btn-salvar-visao-agrupada');
    const modalSalvar = document.getElementById('modalSalvarVisao');
    const btnFecharModal = document.getElementById('btn-fechar-modal-salvar');
    const btnCancelarModal = document.getElementById('btn-cancelar-salvar-visao');
    const btnConfirmarSalvar = document.getElementById('btn-confirmar-salvar-visao');

    if (btnSalvar && modalSalvar) {
        btnSalvar.addEventListener('click', function() {
            modalSalvar.style.display = 'flex';
        });

        [btnFecharModal, btnCancelarModal].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', function() {
                    modalSalvar.style.display = 'none';
                });
            }
        });

        if (btnConfirmarSalvar) {
            btnConfirmarSalvar.addEventListener('click', function() {
                const nomeVisao = document.getElementById('input-nome-visao').value.trim();
                const comp1 = <?php echo (int)$idComp1; ?>;
                const comp2 = <?php echo (int)$idComp2; ?>;

                if (!nomeVisao) {
                    alert('Por favor, informe um nome para a visão agrupada.');
                    return;
                }

                btnConfirmarSalvar.disabled = true;
                btnConfirmarSalvar.innerText = 'Salvando...';

                $.ajax({
                    url: 'salvar_visao_agrupada.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        comp1: comp1,
                        comp2: comp2,
                        nome: nomeVisao
                    }
                }).done(function(res) {
                    modalSalvar.style.display = 'none';
                    btnConfirmarSalvar.disabled = false;
                    btnConfirmarSalvar.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.1rem;">save</span> Salvar na Lista';
                    if (res.success) {
                        alert(res.message);
                    } else {
                        alert('Erro ao salvar: ' + (res.error || 'Erro desconhecido.'));
                    }
                }).fail(function(xhr, status, error) {
                    btnConfirmarSalvar.disabled = false;
                    btnConfirmarSalvar.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.1rem;">save</span> Salvar na Lista';
                    alert('Erro de comunicação: ' + error);
                });
            });
        }
    }
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
