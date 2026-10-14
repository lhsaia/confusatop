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

            $temSuspenso = false;
            $temLesionado = false;
            try {
                $stmtCols = $ldb->query("PRAGMA table_info(jogador)");
                if ($stmtCols) {
                    while ($col = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
                        $colName = $col['name'] ?? '';
                        if (strcasecmp($colName, 'Suspenso') === 0) $temSuspenso = true;
                        if (strcasecmp($colName, 'Lesionado') === 0) $temLesionado = true;
                    }
                }
            } catch (\Throwable $e) {}

            $colSuspenso = $temSuspenso ? "COALESCE(j.Suspenso, 0) as Suspenso" : "0 as Suspenso";
            $colLesionado = $temLesionado ? "COALESCE(j.Lesionado, 0) as Lesionado" : "0 as Lesionado";

            $query = "SELECT j.ID, j.Nome, j.Nivel, 
                             {$colSuspenso}, 
                             {$colLesionado},
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
        if (!$ldb || $clubeId === 0) return $tecnicoPreferidoId;

        try {
            if ($tecnicoPreferidoId !== 0) {
                $stmtTec = $ldb->prepare("SELECT ID FROM tecnico WHERE ID = :id LIMIT 1");
                $stmtTec->bindValue(':id', $tecnicoPreferidoId, PDO::PARAM_INT);
                $stmtTec->execute();
                if ($stmtTec->fetch()) {
                    return $tecnicoPreferidoId;
                }
            }
            $stmtTecC = $ldb->prepare("SELECT ID FROM tecnico WHERE ID = :id LIMIT 1");
            $stmtTecC->bindValue(':id', $clubeId, PDO::PARAM_INT);
            $stmtTecC->execute();
            if ($stmtTecC->fetch()) {
                return $clubeId;
            }
        } catch (\Throwable $e) {}

        return ($tecnicoPreferidoId !== 0) ? $tecnicoPreferidoId : $clubeId;
    }
}

