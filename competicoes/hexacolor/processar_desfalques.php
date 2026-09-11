<?php
// Function to process player cards, injuries, and suspensions after a match simulation
function processarPosJogo($db, $idCompeticao, $idPartida, $hylFile, $hyjFile, $suspensosAntesPartida) {
    // 1. Obter opções de suspensão da competição
    $stmtOpt = $db->prepare("SELECT suspensao, expulso_dois_amarelos FROM competicao_opcoes WHERE id_competicao = :idComp LIMIT 1");
    $stmtOpt->bindValue(':idComp', $idCompeticao, PDO::PARAM_INT);
    $stmtOpt->execute();
    $options = $stmtOpt->fetch(PDO::FETCH_ASSOC);
    $criterioSuspensao = isset($options['suspensao']) ? (int)$options['suspensao'] : 0; // 0=apenas vermelho, 1=2 amarelos, 2=3 amarelos
    $expulsoDoisAmarelos = isset($options['expulso_dois_amarelos']) ? (int)$options['expulso_dois_amarelos'] : 0; // 0=não contabilizar (descarta ambos), 1=contabilizar 1 amarelo, 2=contabilizar ambos (2 amarelos)

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
                            if ($expulsoDoisAmarelos == 0) {
                                // Não contabilizar nenhum dos 2 amarelos
                                $novoAmarelos = max(0, $novoAmarelos - 2);
                            } elseif ($expulsoDoisAmarelos == 1) {
                                // Contabilizar 1 amarelo (descarta 1 dos 2)
                                $novoAmarelos = max(0, $novoAmarelos - 1);
                            } elseif ($expulsoDoisAmarelos == 2) {
                                // Contabilizar ambos os amarelos
                                // Mantém os 2 amarelos somados em $novoAmarelos
                            }
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

    // 5. Obter informações dos clubes da partida
    $stmtJogoInfo = $db->prepare("SELECT timeA_id, timeA_nome, timeB_id, timeB_nome FROM jogos_clube WHERE id = :idPartida LIMIT 1");
    $stmtJogoInfo->bindValue(':idPartida', $idPartida, PDO::PARAM_INT);
    $stmtJogoInfo->execute();
    $jogoInfo = $stmtJogoInfo->fetch(PDO::FETCH_ASSOC);

    $timeA_id = (int)($jogoInfo['timeA_id'] ?? 0);
    $nome_time_A = $jogoInfo['timeA_nome'] ?? '';
    $timeB_id = (int)($jogoInfo['timeB_id'] ?? 0);
    $nome_time_B = $jogoInfo['timeB_nome'] ?? '';

    // Ler dados da súmula .hyl (onde estão os eventos detalhados e nomes da escalação)
    $hylData = file_exists($hylFile) ? json_decode(file_get_contents($hylFile), true) : null;
    $playerMap1 = [];
    $playerMap2 = [];
    if ($hylData) {
        foreach ($hylData['escalacaoTime1'] ?? [] as $p) {
            $pId = (int)($p['id'] ?? 0);
            if ($pId > 0) $playerMap1[$pId] = $p;
        }
        foreach ($hylData['escalacaoTime2'] ?? [] as $p) {
            $pId = (int)($p['id'] ?? 0);
            if ($pId > 0) $playerMap2[$pId] = $p;
        }
    }

    // 6. Gravar eventos detalhados (gols e cartões) na tabela unificada jogos_clube_eventos
    if ($hylData && !empty($hylData['eventos']) && is_array($hylData['eventos'])) {
        // Limpa eventos anteriores desta partida para evitar duplicações
        $stmtDelEv = $db->prepare("DELETE FROM jogos_clube_eventos WHERE id_jogo = :idJogo");
        $stmtDelEv->bindValue(':idJogo', $idPartida, PDO::PARAM_INT);
        $stmtDelEv->execute();

        $stmtInsEv = $db->prepare("INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($hylData['eventos'] as $ev) {
            $tipoEvStr = $ev['tipoEvento'] ?? '';
            $tipoEvento = 0;
            switch ($tipoEvStr) {
                case 'gol':       $tipoEvento = 1; break;
                case 'amarelo':   $tipoEvento = 2; break;
                case 'vermelho':  $tipoEvento = 3; break;
                case 'golContra': $tipoEvento = 4; break;
            }

            // Ignorar eventos ocorridos durante disputa de pênaltis pós-jogo (tempo > 4)
            $tempoRaw = isset($ev['tempo']) ? (int)$ev['tempo'] : 1;
            if ($tempoRaw > 4) {
                continue;
            }

            if ($tipoEvento > 0) {
                $minuto = isset($ev['minutos']) ? (int)$ev['minutos'] : null;
                $tempo = $tempoRaw;
                if ($minuto !== null && $minuto > 45 && $tempo == 1) {
                    $tempo = 2;
                }

                $tempId = (int)($ev['idJogador'] ?? 0);
                $nomeJog = '';
                $idTm = 0;
                $nomeTm = '';

                if (isset($playerMap1[$tempId])) {
                    $nomeJog = $playerMap1[$tempId]['nome'] ?? '';
                    $idTm = $timeA_id;
                    $nomeTm = $nome_time_A;
                } elseif (isset($playerMap2[$tempId])) {
                    $nomeJog = $playerMap2[$tempId]['nome'] ?? '';
                    $idTm = $timeB_id;
                    $nomeTm = $nome_time_B;
                } else {
                    $teamNum = (int)($ev['time'] ?? 1);
                    $idTm = ($teamNum === 2) ? $timeB_id : $timeA_id;
                    $nomeTm = ($teamNum === 2) ? $nome_time_B : $nome_time_A;
                }

                $stmtInsEv->execute([
                    $idPartida,
                    $tempo,
                    $minuto,
                    $tipoEvento,
                    $tempId,
                    mb_substr($nomeJog, 0, 40),
                    $idTm,
                    $nomeTm
                ]);
            }
        }
    }

    // 7. Gravar escalações na tabela unificada jogos_clube_escalacao
    if (file_exists($hyjFile)) {
        $json = json_decode(file_get_contents($hyjFile));
        if ($json) {
            $stmtDelEsc = $db->prepare("DELETE FROM jogos_clube_escalacao WHERE id_partida = :idPartida");
            $stmtDelEsc->bindValue(':idPartida', $idPartida, PDO::PARAM_INT);
            $stmtDelEsc->execute();

            $stmtInsEsc = $db->prepare("INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_tempo, entrada_minuto, saida_tempo, saida_minuto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $timesEsc = [];
            if (isset($json->time1)) $timesEsc[] = ['obj' => $json->time1, 'id' => $timeA_id, 'nome' => $nome_time_A, 'hylMap' => $playerMap1];
            if (isset($json->time2)) $timesEsc[] = ['obj' => $json->time2, 'id' => $timeB_id, 'nome' => $nome_time_B, 'hylMap' => $playerMap2];

            foreach ($timesEsc as $tmGroup) {
                $tmObj = $tmGroup['obj'];
                $tmId = $tmGroup['id'] > 0 ? $tmGroup['id'] : (int)($tmObj->idTime ?? 0);
                $tmNome = !empty($tmGroup['nome']) ? $tmGroup['nome'] : ($tmObj->nomeTime ?? '');
                $hMap = $tmGroup['hylMap'];

                if (isset($tmObj->jogadores) && is_array($tmObj->jogadores)) {
                    foreach ($tmObj->jogadores as $idx => $jg) {
                        $jgId = isset($jg->idJogador) ? (int)$jg->idJogador : 0;
                        $jgNome = isset($jg->nome) && !empty($jg->nome) ? $jg->nome : ($hMap[$jgId]['nome'] ?? '');
                        $pos = isset($jg->posicao) && !empty($jg->posicao) ? $jg->posicao : ($hMap[$jgId]['posicao'] ?? '');
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

