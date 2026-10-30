<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Acesso negado. Faça login para exportar os dados.");
}

$idCompeticao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$idCompeticao) {
    http_response_code(400);
    die("ID da competição não fornecido.");
}

$formato = isset($_GET['formato']) ? strtolower(trim($_GET['formato'])) : (isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'xlsx');

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simplexlsx/SimpleXLSXGen.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";
include_once __DIR__ . "/sumula_helper.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    http_response_code(404);
    die("Competição não encontrada.");
}

$nome_competicao = $compInfo['nome'] ?? 'Competicao';
$ano_competicao = $compInfo['ano'] ?? date('Y');
$nomeComposto = $ano_competicao . " - " . $nome_competicao;

// Conectar ao SQLite da competição
$compDatabase = new SQLiteDatabase();
$compDatabase->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
$cdb = null;
if (file_exists($compDatabase->fileName)) {
    $cdb = $compDatabase->getConnection();
}

// 1. Carregar Clubes
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
    $stmtPortalTimes = $db->query("SELECT ID, Nome, TresLetras FROM clube");
    if ($stmtPortalTimes) {
        while ($pTime = $stmtPortalTimes->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($pTime['Nome'])) {
                $pTime['Nome'] = trim(html_entity_decode(html_entity_decode(stripslashes($pTime['Nome']), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $clubes[(int)$pTime['ID']] = $pTime;
        }
    }
} catch (\Throwable $e) {}

// 2. Carregar Jogadores
$jogadoresMap = [];
if ($cdb) {
    try {
        $stmtJogadores = $cdb->query("SELECT ID, Nome, Nivel FROM jogador");
        if ($stmtJogadores) {
            while ($row = $stmtJogadores->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['Nome'])) {
                    $row['Nome'] = trim(html_entity_decode(html_entity_decode(stripslashes($row['Nome']), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }
                $jogadoresMap[(int)$row['ID']] = $row;
            }
        }
    } catch (\Throwable $e) {}
}

try {
    $stmtPortalJogadores = $db->query("SELECT id, Nome, Nivel FROM jogador");
    if ($stmtPortalJogadores) {
        while ($pJog = $stmtPortalJogadores->fetch(PDO::FETCH_ASSOC)) {
            $pId = (int)$pJog['id'];
            $cleanNome = trim(html_entity_decode(html_entity_decode(stripslashes($pJog['Nome'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!empty($cleanNome)) {
                if (!isset($jogadoresMap[$pId])) {
                    $jogadoresMap[$pId] = [
                        'ID' => $pId,
                        'Nome' => $cleanNome,
                        'Nivel' => $pJog['Nivel'] ?? 0
                    ];
                } else {
                    $jogadoresMap[$pId]['Nome'] = $cleanNome;
                }
            }
        }
    }
} catch (\Throwable $e) {}

// 3. Mapear Jogador -> Clube (Elenco e Contratos)
$jogadorClubeMap = [];
if ($cdb) {
    try {
        $stmtElenco = $cdb->query("SELECT * FROM elenco");
        if ($stmtElenco) {
            while ($row = $stmtElenco->fetch(PDO::FETCH_ASSOC)) {
                $clubeId = (int)$row['Clube'];
                for ($i = 1; $i <= 23; $i++) {
                    $pId = (int)($row['Jogador' . $i] ?? 0);
                    if ($pId != 0) {
                        $jogadorClubeMap[$pId] = $clubeId;
                    }
                }
            }
        }
    } catch (\Throwable $e) {}
}

try {
    $stmtContratos = $db->query("SELECT jogador, clube FROM contratos_jogador WHERE tipoContrato = 0");
    if ($stmtContratos) {
        while ($cRow = $stmtContratos->fetch(PDO::FETCH_ASSOC)) {
            $pId = (int)$cRow['jogador'];
            $cId = (int)$cRow['clube'];
            if (!isset($jogadorClubeMap[$pId])) {
                $jogadorClubeMap[$pId] = $cId;
            }
        }
    }
} catch (\Throwable $e) {}

// 4. Carregar Jogos da Competição
$stmtJogos = $db->prepare("SELECT id, timeA_id, timeA_nome, timeA_gols, timeB_id, timeB_nome, timeB_gols, timeA_penaltis, timeB_penaltis, data, status, fase, grupo, path 
                           FROM jogos_clube 
                           WHERE competicao_id = :idComp 
                           ORDER BY data ASC, id ASC");
$stmtJogos->execute([':idComp' => $idCompeticao]);
$jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

$jogosPorPath = [];
foreach ($jogos as $j) {
    $pBasename = !empty($j['path']) ? basename($j['path']) : '';
    if ($pBasename !== '') {
        $jogosPorPath[$pBasename] = $j;
    }
}

// 5. Coletar e Processar Arquivos .hyj
$playerStats = [];
$processedFiles = [];

$searchDirs = [
    $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto,
    $_SERVER['DOCUMENT_ROOT'] . "/full hexa suite/Hexacolor YMT/Partidas/" . $nomeComposto
];

$arquivosHyj = [];
foreach ($searchDirs as $sDir) {
    if (is_dir($sDir)) {
        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'hyj') {
                    $arquivosHyj[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        } catch (\Throwable $e) {}
    }
}

// Complementar com busca de arquivos via helper para paths cadastrados
if (function_exists('encontrarArquivoSumula')) {
    foreach ($jogos as $j) {
        if (!empty($j['path'])) {
            $fSumula = encontrarArquivoSumula($nomeComposto, $j['path'], 'hyj');
            if ($fSumula && file_exists($fSumula) && !in_array($fSumula, $arquivosHyj)) {
                $arquivosHyj[] = $fSumula;
            }
        }
    }
}

foreach ($arquivosHyj as $filePath) {
    $real = realpath($filePath);
    if ($real && isset($processedFiles[$real])) {
        continue;
    }
    if ($real) {
        $processedFiles[$real] = true;
    }

    $baseFilename = pathinfo($filePath, PATHINFO_FILENAME);
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

    // Sigilo Temporal: jogos não encerrados no tempo real não são processados
    if ($jogoCorrespondente) {
        $dataJogo = !empty($jogoCorrespondente['data']) ? strtotime($jogoCorrespondente['data']) : time();
        $temPen = ($jogoCorrespondente['timeA_penaltis'] !== null && $jogoCorrespondente['timeA_penaltis'] !== '');
        $duracaoSegundos = $temPen ? (150 * 60) : (120 * 60);
        $jaTerminou = (time() >= ($dataJogo + $duracaoSegundos));
        
        if ((int)$jogoCorrespondente['status'] !== 1 || !$jaTerminou) {
            continue;
        }
    }

    $content = @file_get_contents($filePath);
    if ($content !== false) {
        $json = json_decode($content);
        if ($json) {
            $clubesJogo = [
                'time1' => (int)($jogoCorrespondente['timeA_id'] ?? ($json->time1->idTime ?? 0)),
                'time2' => (int)($jogoCorrespondente['timeB_id'] ?? ($json->time2->idTime ?? 0))
            ];

            foreach ($clubesJogo as $tKey => $idTimePartida) {
                if (isset($json->$tKey->jogadores) && is_array($json->$tKey->jogadores)) {
                    foreach ($json->$tKey->jogadores as $pj) {
                        $pid = (int)($pj->idJogador ?? 0);
                        if ($pid <= 0) continue;

                        if (!isset($playerStats[$pid])) {
                            $clubeId = $jogadorClubeMap[$pid] ?? ($idTimePartida > 0 ? $idTimePartida : 0);
                            $playerStats[$pid] = [
                                'id' => $pid,
                                'nome' => $pj->nomeJogador ?? ($jogadoresMap[$pid]['Nome'] ?? ('Jogador ' . $pid)),
                                'clube_id' => $clubeId,
                                'posicao' => !empty($pj->posicao) ? (string)$pj->posicao : '',
                                'partidas' => 0,
                                'jogos_com_nota' => 0,
                                'soma_notas' => 0.0,
                                'media' => 0.0,
                                'gols' => 0,
                                'assistencias' => 0
                            ];
                        }

                        if (!empty($pj->posicao) && empty($playerStats[$pid]['posicao'])) {
                            $playerStats[$pid]['posicao'] = (string)$pj->posicao;
                        }

                        $minutos = (int)($pj->minutos ?? 0);
                        $partidaFlag = (int)($pj->partida ?? 0);
                        if ($minutos > 0 || $partidaFlag === 1) {
                            $playerStats[$pid]['partidas']++;
                        }

                        if (isset($pj->nota) && is_numeric($pj->nota) && floatval($pj->nota) > 0) {
                            $playerStats[$pid]['soma_notas'] += floatval($pj->nota);
                            $playerStats[$pid]['jogos_com_nota']++;
                        }

                        $playerStats[$pid]['gols'] += (int)($pj->gols ?? 0);
                        $playerStats[$pid]['assistencias'] += (int)($pj->assistencias ?? 0);
                    }
                }
            }
        }
    }
}

// 6. Consolidar Lista e Calcular Médias
$listaJogadores = [];
foreach ($playerStats as $pid => $p) {
    if ($p['partidas'] > 0 || $p['jogos_com_nota'] > 0) {
        $p['media'] = ($p['jogos_com_nota'] > 0) ? round($p['soma_notas'] / $p['jogos_com_nota'], 2) : 0.0;
        
        // Atualizar Nome mais recente se presente no cadastro
        if (isset($jogadoresMap[$pid]['Nome']) && !empty($jogadoresMap[$pid]['Nome'])) {
            $p['nome'] = $jogadoresMap[$pid]['Nome'];
        }
        
        // Resolver Nome do Clube
        $cid = $jogadorClubeMap[$pid] ?? $p['clube_id'];
        $p['clube'] = $clubes[$cid]['Nome'] ?? ($cid > 0 ? ('Time #' . $cid) : 'Sem Clube');

        $listaJogadores[] = $p;
    }
}

// Ordenar por Média (descrescente), depois Partidas (decrescente), depois Nome (ascendente)
usort($listaJogadores, function($a, $b) {
    if ($b['media'] != $a['media']) {
        return ($b['media'] > $a['media']) ? 1 : -1;
    }
    if ($b['partidas'] != $a['partidas']) {
        return $b['partidas'] - $a['partidas'];
    }
    return strcasecmp($a['nome'], $b['nome']);
});

$filenameSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', 'notas_jogadores_' . $nome_competicao . '_' . $ano_competicao);

// 7. Exportação em Formato CSV ou Excel (XLSX)
if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filenameSlug . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // UTF-8 BOM para garantir correta acentuação no Excel
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');

    // Cabeçalho com as informações solicitadas
    fputcsv($output, [
        'Posição Ranking',
        'ID Jogador',
        'Nome do Jogador',
        'Clube',
        'Posição',
        'Partidas Disputadas',
        'Jogos com Nota',
        'Nota Média',
        'Gols',
        'Assistências'
    ], ';');

    $rank = 1;
    foreach ($listaJogadores as $j) {
        fputcsv($output, [
            $rank++ . 'º',
            $j['id'],
            $j['nome'],
            $j['clube'],
            $j['posicao'],
            $j['partidas'],
            $j['jogos_com_nota'],
            number_format($j['media'], 2, ',', '.'),
            $j['gols'],
            $j['assistencias']
        ], ';');
    }
    fclose($output);
    exit();
} else {
    // Formato Excel (.xlsx) nativo
    $rowsNotas = [
        [
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Posição Ranking</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>ID Jogador</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Nome do Jogador</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Clube</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Posição</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Partidas Disputadas</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Jogos com Nota</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Nota Média</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols</b></style>',
            '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Assistências</b></style>'
        ]
    ];

    $rank = 1;
    foreach ($listaJogadores as $j) {
        $rowsNotas[] = [
            $rank++ . 'º',
            $j['id'],
            htmlspecialchars($j['nome'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($j['clube'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($j['posicao'], ENT_QUOTES, 'UTF-8'),
            $j['partidas'],
            $j['jogos_com_nota'],
            $j['media'],
            $j['gols'],
            $j['assistencias']
        ];
    }

    $xlsx = SimpleXLSXGen::fromArray($rowsNotas, 'Notas dos Jogadores');
    $xlsx->downloadAs($filenameSlug . '.xlsx');
    exit();
}
