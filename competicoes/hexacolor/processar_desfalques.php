<?php
// Function to process player cards, injuries, and suspensions after a match simulation
function processarPosJogo($db, $idCompeticao, $idPartida, $hylFile, $hyjFile, $suspensosAntesPartida) {
    // 1. Obter opções de suspensão da competição
    $stmtOpt = $db->prepare("SELECT suspensao FROM competicao_opcoes WHERE id_competicao = :idComp LIMIT 1");
    $stmtOpt->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtOpt->execute();
    $options = $stmtOpt->fetch(PDO::FETCH_ASSOC);
    $criterioSuspensao = isset($options['suspensao']) ? (int)$options['suspensao'] : 0; // 0=apenas vermelho, 1=2 amarelos, 2=3 amarelos

    // Definir limite de cartões amarelos para suspensão
    $limiteAmarelos = 0;
    if ($criterioSuspensao == 1) {
        $limiteAmarelos = 2;
    } elseif ($criterioSuspensao == 2) {
        $limiteAmarelos = 3;
    }

    // 2. Decrementar jogos de suspensão dos jogadores que cumpriram nesta rodada
    if (!empty($suspensosAntesPartida)) {
        foreach ($suspensosAntesPartida as $pId) {
            $stmtGet = $db->prepare("SELECT jogos_restantes, suspenso FROM competicao_suspensos WHERE id_competicao = :idComp AND id_jogador = :idJog LIMIT 1");
            $stmtGet->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
            $stmtGet->bindValue(':idJog', $pId, PDO::PARAM_INT);
            $stmtGet->execute();
            $rowSus = $stmtGet->fetch(PDO::FETCH_ASSOC);
            if ($rowSus) {
                $restantes = (int)$rowSus['jogos_restantes'] - 1;
                $suspenso = ($restantes > 0) ? 1 : 0;
                if ($restantes < 0) $restantes = 0;

                $stmtUp = $db->prepare("UPDATE competicao_suspensos SET jogos_restantes = :restantes, suspenso = :suspenso WHERE id_competicao = :idComp AND id_jogador = :idJog");
                $stmtUp->bindValue(':restantes', $restantes, PDO::PARAM_INT);
                $stmtUp->bindValue(':suspenso', $suspenso, PDO::PARAM_INT);
                $stmtUp->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                $stmtUp->bindValue(':idJog', $pId, PDO::PARAM_INT);
                $stmtUp->execute();
            }
        }
    }

    // 3. Processar o arquivo JSON da partida (.hyj) para cartões
    if (file_exists($hyjFile)) {
        $json = json_decode(file_get_contents($hyjFile));
        if ($json) {
            $players = [];
            if (isset($json->time1->jogadores)) {
                $players = array_merge($players, $json->time1->jogadores);
            }
            if (isset($json->time2->jogadores)) {
                $players = array_merge($players, $json->time2->jogadores);
            }

            foreach ($players as $pj) {
                $pId = (int)$pj->idJogador;
                if ($pId <= 0) continue;

                $amarelosPartida = (int)$pj->amarelos;
                $vermelhosPartida = (int)$pj->vermelhos;

                if ($amarelosPartida > 0 || $vermelhosPartida > 0) {
                    // Obter registro atual do jogador
                    $stmtGet = $db->prepare("SELECT cartoes_amarelos, suspenso, jogos_restantes FROM competicao_suspensos WHERE id_competicao = :idComp AND id_jogador = :idJog LIMIT 1");
                    $stmtGet->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                    $stmtGet->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtGet->execute();
                    $rowSus = $stmtGet->fetch(PDO::FETCH_ASSOC);

                    if (!$rowSus) {
                        // Inserir registro inicial
                        $stmtIns = $db->prepare("INSERT INTO competicao_suspensos (id_competicao, id_jogador, cartoes_amarelos, suspenso, jogos_restantes) VALUES (:idComp, :idJog, 0, 0, 0)");
                        $stmtIns->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                        $stmtIns->bindValue(':idJog', $pId, PDO::PARAM_INT);
                        $stmtIns->execute();
                        $currentAmarelos = 0;
                        $currentSuspenso = 0;
                        $currentRestantes = 0;
                    } else {
                        $currentAmarelos = (int)$rowSus['cartoes_amarelos'];
                        $currentSuspenso = (int)$rowSus['suspenso'];
                        $currentRestantes = (int)$rowSus['jogos_restantes'];
                    }

                    $novoAmarelos = $currentAmarelos + $amarelosPartida;
                    $novoSuspenso = $currentSuspenso;
                    $novoRestantes = $currentRestantes;

                    // Lógica de suspensão por amarelos acumulados
                    if ($limiteAmarelos > 0 && $novoAmarelos >= $limiteAmarelos) {
                        $novoSuspenso = 1;
                        $novoRestantes = max($novoRestantes, 1);
                        $novoAmarelos = 0; // zerar após suspensão por acúmulo
                    }

                    // Lógica de suspensão por cartão vermelho na partida
                    if ($vermelhosPartida > 0) {
                        $novoSuspenso = 1;
                        $novoRestantes = max($novoRestantes, 1);
                        if ($amarelosPartida >= 2) {
                            $novoAmarelos = max(0, $novoAmarelos - 2);
                        }
                    }

                    // Salvar de volta no banco
                    $stmtUp = $db->prepare("UPDATE competicao_suspensos SET cartoes_amarelos = :amarelos, suspenso = :suspenso, jogos_restantes = :restantes WHERE id_competicao = :idComp AND id_jogador = :idJog");
                    $stmtUp->bindValue(':amarelos', $novoAmarelos, PDO::PARAM_INT);
                    $stmtUp->bindValue(':suspenso', $novoSuspenso, PDO::PARAM_INT);
                    $stmtUp->bindValue(':restantes', $novoRestantes, PDO::PARAM_INT);
                    $stmtUp->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                    $stmtUp->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtUp->execute();
                }
            }
        }
    }

    // 4. Processar o arquivo XML da partida (.hyl) para lesões
    if (file_exists($hylFile)) {
        $xml = json_decode(file_get_contents($hylFile));
        if ($xml && isset($xml->eventos)) {
            foreach ($xml->eventos as $ev) {
                if ($ev->tipoEvento == "lesao") {
                    $pId = (int)$ev->idJogador;
                    $duracao = (int)$ev->duracao; // em dias
                    if ($pId > 0 && $duracao > 0) {
                        // Atualizar a data limite de lesão na tabela jogador
                        $stmtLes = $db->prepare("UPDATE jogador SET lesionado_ate = DATE_ADD(CURDATE(), INTERVAL :duracao DAY) WHERE ID = :idJog");
                        $stmtLes->bindValue(':duracao', $duracao, PDO::PARAM_INT);
                        $stmtLes->bindValue(':idJog', $pId, PDO::PARAM_INT);
                        $stmtLes->execute();
                    }
                }
            }
        }
    }
}
