<?php
error_reporting(E_ERROR | E_PARSE);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

function json_fail($msg) {
    $spurious = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $msg . ($spurious ? ' | PHP output: ' . strip_tags($spurious) : '')]);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    json_fail('Sem permissão. Faça o login para importar partidas.');
}

if ($_SESSION['emTestes'] ?? false) {
    json_fail('Usuários em período de testes não podem importar partidas.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('Método inválido.');
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input || empty($input['games'])) {
    json_fail('Dados de jogos não enviados ou vazios.');
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogos_clube.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();
$jogoClube  = new Jogo($db);
$timeObj    = new Time($db);
$jogadorObj = new Jogador($db);
$usuarioObj = new Usuario($db);

$results       = [];
$importedCount = 0;
$skippedCount  = 0;

try {
    $db->beginTransaction();

    foreach ($input['games'] as $game) {
        if (!empty($game['error'])) {
            $skippedCount++;
            continue;
        }

        $timeA_id  = isset($game['timeA_id']) ? (int)$game['timeA_id'] : 0;
        $timeB_id  = isset($game['timeB_id']) ? (int)$game['timeB_id'] : 0;
        $game_date = isset($game['data'])      ? (string)$game['data']  : '';

        if (!$timeA_id || !$timeB_id || !$game_date) {
            $results[] = [
                'filename' => $game['filename'] ?? '',
                'success'  => false,
                'error'    => 'Clube A ou B inválido ou data vazia.'
            ];
            $skippedCount++;
            continue;
        }

        // Buscar nomes dos clubes
        $stA = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
        $stA->execute([$timeA_id]);
        $nome_time_A = $stA->fetchColumn() ?: ($game['time1_raw'] ?? '');

        $stB = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
        $stB->execute([$timeB_id]);
        $nome_time_B = $stB->fetchColumn() ?: ($game['time2_raw'] ?? '');

        // Configurar jogo
        $jogoClube->timeA_id = $timeA_id;
        $jogoClube->timeA_nome = $nome_time_A;
        $jogoClube->timeB_id = $timeB_id;
        $jogoClube->timeB_nome = $nome_time_B;
        $jogoClube->data = $game_date;
        $jogoClube->estadio_nome = isset($game['estadio']) ? (string)$game['estadio'] : '';
        $jogoClube->estadio_id = null;
        $jogoClube->competicao_id = isset($game['campeonato']) ? (int)$game['campeonato'] : 0;
        $jogoClube->competicao_tipo = isset($game['competicao_tipo']) ? (int)$game['competicao_tipo'] : 1;
        $jogoClube->fase = isset($game['fase']) ? (int)$game['fase'] : 0;
        $jogoClube->dono = (int)($_SESSION['user_id'] ?? 0);

        // Placar normal
        $placarTime1 = isset($game['placarTime1']) ? (int)$game['placarTime1'] : 0;
        $placarTime2 = isset($game['placarTime2']) ? (int)$game['placarTime2'] : 0;

        // Prorrogação
        $placarProrr1 = (isset($game['placarProrrogacaoTime1']) && (int)$game['placarProrrogacaoTime1'] >= 0) ? (int)$game['placarProrrogacaoTime1'] : -1;
        $placarProrr2 = (isset($game['placarProrrogacaoTime2']) && (int)$game['placarProrrogacaoTime2'] >= 0) ? (int)$game['placarProrrogacaoTime2'] : -1;

        $jogoClube->timeA_gols = $placarTime1 + ($placarProrr1 >= 0 ? $placarProrr1 : 0);
        $jogoClube->timeB_gols = $placarTime2 + ($placarProrr2 >= 0 ? $placarProrr2 : 0);

        // Pênaltis
        $jogoClube->timeA_penaltis = (isset($game['placarPenaltisTime1']) && (int)$game['placarPenaltisTime1'] >= 0) ? (int)$game['placarPenaltisTime1'] : null;
        $jogoClube->timeB_penaltis = (isset($game['placarPenaltisTime2']) && (int)$game['placarPenaltisTime2'] >= 0) ? (int)$game['placarPenaltisTime2'] : null;

        $isDuplicate = !empty($game['is_duplicate']) && !empty($game['existing_id']);

        if ($isDuplicate) {
            $idJogo = (int)$game['existing_id'];
            $jogoClube->id = $idJogo;
            $current = $jogoClube->getSingleMatchInfo($idJogo);
            $jogoClube->dono = $current['dono'] ?? $_SESSION['user_id'];
            $jogoClube->atualizar();
            $actionLabel = 'Atualizado';
        } else {
            if (!$jogoClube->importar()) {
                $results[] = [
                    'filename' => $game['filename'] ?? '',
                    'success'  => false,
                    'error'    => 'Erro ao gravar jogo no banco de dados.'
                ];
                $skippedCount++;
                continue;
            }
            $idJogo = (int)$db->lastInsertId();
            if ($idJogo <= 0) {
                $idJogo = (int)$jogoClube->getMatchId();
            }
            $actionLabel = 'Importado';
        }

        // Inserir Eventos apenas se vierem eventos no arquivo
        $escalacaoTime1 = $game['escalacaoTime1'] ?? [];
        $escalacaoTime2 = $game['escalacaoTime2'] ?? [];
        $raw_eventos    = $game['eventos']         ?? [];

        // Pre-mapear id_jogador correto no banco para cada atleta das escalações
        $dbPlayerMapA = [];
        $dbPlayerMapB = [];

        foreach ($escalacaoTime1 as $p) {
            $pNome = trim((string)($p['nome'] ?? ''));
            if (empty($pNome)) continue;
            $pos = $p['posicao'] ?? '';
            $tempId = (int)($p['id'] ?? 0);
            if ($pos === 'T') {
                $dbPlayerMapA[$tempId] = 0;
            } else {
                $dbId = $jogadorObj->idPorNomeClube($pNome, $timeA_id, $tempId);
                $dbPlayerMapA[$tempId] = $dbId > 0 ? $dbId : 0;
            }
        }

        foreach ($escalacaoTime2 as $p) {
            $pNome = trim((string)($p['nome'] ?? ''));
            if (empty($pNome)) continue;
            $pos = $p['posicao'] ?? '';
            $tempId = (int)($p['id'] ?? 0);
            if ($pos === 'T') {
                $dbPlayerMapB[$tempId] = 0;
            } else {
                $dbId = $jogadorObj->idPorNomeClube($pNome, $timeB_id, $tempId);
                $dbPlayerMapB[$tempId] = $dbId > 0 ? $dbId : 0;
            }
        }

        if (!empty($raw_eventos)) {
            $jogoClube->limparEventos($idJogo);
            $stmtEv = $db->prepare("INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?,?,?,?,?,?,?,?)");

            foreach ($raw_eventos as $ev) {
                $tipoEvento = 0;
                switch ($ev['tipoEvento'] ?? '') {
                    case 'amarelo':   $tipoEvento = 2; break;
                    case 'vermelho':  $tipoEvento = 3; break;
                    case 'gol':       $tipoEvento = 1; break;
                    case 'golContra': $tipoEvento = 4; break;
                }

                if ($tipoEvento > 0) {
                    $nomeJogador = '';
                    $idTime = 0;
                    $nomeTime = '';
                    $tempId = (int)($ev['idJogador'] ?? 0);
                    $dbJogadorId = 0;

                    foreach ($escalacaoTime1 as $p) {
                        if ($tempId === (int)($p['id'] ?? 0)) {
                            $nomeJogador = $p['nome'] ?? '';
                            $idTime = $timeA_id;
                            $nomeTime = $nome_time_A;
                            $dbJogadorId = $dbPlayerMapA[$tempId] ?? 0;
                            break;
                        }
                    }
                    if ($idTime === 0) {
                        foreach ($escalacaoTime2 as $p) {
                            if ($tempId === (int)($p['id'] ?? 0)) {
                                $nomeJogador = $p['nome'] ?? '';
                                $idTime = $timeB_id;
                                $nomeTime = $nome_time_B;
                                $dbJogadorId = $dbPlayerMapB[$tempId] ?? 0;
                                break;
                            }
                        }
                    }

                    if ($idTime > 0) {
                        if ($dbJogadorId <= 0 && !empty($nomeJogador)) {
                            $dbJogadorId = $jogadorObj->idPorNomeClube($nomeJogador, $idTime, $tempId);
                        }

                        $rawMin = $ev['minutos'] ?? null;
                        $minutosVal = ($rawMin !== null && $rawMin !== '' && is_numeric($rawMin)) ? (int)$rawMin : null;
                        $tempoVal = (int)($ev['tempo'] ?? 1);
                        if ($minutosVal !== null && $minutosVal > 45) {
                            $tempoVal = 2;
                        }

                        $stmtEv->execute([
                            $idJogo,
                            $tempoVal,
                            $minutosVal,
                            $tipoEvento,
                            $dbJogadorId > 0 ? $dbJogadorId : 0,
                            mb_substr($nomeJogador, 0, 40),
                            $idTime,
                            $nomeTime
                        ]);
                    }
                }
            }
        }

        // Inserir Escalações apenas se vierem escalações no arquivo
        if (!empty($escalacaoTime1) || !empty($escalacaoTime2)) {
            $jogoClube->limparEscalacao($idJogo);
            $stmtEsc = $db->prepare("INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, id_jogador, nome_jogador, numero, posicao, titular, entrada_minuto, saida_minuto) VALUES (?,?,?,?,?,?,?,?,?,?)");

            foreach ($escalacaoTime1 as $p) {
                $pNome = trim((string)($p['nome'] ?? ''));
                if (empty($pNome)) continue;
                $tempId = (int)($p['id'] ?? 0);
                $finalId = $dbPlayerMapA[$tempId] ?? 0;

                $stmtEsc->execute([
                    $idJogo,
                    $timeA_id,
                    $nome_time_A,
                    $finalId > 0 ? $finalId : 0,
                    mb_substr($pNome, 0, 40),
                    isset($p['numero']) && is_numeric($p['numero']) ? (int)$p['numero'] : null,
                    $p['posicao'] ?? '',
                    isset($p['titular']) ? (int)$p['titular'] : 1,
                    isset($p['entrada_minuto']) && is_numeric($p['entrada_minuto']) ? (int)$p['entrada_minuto'] : null,
                    isset($p['saida_minuto']) && is_numeric($p['saida_minuto']) ? (int)$p['saida_minuto'] : null
                ]);
            }

            foreach ($escalacaoTime2 as $p) {
                $pNome = trim((string)($p['nome'] ?? ''));
                if (empty($pNome)) continue;
                $tempId = (int)($p['id'] ?? 0);
                $finalId = $dbPlayerMapB[$tempId] ?? 0;

                $stmtEsc->execute([
                    $idJogo,
                    $timeB_id,
                    $nome_time_B,
                    $finalId > 0 ? $finalId : 0,
                    mb_substr($pNome, 0, 40),
                    isset($p['numero']) && is_numeric($p['numero']) ? (int)$p['numero'] : null,
                    $p['posicao'] ?? '',
                    isset($p['titular']) ? (int)$p['titular'] : 1,
                    isset($p['entrada_minuto']) && is_numeric($p['entrada_minuto']) ? (int)$p['entrada_minuto'] : null,
                    isset($p['saida_minuto']) && is_numeric($p['saida_minuto']) ? (int)$p['saida_minuto'] : null
                ]);
            }
        }

        $results[] = ['filename' => $game['filename'] ?? '', 'success' => true, 'id' => $idJogo, 'action' => $actionLabel];
        $importedCount++;
    }

    $usuarioObj->atualizarAlteracao($_SESSION['user_id']);
    $db->commit();

    $spurious = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => true,
        'results'  => $results,
        'imported' => $importedCount,
        'skipped'  => $skippedCount,
        'debug'    => $spurious ?: null
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $spurious = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => 'Erro na transação: ' . $e->getMessage() . ($spurious ? ' | PHP: ' . strip_tags($spurious) : '')
    ]);
}
