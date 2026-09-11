<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die("Acesso negado.");
}

$idCompeticao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$idCompeticao) {
    die("ID da competição não fornecido.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simplexlsx/SimpleXLSXGen.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    die("Competição não encontrada.");
}

// Check permission
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die("Você não tem permissão para exportar esta planilha.");
}

$nome_competicao = $compInfo['nome'] ?? 'Competicao';
$ano_competicao = $compInfo['ano'] ?? date('Y');

// Conectar ao SQLite da competição
$compDatabase = new SQLiteDatabase();
$compDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
$cdb = $compDatabase->getConnection();

// Carregar Clubes
$clubes = [];
if ($cdb) {
    try {
        $stmtClubes = $cdb->query("SELECT ID, Nome, TresLetras FROM clube");
        if ($stmtClubes) {
            while ($row = $stmtClubes->fetch(PDO::FETCH_ASSOC)) {
                $clubes[(int)$row['ID']] = $row;
            }
        }
    } catch (\Throwable $e) {}
}

try {
    $stmtPortalTimes = $db->query("SELECT id, nome as Nome, sigla as TresLetras FROM time");
    if ($stmtPortalTimes) {
        while ($pTime = $stmtPortalTimes->fetch(PDO::FETCH_ASSOC)) {
            $clubes[(int)$pTime['id']] = $pTime;
        }
    }
} catch (\Throwable $e) {}

// Carregar Jogadores
$jogadoresMap = [];
if ($cdb) {
    try {
        $stmtJogadores = $cdb->query("SELECT ID, Nome, Nivel FROM jogador");
        if ($stmtJogadores) {
            while ($row = $stmtJogadores->fetch(PDO::FETCH_ASSOC)) {
                $jogadoresMap[(int)$row['ID']] = $row;
            }
        }
    } catch (\Throwable $e) {}
}

// Mapear Jogador -> Clube
$jogadorClubeMap = [];
if ($cdb) {
    try {
        $stmtElenco = $cdb->query("SELECT * FROM elenco");
        if ($stmtElenco) {
            while ($row = $stmtElenco->fetch(PDO::FETCH_ASSOC)) {
                $clubeId = (int)$row['Clube'];
                for ($i = 1; $i <= 23; $i++) {
                    $pId = (int)($row['Jogador' . $i] ?? 0);
                    if ($pId > 0) {
                        $jogadorClubeMap[$pId] = $clubeId;
                    }
                }
            }
        }
    } catch (\Throwable $e) {}
}

// Carregar Estádios e Árbitros
$estadiosMap = [];
if ($cdb) {
    try {
        $stmtEst = $cdb->query("SELECT ID, Nome FROM estadio");
        if ($stmtEst) {
            while ($r = $stmtEst->fetch(PDO::FETCH_ASSOC)) {
                $estadiosMap[(int)$r['ID']] = $r['Nome'];
            }
        }
    } catch (\Throwable $e) {}
}
try {
    $stmtEstP = $db->query("SELECT id, Nome FROM estadio");
    if ($stmtEstP) {
        while ($r = $stmtEstP->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($estadiosMap[(int)$r['id']])) {
                $estadiosMap[(int)$r['id']] = $r['Nome'];
            }
        }
    }
} catch (\Throwable $e) {}

$arbitrosMap = [];
if ($cdb) {
    try {
        $stmtArb = $cdb->query("SELECT ID, Arbitro FROM trioarbitragem");
        if ($stmtArb) {
            while ($r = $stmtArb->fetch(PDO::FETCH_ASSOC)) {
                $arbitrosMap[(int)$r['ID']] = $r['Arbitro'];
            }
        }
    } catch (\Throwable $e) {}
}
try {
    $stmtArbP = $db->query("SELECT id, nomeArbitro FROM arbitros");
    if ($stmtArbP) {
        while ($r = $stmtArbP->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($arbitrosMap[(int)$r['id']])) {
                $arbitrosMap[(int)$r['id']] = $r['nomeArbitro'];
            }
        }
    }
} catch (\Throwable $e) {}

