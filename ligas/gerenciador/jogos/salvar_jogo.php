<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

if ($_POST) {
    $db->beginTransaction();
    try {
        $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        
        // Set Jogo properties
        $jogo->id = $match_id;
        $jogo->timeA_id = (isset($_POST['timeA_id']) && $_POST['timeA_id'] !== "" && (int)$_POST['timeA_id'] > 0) ? (int)$_POST['timeA_id'] : 0;
        $jogo->timeA_nome = trim((string)($_POST['timeA_nome'] ?? ''));
        $jogo->timeA_gols = isset($_POST['timeA_gols']) ? (int)$_POST['timeA_gols'] : 0;
        $jogo->timeB_id = (isset($_POST['timeB_id']) && $_POST['timeB_id'] !== "" && (int)$_POST['timeB_id'] > 0) ? (int)$_POST['timeB_id'] : 0;
        $jogo->timeB_nome = trim((string)($_POST['timeB_nome'] ?? ''));
        $jogo->timeB_gols = isset($_POST['timeB_gols']) ? (int)$_POST['timeB_gols'] : 0;
        $jogo->timeA_penaltis = (isset($_POST['timeA_penaltis']) && trim((string)$_POST['timeA_penaltis']) !== "") ? (int)$_POST['timeA_penaltis'] : null;
        $jogo->timeB_penaltis = (isset($_POST['timeB_penaltis']) && trim((string)$_POST['timeB_penaltis']) !== "") ? (int)$_POST['timeB_penaltis'] : null;
        $jogo->data = !empty($_POST['data']) ? $_POST['data'] : date('Y-m-d');
        $jogo->competicao_id = (isset($_POST['competicao_id']) && (int)$_POST['competicao_id'] > 0) ? (int)$_POST['competicao_id'] : 0;
        $jogo->competicao_tipo = isset($_POST['competicao_tipo']) ? (int)$_POST['competicao_tipo'] : 0;
        $jogo->estadio_id = (isset($_POST['estadio_id']) && (int)$_POST['estadio_id'] > 0) ? (int)$_POST['estadio_id'] : 0;
        $jogo->estadio_nome = trim((string)($_POST['estadio_nome'] ?? ''));
        $jogo->fase = isset($_POST['fase']) ? (int)$_POST['fase'] : 0;

        // Auto-fetch club names if missing but ID exists
        if ($jogo->timeA_id > 0 && empty($jogo->timeA_nome)) {
            $stA = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
            $stA->execute([$jogo->timeA_id]);
            $jogo->timeA_nome = $stA->fetchColumn() ?: '';
        }
        if ($jogo->timeB_id > 0 && empty($jogo->timeB_nome)) {
            $stB = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
            $stB->execute([$jogo->timeB_id]);
            $jogo->timeB_nome = $stB->fetchColumn() ?: '';
        }

        if ($match_id > 0) {
            // Preserve original owner
            $currentMatch = $jogo->getSingleMatchInfo($match_id);
            $jogo->dono = isset($currentMatch['dono']) ? $currentMatch['dono'] : ($_SESSION['user_id'] ?? 0);
            $jogo->atualizar();
        } else {
            $jogo->dono = $_SESSION['user_id'] ?? 0;
            $jogo->importar();
            $match_id = (int)$db->lastInsertId();
        }

        if ($match_id <= 0) {
            throw new Exception("Não foi possível obter o ID da partida.");
        }

        // Helper to fetch player name if missing
        $getPlayerName = function($db, $id) {
            if (!$id || $id <= 0) return '';
            $st = $db->prepare("SELECT Nome FROM jogador WHERE ID = ?");
            $st->execute([$id]);
            return $st->fetchColumn() ?: '';
        };

        // Update Events only if events payload was submitted
        if (isset($_POST['events']) && is_array($_POST['events'])) {
            $jogo->limparEventos($match_id);
            
            $queryEv = "INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?,?,?,?,?,?,?,?)";
            $stmtEv = $db->prepare($queryEv);
            foreach ($_POST['events'] as $ev) {
                $side = $ev['side'] ?? 'A';
                $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                $tNome = ($side == 'A') ? $jogo->timeA_nome : $jogo->timeB_nome;
                $rawMin = isset($ev['minutos']) ? trim((string)$ev['minutos']) : '';
                $minutos = ($rawMin !== '' && is_numeric($rawMin)) ? (int)$rawMin : null;
                $tempo = ($minutos !== null && $minutos > 45) ? 2 : 1;

                $pId = (!empty($ev['id_jogador']) && (int)$ev['id_jogador'] > 0) ? (int)$ev['id_jogador'] : -1;
                $pNome = trim((string)($ev['nome_jogador'] ?? ''));
                if ($pId > 0 && empty($pNome)) {
                    $pNome = $getPlayerName($db, $pId);
                }

                // Only insert if event has at least a player name or valid player ID
                if (!empty($pNome) || $pId > 0) {
                    $stmtEv->execute([
                        $match_id,
                        $tempo,
                        $minutos,
                        isset($ev['tipo']) ? (int)$ev['tipo'] : 1,
                        $pId,
                        $pNome,
                        $tId ? (int)$tId : 0,
                        $tNome
                    ]);
                }
            }
        }

        // Update Lineups only if lineup payload was submitted
        if (isset($_POST['lineup']) && is_array($_POST['lineup'])) {
            $jogo->limparEscalacao($match_id);
            
            $queryEsc = "INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, id_jogador, nome_jogador, numero, posicao, titular, entrada_minuto, saida_minuto) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $stmtEsc = $db->prepare($queryEsc);

            foreach (['A', 'B'] as $side) {
                if (isset($_POST['lineup'][$side]) && is_array($_POST['lineup'][$side])) {
                    $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                    $tNome = ($side == 'A') ? $jogo->timeA_nome : $jogo->timeB_nome;
                    
                    foreach ($_POST['lineup'][$side] as $p) {
                        $pId = (!empty($p['id_jogador']) && (int)$p['id_jogador'] > 0) ? (int)$p['id_jogador'] : -1;
                        $pNome = trim((string)($p['nome_jogador'] ?? ''));
                        if ($pId > 0 && empty($pNome)) {
                            $pNome = $getPlayerName($db, $pId);
                        }

                        // Ignore completely empty rows
                        if (empty($pNome) && $pId <= 0 && empty($p['num'])) {
                            continue;
                        }

                        $pNum = (isset($p['num']) && trim((string)$p['num']) !== '' && is_numeric($p['num'])) ? (int)$p['num'] : null;
                        $pSubIn = (isset($p['sub_in']) && trim((string)$p['sub_in']) !== '' && is_numeric($p['sub_in'])) ? (int)$p['sub_in'] : null;
                        $pSubOut = (isset($p['sub_out']) && trim((string)$p['sub_out']) !== '' && is_numeric($p['sub_out'])) ? (int)$p['sub_out'] : null;

                        $stmtEsc->execute([
                            $match_id,
                            $tId ? (int)$tId : 0,
                            $tNome,
                            $pId,
                            $pNome,
                            $pNum,
                            $p['pos'] ?? '',
                            isset($p['titular']) ? 1 : 0,
                            $pSubIn,
                            $pSubOut
                        ]);
                    }
                }
            }
        }

        // Commit transaction if all succeeded
        $db->commit();

        if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'match_id' => $match_id]);
            exit;
        }

        header("Location: view.php?match_id=" . $match_id);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        echo "Erro ao salvar a partida: " . $e->getMessage();
    }
}
