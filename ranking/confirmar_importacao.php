<?php
// Suprimir notices/warnings do XDebug que corrompem a resposta JSON
error_reporting(E_ERROR | E_PARSE);
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

function json_fail($msg) {
    $spurious = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    // Inclui output espúrio no erro para facilitar debug
    echo json_encode(['success' => false, 'error' => $msg . ($spurious ? ' | PHP output: ' . strip_tags($spurious) : '')]);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    json_fail('Sem permissão. Você precisa estar logado para importar jogos do ranking.');
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
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();
$jogoObj  = new Jogo($db);
$jogadorObj = new Jogador($db);
$usuarioObj = new Usuario($db);

$results       = [];
$importedCount = 0;
$skippedCount  = 0;

try {
    $db->beginTransaction();

    foreach ($input['games'] as $game) {
        // Ignorar entradas de erro (sem timeA/timeB)
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
                'error'    => 'Seleção A ou B inválida ou data vazia.'
            ];
            $skippedCount++;
            continue;
        }

        // Buscar nomes dos países
        $stA = $db->prepare("SELECT nome FROM paises WHERE id = ?");
        $stA->execute([$timeA_id]);
        $nome_pais_A = $stA->fetchColumn() ?: ($game['time1_raw'] ?? '');

        $stB = $db->prepare("SELECT nome FROM paises WHERE id = ?");
        $stB->execute([$timeB_id]);
        $nome_pais_B = $stB->fetchColumn() ?: ($game['time2_raw'] ?? '');

        // Configurar jogo
        $jogoObj->timeA_id = $timeA_id;
        $jogoObj->timeB_id = $timeB_id;
        $jogoObj->data     = $game_date;
        $jogoObj->estadio  = isset($game['estadio'])    ? (string)$game['estadio']    : '';
        $jogoObj->campeonato = isset($game['campeonato']) ? (int)$game['campeonato']  : 10;
        $jogoObj->fase     = isset($game['fase'])        ? (int)$game['fase']         : 0;

        // Placar normal
        $placarTime1 = isset($game['placarTime1']) ? (int)$game['placarTime1'] : 0;
        $placarTime2 = isset($game['placarTime2']) ? (int)$game['placarTime2'] : 0;

        // Prorrogação: só conta se >= 0
        $placarProrr1 = (isset($game['placarProrrogacaoTime1']) && (int)$game['placarProrrogacaoTime1'] >= 0) ? (int)$game['placarProrrogacaoTime1'] : -1;
        $placarProrr2 = (isset($game['placarProrrogacaoTime2']) && (int)$game['placarProrrogacaoTime2'] >= 0) ? (int)$game['placarProrrogacaoTime2'] : -1;

        $jogoObj->timeA_gols = $placarTime1 + ($placarProrr1 >= 0 ? $placarProrr1 : 0);
        $jogoObj->timeB_gols = $placarTime2 + ($placarProrr2 >= 0 ? $placarProrr2 : 0);

        // Pênaltis: null se não houver disputa
        $jogoObj->timeA_penaltis = (isset($game['placarPenaltisTime1']) && (int)$game['placarPenaltisTime1'] >= 0) ? (int)$game['placarPenaltisTime1'] : null;
        $jogoObj->timeB_penaltis = (isset($game['placarPenaltisTime2']) && (int)$game['placarPenaltisTime2'] >= 0) ? (int)$game['placarPenaltisTime2'] : null;

        // Determinar se é novo ou duplicado
        $isDuplicate = !empty($game['is_duplicate']) && !empty($game['existing_id']);

        if ($isDuplicate) {
            // Atualizar jogo existente
            $idJogo = (int)$game['existing_id'];
            $jogoObj->atualizarPlacarJogo($idJogo);
            $actionLabel = 'Atualizado';
        } else {
            // Inserir novo jogo
            if (!$jogoObj->importar()) {
                $results[] = [
                    'filename' => $game['filename'] ?? '',
                    'success'  => false,
                    'error'    => 'Erro ao inserir no banco.'
                ];
                $skippedCount++;
                continue;
            }
            $idJogo = ($db->lastInsertId() != 0) ? (int)$db->lastInsertId() : $jogoObj->getMatchId();
            $actionLabel = 'Importado';
        }

        // Eventos
        $log_eventos  = [];
        $escalacaoTime1 = $game['escalacaoTime1'] ?? [];
        $escalacaoTime2 = $game['escalacaoTime2'] ?? [];
        $raw_eventos    = $game['eventos']         ?? [];

        foreach ($raw_eventos as $single_event) {
            $tipoEvento = 0;
            switch ($single_event['tipoEvento'] ?? '') {
                case 'amarelo':     $tipoEvento = 2; break;
                case 'vermelho':    $tipoEvento = 3; break;
                case 'gol':         $tipoEvento = 1; break;
                case 'golContra':   $tipoEvento = 4; break;
                case 'golAnuladoVAR':
                    array_pop($log_eventos);
                    break;
            }
            if ($tipoEvento > 0) {
                $nomeJogador = '';
                $idTime      = 0;
                $tempId      = (int)($single_event['idJogador'] ?? 0);

                foreach ($escalacaoTime1 as $p) {
                    if ($tempId === (int)($p['id'] ?? 0)) { $nomeJogador = $p['nome'] ?? ''; $idTime = $timeA_id; break; }
                }
                if ($idTime === 0) {
                    foreach ($escalacaoTime2 as $p) {
                        if ($tempId === (int)($p['id'] ?? 0)) { $nomeJogador = $p['nome'] ?? ''; $idTime = $timeB_id; break; }
                    }
                }

                if ($idTime > 0) {
                    $idJogador = $jogadorObj->idPorNomePais($nomeJogador, $idTime, $tempId);
                    $rawMin = $single_event['minutos'] ?? null;
                    $minutosVal = ($rawMin !== null && $rawMin !== '' && is_numeric($rawMin)) ? (int)$rawMin : null;
                    $log_eventos[] = [
                        'tempo'       => $single_event['tempo'] ?? 1,
                        'minutos'     => $minutosVal,
                        'tipo'        => $tipoEvento,
                        'idJogador'   => $idJogador > 0 ? $idJogador : 0,
                        'nomeJogador' => mb_substr($nomeJogador, 0, 40),
                        'idTime'      => $idTime
                    ];
                }
            }
        }

        // Importar Eventos apenas se vieram eventos no arquivo
        if (!empty($log_eventos)) {
            $jogoObj->limparEventos($idJogo);
            $jogoObj->importarEventos($log_eventos, $idJogo);
        }

        // Processar e Importar Escalação
        $log_escalacao = [];
        foreach ($escalacaoTime1 as $p) {
            $pNome = trim((string)($p['nome'] ?? ''));
            if (empty($pNome)) continue;
            $tempId = (int)($p['id'] ?? 0);
            $idJog = $jogadorObj->idPorNomePais($pNome, $timeA_id, $tempId);
            $log_escalacao[] = [
                'id_time' => $timeA_id,
                'nome_time' => $nome_pais_A,
                'posicao' => $p['posicao'] ?? '',
                'numero' => isset($p['numero']) && is_numeric($p['numero']) ? (int)$p['numero'] : null,
                'id_jogador' => $idJog > 0 ? $idJog : null,
                'nome_jogador' => mb_substr($pNome, 0, 40),
                'titular' => isset($p['titular']) ? (int)$p['titular'] : 1,
                'entrada_minuto' => isset($p['entrada_minuto']) && is_numeric($p['entrada_minuto']) ? (int)$p['entrada_minuto'] : null,
                'saida_minuto' => isset($p['saida_minuto']) && is_numeric($p['saida_minuto']) ? (int)$p['saida_minuto'] : null
            ];
        }

        foreach ($escalacaoTime2 as $p) {
            $pNome = trim((string)($p['nome'] ?? ''));
            if (empty($pNome)) continue;
            $tempId = (int)($p['id'] ?? 0);
            $idJog = $jogadorObj->idPorNomePais($pNome, $timeB_id, $tempId);
            $log_escalacao[] = [
                'id_time' => $timeB_id,
                'nome_time' => $nome_pais_B,
                'posicao' => $p['posicao'] ?? '',
                'numero' => isset($p['numero']) && is_numeric($p['numero']) ? (int)$p['numero'] : null,
                'id_jogador' => $idJog > 0 ? $idJog : null,
                'nome_jogador' => mb_substr($pNome, 0, 40),
                'titular' => isset($p['titular']) ? (int)$p['titular'] : 1,
                'entrada_minuto' => isset($p['entrada_minuto']) && is_numeric($p['entrada_minuto']) ? (int)$p['entrada_minuto'] : null,
                'saida_minuto' => isset($p['saida_minuto']) && is_numeric($p['saida_minuto']) ? (int)$p['saida_minuto'] : null
            ];
        }

        // Importar Escalação apenas se vieram escalações no arquivo
        if (!empty($log_escalacao)) {
            $jogoObj->limparEscalacao($idJogo);
            $jogoObj->importarEscalacao($log_escalacao, $idJogo);
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
        'debug'    => $spurious ?: null   // remove após estabilizar
    ]);

} catch (Exception $e) {
    $db->rollBack();
    $spurious = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => 'Erro na transação: ' . $e->getMessage() . ($spurious ? ' | PHP: ' . strip_tags($spurious) : '')
    ]);
}