$fasesMap = [];
try {
    $stmtF = $competicaoObj->lerFases();
    if ($stmtF) {
        while ($r = $stmtF->fetch(PDO::FETCH_ASSOC)) {
            $fasesMap[(int)$r['id']] = $r['nome'];
        }
    }
} catch (\Throwable $e) {}

// Carregar todos os jogos da competição
$stmtJogos = $db->prepare("SELECT id, timeA_id, timeA_nome, timeA_gols, timeB_id, timeB_nome, timeB_gols, timeA_penaltis, timeB_penaltis, data, status, fase, grupo, estadio_id, arbitro_id, neutro, path 
                           FROM jogos_clube 
                           WHERE competicao_id = :idComp 
                             AND simulador_interno = 1
                           ORDER BY fase ASC, data ASC, id ASC");
$stmtJogos->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
$stmtJogos->execute();
$jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

// Carregar slots atribuídos
$assignedSlotTeams = [];
$stmtTimesSlots = $competicaoObj->carregarListaTimes($idCompeticao);
while ($rSlot = $stmtTimesSlots->fetch(PDO::FETCH_ASSOC)) {
    $sName = !empty($rSlot['slot']) ? $rSlot['slot'] : ("Slot " . $rSlot['codigo_time']);
    if (!empty($rSlot['id_time_portal']) && intval($rSlot['id_time_portal']) > 0) {
        $assignedSlotTeams[$sName] = intval($rSlot['id_time_portal']);
    } else if ($rSlot['has_team'] == 1 || $rSlot['has_team'] == '1') {
        $assignedSlotTeams[$sName] = -1 * abs(intval($rSlot['codigo_time']));
    }
}

// 1. Processar Classificação
$gruposDetectados = [];
$clubeGrupoMap = [];
foreach ($jogos as $j) {
    if ($j['fase'] == 2) {
        $g = trim($j['grupo'] ?? '');
        if ($g !== '') {
            if (!in_array($g, $gruposDetectados)) {
                $gruposDetectados[] = $g;
            }
            if (!empty($j['timeA_id']) && !isset($clubeGrupoMap[$j['timeA_id']])) $clubeGrupoMap[$j['timeA_id']] = $g;
            if (!empty($j['timeB_id']) && !isset($clubeGrupoMap[$j['timeB_id']])) $clubeGrupoMap[$j['timeB_id']] = $g;
        }
    }
}
sort($gruposDetectados);
$temGrupos = !empty($gruposDetectados);

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
} else {
    $tabelaPorGrupo['Geral'] = [];
    $timesParticipantesIds = [];
    foreach ($assignedSlotTeams as $sName => $cIdPortal) {
        if ($cIdPortal > 0) $timesParticipantesIds[$cIdPortal] = true;
    }
    foreach ($jogos as $j) {
        if ($j['fase'] == 2) {
            if (!empty($j['timeA_id']) && (int)$j['timeA_id'] > 0) $timesParticipantesIds[(int)$j['timeA_id']] = true;
            if (!empty($j['timeB_id']) && (int)$j['timeB_id'] > 0) $timesParticipantesIds[(int)$j['timeB_id']] = true;
        }
    }
    if (!empty($timesParticipantesIds)) {
        foreach (array_keys($timesParticipantesIds) as $idC) {
            if (isset($clubes[$idC])) {
                $c = $clubes[$idC];
                $tabelaPorGrupo['Geral'][$idC] = [
                    'id' => $idC,
                    'nome' => $c['Nome'],
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
    } else {
        foreach ($clubes as $idC => $c) {
            $tabelaPorGrupo['Geral'][$idC] = [
                'id' => $idC,
                'nome' => $c['Nome'],
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
}

foreach ($jogos as $j) {
    $dataJogo = !empty($j['data']) ? strtotime($j['data']) : time();
    $temPen = ($j['timeA_penaltis'] !== null && $j['timeA_penaltis'] !== '');
    $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
    $jaTerminou = (time() >= ($dataJogo + $duracaoSegundos));

    if ($j['status'] == 1 && $jaTerminou && $j['fase'] == 2) {
        $idA = (int)$j['timeA_id'];
        $idB = (int)$j['timeB_id'];
        $golsA = (int)$j['timeA_gols'];
        $golsB = (int)$j['timeB_gols'];
        
        $g = trim($j['grupo'] ?? '');
        if ($temGrupos) {
            if ($g === '' || !isset($tabelaPorGrupo[$g])) {
                $g = $clubeGrupoMap[$idA] ?? ($clubeGrupoMap[$idB] ?? '');
            }
        } else {
            $g = 'Geral';
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

// 2. Processar Estatísticas de Jogadores via arquivos .hyj
$playerStats = [];
$nomeComposto = $ano_competicao . " - " . $nome_competicao;
$dirPartidas = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada";

$jogosPorPath = [];
foreach ($jogos as $j) {
    $pBasename = !empty($j['path']) ? basename($j['path']) : '';
    if ($pBasename !== '') {
        $jogosPorPath[$pBasename] = $j;
    }
}

if (is_dir($dirPartidas)) {
    $files = scandir($dirPartidas);
    foreach ($files as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'hyj') {
            $baseFilename = pathinfo($f, PATHINFO_FILENAME);
            $jogoCorrespondente = null;
            if (isset($jogosPorPath[$baseFilename])) {
                $jogoCorrespondente = $jogosPorPath[$baseFilename];
            } else {
                foreach ($jogos as $j) {
                    if (!empty($j['path']) && strpos($j['path'], $baseFilename) !== false) {
                        $jogoCorrespondente = $j;
                        break;
                    }
                }
            }

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
                                if ((int)$pj->minutos > 0) $playerStats[$pid]['partidas']++;
                            }
                        }
                    }
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
                                if ((int)$pj->minutos > 0) $playerStats[$pid]['partidas']++;
                            }
                        }
                    }
                }
            }
        }
    }
}

