<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_admin = $is_logged_in && ((int)($_SESSION['admin_status'] ?? 0) === 1);
$match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;

if (!$is_logged_in || ($match_id > 0 && !$is_admin)) {
    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        header('Content-Type: application/json');
        $msg = !$is_logged_in ? 'Você precisa estar logado para salvar partidas.' : 'Apenas administradores podem editar partidas existentes do Ranking.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    die('Acesso não autorizado.');
}

if ($_POST) {
    $db->beginTransaction();
    try {
        $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
        
        // Propriedades do Jogo de Seleção
        $jogo->id = $match_id;
        $jogo->timeA_id = (isset($_POST['timeA_id']) && $_POST['timeA_id'] !== "" && (int)$_POST['timeA_id'] > 0) ? (int)$_POST['timeA_id'] : 0;
        $jogo->timeA_gols = isset($_POST['timeA_gols']) ? (int)$_POST['timeA_gols'] : 0;
        $jogo->timeB_id = (isset($_POST['timeB_id']) && $_POST['timeB_id'] !== "" && (int)$_POST['timeB_id'] > 0) ? (int)$_POST['timeB_id'] : 0;
        $jogo->timeB_gols = isset($_POST['timeB_gols']) ? (int)$_POST['timeB_gols'] : 0;
        $jogo->timeA_penaltis = (isset($_POST['timeA_penaltis']) && trim((string)$_POST['timeA_penaltis']) !== "") ? (int)$_POST['timeA_penaltis'] : null;
        $jogo->timeB_penaltis = (isset($_POST['timeB_penaltis']) && trim((string)$_POST['timeB_penaltis']) !== "") ? (int)$_POST['timeB_penaltis'] : null;
        $jogo->data = !empty($_POST['data']) ? $_POST['data'] : date('Y-m-d');
        $jogo->campeonato = (isset($_POST['campeonato']) && (int)$_POST['campeonato'] > 0) ? (int)$_POST['campeonato'] : 10;
        $jogo->estadio = trim((string)($_POST['estadio'] ?? ''));
        $jogo->fase = isset($_POST['fase']) ? (int)$_POST['fase'] : 0;

        // Recuperar nomes dos países
        $stA = $db->prepare("SELECT nome FROM paises WHERE id = ?");
        $stA->execute([$jogo->timeA_id]);
        $nome_pais_A = $stA->fetchColumn() ?: ($_POST['timeA_nome'] ?? '');

        $stB = $db->prepare("SELECT nome FROM paises WHERE id = ?");
        $stB->execute([$jogo->timeB_id]);
        $nome_pais_B = $stB->fetchColumn() ?: ($_POST['timeB_nome'] ?? '');

        if ($match_id > 0) {
            $jogo->atualizar();
        } else {
            $jogo->importar();
            $match_id = (int)$db->lastInsertId();
            if ($match_id <= 0) {
                $match_id = (int)$jogo->getMatchId();
            }
        }

        if ($match_id <= 0) {
            throw new Exception("Não foi possível obter o ID da partida.");
        }

        // Helper para buscar nome do jogador se ausente
        $getPlayerName = function($db, $id) {
            if (!$id || $id <= 0) return '';
            $st = $db->prepare("SELECT Nome FROM jogador WHERE ID = ?");
            $st->execute([$id]);
            return $st->fetchColumn() ?: '';
        };

        // Atualizar Eventos apenas se o payload de eventos foi enviado
        if (isset($_POST['events']) && is_array($_POST['events'])) {
            $jogo->limparEventos($match_id);
            
            $queryEv = "INSERT INTO jogos_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time) VALUES (?,?,?,?,?,?,?)";
            $stmtEv = $db->prepare($queryEv);

            foreach ($_POST['events'] as $ev) {
                $side = $ev['side'] ?? 'A';
                $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                $rawMin = isset($ev['minutos']) ? trim((string)$ev['minutos']) : '';
                $minutos = ($rawMin !== '' && is_numeric($rawMin)) ? (int)$rawMin : null;
                $tempo = ($minutos !== null && $minutos > 45) ? 2 : 1;

                $rawPId = isset($ev['id_jogador']) ? (int)$ev['id_jogador'] : 0;
                $pId = ($rawPId > 0) ? $rawPId : 0;
                $pNome = trim((string)($ev['nome_jogador'] ?? ''));

                if ($pId > 0 && empty($pNome)) {
                    $pNome = $getPlayerName($db, $pId);
                }

                if (!empty($pNome) || $pId > 0) {
                    $stmtEv->execute([
                        $match_id,
                        $tempo,
                        $minutos,
                        isset($ev['tipo']) ? (int)$ev['tipo'] : 1,
                        $pId,
                        $pNome,
                        $tId ? (int)$tId : 0
                    ]);
                }
            }
        }

        // Atualizar Escalações apenas se o payload de lineup foi enviado
        if (isset($_POST['lineup']) && is_array($_POST['lineup'])) {
            $jogo->limparEscalacao($match_id);
            
            $queryEsc = "INSERT INTO jogos_escalacao (id_jogo, id_time, nome_time, id_jogador, nome_jogador, numero, posicao, titular, entrada_minuto, saida_minuto) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $stmtEsc = $db->prepare($queryEsc);

            foreach (['A', 'B'] as $side) {
                if (isset($_POST['lineup'][$side]) && is_array($_POST['lineup'][$side])) {
                    $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                    $tNome = ($side == 'A') ? $nome_pais_A : $nome_pais_B;
                    
                    foreach ($_POST['lineup'][$side] as $p) {
                        $rawPId = isset($p['id_jogador']) ? (int)$p['id_jogador'] : 0;
                        $pId = ($rawPId > 0) ? $rawPId : 0;
                        $pNome = trim((string)($p['nome_jogador'] ?? ''));

                        if ($pId > 0 && empty($pNome)) {
                            $pNome = $getPlayerName($db, $pId);
                        }

                        // Ignorar linhas completamente vazias
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

        // Commit da transação se tudo deu certo
        $db->commit();

        if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'match_id' => $match_id]);
            exit;
        }

        header("Location: /ranking/match_info.php?match_id=" . $match_id);
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
