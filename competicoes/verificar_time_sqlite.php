<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'error' => 'Acesso negado. Por favor refaça o login.']));
}

$idCompeticao = intval($_POST['codigo_competicao'] ?? $_GET['codigo_competicao'] ?? 0);
if ($idCompeticao <= 0) {
    die(json_encode(['success' => false, 'error' => 'Código de competição inválido.']));
}

$vaga = intval($_POST['vaga'] ?? $_GET['vaga'] ?? 0);
$todos = intval($_POST['todos'] ?? $_GET['todos'] ?? 0);
$clubeIdParam = isset($_POST['clube_id']) ? intval($_POST['clube_id']) : null;

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php");

$database = new Database();
$db = $database->getConnection();

$sqliteFile = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
if (!file_exists($sqliteFile) || filesize($sqliteFile) < 100) {
    die(json_encode(['success' => false, 'error' => 'O banco de dados SQLite desta competição ainda não possui dados ou arquivos gravados.']));
}

$liteDatabase = new SQLiteDatabase();
$liteDatabase->fileName = $sqliteFile;
$ldb = $liteDatabase->getConnection();
if (!$ldb) {
    die(json_encode(['success' => false, 'error' => 'Não foi possível conectar ao banco SQLite da competição.']));
}

function verificarIntegridadeTimeSQLite($ldb, $clubeId, $vaga = 0) {
    $erros = [];
    $detalhes = [
        'clube_id' => $clubeId,
        'vaga' => $vaga,
        'nome' => "Time #{$clubeId}",
        'tecnico' => 'Não definido',
        'estadio' => 'Não definido',
        'clima' => 'Não definido',
        'capitao' => 'Não definido',
        'goleiro' => 'Não definido',
        'total_jogadores' => 0
    ];

    // 1. Clube
    $stClube = $ldb->prepare("SELECT * FROM clube WHERE ID = :id LIMIT 1");
    $stClube->execute([':id' => $clubeId]);
    $rClube = $stClube->fetch(PDO::FETCH_ASSOC);

    if (!$rClube) {
        $erros[] = "O clube (ID {$clubeId}) não foi encontrado na tabela `clube` do banco de dados SQLite da competição.";
        return [
            'clube_id' => $clubeId,
            'vaga' => $vaga,
            'nome' => "Time #{$clubeId}",
            'success' => false,
            'erros' => $erros,
            'detalhes' => $detalhes
        ];
    }

    $detalhes['nome'] = $rClube['Nome'] ?: "Time #{$clubeId}";
    $idEstadio = (int)($rClube['Estadio'] ?? 0);

    // 2. Estádio e Clima
    if ($idEstadio === 0) {
        // Fallback: se o clube não tem estádio associado, verificar se a competição tem pelo menos 1 estádio (fallback padrão do Hexacolor)
        $stFirstEst = $ldb->query("SELECT * FROM estadio LIMIT 1");
        $rFirstEst = $stFirstEst ? $stFirstEst->fetch(PDO::FETCH_ASSOC) : null;
        if ($rFirstEst) {
            $detalhes['estadio'] = ($rFirstEst['Nome'] ?: "Estádio #{$rFirstEst['ID']}") . " (padrão da competição)";
        } else {
            $erros[] = "O time não possui estádio associado na tabela `clube` do SQLite e não há nenhum estádio na competição.";
        }
    } else {
        $stEst = $ldb->prepare("SELECT * FROM estadio WHERE ID = :id LIMIT 1");
        $stEst->execute([':id' => $idEstadio]);
        $rEst = $stEst->fetch(PDO::FETCH_ASSOC);

        // Se não encontrou o estádio pelo ID:
        if (!$rEst) {
            // Tentar auto-recuperar/curar o registro do estádio no SQLite para times importados (ID negativo)
            try {
                // Verificar se existe clima correspondente para associar
                $stCliCheck = $ldb->prepare("SELECT ID, Nome FROM clima WHERE ID = :id LIMIT 1");
                $stCliCheck->execute([':id' => $idEstadio]);
                $rCliFound = $stCliCheck->fetch(PDO::FETCH_ASSOC);
                $climaIdUsar = $rCliFound ? $idEstadio : 0;

                if ($climaIdUsar === 0) {
                    $stAnyCli = $ldb->query("SELECT ID FROM clima LIMIT 1");
                    $anyCliId = $stAnyCli ? $stAnyCli->fetchColumn() : null;
                    if ($anyCliId !== false && $anyCliId !== null) {
                        $climaIdUsar = (int)$anyCliId;
                    }
                }

                $nomeEstadioAuto = "Estádio " . ($detalhes['nome'] ?: "Time #{$clubeId}");
                $insEst = $ldb->prepare("INSERT OR IGNORE INTO estadio (ID, Nome, Capacidade, Clima, Altitude, Caldeirao) VALUES (:id, :nome, 30000, :clima, 0, 0)");
                $insEst->execute([
                    ':id' => $idEstadio,
                    ':nome' => $nomeEstadioAuto,
                    ':clima' => $climaIdUsar
                ]);

                // Reconsultar após auto-recuperação
                $stEst->execute([':id' => $idEstadio]);
                $rEst = $stEst->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        if ($rEst) {
            $detalhes['estadio'] = $rEst['Nome'] ?: "Estádio #{$idEstadio}";
            $idClima = (int)($rEst['Clima'] ?? 0);
            if ($idClima !== 0) {
                $stCli = $ldb->prepare("SELECT * FROM clima WHERE ID = :id LIMIT 1");
                $stCli->execute([':id' => $idClima]);
                $rCli = $stCli->fetch(PDO::FETCH_ASSOC);
                if ($rCli) {
                    $detalhes['clima'] = $rCli['Nome'] ?: "Clima #{$idClima}";
                } else {
                    $detalhes['clima'] = "Clima padrão";
                }
            }
        } else {
            // Fallback do simulador Hexacolor: se o estádio específico não existe, busca qualquer estádio na competição
            $stAnyEst = $ldb->query("SELECT * FROM estadio LIMIT 1");
            $rAnyEst = $stAnyEst ? $stAnyEst->fetch(PDO::FETCH_ASSOC) : null;
            if ($rAnyEst) {
                $detalhes['estadio'] = ($rAnyEst['Nome'] ?: "Estádio #{$rAnyEst['ID']}") . " (fallback da competição)";
            } else {
                $erros[] = "O estádio (#{$idEstadio}) vinculado ao clube não existe no SQLite e não há nenhum estádio na competição.";
            }
        }
    }

    // 3. Elenco e Técnico
    $stEl = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :id LIMIT 1");
    $stEl->execute([':id' => $clubeId]);
    $rEl = $stEl->fetch(PDO::FETCH_ASSOC);

    $jogadoresElenco = [];
    if (!$rEl) {
        $erros[] = "Time sem registro na tabela `elenco` do SQLite da competição.";
    } else {
        $idTecnico = (int)($rEl['Tecnico'] ?? 0);
        if ($idTecnico === 0) {
            $erros[] = "Time sem técnico definido na tabela `elenco` do SQLite.";
        } else {
            $stTec = $ldb->prepare("SELECT * FROM tecnico WHERE ID = :id LIMIT 1");
            $stTec->execute([':id' => $idTecnico]);
            $rTec = $stTec->fetch(PDO::FETCH_ASSOC);
            if (!$rTec) {
                // Se o técnico não existe na tabela tecnico (comum em times YMT onde o técnico é ID negativo), auto-recuperar
                try {
                    $nomeTecAuto = "Técnico " . ($detalhes['nome'] ?: "Time #{$clubeId}");
                    $insTec = $ldb->prepare("INSERT OR IGNORE INTO tecnico (ID, Nome, Idade, Nivel, Mentalidade, Estilo) VALUES (:id, :nome, 45, 5, 3, 3)");
                    $insTec->execute([
                        ':id' => $idTecnico,
                        ':nome' => $nomeTecAuto
                    ]);
                    $stTec->execute([':id' => $idTecnico]);
                    $rTec = $stTec->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}
            }

            if (!$rTec) {
                $erros[] = "Técnico (#{$idTecnico}) do elenco não existe na tabela `tecnico` do SQLite.";
            } else {
                $detalhes['tecnico'] = $rTec['Nome'] ?: "Técnico #{$idTecnico}";
                if (!isset($rTec['Mentalidade']) || !isset($rTec['Estilo'])) {
                    $erros[] = "Técnico '{$detalhes['tecnico']}' está com parâmetros táticos (mentalidade/estilo) incompletos no SQLite.";
                }
            }
        }

        for ($k = 1; $k <= 23; $k++) {
            $pid = (int)($rEl['Jogador' . $k] ?? 0);
            if ($pid !== 0) {
                $jogadoresElenco[] = $pid;
            }
        }
        $detalhes['total_jogadores'] = count($jogadoresElenco);

        if (count($jogadoresElenco) < 11) {
            $erros[] = "Elenco incompleto no SQLite: possui apenas " . count($jogadoresElenco) . " jogadores (mínimo exigido: 11).";
        }
        if (count($jogadoresElenco) > 23) {
            $erros[] = "Elenco com mais de 23 jogadores no SQLite (" . count($jogadoresElenco) . ").";
        }

        // Duplicatas no elenco
        $countsEl = array_count_values($jogadoresElenco);
        foreach ($countsEl as $pId => $qtd) {
            if ($qtd > 1) {
                $erros[] = "Jogador (#{$pId}) aparece duplicado {$qtd} vezes no elenco do SQLite.";
            }
        }
    }

    // 4. Integridade dos Jogadores no SQLite
    $jogadoresMap = [];
    if (!empty($jogadoresElenco)) {
        $stJog = $ldb->prepare("SELECT ID, Nome, Idade FROM jogador WHERE ID = :pid LIMIT 1");
        $stPos = $ldb->prepare("SELECT Jogador, G FROM posicaojogador WHERE Jogador = :pid LIMIT 1");
        $stAj = $ldb->prepare("SELECT Jogador FROM atributosjogador WHERE Jogador = :pid LIMIT 1");
        $stAg = $ldb->prepare("SELECT Goleiro FROM atributosgoleiro WHERE Goleiro = :pid LIMIT 1");

        foreach ($jogadoresElenco as $pid) {
            $stJog->execute([':pid' => $pid]);
            $rJog = $stJog->fetch(PDO::FETCH_ASSOC);
            if (!$rJog) {
                $erros[] = "Jogador (#{$pid}) listado no elenco não existe na tabela `jogador` do SQLite.";
                continue;
            }
            $jogadoresMap[$pid] = $rJog;

            if (isset($rJog['Idade']) && (int)$rJog['Idade'] > 45) {
                $erros[] = "Jogador '{$rJog['Nome']}' (#{$pid}) tem {$rJog['Idade']} anos (acima do limite de 45 anos permitido pelo Hexacolor).";
            }

            $stPos->execute([':pid' => $pid]);
            if (!$stPos->fetch()) {
                $erros[] = "Jogador '{$rJog['Nome']}' (#{$pid}) não possui posições na tabela `posicaojogador` do SQLite.";
            }

            $stAj->execute([':pid' => $pid]);
            $hasAj = (bool)$stAj->fetch();
            $stAg->execute([':pid' => $pid]);
            $hasAg = (bool)$stAg->fetch();
            if (!$hasAj && !$hasAg) {
                $erros[] = "Jogador '{$rJog['Nome']}' (#{$pid}) não possui atributos em `atributosjogador` nem em `atributosgoleiro` no SQLite.";
            }
        }
    }

    // 5. Escalação Titular
    $stEsc = $ldb->prepare("SELECT * FROM escalacao WHERE Clube = :id LIMIT 1");
    $stEsc->execute([':id' => $clubeId]);
    $rEsc = $stEsc->fetch(PDO::FETCH_ASSOC);

    if (!$rEsc) {
        $erros[] = "Time sem registro na tabela `escalacao` do SQLite da competição.";
    } else {
        $titulares = [];
        $goleirosEscalados = 0;

        for ($k = 1; $k <= 11; $k++) {
            $pos = trim($rEsc['Pos' . $k] ?? '');
            $jid = (int)($rEsc['Jogador' . $k] ?? 0);

            if ($jid === 0) {
                $erros[] = "Posição titular {$k} está sem jogador escalado (ID 0).";
            } else {
                if (empty($pos)) {
                    $erros[] = "Titular {$k} (#{$jid}) está sem posição tática definida na escalação.";
                }
                if ($pos === 'G') {
                    $goleirosEscalados++;
                    if (!isset($detalhes['goleiro']) || $detalhes['goleiro'] === 'Não definido') {
                        $detalhes['goleiro'] = isset($jogadoresMap[$jid]['Nome']) ? $jogadoresMap[$jid]['Nome'] : "Goleiro #{$jid}";
                    }
                }
                $titulares[] = $jid;

                if (!in_array($jid, $jogadoresElenco)) {
                    $erros[] = "Titular {$k} (#{$jid}) não pertence ao elenco cadastrado no SQLite.";
                }
            }
        }

        // Duplicatas nos titulares
        $countsTit = array_count_values($titulares);
        foreach ($countsTit as $jid => $qtd) {
            if ($qtd > 1) {
                $erros[] = "Jogador titular (#{$jid}) está escalado em {$qtd} posições simultâneas na escalação.";
            }
        }

        // Goleiro
        if ($goleirosEscalados === 0) {
            $erros[] = "Nenhum goleiro ('G') escalado entre os 11 titulares no SQLite.";
        } elseif ($goleirosEscalados > 1) {
            $erros[] = "Existem {$goleirosEscalados} goleiros escalados como titulares (o simulador exige exatamente 1).";
        }

        // Capitão
        $capitao = (int)($rEsc['Capitao'] ?? 0);
        if ($capitao === 0) {
            $erros[] = "Time sem capitão definido na escalação do SQLite.";
        } else {
            $detalhes['capitao'] = isset($jogadoresMap[$capitao]['Nome']) ? $jogadoresMap[$capitao]['Nome'] : "Capitão #{$capitao}";
            if (!in_array($capitao, $titulares)) {
                $erros[] = "O capitão '{$detalhes['capitao']}' (#{$capitao}) não está escalado entre os 11 titulares no SQLite.";
            }
        }

        // Pênaltis
        $penaltis = [
            (int)($rEsc['Penalti1'] ?? 0),
            (int)($rEsc['Penalti2'] ?? 0),
            (int)($rEsc['Penalti3'] ?? 0)
        ];
        $penValidos = array_filter($penaltis, function($p) { return $p !== 0; });
        if (count($penValidos) < 3) {
            $erros[] = "A escalação não possui os 3 cobradores de pênaltis definidos no SQLite.";
        } else {
            foreach ($penaltis as $pIdx => $pid) {
                if (!in_array($pid, $titulares)) {
                    $pNome = isset($jogadoresMap[$pid]['Nome']) ? $jogadoresMap[$pid]['Nome'] : "#{$pid}";
                    $erros[] = "Cobrador de pênalti " . ($pIdx + 1) . " '{$pNome}' não está entre os 11 titulares no SQLite.";
                }
            }
        }
    }

    return [
        'clube_id' => $clubeId,
        'vaga' => $vaga,
        'nome' => $detalhes['nome'],
        'success' => empty($erros),
        'erros' => $erros,
        'detalhes' => $detalhes
    ];
}

// Resolução dos clubes a verificar
$timesParaVerificar = [];

if ($todos === 1) {
    // Buscar todas as vagas que possuem time cadastrado
    $stTimes = $db->prepare("SELECT codigo_time, has_team, id_time_portal FROM competicao_times WHERE id_competicao = :idComp ORDER BY codigo_time ASC");
    $stTimes->execute([':idComp' => $idCompeticao]);
    while ($row = $stTimes->fetch(PDO::FETCH_ASSOC)) {
        $hasTeam = (int)($row['has_team'] ?? 0);
        $idPortal = !empty($row['id_time_portal']) ? (int)$row['id_time_portal'] : 0;
        $vNum = (int)$row['codigo_time'];

        if ($hasTeam === 1 || $idPortal > 0) {
            $cid = $idPortal > 0 ? $idPortal : (-1 * abs($vNum));
            $timesParaVerificar[] = ['vaga' => $vNum, 'clube_id' => $cid];
        }
    }

    if (empty($timesParaVerificar)) {
        die(json_encode(['success' => false, 'error' => 'Nenhum time encontrado vinculado a esta competição até o momento.']));
    }

    $resultados = [];
    $totalErros = 0;
    foreach ($timesParaVerificar as $item) {
        $vNum = $item['vaga'];
        $cid = $item['clube_id'];
        
        // Se o clube não existir pelo ID do portal, tenta o ID negativo da vaga (fallback)
        $stCheck = $ldb->prepare("SELECT 1 FROM clube WHERE ID = :id LIMIT 1");
        $stCheck->execute([':id' => $cid]);
        if (!$stCheck->fetch()) {
            $altCid = -1 * abs($vNum);
            $stCheckAlt = $ldb->prepare("SELECT 1 FROM clube WHERE ID = :id LIMIT 1");
            $stCheckAlt->execute([':id' => $altCid]);
            if ($stCheckAlt->fetch()) {
                $cid = $altCid;
            }
        }

        $res = verificarIntegridadeTimeSQLite($ldb, $cid, $vNum);
        if (!$res['success']) {
            $totalErros++;
        }
        $resultados[] = $res;
    }

    die(json_encode([
        'success' => ($totalErros === 0),
        'todos' => true,
        'total_verificados' => count($resultados),
        'total_com_erros' => $totalErros,
        'resultados' => $resultados
    ]));

} else {
    // Verificação de uma única vaga ou clube específico
    $targetClubeId = 0;
    $vagaNum = $vaga > 0 ? $vaga : 0;

    if ($vagaNum > 0) {
        $stVaga = $db->prepare("SELECT codigo_time, has_team, id_time_portal FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :vaga LIMIT 1");
        $stVaga->execute([':idComp' => $idCompeticao, ':vaga' => $vagaNum]);
        $rowVaga = $stVaga->fetch(PDO::FETCH_ASSOC);

        if ($rowVaga) {
            $hasTeam = (int)($rowVaga['has_team'] ?? 0);
            $idPortal = !empty($rowVaga['id_time_portal']) ? (int)$rowVaga['id_time_portal'] : 0;
            if ($idPortal > 0) {
                $targetClubeId = $idPortal;
            } elseif ($hasTeam === 1) {
                $targetClubeId = -1 * abs($vagaNum);
            }
        }
    }

    if ($targetClubeId === 0 && $clubeIdParam !== null && $clubeIdParam !== 0) {
        $targetClubeId = $clubeIdParam;
    }

    if ($targetClubeId === 0 && $vagaNum > 0) {
        $targetClubeId = -1 * abs($vagaNum);
    }

    if ($targetClubeId === 0) {
        die(json_encode(['success' => false, 'error' => 'Esta vaga ainda não possui nenhum time salvo ou importado no SQLite da competição.']));
    }

    // Fallback: verificar se o time existe no SQLite pelo targetClubeId ou pelo ID negativo da vaga
    $stCheck = $ldb->prepare("SELECT 1 FROM clube WHERE ID = :id LIMIT 1");
    $stCheck->execute([':id' => $targetClubeId]);
    if (!$stCheck->fetch() && $vagaNum > 0) {
        $altCid = -1 * abs($vagaNum);
        $stCheckAlt = $ldb->prepare("SELECT 1 FROM clube WHERE ID = :id LIMIT 1");
        $stCheckAlt->execute([':id' => $altCid]);
        if ($stCheckAlt->fetch()) {
            $targetClubeId = $altCid;
        }
    }

    $res = verificarIntegridadeTimeSQLite($ldb, $targetClubeId, $vagaNum);

    die(json_encode([
        'success' => $res['success'],
        'todos' => false,
        'clube_id' => $res['clube_id'],
        'vaga' => $res['vaga'],
        'nome' => $res['nome'],
        'erros' => $res['erros'],
        'detalhes' => $res['detalhes']
    ]));
}