// 3. Consultar DM e Suspensões
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
                    'tipo' => 'Departamento Médico',
                    'retorno' => date('d/m/Y', strtotime($l['lesionado_ate']))
                ];
            }
        }
    } catch (Exception $e) {}
}

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
            'tipo' => 'Suspenso',
            'retorno' => 'Próxima Partida'
        ];
    }
}

// Cartões Amarelos Acumulados
$stmtAmarelos = $db->prepare("SELECT cs.id_jogador, cs.cartoes_amarelos 
                             FROM competicao_suspensos cs 
                             WHERE cs.id_competicao = :idComp AND cs.cartoes_amarelos > 0 
                             ORDER BY cs.cartoes_amarelos DESC");
$stmtAmarelos->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
$stmtAmarelos->execute();
$amarelosRows = $stmtAmarelos->fetchAll(PDO::FETCH_ASSOC);
$cartoesAmarelosAcumulados = [];
foreach ($amarelosRows as $ar) {
    $cartoesAmarelosAcumulados[(int)$ar['id_jogador']] = (int)$ar['cartoes_amarelos'];
}

// Preparar Artilharia, Assistências e Cartões
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
        'partidas' => $stats['partidas'],
        'gols' => $stats['gols'],
        'assistencias' => $stats['assistencias'],
        'amarelos' => $stats['amarelos'],
        'vermelhos' => $stats['vermelhos'],
        'amarelos_acumulados' => $cartoesAmarelosAcumulados[$pid] ?? $stats['amarelos']
    ];
    
    if ($stats['gols'] > 0) {
        $artilharia[] = $pInfo;
    }
    if ($stats['assistencias'] > 0) {
        $assistencias[] = $pInfo;
    }
    if ($stats['amarelos'] > 0 || $stats['vermelhos'] > 0 || isset($cartoesAmarelosAcumulados[$pid])) {
        $cartoes[] = $pInfo;
    }
}