if (!function_exists('garantirIntegridadeJogadorSQLite')) {
    function garantirIntegridadeJogadorSQLite($ldb, $jogadorId) {
        $jogadorId = (int)$jogadorId;
        if (!$ldb || $jogadorId === 0) return false;

        try {
            $stmtJ = $ldb->prepare("SELECT ID FROM jogador WHERE ID = :id LIMIT 1");
            $stmtJ->bindValue(':id', $jogadorId, PDO::PARAM_INT);
            $stmtJ->execute();
            return (bool)$stmtJ->fetch();
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

            // 2. Ler escalação atual
            $stmtEsc = $ldb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
            $stmtEsc->bindValue(':clube', $clubeId, PDO::PARAM_INT);
            $stmtEsc->execute();
            $esc = $stmtEsc->fetch(PDO::FETCH_ASSOC);

            if (!$esc || empty($esc['Jogador1'])) {
                return;
            }

            // Se nenhum dos 11 titulares está em desfalquesIds e nenhum está inválido, manter escalação 100% intacta
            $precisaAjuste = false;
            for ($i = 1; $i <= 11; $i++) {
                $jId = (int)($esc['Jogador' . $i] ?? 0);
                if ($jId === 0 || in_array($jId, $desfalquesIds)) {
                    $precisaAjuste = true;
                    break;
                }
            }

            if (!$precisaAjuste) {
                $titularesExistentes = [];
                for ($i = 1; $i <= 11; $i++) {
                    $titularesExistentes[$i] = (int)$esc['Jogador' . $i];
                }
                garantirTitularesNoElencoSQLite($ldb, $clubeId, $titularesExistentes);
                return;
            }

            // 3. Montar os 11 titulares mantendo estritamente as posições táticas cadastradas na escalação
            $titularesEscolhidos = [];
            $posicoesFinais = [];

            // Slot 1: Goleiro
            $jog1 = (int)($esc['Jogador1'] ?? 0);
            if (!isset($goleiros[$jog1]) || in_array($jog1, $titularesEscolhidos)) {
                $jog1 = !empty($goleiros) ? array_key_first($goleiros) : array_key_first($disponiveis);
            }
            $titularesEscolhidos[1] = $jog1;
            $posicoesFinais[1] = !empty($esc['Pos1']) ? $esc['Pos1'] : 'G';
            unset($goleiros[$jog1], $disponiveis[$jog1]);

            // Slots 2 a 11: Preserva a posição original cadastrada no banco e substitui apenas jogador desfalcado
            for ($i = 2; $i <= 11; $i++) {
                $posRaw = $esc['Pos' . $i] ?? 'MC';
                $posNorm = normalizarPosicaoHexacolor($posRaw, $i);
                if ($posNorm === 'G') $posNorm = 'Z';

                $jogId = (int)($esc['Jogador' . $i] ?? 0);
                if (!isset($jogadoresLinha[$jogId]) || in_array($jogId, $titularesEscolhidos)) {
                    // Buscar melhor substituto por afinidade de setor caso o titular esteja suspenso/desfalcado
                    $candidatoSub = null;
                    foreach ($jogadoresLinha as $candId => $candJ) {
                        if (normalizarPosicaoHexacolor($candJ['Pos'] ?? '', $i) === $posNorm) {
                            $candidatoSub = $candId;
                            break;
                        }
                    }
                    if (!$candidatoSub) {
                        $isDef = in_array($posNorm, ['Z', 'LD', 'LE', 'AD', 'AE', 'V']);
                        $isAta = in_array($posNorm, ['MA', 'PD', 'PE', 'Am', 'Aa']);
                        foreach ($jogadoresLinha as $candId => $candJ) {
                            $pCand = normalizarPosicaoHexacolor($candJ['Pos'] ?? '', 2);
                            if ($isDef && in_array($pCand, ['Z', 'LD', 'LE', 'AD', 'AE', 'V'])) {
                                $candidatoSub = $candId;
                                break;
                            } elseif ($isAta && in_array($pCand, ['MA', 'PD', 'PE', 'Am', 'Aa'])) {
                                $candidatoSub = $candId;
                                break;
                            }
                        }
                    }
                    $jogId = $candidatoSub ? $candidatoSub : (!empty($jogadoresLinha) ? array_key_first($jogadoresLinha) : array_key_first($disponiveis));
                }

                $titularesEscolhidos[$i] = $jogId;
                $posicoesFinais[$i] = $posRaw;
                unset($jogadoresLinha[$jogId], $disponiveis[$jogId]);
            }

            // 4. Garantir capitão e batedores de pênalti válidos
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

            // 5. Gravar com INSERT OR REPLACE INTO escalacao
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

            // 6. Garantir que o elenco do clube no SQLite contenha todos os 11 titulares
            garantirTitularesNoElencoSQLite($ldb, $clubeId, $titularesEscolhidos);

        } catch (\Throwable $e) {
            error_log("Erro em sanitizarEscalacaoPreSimulacao para o clube #{$clubeId}: " . $e->getMessage());
        }
    }
}

if (!function_exists('aplicarEscalacaoEmergenciaSQLite')) {
    /**
     * Desabilitado: não deve alterar nem inventar formações arbitrárias.
     */
    function aplicarEscalacaoEmergenciaSQLite($ldb, $clubeId) {
        return;
    }
}

