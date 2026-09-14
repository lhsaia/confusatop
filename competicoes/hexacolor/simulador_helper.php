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

if (!function_exists('sanitizarEscalacaoPreSimulacao')) {
    function sanitizarEscalacaoPreSimulacao($ldb, $clubeId, $desfalquesIds = []) {
        if (!$ldb || $clubeId <= 0) return;

        try {
            // 1. Obter todos os jogadores válidos do clube no SQLite
            $stmtJog = $ldb->prepare("SELECT ID, Nome, Pos, Nivel FROM jogador WHERE Time = :clube AND (Suspenso = 0 OR Suspenso IS NULL) AND (Lesionado = 0 OR Lesionado IS NULL)");
            $stmtJog->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtJog->execute();
            $jogadoresClube = $stmtJog->fetchAll(PDO::FETCH_ASSOC);

            // Se não encontrou por Time, busca pelo elenco
            if (empty($jogadoresClube)) {
                $stmtEl = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
                $stmtEl->bindValue(':clube', $clubeId, PDO::PARAM_INT);
                $stmtEl->execute();
                $elRow = $stmtEl->fetch(PDO::FETCH_ASSOC);
                $elIds = [];
                if ($elRow) {
                    for ($i = 1; $i <= 23; $i++) {
                        if (!empty($elRow['Jogador' . $i])) {
                            $elIds[] = (int)$elRow['Jogador' . $i];
                        }
                    }
                }
                if (!empty($elIds)) {
                    $inIds = implode(',', array_unique($elIds));
                    $stmtJog = $ldb->query("SELECT ID, Nome, Pos, Nivel FROM jogador WHERE ID IN ($inIds) AND (Suspenso = 0 OR Suspenso IS NULL) AND (Lesionado = 0 OR Lesionado IS NULL)");
                    $jogadoresClube = $stmtJog->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // Mapear jogadores disponíveis por ID (descartando os que estão em desfalques)
            $disponiveis = [];
            $goleiros = [];
            $jogadoresLinha = [];

            foreach ($jogadoresClube as $j) {
                $jId = (int)$j['ID'];
                if (in_array($jId, $desfalquesIds)) continue;

                $disponiveis[$jId] = $j;
                $posNorm = normalizarPosicaoHexacolor($j['Pos'] ?? '', 2);
                if ($posNorm === 'G') {
                    $goleiros[$jId] = $j;
                } else {
                    $jogadoresLinha[$jId] = $j;
                }
            }

            // Se o time tem menos de 11 jogadores disponíveis, cria temporariamente reservas no SQLite
            $totalDisp = count($disponiveis);
            if ($totalDisp < 11) {
                $faltam = 11 - $totalDisp;
                for ($k = 1; $k <= $faltam; $k++) {
                    $genPos = ($k === 1 && empty($goleiros)) ? 'G' : 'MC';
                    $stmtMaxId = $ldb->query("SELECT MAX(ID) FROM jogador");
                    $maxId = (int)$stmtMaxId->fetchColumn();
                    $newId = max(90000, $maxId + 1);

                    $stmtInsGen = $ldb->prepare("INSERT INTO jogador (ID, Nome, Time, Pos, Nivel, Idade, Apto, Suspenso, Lesionado, StringPosicoes) 
                        VALUES (:id, :nome, :time, :pos, 50, 22, 1, 0, 0, :strPos)");
                    $stmtInsGen->execute([
                        ':id' => $newId,
                        ':nome' => "Reserva Genérico {$k}",
                        ':time' => $clubeId,
                        ':pos' => $genPos,
                        ':strPos' => ($genPos === 'G') ? '100000000000000' : '000000000100000'
                    ]);

                    $genPlayer = ['ID' => $newId, 'Nome' => "Reserva Genérico {$k}", 'Pos' => $genPos, 'Nivel' => 50];
                    $disponiveis[$newId] = $genPlayer;
                    if ($genPos === 'G') {
                        $goleiros[$newId] = $genPlayer;
                    } else {
                        $jogadoresLinha[$newId] = $genPlayer;
                    }
                }
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

            // Slot 1: Goleiro
            $jog1 = (int)($esc['Jogador1'] ?? 0);
            if (!isset($disponiveis[$jog1]) || in_array($jog1, $titularesEscolhidos)) {
                // Escolhe primeiro goleiro disponível ou qualquer jogador
                $jog1 = !empty($goleiros) ? array_key_first($goleiros) : array_key_first($disponiveis);
            }
            $titularesEscolhidos[1] = $jog1;
            $posicoesFinais[1] = 'G';
            unset($disponiveis[$jog1]);

            // Slots 2 a 11: Jogadores de linha
            for ($i = 2; $i <= 11; $i++) {
                $posRaw = $esc['Pos' . $i] ?? ($defaultPositions[$i] ?? 'MC');
                $posNorm = normalizarPosicaoHexacolor($posRaw, $i);
                if ($posNorm === 'G') $posNorm = 'Z'; // Não permitir goleiro na linha

                $jogId = (int)($esc['Jogador' . $i] ?? 0);
                if (!isset($disponiveis[$jogId]) || in_array($jogId, $titularesEscolhidos)) {
                    // Seleciona o melhor disponível
                    $jogId = array_key_first($disponiveis);
                }

                $titularesEscolhidos[$i] = $jogId;
                $posicoesFinais[$i] = $posNorm;
                unset($disponiveis[$jogId]);
            }

            // 4. Validação e Equilíbrio Tático Mínimo (Prevenção de somaProb == 0)
            // A engine sorteia marcadores e atacantes por setor:
            // Defesa: Z, LD, LE, AD, AE, V
            // Meio: V, MC, MD, ME, MA
            // Ataque: MA, PD, PE, Am, Aa
            $defensores = 0;
            $meias = 0;
            $atacantes = 0;

            for ($i = 2; $i <= 11; $i++) {
                $p = $posicoesFinais[$i];
                if (in_array($p, ['Z', 'LD', 'LE', 'AD', 'AE', 'V'])) $defensores++;
                if (in_array($p, ['V', 'MC', 'MD', 'ME', 'MA'])) $meias++;
                if (in_array($p, ['MA', 'PD', 'PE', 'Am', 'Aa'])) $atacantes++;
            }

            // Se tiver menos de 2 defensores, ajusta slots 2 e 3 para Z
            if ($defensores < 2) {
                $posicoesFinais[2] = 'Z';
                $posicoesFinais[3] = 'Z';
            }
            // Se tiver menos de 2 meias, ajusta slot 6 e 7 para MC
            if ($meias < 2) {
                $posicoesFinais[6] = 'V';
                $posicoesFinais[7] = 'MC';
            }
            // Se tiver menos de 1 atacante, ajusta slot 11 para Am
            if ($atacantes < 1) {
                $posicoesFinais[11] = 'Am';
            }

            // 5. Atualizar tabela escalacao no SQLite com a escalação saneada
            $updateSql = "UPDATE escalacao SET ";
            $updateParts = [];
            $params = [':clube' => $clubeId];

            for ($i = 1; $i <= 11; $i++) {
                $updateParts[] = "Jogador{$i} = :jog{$i}";
                $updateParts[] = "Pos{$i} = :pos{$i}";
                $params[':jog' . $i] = $titularesEscolhidos[$i];
                $params[':pos' . $i] = $posicoesFinais[$i];
            }

            // Garantir capitão e batedores de pênalti válidos
            $capitao = (int)($esc['Capitao'] ?? 0);
            if (!in_array($capitao, $titularesEscolhidos)) {
                $capitao = $titularesEscolhidos[1]; // default capitão
            }
            $updateParts[] = "Capitao = :capitao";
            $params[':capitao'] = $capitao;

            for ($p = 1; $p <= 3; $p++) {
                $pen = (int)($esc['Penalti' . $p] ?? 0);
                if (!in_array($pen, $titularesEscolhidos)) {
                    $pen = $titularesEscolhidos[min(11, 10 + $p - 1)] ?? $titularesEscolhidos[2];
                }
                $updateParts[] = "Penalti{$p} = :pen{$p}";
                $params[':pen' . $p] = $pen;
            }

            $updateSql .= implode(', ', $updateParts) . " WHERE Clube = :clube";
            $stmtUp = $ldb->prepare($updateSql);
            $stmtUp->execute($params);

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
        if (!$ldb || $clubeId <= 0) return;
        try {
            $stmtJog = $ldb->prepare("SELECT ID, Pos, Nivel FROM jogador WHERE Time = :clube ORDER BY Nivel DESC");
            $stmtJog->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtJog->execute();
            $jogadores = $stmtJog->fetchAll(PDO::FETCH_ASSOC);

            if (count($jogadores) < 11) {
                // Busca de qualquer clube se faltar
                $stmtAll = $ldb->query("SELECT ID, Pos, Nivel FROM jogador ORDER BY Nivel DESC LIMIT 25");
                $jogadores = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
            }

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

            while (count($linha) < 10) {
                $linha[] = $goleiro;
            }

            $emergenciaPositions = [
                1 => 'G',
                2 => 'LD', 3 => 'Z', 4 => 'Z', 5 => 'LE',
                6 => 'V', 7 => 'MC', 8 => 'MD', 9 => 'ME',
                10 => 'Am', 11 => 'Aa'
            ];

            $updateParts = ["Jogador1 = :jog1", "Pos1 = 'G'"];
            $params = [':clube' => $clubeId, ':jog1' => $goleiro];

            for ($i = 2; $i <= 11; $i++) {
                $updateParts[] = "Jogador{$i} = :jog{$i}";
                $updateParts[] = "Pos{$i} = :pos{$i}";
                $params[':jog' . $i] = $linha[$i - 2];
                $params[':pos' . $i] = $emergenciaPositions[$i];
            }

            $params[':capitao'] = $goleiro;
            $params[':pen1'] = $linha[8] ?? $goleiro;
            $params[':pen2'] = $linha[9] ?? $goleiro;
            $params[':pen3'] = $linha[7] ?? $goleiro;
            $updateParts[] = "Capitao = :capitao";
            $updateParts[] = "Penalti1 = :pen1";
            $updateParts[] = "Penalti2 = :pen2";
            $updateParts[] = "Penalti3 = :pen3";

            $sql = "UPDATE escalacao SET " . implode(', ', $updateParts) . " WHERE Clube = :clube";
            $stmt = $ldb->prepare($sql);
            $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log("Erro em aplicarEscalacaoEmergenciaSQLite: " . $e->getMessage());
        }
    }
}