// Incluir jogadores que só tenham cartões amarelos acumulados no banco
foreach ($cartoesAmarelosAcumulados as $pid => $qtdAmarelos) {
    if (!isset($playerStats[$pid]) && isset($jogadoresMap[$pid])) {
        $clubeId = $jogadorClubeMap[$pid] ?? 0;
        $cartoes[] = [
            'id' => $pid,
            'nome' => $jogadoresMap[$pid]['Nome'],
            'clube' => $clubes[$clubeId]['Nome'] ?? 'Sem clube',
            'partidas' => 0,
            'gols' => 0,
            'assistencias' => 0,
            'amarelos' => $qtdAmarelos,
            'vermelhos' => 0,
            'amarelos_acumulados' => $qtdAmarelos
        ];
    }
}

usort($artilharia, function($a, $b) {
    if ($a['gols'] != $b['gols']) return $b['gols'] - $a['gols'];
    return $a['partidas'] - $b['partidas'];
});

usort($assistencias, function($a, $b) {
    if ($a['assistencias'] != $b['assistencias']) return $b['assistencias'] - $a['assistencias'];
    return $a['partidas'] - $b['partidas'];
});

usort($cartoes, function($a, $b) {
    if ($a['vermelhos'] != $b['vermelhos']) return $b['vermelhos'] - $a['vermelhos'];
    return $b['amarelos_acumulados'] - $a['amarelos_acumulados'];
});

use Shuchkin\SimpleXLSXGen;

$xlsx = new SimpleXLSXGen();

// ==========================================
// ABA 1: CLASSIFICAÇÃO
// ==========================================
$rowsClassificacao = [
    ['<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Posição</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Grupo</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Sigla</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Pontos</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogos</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Vitórias</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Empates</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Derrotas</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>GP</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>GC</b></style>',
     '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>SG</b></style>']
];

foreach ($tabelaPorGrupo as $gName => $grupoTab) {
    $pos = 1;
    foreach ($grupoTab as $team) {
        $rowsClassificacao[] = [
            $pos++ . 'º',
            htmlspecialchars($gName),
            htmlspecialchars($team['nome']),
            htmlspecialchars($team['sigla']),
            $team['pontos'],
            $team['jogos'],
            $team['vitorias'],
            $team['empates'],
            $team['derrotas'],
            $team['gp'],
            $team['gc'],
            $team['sg']
        ];
    }
}
$xlsx->addSheet($rowsClassificacao, 'Classificação');

