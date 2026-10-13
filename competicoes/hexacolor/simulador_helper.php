<?php
/**
 * Helper de Sanitização e Validação Pré-Simulação para a Engine Hexacolor YMT
 * Garante que formações, posições, desfalques e elencos estejam 100% íntegros
 * antes da execução da engine Java legada/fixa, prevenindo IllegalArgumentException (bound must be positive)
 * e crashes por falta de marcadores/posições válidas.
 */

if (!function_exists('normalizarPosicaoHexacolor')) {
    function normalizarPosicaoHexacolor($pos, $slotIndex = 2) {
        $pos = strtoupper(trim((string)$pos));
        $map = [
            'G' => 'G', 'GOL' => 'G', 'GK' => 'G', 'GOALKEEPER' => 'G',
            'Z' => 'Z', 'ZAG' => 'Z', 'CB' => 'Z', 'DF' => 'Z', 'DC' => 'Z', 'DEF' => 'Z',
            'LD' => 'LD', 'LAT_DIR' => 'LD', 'RB' => 'LD', 'DR' => 'LD',
            'LE' => 'LE', 'LAT_ESQ' => 'LE', 'LB' => 'LE', 'DL' => 'LE',
            'V' => 'V', 'VOL' => 'V', 'DM' => 'V', 'DMC' => 'V',
            'MC' => 'MC', 'MEI' => 'MC', 'CM' => 'MC', 'MF' => 'MC', 'M' => 'MC',
            'MD' => 'MD', 'MEI_DIR' => 'MD', 'RM' => 'MD', 'MR' => 'MD',
            'ME' => 'ME', 'MEI_ESQ' => 'ME', 'LM' => 'ME', 'ML' => 'ME',
            'AD' => 'AD', 'ALA_DIR' => 'AD', 'RWB' => 'AD',
            'AE' => 'AE', 'ALA_ESQ' => 'AE', 'LWB' => 'AE',
            'MA' => 'MA', 'MEI_ATQ' => 'MA', 'AM' => 'MA', 'AMC' => 'MA', 'CAM' => 'MA',
            'PD' => 'PD', 'PON_DIR' => 'PD', 'RW' => 'PD', 'AMR' => 'PD',
            'PE' => 'PE', 'PON_ESQ' => 'PE', 'LW' => 'PE', 'AML' => 'PE',
            'AM' => 'Am', 'Am' => 'Am', 'ATA' => 'Am', 'AT' => 'Am', 'ST' => 'Am', 'CF' => 'Am', 'FW' => 'Am', 'CA' => 'Am',
            'AA' => 'Aa', 'Aa' => 'Aa', 'CENTROAVANTE' => 'Aa', 'SA' => 'Aa'
        ];

        if (isset($map[$pos])) {
            return $map[$pos];
        }

        // Fallback baseado no slot do jogador (1 a 11)
        if ($slotIndex === 1) return 'G';
        if ($slotIndex <= 5) return 'Z';
        if ($slotIndex <= 8) return 'MC';
        return 'Am';
    }
}

