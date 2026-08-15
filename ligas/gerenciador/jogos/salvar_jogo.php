<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

if ($_POST) {
    $match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;
    
    // Set Jogo properties
    $jogo->id = $match_id;
    $jogo->timeA_id = (isset($_POST['timeA_id']) && $_POST['timeA_id'] !== "") ? $_POST['timeA_id'] : 0;
    $jogo->timeA_nome = $_POST['timeA_nome'];
    $jogo->timeA_gols = $_POST['timeA_gols'];
    $jogo->timeB_id = (isset($_POST['timeB_id']) && $_POST['timeB_id'] !== "") ? $_POST['timeB_id'] : 0;
    $jogo->timeB_nome = $_POST['timeB_nome'];
    $jogo->timeB_gols = $_POST['timeB_gols'];
    $jogo->timeA_penaltis = (isset($_POST['timeA_penaltis']) && $_POST['timeA_penaltis'] !== "") ? $_POST['timeA_penaltis'] : null;
    $jogo->timeB_penaltis = (isset($_POST['timeB_penaltis']) && $_POST['timeB_penaltis'] !== "") ? $_POST['timeB_penaltis'] : null;
    $jogo->data = $_POST['data'];
    $jogo->competicao_id = (isset($_POST['competicao_id']) && $_POST['competicao_id'] !== "") ? $_POST['competicao_id'] : 0;
    $jogo->competicao_tipo = $_POST['competicao_tipo'];
    $jogo->competicao_tipo = $_POST['competicao_tipo'];
    $jogo->estadio_id = (isset($_POST['estadio_id']) && $_POST['estadio_id'] !== "") ? $_POST['estadio_id'] : 0;
    $jogo->estadio_nome = $_POST['estadio_nome'];
    $jogo->fase = $_POST['fase'];

    if ($match_id > 0) {
        // Preserve original owner
        $currentMatch = $jogo->getSingleMatchInfo($match_id);
        $jogo->dono = $currentMatch['dono'];
        $jogo->atualizar();
    } else {
        $jogo->dono = $_SESSION['user_id'];
        $jogo->importar(); // This will insert since id is 0
        $match_id = $db->lastInsertId();
    }

    if ($match_id > 0) {
        // Helper to fetch player name if missing
        $getPlayerName = function($db, $id) {
            if (!$id) return '';
            $st = $db->prepare("SELECT Nome FROM jogador WHERE id = ?");
            $st->execute([$id]);
            return $st->fetchColumn() ?: '';
        };

        // Clear existing related data
        $jogo->limparEventos($match_id);
        $jogo->limparEscalacao($match_id);

        // Insert Events
        if (isset($_POST['events']) && is_array($_POST['events'])) {
            $queryEv = "INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?,?,?,?,?,?,?,?)";
            $stmtEv = $db->prepare($queryEv);
            foreach ($_POST['events'] as $ev) {
                $side = $ev['side'];
                $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                $tNome = ($side == 'A') ? $jogo->timeA_nome : $jogo->timeB_nome;
                $tempo = ($ev['minutos'] > 45) ? 2 : 1; // Basic assumption

                $pId = (!empty($ev['id_jogador'])) ? $ev['id_jogador'] : 0;
                $pNome = $ev['nome_jogador'] ?? '';
                if ($pId > 0 && empty($pNome)) {
                    $pNome = $getPlayerName($db, $pId);
                }

                $stmtEv->execute([
                    $match_id,
                    $tempo,
                    $ev['minutos'] ?? 0,
                    $ev['tipo'] ?? 1,
                    $pId,
                    $pNome,
                    $tId ? $tId : 0,
                    $tNome
                ]);
            }
        }

        // Insert Lineups
        foreach (['A', 'B'] as $side) {
            if (isset($_POST['lineup'][$side]) && is_array($_POST['lineup'][$side])) {
                $queryEsc = "INSERT INTO jogos_clube_escalacao (id_partida, id_time, id_jogador, nome_jogador, numero, posicao, titular, entrada_minuto, saida_minuto) VALUES (?,?,?,?,?,?,?,?,?)";
                $stmtEsc = $db->prepare($queryEsc);
                $tId = ($side == 'A') ? $jogo->timeA_id : $jogo->timeB_id;
                
                foreach ($_POST['lineup'][$side] as $p) {
                    $pId = (!empty($p['id_jogador'])) ? $p['id_jogador'] : 0;
                    $pNome = $p['nome_jogador'] ?? '';
                    if ($pId > 0 && empty($pNome)) {
                        $pNome = $getPlayerName($db, $pId);
                    }

                    $stmtEsc->execute([
                        $match_id,
                        $tId ? $tId : 0,
                        $pId,
                        $pNome,
                        $p['num'] ?? '',
                        $p['pos'] ?? '',
                        isset($p['titular']) ? 1 : 0,
                        (!empty($p['sub_in'])) ? $p['sub_in'] : null,
                        (!empty($p['sub_out'])) ? $p['sub_out'] : null
                    ]);
                }
            }
        }

        if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'match_id' => $match_id]);
            exit;
        }

        header("Location: view.php?match_id=" . $match_id);
        exit;
    } else {
        if(isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar a partida.']);
            exit;
        }
        echo "Erro ao salvar a partida.";
    }
}
