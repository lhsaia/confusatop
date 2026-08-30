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

    // 3. Processar cartões e lesões a partir do arquivo JSON da partida (.hyj)
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
                $pId = (int)($pj->idJogador ?? 0);
                if ($pId == 0) continue;

                $amarelosPartida = (int)($pj->amarelos ?? 0);
                $vermelhosPartida = (int)($pj->vermelhos ?? 0);
                $temLesao = (int)($pj->lesao ?? 0);
                $duracaoLesao = (int)($pj->duracaoLesao ?? 0);

                // --- PROCESSAR CARTÕES E SUSPENSÕES ---
                if ($amarelosPartida > 0 || $vermelhosPartida > 0) {
                    $stmtGet = $db->prepare("SELECT cartoes_amarelos, suspenso, jogos_restantes FROM competicao_suspensos WHERE id_competicao = :idComp AND id_jogador = :idJog LIMIT 1");
                    $stmtGet->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                    $stmtGet->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtGet->execute();
                    $rowSus = $stmtGet->fetch(PDO::FETCH_ASSOC);

                    if (!$rowSus) {
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

                    // Salvar suspensão/cartões no banco
                    $stmtUp = $db->prepare("UPDATE competicao_suspensos SET cartoes_amarelos = :amarelos, suspenso = :suspenso, jogos_restantes = :restantes WHERE id_competicao = :idComp AND id_jogador = :idJog");
                    $stmtUp->bindValue(':amarelos', $novoAmarelos, PDO::PARAM_INT);
                    $stmtUp->bindValue(':suspenso', $novoSuspenso, PDO::PARAM_INT);
                    $stmtUp->bindValue(':restantes', $novoRestantes, PDO::PARAM_INT);
                    $stmtUp->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                    $stmtUp->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtUp->execute();
                }

                // --- PROCESSAR LESÕES ---
                if ($temLesao == 1 || $duracaoLesao > 0) {
                    $duracao = ($duracaoLesao > 0) ? $duracaoLesao : 7;

                    // Atualiza tabela jogador global
                    $stmtLes = $db->prepare("UPDATE jogador SET lesionado_ate = DATE_ADD(CURDATE(), INTERVAL :duracao DAY) WHERE ID = :idJog");
                    $stmtLes->bindValue(':duracao', $duracao, PDO::PARAM_INT);
                    $stmtLes->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtLes->execute();

                    // Grava/atualiza também na tabela competicao_suspensos
                    $stmtGetLes = $db->prepare("SELECT 1 FROM competicao_suspensos WHERE id_competicao = :idComp AND id_jogador = :idJog LIMIT 1");
                    $stmtGetLes->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                    $stmtGetLes->bindValue(':idJog', $pId, PDO::PARAM_INT);
                    $stmtGetLes->execute();
                    if (!$stmtGetLes->fetch()) {
                        $stmtInsLes = $db->prepare("INSERT INTO competicao_suspensos (id_competicao, id_jogador, cartoes_amarelos, suspenso, jogos_restantes, lesionado_ate) VALUES (:idComp, :idJog, 0, 0, 0, DATE_ADD(CURDATE(), INTERVAL :duracao DAY))");
                        $stmtInsLes->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                        $stmtInsLes->bindValue(':idJog', $pId, PDO::PARAM_INT);
                        $stmtInsLes->bindValue(':duracao', $duracao, PDO::PARAM_INT);
                        $stmtInsLes->execute();
                    } else {
                        $stmtUpdLes = $db->prepare("UPDATE competicao_suspensos SET lesionado_ate = DATE_ADD(CURDATE(), INTERVAL :duracao DAY) WHERE id_competicao = :idComp AND id_jogador = :idJog");
                        $stmtUpdLes->bindValue(':duracao', $duracao, PDO::PARAM_INT);
                        $stmtUpdLes->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
                        $stmtUpdLes->bindValue(':idJog', $pId, PDO::PARAM_INT);
                        $stmtUpdLes->execute();
                    }
                }
            }
        }
    }

    // 5. Gravar eventos detalhados na tabela unificada jogos_clube_eventos
    if (file_exists($hyjFile)) {
        $json = json_decode(file_get_contents($hyjFile));
        if ($json && isset($json->lances)) {
            // Limpa eventos anteriores desta partida para evitar duplicações
            $stmtDelEv = $db->prepare("DELETE FROM jogos_clube_eventos WHERE id_jogo = :idJogo");
            $stmtDelEv->bindValue(':idJogo', $idPartida, PDO::PARAM_INT);
            $stmtDelEv->execute();

            $stmtInsEv = $db->prepare("INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json->lances as $lance) {
                $minuto = isset($lance->minuto) ? (int)$lance->minuto : 0;
                $tempo = ($minuto <= 45) ? 1 : 2;
                $tipo = isset($lance->tipo) ? $lance->tipo : '';
                $idJog = isset($lance->idJogador) ? (int)$lance->idJogador : 0;
                $nomeJog = isset($lance->nomeJogador) ? $lance->nomeJogador : '';
                $idTm = isset($lance->idTime) ? (int)$lance->idTime : 0;
                $nomeTm = isset($lance->nomeTime) ? $lance->nomeTime : '';

                if (!empty($tipo)) {
                    $stmtInsEv->execute([$idPartida, $tempo, $minuto, $tipo, $idJog, $nomeJog, $idTm, $nomeTm]);
                }
            }
        }

        // 6. Gravar escalações na tabela unificada jogos_clube_escalacao
        if ($json) {
            $stmtDelEsc = $db->prepare("DELETE FROM jogos_clube_escalacao WHERE id_partida = :idPartida");
            $stmtDelEsc->bindValue(':idPartida', $idPartida, PDO::PARAM_INT);
            $stmtDelEsc->execute();

            $stmtInsEsc = $db->prepare("INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_tempo, entrada_minuto, saida_tempo, saida_minuto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $timesEsc = [];
            if (isset($json->time1)) $timesEsc[] = $json->time1;
            if (isset($json->time2)) $timesEsc[] = $json->time2;

            foreach ($timesEsc as $tmObj) {
                $tmId = isset($tmObj->idTime) ? (int)$tmObj->idTime : 0;
                $tmNome = isset($tmObj->nomeTime) ? $tmObj->nomeTime : '';
                if (isset($tmObj->jogadores) && is_array($tmObj->jogadores)) {
                    foreach ($tmObj->jogadores as $idx => $jg) {
                        $jgId = isset($jg->idJogador) ? (int)$jg->idJogador : 0;
                        $jgNome = isset($jg->nome) ? $jg->nome : '';
                        $pos = isset($jg->posicao) ? $jg->posicao : '';
                        $num = isset($jg->numero) ? (int)$jg->numero : ($idx + 1);
                        $titular = ($idx < 11) ? 1 : 0;
                        $entTempo = isset($jg->entradaTempo) ? (int)$jg->entradaTempo : 0;
                        $entMin = isset($jg->entradaMinuto) ? (int)$jg->entradaMinuto : 0;
                        $saiTempo = isset($jg->saidaTempo) ? (int)$jg->saidaTempo : 0;
                        $saiMin = isset($jg->saidaMinuto) ? (int)$jg->saidaMinuto : 0;

                        $stmtInsEsc->execute([$idPartida, $tmId, $tmNome, $pos, $num, $jgId, $jgNome, $titular, $entTempo, $entMin, $saiTempo, $saiMin]);
                    }
                }
            }
        }
    }
}