if (!function_exists('imagemValida')) {
    function imagemValida($absPath) {
        if (!file_exists($absPath) || !is_file($absPath) || @filesize($absPath) <= 0) {
            return false;
        }
        $handle = @fopen($absPath, 'rb');
        if (!$handle) return false;
        $header = fread($handle, 12);
        fclose($handle);
        if (strlen($header) < 4) return false;
        $b = unpack('C*', $header);
        // PNG
        if ($b[1] === 0x89 && $b[2] === 0x50 && $b[3] === 0x4E && $b[4] === 0x47) return true;
        // JPEG
        if ($b[1] === 0xFF && $b[2] === 0xD8 && $b[3] === 0xFF) return true;
        // GIF
        if ($b[1] === 0x47 && $b[2] === 0x49 && $b[3] === 0x46) return true;
        // WEBP
        if (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') return true;
        return false;
    }
}

if (!function_exists('resolverCaminhoImagem')) {
    function resolverCaminhoImagem($relPath, $hexacolorDir, $docRoot, $fallbackRel) {
        if (empty($relPath)) {
            return $fallbackRel;
        }
        
        // Caminho absoluto no disco
        if (strpos($relPath, '../../') === 0) {
            $sub = substr($relPath, 6);
            $abs = $docRoot . '/' . ltrim($sub, '/\\');
        } else {
            $abs = $hexacolorDir . '/' . ltrim($relPath, '/\\');
        }
        
        $resolvedAbs = null;
        $resolvedRel = null;
        
        if (file_exists($abs) && is_file($abs) && @filesize($abs) > 0) {
            $resolvedAbs = $abs;
            $resolvedRel = $relPath;
        } else {
            // Tenta encontrar ignorando maiúsculas/minúsculas no mesmo diretório
            $dir = dirname($abs);
            $filename = basename($abs);
            if (is_dir($dir)) {
                $files = @scandir($dir);
                if ($files) {
                    foreach ($files as $f) {
                        if ($f !== '.' && $f !== '..' && strcasecmp($f, $filename) === 0 && is_file($dir . '/' . $f)) {
                            $resolvedAbs = $dir . '/' . $f;
                            if (strpos($relPath, '../../') === 0) {
                                $resolvedRel = '../../' . ltrim(substr(dirname($relPath), 6), '/\\') . '/' . $f;
                            } else {
                                $resolvedRel = (dirname($relPath) !== '.' ? dirname($relPath) . '/' : '') . $f;
                            }
                            break;
                        }
                    }
                }
            }
            
            // Tenta procurar em images/escudos, images/uniformes ou images/bandeiras pelo nome do arquivo
            if (!$resolvedAbs) {
                $filenameBase = basename($relPath);
                if (!empty($filenameBase)) {
                    $altDirs = [
                        $docRoot . '/images/escudos',
                        $docRoot . '/images/uniformes',
                        $docRoot . '/images/bandeiras',
                        $hexacolorDir . '/Imagens',
                        $hexacolorDir . '/Escudos',
                        $hexacolorDir . '/Uniformes',
                        $hexacolorDir . '/imagens',
                        $hexacolorDir . '/escudos',
                        $hexacolorDir . '/uniformes'
                    ];
                    foreach ($altDirs as $altDir) {
                        if (is_dir($altDir)) {
                            $altFiles = @scandir($altDir);
                            if ($altFiles) {
                                foreach ($altFiles as $af) {
                                    if ($af !== '.' && $af !== '..' && strcasecmp($af, $filenameBase) === 0 && is_file($altDir . '/' . $af)) {
                                        $resolvedAbs = $altDir . '/' . $af;
                                        if (strpos($altDir, $docRoot) === 0) {
                                            $relFromDoc = substr($altDir, strlen($docRoot));
                                            $resolvedRel = '../../' . ltrim($relFromDoc, '/\\') . '/' . $af;
                                        } elseif (strpos($altDir, $hexacolorDir) === 0) {
                                            $relFromHexa = substr($altDir, strlen($hexacolorDir));
                                            $resolvedRel = ltrim($relFromHexa, '/\\') . '/' . $af;
                                        }
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Se não encontrou o arquivo em lugar nenhum, usar fallback
        if (!$resolvedAbs || !file_exists($resolvedAbs) || @filesize($resolvedAbs) <= 0) {
            return $fallbackRel;
        }
        
        $ext = strtolower(pathinfo($resolvedAbs, PATHINFO_EXTENSION));
        $isWebp = ($ext === 'webp');
        if (!$isWebp) {
            $h = @fopen($resolvedAbs, 'rb');
            if ($h) {
                $sig = fread($h, 12);
                fclose($h);
                if (strlen($sig) >= 12 && substr($sig, 0, 4) === 'RIFF' && substr($sig, 8, 4) === 'WEBP') {
                    $isWebp = true;
                }
            }
        }
        
        // Tratamento especial para WEBP: Java 8 (ImageIO) não suporta .webp nativamente e dá erro "image == null"
        if ($isWebp) {
            $cacheDir = $hexacolorDir . '/cache_images';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }
            $pngFilename = md5($resolvedAbs) . '.png';
            $pngAbs = $cacheDir . '/' . $pngFilename;
            $pngRel = 'cache_images/' . $pngFilename;
            
            if (file_exists($pngAbs) && @filesize($pngAbs) > 0) {
                return $pngRel;
            }
            
            if (function_exists('imagecreatefromwebp')) {
                $img = @imagecreatefromwebp($resolvedAbs);
                if ($img) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    if (@imagepng($img, $pngAbs, 6)) {
                        @imagedestroy($img);
                        return $pngRel;
                    }
                    @imagedestroy($img);
                }
            }
            
            // Se a conversão webp falhou, usar o placeholder PNG padrão
            return $fallbackRel;
        }
        
        return $resolvedRel;
    }
}

if (!function_exists('normalizarImagensClubesSQLite')) {
    function normalizarImagensClubesSQLite($ldb, $hexacolorDir, $docRoot) {
        if (!$ldb) return;
        try {
            // Garantir compatibilidade de pastas no Linux
            $imageFolders = ['Imagens' => 'imagens', 'Escudos' => 'escudos', 'Uniformes' => 'uniformes'];
            foreach ($imageFolders as $orig => $lower) {
                $origDir = $hexacolorDir . '/' . $orig;
                $lowerDir = $hexacolorDir . '/' . $lower;
                if (is_dir($origDir) && !is_dir($lowerDir)) {
                    if (!@symlink($origDir, $lowerDir)) {
                        @mkdir($lowerDir, 0777, true);
                        $files = glob($origDir . '/*');
                        if ($files) {
                            foreach ($files as $f) {
                                if (is_file($f)) {
                                    @copy($f, $lowerDir . '/' . basename($f));
                                }
                            }
                        }
                    }
                }
            }

            $stmtClubs = $ldb->query("SELECT ID, Nome, Escudo, Uniforme1, Uniforme2 FROM clube");
            if (!$stmtClubs) return;
            $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
            $stmtClubs = null;
            
            $fallbackEscudo = 'Imagens/EscudoPadrao.png';
            $fallbackUniforme = 'Imagens/UniformePadrao.PNG';
            
            $stmtUpClube = $ldb->prepare("UPDATE clube SET Escudo = :esc, Uniforme1 = :uni1, Uniforme2 = :uni2 WHERE ID = :id");
            
            foreach ($clubs as $c) {
                $changed = false;
                $escudo = trim($c['Escudo'] ?? '');
                $uni1 = trim($c['Uniforme1'] ?? '');
                $uni2 = trim($c['Uniforme2'] ?? '');
                
                $escudoResolvido = resolverCaminhoImagem($escudo, $hexacolorDir, $docRoot, $fallbackEscudo);
                if ($escudoResolvido !== $escudo) {
                    $escudo = $escudoResolvido;
                    $changed = true;
                }
                
                $uni1Resolvido = resolverCaminhoImagem($uni1, $hexacolorDir, $docRoot, $fallbackUniforme);
                if ($uni1Resolvido !== $uni1) {
                    $uni1 = $uni1Resolvido;
                    $changed = true;
                }
                
                $uni2Resolvido = resolverCaminhoImagem($uni2, $hexacolorDir, $docRoot, $fallbackUniforme);
                if ($uni2Resolvido !== $uni2) {
                    $uni2 = $uni2Resolvido;
                    $changed = true;
                }
                
                if ($changed) {
                    $stmtUpClube->bindValue(':esc', $escudo);
                    $stmtUpClube->bindValue(':uni1', $uni1);
                    $stmtUpClube->bindValue(':uni2', $uni2);
                    $stmtUpClube->bindValue(':id', $c['ID'], PDO::PARAM_INT);
                    $stmtUpClube->execute();
                }
            }
            $stmtUpClube = null;
        } catch (\Throwable $e) {
            error_log("Erro ao normalizar imagens dos clubes: " . $e->getMessage());
        }
    }
}