// ==========================================
// ABA 2: JOGOS & CONFRONTOS
// ==========================================
$rowsJogos = [
    // Instructions block
    ["<b>INSTRUÇÕES DE IMPORTAÇÃO:</b>", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["1. Coluna 'ID Partida': NÃO altere o ID de partidas existentes. Deixe em branco se quiser cadastrar um novo jogo.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["2. Colunas 'Time A ID' e 'Time B ID': Use IDs numéricos válidos. Os nomes são informativos.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["3. 'Data/Hora': Use o formato YYYY-MM-DD HH:MM:SS. 'Neutro': 0 (Mando de A) ou 1 (Campo neutro).", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["4. Para aplicar modificações na tabela, faça o upload desta planilha em 'Importar Excel'.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    [], // Blank
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>ID Partida</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time A ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time A Nome</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols A</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time B ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time B Nome</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols B</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Data/Hora</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Fase ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Fase Nome</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Grupo</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Estádio ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Estádio</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Árbitro ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Árbitro</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Neutro (0/1)</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Status (0/1)</b></style>'
    ]
];

foreach ($jogos as $m) {
    $tAName = $clubes[$m['timeA_id']]['Nome'] ?? ($m['timeA_nome'] ?: "Time #".$m['timeA_id']);
    $tBName = $clubes[$m['timeB_id']]['Nome'] ?? ($m['timeB_nome'] ?: "Time #".$m['timeB_id']);
    $fNome = $fasesMap[$m['fase']] ?? ('Fase '.$m['fase']);
    $estNome = $estadiosMap[$m['estadio_id']] ?? '';
    $arbNome = $arbitrosMap[$m['arbitro_id']] ?? '';

    $rowsJogos[] = [
        $m['id'],
        $m['timeA_id'],
        '<style bgcolor="#F1F5F9">' . htmlspecialchars($tAName, ENT_QUOTES, 'UTF-8') . '</style>',
        $m['timeA_gols'],
        $m['timeB_id'],
        '<style bgcolor="#F1F5F9">' . htmlspecialchars($tBName, ENT_QUOTES, 'UTF-8') . '</style>',
        $m['timeB_gols'],
        $m['data'],
        $m['fase'],
        htmlspecialchars($fNome),
        $m['grupo'],
        $m['estadio_id'],
        htmlspecialchars($estNome),
        $m['arbitro_id'],
        htmlspecialchars($arbNome),
        $m['neutro'],
        $m['status']
    ];
}
$xlsx->addSheet($rowsJogos, 'Jogos');

// ==========================================
// ABA 3: ARTILHARIA
// ==========================================
$rowsArtilharia = [
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Posição</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogador</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Partidas</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols</b></style>'
    ]
];
$pos = 1;
foreach ($artilharia as $art) {
    $rowsArtilharia[] = [
        $pos++ . 'º',
        htmlspecialchars($art['nome']),
        htmlspecialchars($art['clube']),
        $art['partidas'],
        $art['gols']
    ];
}
$xlsx->addSheet($rowsArtilharia, 'Artilharia');

// ==========================================
// ABA 4: ASSISTÊNCIAS
// ==========================================
$rowsAssistencias = [
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Posição</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogador</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Partidas</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Assistências</b></style>'
    ]
];
$pos = 1;
foreach ($assistencias as $ass) {
    $rowsAssistencias[] = [
        $pos++ . 'º',
        htmlspecialchars($ass['nome']),
        htmlspecialchars($ass['clube']),
        $ass['partidas'],
        $ass['assistencias']
    ];
}
$xlsx->addSheet($rowsAssistencias, 'Assistências');

// ==========================================
// ABA 5: CARTÕES
// ==========================================
$rowsCartoes = [
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogador</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Vermelhos</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Amarelos (Em Partida)</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Amarelos (Acumulados)</b></style>'
    ]
];
foreach ($cartoes as $car) {
    $rowsCartoes[] = [
        htmlspecialchars($car['nome']),
        htmlspecialchars($car['clube']),
        $car['vermelhos'],
        $car['amarelos'],
        $car['amarelos_acumulados']
    ];
}
$xlsx->addSheet($rowsCartoes, 'Cartões');

// ==========================================
// ABA 6: DM & SUSPENSÕES
// ==========================================
$rowsDM = [
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogador</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Condição</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Previsão de Retorno</b></style>'
    ]
];
foreach ($lesionados as $les) {
    $rowsDM[] = [
        htmlspecialchars($les['nome']),
        htmlspecialchars($les['clube']),
        $les['tipo'],
        $les['retorno']
    ];
}
foreach ($suspensos as $sus) {
    $rowsDM[] = [
        htmlspecialchars($sus['nome']),
        htmlspecialchars($sus['clube']),
        $sus['tipo'],
        $sus['retorno']
    ];
}
$xlsx->addSheet($rowsDM, 'DM & Suspensões');

$filenameSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nome_competicao . '_' . $ano_competicao);
$xlsx->downloadAs('dados_competicao_' . $filenameSlug . '.xlsx');
exit();