if (!function_exists('obterJogadoresClubeSQLite')) {
    function obterJogadoresClubeSQLite($ldb, $clubeId) {
        $clubeId = (int)$clubeId;
        if (!$ldb || $clubeId === 0) return [];

        $elIds = [];
        try {
            // 1. Tentar ler do elenco
            $stmtEl = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
            $stmtEl->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtEl->execute();
            $elRow = $stmtEl->fetch(PDO::FETCH_ASSOC);
            if ($elRow) {
                for ($i = 1; $i <= 23; $i++) {
                    if (!empty($elRow['Jogador' . $i])) {
                        $elIds[] = (int)$elRow['Jogador' . $i];
                    }
                }
            }

            // 2. Se o elenco estiver vazio ou tiver menos de 11 jogadores:
            if (count($elIds) < 11) {
                // Se for time YMT (ID negativo), os jogadores foram gerados na faixa de IDs: $clubeId * 1000 - key
                if ($clubeId < 0) {
                    $maxIdYmt = $clubeId * 1000;
                    $minIdYmt = ($clubeId * 1000) - 100;
                    $stmtYmt = $ldb->prepare("SELECT ID FROM jogador WHERE ID <= :maxId AND ID >= :minId ORDER BY ID DESC");
                    $stmtYmt->bindValue(':maxId', $maxIdYmt, PDO::PARAM_INT);
                    $stmtYmt->bindValue(':minId', $minIdYmt, PDO::PARAM_INT);
                    $stmtYmt->execute();
                    while ($jYmtId = $stmtYmt->fetchColumn()) {
                        $valId = (int)$jYmtId;
                        if (!in_array($valId, $elIds)) {
                            $elIds[] = $valId;
                        }
                    }
                }
            }

            // 3. Se ainda assim não tiver jogadores suficientes, buscar jogadores no banco
            if (count($elIds) < 11) {
                $stmtAll = $ldb->query("SELECT ID FROM jogador ORDER BY Nivel DESC LIMIT 25");
                if ($stmtAll) {
                    while ($jAllId = $stmtAll->fetchColumn()) {
                        $valId = (int)$jAllId;
                        if (!in_array($valId, $elIds)) {
                            $elIds[] = $valId;
                        }
                        if (count($elIds) >= 23) break;
                    }
                }
            }

            if (empty($elIds)) return [];

            // 4. Buscar detalhes dos jogadores e suas posições
            $inIds = implode(',', array_unique(array_filter($elIds)));
            if (empty($inIds)) return [];

            $query = "SELECT j.ID, j.Nome, j.Nivel, 
                             COALESCE(j.Suspenso, 0) as Suspenso, 
                             COALESCE(j.Lesionado, 0) as Lesionado,
                             pj.G, pj.LD, pj.LE, pj.Z, pj.AD, pj.AE, pj.V, pj.MD, pj.ME, pj.MC, pj.PD, pj.PE, pj.MA, pj.Am, pj.Aa
                      FROM jogador j
                      LEFT JOIN posicaojogador pj ON j.ID = pj.Jogador
                      WHERE j.ID IN ($inIds)";
            $stmt = $ldb->query($query);
            $jogadores = [];
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $jId = (int)$row['ID'];
                    
                    // Determinar posição primária
                    $pos = 'MC';
                    if (!empty($row['G']) && (int)$row['G'] === 1) {
                        $pos = 'G';
                    } else {
                        $posicoesOrdem = ['Z', 'LD', 'LE', 'V', 'MC', 'MD', 'ME', 'MA', 'PD', 'PE', 'Am', 'Aa', 'AD', 'AE'];
                        foreach ($posicoesOrdem as $pCheck) {
                            if (!empty($row[$pCheck]) && (int)$row[$pCheck] === 1) {
                                $pos = $pCheck;
                                break;
                            }
                        }
                    }
                    
                    // Fallback para goleiro se estiver na tabela atributosgoleiro
                    if ($pos !== 'G') {
                        $stmtGk = $ldb->prepare("SELECT 1 FROM atributosgoleiro WHERE Goleiro = :id LIMIT 1");
                        $stmtGk->bindValue(':id', $jId, PDO::PARAM_INT);
                        $stmtGk->execute();
                        if ($stmtGk->fetch()) {
                            $pos = 'G';
                        }
                    }

                    $row['Pos'] = $pos;
                    $jogadores[$jId] = $row;
                }
            }

            return $jogadores;
        } catch (\Throwable $e) {
            error_log("Erro em obterJogadoresClubeSQLite para o clube #{$clubeId}: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('garantirTecnicoValidoSQLite')) {
    function garantirTecnicoValidoSQLite($ldb, $clubeId, $tecnicoPreferidoId = 0) {
        $clubeId = (int)$clubeId;
        $tecnicoPreferidoId = (int)$tecnicoPreferidoId;
        if (!$ldb || $clubeId === 0) return $clubeId;

        try {
            $candidatos = [];
            if ($tecnicoPreferidoId !== 0) {
                $candidatos[] = $tecnicoPreferidoId;
            }
            if (!in_array($clubeId, $candidatos)) {
                $candidatos[] = $clubeId;
            }

            $tecRow = null;
            $stmtTec = $ldb->prepare("SELECT ID, Nome, Idade, Nivel, Mentalidade, Estilo FROM tecnico WHERE ID = :id LIMIT 1");
            foreach ($candidatos as $cid) {
                $stmtTec->bindValue(':id', $cid, PDO::PARAM_INT);
                $stmtTec->execute();
                $row = $stmtTec->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['ID'])) {
                    $tecRow = $row;
                    break;
                }
            }

            if ($tecRow) {
                $tid = (int)$tecRow['ID'];
                $ment = (int)($tecRow['Mentalidade'] ?? 0);
                $est = (int)($tecRow['Estilo'] ?? 0);
                $niv = (int)($tecRow['Nivel'] ?? 0);
                $idade = (int)($tecRow['Idade'] ?? 0);
                $nome = trim((string)($tecRow['Nome'] ?? ''));

                $precisaUpdate = false;
                if ($ment < 1 || $ment > 5) { $ment = 3; $precisaUpdate = true; }
                if ($est < 1 || $est > 5) { $est = 3; $precisaUpdate = true; }
                if ($niv < 1 || $niv > 10) { $niv = 5; $precisaUpdate = true; }
                if ($idade <= 0) { $idade = 45; $precisaUpdate = true; }
                if ($nome === '') { $nome = "Técnico #{$tid}"; $precisaUpdate = true; }

                if ($precisaUpdate) {
                    $stmtUp = $ldb->prepare("UPDATE tecnico SET Nome = :nome, Idade = :idade, Nivel = :nivel, Mentalidade = :ment, Estilo = :est WHERE ID = :id");
                    $stmtUp->execute([
                        ':nome' => $nome,
                        ':idade' => $idade,
                        ':nivel' => $niv,
                        ':ment' => $ment,
                        ':est' => $est,
                        ':id' => $tid
                    ]);
                }
                return $tid;
            }

            // Se não existe registro na tabela tecnico (ex: times .ymt importados anteriormente), criar técnico padrão válido
            $tid = ($tecnicoPreferidoId !== 0) ? $tecnicoPreferidoId : $clubeId;
            $nomeClube = "Clube {$clubeId}";
            try {
                $stmtClube = $ldb->prepare("SELECT Nome FROM clube WHERE ID = :id LIMIT 1");
                $stmtClube->bindValue(':id', $clubeId, PDO::PARAM_INT);
                $stmtClube->execute();
                $cNome = $stmtClube->fetchColumn();
                if ($cNome) $nomeClube = trim((string)$cNome);
            } catch (\Throwable $e) {}

            $stmtIns = $ldb->prepare("INSERT OR REPLACE INTO tecnico (ID, Nome, Idade, Nivel, Mentalidade, Estilo) VALUES (:id, :nome, 45, 5, 3, 3)");
            $stmtIns->execute([
                ':id' => $tid,
                ':nome' => "Técnico (" . $nomeClube . ")"
            ]);
            return $tid;
        } catch (\Throwable $e) {
            error_log("Erro em garantirTecnicoValidoSQLite para o clube #{$clubeId}: " . $e->getMessage());
            return $clubeId;
        }
    }
}

if (!function_exists('garantirIntegridadeJogadorSQLite')) {
    function garantirIntegridadeJogadorSQLite($ldb, $jogadorId) {
        $jogadorId = (int)$jogadorId;
        if (!$ldb || $jogadorId === 0) return false;

        try {
            $stmtJ = $ldb->prepare("SELECT ID, Mentalidade, Nivel FROM jogador WHERE ID = :id LIMIT 1");
            $stmtJ->bindValue(':id', $jogadorId, PDO::PARAM_INT);
            $stmtJ->execute();
            $jRow = $stmtJ->fetch(PDO::FETCH_ASSOC);
            if (!$jRow) {
                return false;
            }

            $ment = (int)($jRow['Mentalidade'] ?? 0);
            if ($ment < 1 || $ment > 8) {
                $stmtUpM = $ldb->prepare("UPDATE jogador SET Mentalidade = 4 WHERE ID = :id");
                $stmtUpM->bindValue(':id', $jogadorId, PDO::PARAM_INT);
                $stmtUpM->execute();
            }

            $stmtPos = $ldb->prepare("SELECT G FROM posicaojogador WHERE Jogador = :id LIMIT 1");
            $stmtPos->bindValue(':id', $jogadorId, PDO::PARAM_INT);
            $stmtPos->execute();
            $posRow = $stmtPos->fetch(PDO::FETCH_ASSOC);
            if (!$posRow) {
                $stmtInsPos = $ldb->prepare("INSERT OR IGNORE INTO posicaojogador (Jogador, G, LD, LE, Z, AD, AE, V, MD, ME, MC, PD, PE, MA, Am, Aa) VALUES (:id, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0)");
                $stmtInsPos->bindValue(':id', $jogadorId, PDO::PARAM_INT);
                $stmtInsPos->execute();
            }

            // Garantir que haja linha tanto em atributosjogador quanto em atributosgoleiro para evitar NPE se atuar improvisado
            $stmtInsAj = $ldb->prepare("INSERT OR IGNORE INTO atributosjogador (Jogador, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Determinacao, determinacaoOriginal) VALUES (:id, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 1, 1)");
            $stmtInsAj->bindValue(':id', $jogadorId, PDO::PARAM_INT);
            $stmtInsAj->execute();

            $stmtInsAg = $ldb->prepare("INSERT OR IGNORE INTO atributosgoleiro (Goleiro, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, determinacaoOriginal) VALUES (:id, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 1, 1)");
            $stmtInsAg->bindValue(':id', $jogadorId, PDO::PARAM_INT);
            $stmtInsAg->execute();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('garantirTitularesNoElencoSQLite')) {
    function garantirTitularesNoElencoSQLite($ldb, $clubeId, array $titulares) {
        $clubeId = (int)$clubeId;
        if (!$ldb || $clubeId === 0) return;

        try {
            $stmtEl = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
            $stmtEl->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtEl->execute();
            $elRow = $stmtEl->fetch(PDO::FETCH_ASSOC);

            $tecnicoPreferido = $elRow ? (int)($elRow['Tecnico'] ?? 0) : 0;
            $tecnicoValidoId = garantirTecnicoValidoSQLite($ldb, $clubeId, $tecnicoPreferido);

            $elPlayers = [];
            if ($elRow) {
                for ($i = 1; $i <= 23; $i++) {
                    $pid = (int)($elRow['Jogador' . $i] ?? 0);
                    if ($pid !== 0 && !in_array($pid, $elPlayers)) {
                        if (garantirIntegridadeJogadorSQLite($ldb, $pid)) {
                            $elPlayers[] = $pid;
                        }
                    }
                }
            }

            // Inserir os titulares no topo do elenco se faltar algum
            $titularesValues = array_values($titulares);
            foreach ($titularesValues as $tId) {
                $tId = (int)$tId;
                if ($tId !== 0 && !in_array($tId, $elPlayers)) {
                    if (garantirIntegridadeJogadorSQLite($ldb, $tId)) {
                        array_unshift($elPlayers, $tId);
                    }
                }
            }

            // Preencher até 23 slots se necessário
            while (count($elPlayers) < 23) {
                $elPlayers[] = 0;
            }
            $elPlayers = array_slice($elPlayers, 0, 23);

            $sqlEl = "INSERT OR REPLACE INTO elenco (
                Clube, Jogador1, Jogador2, Jogador3, Jogador4, Jogador5, Jogador6, Jogador7, Jogador8, Jogador9, Jogador10, 
                Jogador11, Jogador12, Jogador13, Jogador14, Jogador15, Jogador16, Jogador17, Jogador18, Jogador19, Jogador20, 
                Jogador21, Jogador22, Jogador23, Tecnico
            ) VALUES (
                :clube, :j1, :j2, :j3, :j4, :j5, :j6, :j7, :j8, :j9, :j10,
                :j11, :j12, :j13, :j14, :j15, :j16, :j17, :j18, :j19, :j20,
                :j21, :j22, :j23, :tecnico
            )";
            $stmtInsEl = $ldb->prepare($sqlEl);
            $stmtInsEl->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            for ($k = 1; $k <= 23; $k++) {
                $stmtInsEl->bindValue(':j' . $k, $elPlayers[$k - 1], PDO::PARAM_INT);
            }
            $stmtInsEl->bindValue(':tecnico', $tecnicoValidoId, PDO::PARAM_INT);
            $stmtInsEl->execute();
        } catch (\Throwable $e) {
            error_log("Erro em garantirTitularesNoElencoSQLite para o clube #{$clubeId}: " . $e->getMessage());
        }
    }
}

if (!function_exists('sanitizarEscalacaoPreSimulacao')) {
    function sanitizarEscalacaoPreSimulacao($ldb, $clubeId, $desfalquesIds = []) {
        $clubeId = (int)$clubeId;
        if (!$ldb || $clubeId === 0) return;

        try {
            $jogadoresClube = obterJogadoresClubeSQLite($ldb, $clubeId);

            // Mapear jogadores disponíveis por ID (descartando os que estão em desfalques)
            $disponiveis = [];
            $goleiros = [];
            $jogadoresLinha = [];

            foreach ($jogadoresClube as $j) {
                $jId = (int)$j['ID'];
                if (in_array($jId, $desfalquesIds)) continue;
                if (!empty($j['Suspenso']) || !empty($j['Lesionado'])) continue;

                $disponiveis[$jId] = $j;
                $posNorm = normalizarPosicaoHexacolor($j['Pos'] ?? '', 2);
                if ($posNorm === 'G') {
                    $goleiros[$jId] = $j;
                } else {
                    $jogadoresLinha[$jId] = $j;
                }
            }

            // Função auxiliar para criar jogador reserva genérico quando faltar goleiro ou jogador de linha
            $criarJogadorGenerico = function($genPos, $indice) use ($ldb, &$disponiveis, &$goleiros, &$jogadoresLinha) {
                $stmtMaxId = $ldb->query("SELECT MAX(ID) FROM jogador");
                $maxId = (int)$stmtMaxId->fetchColumn();
                $newId = max(90000, $maxId + 1);

                $stmtInsGen = $ldb->prepare("INSERT OR IGNORE INTO jogador (ID, Nome, Idade, Nivel, Potencial, CrescBase, Mentalidade, CobradorFalta) 
                    VALUES (:id, :nome, 22, 50, 0, 0, 4, 0)");
                $stmtInsGen->execute([
                    ':id' => $newId,
                    ':nome' => "Reserva Genérico {$indice}"
                ]);

                $isGk = ($genPos === 'G') ? 1 : 0;
                $isMc = ($genPos === 'MC') ? 1 : 0;
                $stmtInsPos = $ldb->prepare("INSERT OR IGNORE INTO posicaojogador (Jogador, G, LD, LE, Z, AD, AE, V, MD, ME, MC, PD, PE, MA, Am, Aa) 
                    VALUES (:id, :g, 0, 0, 0, 0, 0, 0, 0, 0, :mc, 0, 0, 0, 0, 0)");
                $stmtInsPos->execute([':id' => $newId, ':g' => $isGk, ':mc' => $isMc]);

                $stmtInsAtG = $ldb->prepare("INSERT OR IGNORE INTO atributosgoleiro (Goleiro, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, determinacaoOriginal) 
                    VALUES (:id, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 1, 1)");
                $stmtInsAtG->execute([':id' => $newId]);

                $stmtInsAtJ = $ldb->prepare("INSERT OR IGNORE INTO atributosjogador (Jogador, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Determinacao, determinacaoOriginal) 
                    VALUES (:id, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 5.0, 1, 1)");
                $stmtInsAtJ->execute([':id' => $newId]);

                $genPlayer = ['ID' => $newId, 'Nome' => "Reserva Genérico {$indice}", 'Pos' => $genPos, 'Nivel' => 50];
                $disponiveis[$newId] = $genPlayer;
                if ($genPos === 'G') {
                    $goleiros[$newId] = $genPlayer;
                } else {
                    $jogadoresLinha[$newId] = $genPlayer;
                }
            };

            $genIdx = 1;
            if (empty($goleiros)) {
                $criarJogadorGenerico('G', $genIdx++);
            }
            while (count($jogadoresLinha) < 10) {
                $criarJogadorGenerico('MC', $genIdx++);
            }

            // 2. Ler escalação atual
            $stmtEsc = $ldb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
            $stmtEsc->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtEsc->execute();
            $esc = $stmtEsc->fetch(PDO::FETCH_ASSOC);

            if (!$esc) {
                // Cria escalação 4-4-2 padrão se não existir
                $esc = ['Clube' => $clubeId];
                for ($i = 1; $i <= 11; $i++) {
                    $esc['Jogador' . $i] = 0;
                }
            }

            // 3. Montar os 11 titulares válidos e não repetidos
            $titularesEscolhidos = [];
            $posicoesFinais = [];

            // Posições táticas padrão para 4-4-2 se necessário
            $defaultPositions = [
                1 => 'G',
                2 => 'LD', 3 => 'Z', 4 => 'Z', 5 => 'LE',
                6 => 'V', 7 => 'MC', 8 => 'MD', 9 => 'ME',
                10 => 'Am', 11 => 'Aa'
            ];

            // Slot 1: Goleiro (estritamente escolhido entre os goleiros disponíveis)
            $jog1 = (int)($esc['Jogador1'] ?? 0);
            if (!isset($goleiros[$jog1]) || in_array($jog1, $titularesEscolhidos)) {
                $jog1 = !empty($goleiros) ? array_key_first($goleiros) : array_key_first($disponiveis);
            }
            $titularesEscolhidos[1] = $jog1;
            $posicoesFinais[1] = 'G';
            unset($goleiros[$jog1], $disponiveis[$jog1]);

            // Slots 2 a 11: Jogadores de linha (estritamente escolhidos entre jogadores de linha disponíveis)
            for ($i = 2; $i <= 11; $i++) {
                $posRaw = $esc['Pos' . $i] ?? ($defaultPositions[$i] ?? 'MC');
                $posNorm = normalizarPosicaoHexacolor($posRaw, $i);
                if ($posNorm === 'G') $posNorm = 'Z';

                $jogId = (int)($esc['Jogador' . $i] ?? 0);
                if (!isset($jogadoresLinha[$jogId]) || in_array($jogId, $titularesEscolhidos)) {
                    $jogId = !empty($jogadoresLinha) ? array_key_first($jogadoresLinha) : array_key_first($disponiveis);
                }

                $titularesEscolhidos[$i] = $jogId;
                $posicoesFinais[$i] = $posNorm;
                unset($jogadoresLinha[$jogId], $disponiveis[$jogId]);
            }

            // 4. Validação e Equilíbrio Tático Mínimo (Prevenção de somaProb == 0)
            $defensores = 0;
            $meias = 0;
            $atacantes = 0;

            for ($i = 2; $i <= 11; $i++) {
                $p = $posicoesFinais[$i];
                if (in_array($p, ['Z', 'LD', 'LE', 'AD', 'AE', 'V'])) $defensores++;
                if (in_array($p, ['V', 'MC', 'MD', 'ME', 'MA'])) $meias++;
                if (in_array($p, ['MA', 'PD', 'PE', 'Am', 'Aa'])) $atacantes++;
            }

            if ($defensores < 2) {
                $posicoesFinais[2] = 'Z';
                $posicoesFinais[3] = 'Z';
            }
            if ($meias < 2) {
                $posicoesFinais[6] = 'V';
                $posicoesFinais[7] = 'MC';
            }
            if ($atacantes < 1) {
                $posicoesFinais[11] = 'Am';
            }

            // 5. Garantir capitão e batedores de pênalti válidos
            $capitao = (int)($esc['Capitao'] ?? 0);
            if (!in_array($capitao, $titularesEscolhidos)) {
                $capitao = $titularesEscolhidos[1];
            }

            $penaltis = [];
            for ($p = 1; $p <= 3; $p++) {
                $pen = (int)($esc['Penalti' . $p] ?? 0);
                if (!in_array($pen, $titularesEscolhidos)) {
                    $pen = $titularesEscolhidos[min(11, 10 + $p - 1)] ?? $titularesEscolhidos[2];
                }
                $penaltis[$p] = $pen;
            }

            // 6. Gravar com INSERT OR REPLACE INTO escalacao garantindo que o registro exista para a engine
            $sqlEsc = "INSERT OR REPLACE INTO escalacao (
                Clube, 
                Pos1, Jogador1, Pos2, Jogador2, Pos3, Jogador3, Pos4, Jogador4, Pos5, Jogador5, 
                Pos6, Jogador6, Pos7, Jogador7, Pos8, Jogador8, Pos9, Jogador9, Pos10, Jogador10, 
                Pos11, Jogador11, Capitao, Penalti1, Penalti2, Penalti3
            ) VALUES (
                :clube,
                :pos1, :jog1, :pos2, :jog2, :pos3, :jog3, :pos4, :jog4, :pos5, :jog5,
                :pos6, :jog6, :pos7, :jog7, :pos8, :jog8, :pos9, :jog9, :pos10, :jog10,
                :pos11, :jog11, :capitao, :pen1, :pen2, :pen3
            )";
            $stmtUp = $ldb->prepare($sqlEsc);
            $stmtUp->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            for ($i = 1; $i <= 11; $i++) {
                $stmtUp->bindValue(':pos' . $i, $posicoesFinais[$i]);
                $stmtUp->bindValue(':jog' . $i, $titularesEscolhidos[$i], PDO::PARAM_INT);
            }
            $stmtUp->bindValue(':capitao', $capitao, PDO::PARAM_INT);
            $stmtUp->bindValue(':pen1', $penaltis[1], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen2', $penaltis[2], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen3', $penaltis[3], PDO::PARAM_INT);
            $stmtUp->execute();

            // 7. Garantir que o elenco do clube no SQLite contenha todos os 11 titulares, atributos e técnico válido
            garantirTitularesNoElencoSQLite($ldb, $clubeId, $titularesEscolhidos);

        } catch (\Throwable $e) {
            error_log("Erro em sanitizarEscalacaoPreSimulacao para o clube #{$clubeId}: " . $e->getMessage());
        }
    }
}

if (!function_exists('aplicarEscalacaoEmergenciaSQLite')) {
    /**
     * Aplica uma formação tática 4-4-2 limpa e infalível com os 11 melhores jogadores aptos
     * caso o JAR tenha falhado na primeira tentativa.
     */
    function aplicarEscalacaoEmergenciaSQLite($ldb, $clubeId) {
        $clubeId = (int)$clubeId;
        if (!$ldb || $clubeId === 0) return;
        try {
            $jogadores = obterJogadoresClubeSQLite($ldb, $clubeId);

            $goleiro = 0;
            $linha = [];
            foreach ($jogadores as $j) {
                $jId = (int)$j['ID'];
                $pNorm = normalizarPosicaoHexacolor($j['Pos'] ?? '', 2);
                if ($pNorm === 'G' && $goleiro === 0) {
                    $goleiro = $jId;
                } else {
                    if (count($linha) < 10 && !in_array($jId, $linha) && $jId !== $goleiro) {
                        $linha[] = $jId;
                    }
                }
            }

            if ($goleiro === 0 && !empty($linha)) {
                $goleiro = array_shift($linha);
            }

            if ($goleiro === 0) {
                $goleiro = max(90000, abs($clubeId) * 1000);
            }

            while (count($linha) < 10) {
                $linha[] = $goleiro;
            }

            $emergenciaPositions = [
                1 => 'G',
                2 => 'LD', 3 => 'Z', 4 => 'Z', 5 => 'LE',
                6 => 'V', 7 => 'MC', 8 => 'MD', 9 => 'ME',
                10 => 'Am', 11 => 'Aa'
            ];

            $sqlEsc = "INSERT OR REPLACE INTO escalacao (
                Clube, 
                Pos1, Jogador1, Pos2, Jogador2, Pos3, Jogador3, Pos4, Jogador4, Pos5, Jogador5, 
                Pos6, Jogador6, Pos7, Jogador7, Pos8, Jogador8, Pos9, Jogador9, Pos10, Jogador10, 
                Pos11, Jogador11, Capitao, Penalti1, Penalti2, Penalti3
            ) VALUES (
                :clube,
                :pos1, :jog1, :pos2, :jog2, :pos3, :jog3, :pos4, :jog4, :pos5, :jog5,
                :pos6, :jog6, :pos7, :jog7, :pos8, :jog8, :pos9, :jog9, :pos10, :jog10,
                :pos11, :jog11, :capitao, :pen1, :pen2, :pen3
            )";
            $stmt = $ldb->prepare($sqlEsc);
            $stmt->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmt->bindValue(':pos1', 'G');
            $stmt->bindValue(':jog1', $goleiro, PDO::PARAM_INT);

            $titulares = [1 => $goleiro];
            for ($i = 2; $i <= 11; $i++) {
                $jogId = $linha[$i - 2];
                $titulares[$i] = $jogId;
                $stmt->bindValue(':pos' . $i, $emergenciaPositions[$i]);
                $stmt->bindValue(':jog' . $i, $jogId, PDO::PARAM_INT);
            }

            $stmt->bindValue(':capitao', $goleiro, PDO::PARAM_INT);
            $stmt->bindValue(':pen1', $linha[8] ?? $goleiro, PDO::PARAM_INT);
            $stmt->bindValue(':pen2', $linha[9] ?? $goleiro, PDO::PARAM_INT);
            $stmt->bindValue(':pen3', $linha[7] ?? $goleiro, PDO::PARAM_INT);
            $stmt->execute();

            garantirTitularesNoElencoSQLite($ldb, $clubeId, $titulares);
        } catch (\Throwable $e) {
            error_log("Erro em aplicarEscalacaoEmergenciaSQLite: " . $e->getMessage());
        }
    }
}
