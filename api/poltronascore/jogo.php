<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

$matchId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid match ID']);
    exit;
}

// Helper: Localizar arquivo .hyj de forma recursiva
function psFindHyjFile($cleanName) {
    static $fileCache = null;
    if ($fileCache === null) {
        $fileCache = [];
        $baseRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
            ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
            : dirname(__DIR__, 2);
        
        $searchDirs = [
            $baseRoot . '/competicoes/hexacolor/Partidas',
            $baseRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;
            try {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if ($file->isDir()) continue;
                    $fn = strtolower($file->getFilename());
                    if (substr($fn, -4) === '.hyj') {
                        $baseKey = strtolower(basename($fn, '.hyj'));
                        if (!isset($fileCache[$baseKey])) {
                            $fileCache[$baseKey] = $file->getPathname();
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    $key = strtolower(basename(basename($cleanName, '.hyj'), '.hyl'));
    return $fileCache[$key] ?? null;
}

// Helper: Calcular nota dinâmica do jogador caso não exista no arquivo .hyj
function psCalculatePlayerRating($pId, $pLevel, $pPos, $isStarter, $goalsScored, $yellowCards, $redCards, $teamGoals, $oppGoals, $matchId) {
    $base = 6.2 + ($pLevel - 50) * 0.02;
    if ($isStarter) $base += 0.3;

    if ($teamGoals > $oppGoals) $base += 0.4;
    elseif ($teamGoals < $oppGoals) $base -= 0.3;

    $base += ($goalsScored * 1.0);
    $base -= ($yellowCards * 0.5);
    $base -= ($redCards * 1.5);

    $isDef = in_array(strtoupper($pPos), ['G', 'Z', 'LD', 'LE', 'V', 'DF', 'CB', 'RB', 'LB', 'DM']);
    if ($isDef) {
        if ($oppGoals === 0) $base += 0.5;
        else $base -= ($oppGoals * 0.15);
    }

    $seed = ($pId * 37 + $matchId * 53) % 11;
    $base += ($seed - 5) * 0.06;

    return max(4.5, min(9.9, round($base, 1)));
}

// Helper: Inferir gênero da partida usando nome da competição e tabela demográfica gen_nomes
function psInferMatchGender($conn, $championshipName, $playerNamesList = []) {
    // 1. Verificar palavras-chave no nome do campeonato
    if (!empty($championshipName)) {
        if (preg_match('/\b(fem|feminina|feminino|femininas|femininos|women|womens|mulheres|damas)\b/i', $championshipName)) {
            return 1;
        }
        if (preg_match('/\b(masc|masculino|masculina|men|homens)\b/i', $championshipName)) {
            return 0;
        }
    }

    // 2. Extrair primeiros nomes dos atletas dos eventos/escalações e cruzar com gen_nomes
    if (!empty($playerNamesList) && is_array($playerNamesList)) {
        $firstNames = [];
        foreach ($playerNamesList as $rawName) {
            $cleaned = trim(preg_replace('/[0-9\(\)\'\"]+/', '', (string)$rawName));
            $parts = preg_split('/\s+/', $cleaned);
            if (!empty($parts[0]) && mb_strlen($parts[0]) >= 2) {
                $firstNames[] = $parts[0];
            }
        }
        $firstNames = array_unique(array_filter($firstNames));

        if (!empty($firstNames)) {
            try {
                $placeholders = implode(',', array_fill(0, count($firstNames), '?'));
                $stmtGen = $conn->prepare("SELECT SUM(F) as fem_count, SUM(M) as masc_count FROM gen_nomes WHERE Nome IN ($placeholders)");
                $stmtGen->execute(array_values($firstNames));
                $rowGen = $stmtGen->fetch(PDO::FETCH_ASSOC);

                $femCount = (int)($rowGen['fem_count'] ?? 0);
                $mascCount = (int)($rowGen['masc_count'] ?? 0);

                if ($femCount > $mascCount) {
                    return 1;
                } elseif ($mascCount > $femCount) {
                    return 0;
                }
            } catch (\Throwable $e) {}
        }
    }

    // Padrão: 0 (Masculino)
    return 0;
}

// Helper: Montar escalações táticas completas a partir do elenco do clube
function psBuildLineupFromClubSquad($conn, $clubId, $clubName, $matchId, $isHome, $teamScore, $oppScore, $playerEvents = [], $status = 'previous', $gender = null) {
    if ($clubId <= 0 && !empty($clubName)) {
        if ($gender !== null) {
            $stC = $conn->prepare("
                SELECT ID FROM clube 
                WHERE Nome = ? OR Nome LIKE ? 
                ORDER BY 
                    CASE 
                        WHEN Nome = ? AND Sexo = ? THEN 0 
                        WHEN Nome = ? THEN 1 
                        WHEN Nome LIKE ? AND Sexo = ? THEN 2 
                        WHEN Nome LIKE ? THEN 3 
                        ELSE 4 
                    END, 
                    ID ASC 
                LIMIT 1
            ");
            $stC->execute([
                $clubName, 
                "%$clubName%", 
                $clubName, 
                $gender, 
                $clubName, 
                "$clubName%", 
                $gender, 
                "$clubName%"
            ]);
        } else {
            $stC = $conn->prepare("
                SELECT ID FROM clube 
                WHERE Nome = ? OR Nome LIKE ? 
                ORDER BY 
                    CASE 
                        WHEN Nome = ? THEN 0 
                        WHEN Nome LIKE ? THEN 1 
                        ELSE 2 
                    END, 
                    ID ASC 
                LIMIT 1
            ");
            $stC->execute([$clubName, "%$clubName%", $clubName, "$clubName%"]);
        }
        $clubId = (int)$stC->fetchColumn();
    }

    if ($clubId <= 0) {
        return ['formation' => '4-4-2', 'starters' => [], 'bench' => []];
    }

    $stmtP = $conn->prepare("
        SELECT j.ID, j.Nome, j.foto, j.Nivel, j.StringPosicoes
        FROM contratos_jogador cj
        INNER JOIN jogador j ON j.ID = cj.jogador
        WHERE cj.clube = ?
        ORDER BY j.Nivel DESC
    ");
    $stmtP->execute([$clubId]);
    $players = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    if (empty($players)) {
        return ['formation' => '4-4-2', 'starters' => [], 'bench' => []];
    }

    $posicoesMap = [
        0 => 'G', 1 => 'LD', 2 => 'Z', 3 => 'LE', 4 => 'V',
        5 => 'MD', 6 => 'MC', 7 => 'ME', 8 => 'MA', 9 => 'PD',
        10 => 'CA', 11 => 'PE', 12 => 'SA', 13 => 'AD', 14 => 'AE'
    ];

    $parsed = [];
    foreach ($players as $p) {
        $sp = str_pad((string)($p['StringPosicoes'] ?? ''), 15, '0');
        $pos = 'LIN';
        for ($i = 0; $i < 15; $i++) {
            if (isset($sp[$i]) && $sp[$i] === '1') {
                $pos = $posicoesMap[$i] ?? 'LIN';
                break;
            }
        }

        $photo = '';
        if (!empty($p['foto']) && $p['foto'] !== 'default.webp' && $p['foto'] !== '0.png') {
            $photo = (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '/images/jogadores/' . basename($p['foto']);
        }

        $parsed[] = [
            'id' => (int)$p['ID'],
            'name' => $p['Nome'],
            'number' => 0,
            'position' => $pos,
            'level' => (int)$p['Nivel'],
            'photo' => $photo,
            'is_gk' => ($pos === 'G')
        ];
    }

    $gks = array_values(array_filter($parsed, function($p) { return $p['is_gk']; }));
    $linePlayers = array_values(array_filter($parsed, function($p) { return !$p['is_gk']; }));

    $defs = [];
    $mids = [];
    $fwds = [];
    foreach ($linePlayers as $lp) {
        $pos = strtoupper(trim($lp['position'] ?? ''));
        if (in_array($pos, ['Z', 'LD', 'LE', 'AD', 'AE', 'DF', 'CB', 'RB', 'LB'])) $defs[] = $lp;
        elseif (in_array($pos, ['V', 'MC', 'MD', 'ME', 'MA', 'M', 'MF', 'DM', 'AM', 'CM', 'RM', 'LM'])) $mids[] = $lp;
        else $fwds[] = $lp;
    }

    $starters = [];
    if (!empty($gks)) $starters[] = array_shift($gks);
    elseif (!empty($linePlayers)) $starters[] = array_shift($linePlayers);

    for ($i = 0; $i < 4 && !empty($defs); $i++) $starters[] = array_shift($defs);
    for ($i = 0; $i < 4 && !empty($mids); $i++) $starters[] = array_shift($mids);
    for ($i = 0; $i < 2 && !empty($fwds); $i++) $starters[] = array_shift($fwds);

    $remainingPool = array_merge($defs, $mids, $fwds);
    usort($remainingPool, function($a, $b) { return $b['level'] <=> $a['level']; });
    while (count($starters) < 11 && !empty($remainingPool)) {
        $starters[] = array_shift($remainingPool);
    }

    $bench = array_merge($gks, $remainingPool);

    foreach ($starters as $idx => &$s) {
        $s['number'] = $idx + 1;
        if ($status !== 'next') {
            $goals = $playerEvents[$s['id']]['goals'] ?? 0;
            $yellows = $playerEvents[$s['id']]['yellows'] ?? 0;
            $reds = $playerEvents[$s['id']]['reds'] ?? 0;
            $s['rating'] = psCalculatePlayerRating($s['id'], $s['level'], $s['position'], true, $goals, $yellows, $reds, $teamScore, $oppScore, $matchId);
        } else {
            $s['rating'] = null;
        }
    }
    unset($s);

    foreach ($bench as $idx => &$b) {
        $b['number'] = count($starters) + $idx + 1;
        $b['rating'] = null;
    }
    unset($b);

    $def = 0; $mid = 0; $fwd = 0;
    foreach ($starters as $s) {
        $pos = strtoupper(trim($s['position'] ?? ''));
        if (in_array($pos, ['Z', 'LD', 'LE', 'AD', 'AE', 'DF', 'CB', 'RB', 'LB'])) $def++;
        elseif (in_array($pos, ['V', 'MC', 'MD', 'ME', 'MA', 'M', 'MF', 'DM', 'AM', 'CM', 'RM', 'LM'])) $mid++;
        elseif (in_array($pos, ['CA', 'SA', 'PD', 'PE', 'A', 'FW', 'ST', 'CF', 'RW', 'LW', 'AA'])) $fwd++;
    }
    $formation = ($def + $mid + $fwd > 0) ? "$def-$mid-$fwd" : '4-4-2';

    return [
        'formation' => $formation,
        'starters' => $starters,
        'bench' => $bench
    ];
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }
    
    // 1. Tentar buscar em jogos_clube (CONFUSA.top)
    $stmt = $conn->prepare("
        SELECT 
            j.id,
            j.timeA_id,
            COALESCE(cA.Nome, j.timeA_nome, CONCAT('Time ', j.timeA_id)) as home_team,
            cA.Escudo as home_logo,
            j.timeA_gols as home_score,
            j.timeA_penaltis as home_penalties,
            j.timeB_id,
            COALESCE(cB.Nome, j.timeB_nome, CONCAT('Time ', j.timeB_id)) as away_team,
            cB.Escudo as away_logo,
            j.timeB_gols as away_score,
            j.timeB_penaltis as away_penalties,
            j.data as match_date,
            COALESCE(NULLIF(j.estadio_nome, ''), eDirect.Nome, eHome.Nome, '') as stadium,
            j.status,
            j.fase,
            j.grupo,
            j.path,
            COALESCE(cl.nome, li.nome, cc.nome, '') as championship_name,
            cl.ano as championship_year,
            COALESCE(cl.genero, li.Sexo, cc.genero, 0) as championship_gender
        FROM jogos_clube j
        LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id AND j.simulador_interno = 1
        LEFT JOIN liga li ON li.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 0
        LEFT JOIN campeonatos_clube cc ON cc.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 1
        LEFT JOIN clube cA ON cA.ID = j.timeA_id
        LEFT JOIN clube cB ON cB.ID = j.timeB_id
        LEFT JOIN estadio eDirect ON eDirect.ID = j.estadio_id
        LEFT JOIN estadio eHome ON eHome.ID = cA.Estadio
        WHERE j.id = ?
    ");
    $stmt->execute([$matchId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $faseMap = [
            1 => 'Fase Preliminar',
            2 => 'Pontos Corridos / Grupos',
            3 => 'Oitavas de Final',
            4 => 'Quartas de Final',
            5 => 'Semifinal',
            6 => 'Disputa de 3º Lugar',
            8 => 'Final',
            9 => '16-avos de Final',
            10 => '32-avos de Final'
        ];
        
        $champName = !empty($row['championship_name']) ? ($row['championship_name'] . (!empty($row['championship_year']) ? ' ' . $row['championship_year'] : '')) : 'Competição CONFUSA';
        $rodadaName = $faseMap[(int)$row['fase']] ?? ('Fase ' . $row['fase']);
        if (!empty($row['grupo'])) {
            $rodadaName .= ' - Grupo ' . strtoupper(trim($row['grupo']));
        }
        
        $homeLogo = '';
        if (!empty($row['home_logo']) && $row['home_logo'] !== '0.png') {
            $homeLogo = (strpos($row['home_logo'], 'http') === 0) ? $row['home_logo'] : '/images/escudos/' . basename($row['home_logo']);
        }
        
        $awayLogo = '';
        if (!empty($row['away_logo']) && $row['away_logo'] !== '0.png') {
            $awayLogo = (strpos($row['away_logo'], 'http') === 0) ? $row['away_logo'] : '/images/escudos/' . basename($row['away_logo']);
        }
        
        $matchDateFormatted = !empty($row['match_date']) ? date('d/m/Y', strtotime($row['match_date'])) : '';
        $matchTimeFormatted = !empty($row['match_date']) ? date('H:i', strtotime($row['match_date'])) : '';
        
        $statusStr = ($row['status'] == 1) ? 'previous' : 'next';
        
        // Buscar eventos e montar lista de artilheiros
        $events = [];
        $homeGoalsList = [];
        $awayGoalsList = [];
        $playerEvents = [];
        
        // 1. Tentar buscar em jogos_clube_eventos
        try {
            $stmtEv = $conn->prepare("SELECT * FROM jogos_clube_eventos WHERE id_jogo = ? ORDER BY tempo DESC, minutos DESC, id_evento DESC");
            $stmtEv->execute([$matchId]);
            while ($evRow = $stmtEv->fetch(PDO::FETCH_ASSOC)) {
                $evTimeId = (int)($evRow['id_time'] ?? 0);
                $timeAId = (int)($row['timeA_id'] ?? 0);
                $timeBId = (int)($row['timeB_id'] ?? 0);
                $evTimeNome = trim((string)($evRow['nome_time'] ?? ''));
                $homeTeamName = trim((string)($row['home_team'] ?? ''));
                $awayTeamName = trim((string)($row['away_team'] ?? ''));

                $isHome = false;
                if ($evTimeId > 0 && $timeAId > 0 && $evTimeId === $timeAId) {
                    $isHome = true;
                } elseif ($evTimeId > 0 && $timeBId > 0 && $evTimeId === $timeBId) {
                    $isHome = false;
                } elseif (!empty($evTimeNome)) {
                    if (strcasecmp($evTimeNome, $homeTeamName) === 0) {
                        $isHome = true;
                    } elseif (strcasecmp($evTimeNome, $awayTeamName) === 0) {
                        $isHome = false;
                    }
                } else {
                    $isHome = true;
                }

                $tipo = (int)$evRow['tipo'];
                $minuto = $evRow['minutos'] ? ((int)$evRow['minutos'] . "'") : '';
                $pName = $evRow['nome_jogador'] ?: '';
                $pId = (int)($evRow['id_jogador'] ?? 0);
                
                $tipoStr = 'event';
                $desc = 'Lance';
                if ($tipo === 1) {
                    $tipoStr = 'goal';
                    $desc = 'Gol';
                    if ($pId > 0) $playerEvents[$pId]['goals'] = ($playerEvents[$pId]['goals'] ?? 0) + 1;
                } elseif ($tipo === 2) {
                    $tipoStr = 'yellow-card';
                    $desc = 'Cartão Amarelo';
                    if ($pId > 0) $playerEvents[$pId]['yellows'] = ($playerEvents[$pId]['yellows'] ?? 0) + 1;
                } elseif ($tipo === 3) {
                    $tipoStr = 'red-card';
                    $desc = 'Cartão Vermelho';
                    if ($pId > 0) $playerEvents[$pId]['reds'] = ($playerEvents[$pId]['reds'] ?? 0) + 1;
                } elseif ($tipo === 4) {
                    $tipoStr = 'own-goal';
                    $desc = 'Gol Contra';
                }
                
                $events[] = [
                    'minute' => $minuto,
                    'type' => $tipoStr,
                    'team_name' => $evRow['nome_time'] ?: ($isHome ? $row['home_team'] : $row['away_team']),
                    'player_name' => $pName,
                    'description' => $desc,
                    'side' => $isHome ? 'home' : 'away',
                    'is_home' => $isHome
                ];
                
                if ($tipo === 1 || $tipo === 4) {
                    $scorerEntry = ($pName ?: 'Gol') . ($minuto ? " $minuto" : "") . ($tipo === 4 ? " (contra)" : "");
                    if ($isHome) {
                        $homeGoalsList[] = $scorerEntry;
                    } else {
                        $awayGoalsList[] = $scorerEntry;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 2. Se eventos estiverem vazios e houver path, carregar do .hyl
        if (empty($events) && !empty($row['path'])) {
            $hylPath = psFindHyjFile($row['path']);
            if ($hylPath) {
                $hylPath = str_ireplace('.hyj', '.hyl', $hylPath);
            }
            if ($hylPath && file_exists($hylPath)) {
                $hylData = json_decode(file_get_contents($hylPath), true);
                if ($hylData && !empty($hylData['eventos']) && is_array($hylData['eventos'])) {
                    $hylPlayerNames = [];
                    foreach (array_merge($hylData['escalacaoTime1'] ?? [], $hylData['escalacaoTime2'] ?? []) as $hp) {
                        $hpId = (int)($hp['id'] ?? 0);
                        $hpNome = trim((string)($hp['nome'] ?? ''));
                        if ($hpId > 0 && $hpNome !== '') {
                            $hylPlayerNames[$hpId] = $hpNome;
                        }
                    }

                    $rawHylEvents = $hylData['eventos'];
                    usort($rawHylEvents, function($a, $b) {
                        $tA = isset($a['tempo']) ? (int)$a['tempo'] : 1;
                        $tB = isset($b['tempo']) ? (int)$b['tempo'] : 1;
                        if ($tA !== $tB) return $tB <=> $tA;
                        $mA = isset($a['minutos']) ? (int)$a['minutos'] : 0;
                        $mB = isset($b['minutos']) ? (int)$b['minutos'] : 0;
                        return $mB <=> $mA;
                    });

                    foreach ($rawHylEvents as $ev) {
                        $tipoEvStr = $ev['tipoEvento'] ?? '';
                        $tipoNum = 0;
                        $tipoStr = 'event';
                        $desc = 'Lance';
                        switch ($tipoEvStr) {
                            case 'gol':       $tipoNum = 1; $tipoStr = 'goal'; $desc = 'Gol'; break;
                            case 'amarelo':   $tipoNum = 2; $tipoStr = 'yellow-card'; $desc = 'Cartão Amarelo'; break;
                            case 'vermelho':  $tipoNum = 3; $tipoStr = 'red-card'; $desc = 'Cartão Vermelho'; break;
                            case 'golContra': $tipoNum = 4; $tipoStr = 'own-goal'; $desc = 'Gol Contra'; break;
                        }

                        $tempoRaw = isset($ev['tempo']) ? (int)$ev['tempo'] : 1;
                        if ($tempoRaw > 4) continue;

                        if ($tipoNum > 0) {
                            $pId = (int)($ev['idJogador'] ?? 0);
                            $pName = $hylPlayerNames[$pId] ?? '';
                            $teamNum = (int)($ev['time'] ?? 1);
                            $isHome = ($teamNum === 1);
                            $side = $isHome ? 'home' : 'away';
                            $nomeTm = $isHome ? $row['home_team'] : $row['away_team'];
                            $minuto = isset($ev['minutos']) ? (int)$ev['minutos'] : null;
                            $minutoStr = ($minuto !== null) ? $minuto . "'" : '';

                            $events[] = [
                                'minute' => $minutoStr,
                                'type' => $tipoStr,
                                'team_name' => $nomeTm,
                                'player_name' => $pName,
                                'description' => $desc,
                                'side' => $side,
                                'is_home' => $isHome
                            ];

                            if ($tipoNum === 1 || $tipoNum === 4) {
                                $scorerEntry = ($pName ?: 'Gol') . ($minutoStr ? " $minutoStr" : "") . ($tipoNum === 4 ? " (contra)" : "");
                                if ($isHome) {
                                    $homeGoalsList[] = $scorerEntry;
                                } else {
                                    $awayGoalsList[] = $scorerEntry;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // 3. Buscar ou Gerar Escalações (Lineups)
        $lineups = [
            'has_lineups' => false,
            'home' => [
                'formation' => '4-4-2',
                'starters' => [],
                'bench' => []
            ],
            'away' => [
                'formation' => '4-4-2',
                'starters' => [],
                'bench' => []
            ]
        ];

        // Buscar notas dos atletas a partir do arquivo .hyj se disponível
        $playerRatings = [];
        if (!empty($row['path'])) {
            $hyjPath = psFindHyjFile($row['path']);
            if ($hyjPath && file_exists($hyjPath)) {
                $hyjJson = @file_get_contents($hyjPath);
                if ($hyjJson) {
                    $hyjData = json_decode($hyjJson, true);
                    foreach (array_merge($hyjData['time1']['jogadores'] ?? [], $hyjData['time2']['jogadores'] ?? []) as $pj) {
                        $pjId = (int)($pj['idJogador'] ?? 0);
                        if ($pjId > 0 && isset($pj['nota']) && (float)$pj['nota'] > 0) {
                            $playerRatings[$pjId] = round((float)$pj['nota'], 1);
                        }
                    }
                }
            }
        }

        try {
            $stmtEsc = $conn->prepare("
                SELECT esc.id, esc.id_partida, esc.id_time, esc.nome_time, esc.posicao, esc.numero,
                       esc.id_jogador, COALESCE(NULLIF(esc.nome_jogador, ''), j.Nome, CONCAT('Jogador #', esc.id_jogador)) as nome_jogador,
                       esc.titular, j.foto, j.Nivel
                FROM jogos_clube_escalacao esc
                LEFT JOIN jogador j ON j.ID = esc.id_jogador
                WHERE esc.id_partida = ?
                ORDER BY esc.id_time ASC, esc.titular DESC, esc.id ASC
            ");
            $stmtEsc->execute([$matchId]);
            $rawEsc = $stmtEsc->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rawEsc)) {
                $lineups['has_lineups'] = true;
                $homeId = (int)$row['timeA_id'];
                $awayId = (int)$row['timeB_id'];
                $homeScore = (int)$row['home_score'];
                $awayScore = (int)$row['away_score'];

                foreach ($rawEsc as $p) {
                    $pId = (int)$p['id_jogador'];
                    $pTimeId = (int)$p['id_time'];
                    $isHomeTeam = ($pTimeId === $homeId) || ($pTimeId > 0 && $pTimeId !== $awayId);

                    $pPhoto = '';
                    if (!empty($p['foto']) && $p['foto'] !== 'default.webp' && $p['foto'] !== '0.png') {
                        $pPhoto = (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '/images/jogadores/' . basename($p['foto']);
                    }

                    $isTitular = ((int)$p['titular'] === 1);
                    $rating = null;
                    if ($statusStr !== 'next') {
                        if (isset($playerRatings[$pId])) {
                            $rating = $playerRatings[$pId];
                        } elseif ($isTitular) {
                            $goals = $playerEvents[$pId]['goals'] ?? 0;
                            $yellows = $playerEvents[$pId]['yellows'] ?? 0;
                            $reds = $playerEvents[$pId]['reds'] ?? 0;
                            $tScore = $isHomeTeam ? $homeScore : $awayScore;
                            $oScore = $isHomeTeam ? $awayScore : $homeScore;
                            $rating = psCalculatePlayerRating($pId, (int)($p['Nivel'] ?? 50), $p['posicao'] ?: 'LIN', true, $goals, $yellows, $reds, $tScore, $oScore, $matchId);
                        }
                    }

                    $playerEntry = [
                        'id' => $pId,
                        'name' => $p['nome_jogador'],
                        'number' => $p['numero'] ? (int)$p['numero'] : null,
                        'position' => $p['posicao'] ?: 'LIN',
                        'level' => (int)($p['Nivel'] ?? 0),
                        'photo' => $pPhoto,
                        'rating' => $rating
                    ];

                    $targetKey = $isHomeTeam ? 'home' : 'away';
                    if ($isTitular) {
                        $lineups[$targetKey]['starters'][] = $playerEntry;
                    } else {
                        $lineups[$targetKey]['bench'][] = $playerEntry;
                    }
                }

                $calcFormation = function($starters) {
                    $def = 0; $mid = 0; $fwd = 0;
                    foreach ($starters as $s) {
                        $pos = strtoupper(trim($s['position'] ?? ''));
                        if (in_array($pos, ['Z', 'LD', 'LE', 'AD', 'AE', 'DF', 'CB', 'RB', 'LB'])) $def++;
                        elseif (in_array($pos, ['V', 'MC', 'MD', 'ME', 'MA', 'M', 'MF', 'DM', 'AM', 'CM', 'RM', 'LM'])) $mid++;
                        elseif (in_array($pos, ['CA', 'SA', 'PD', 'PE', 'A', 'FW', 'ST', 'CF', 'RW', 'LW', 'AA', 'AM'])) $fwd++;
                    }
                    if ($def + $mid + $fwd > 0) return "$def-$mid-$fwd";
                    return '4-4-2';
                };

                $lineups['home']['formation'] = $calcFormation($lineups['home']['starters']);
                $lineups['away']['formation'] = $calcFormation($lineups['away']['starters']);
            } else {
                // Fallback: Gerar escalações pelo elenco dos clubes
                $homeLineup = psBuildLineupFromClubSquad($conn, (int)$row['timeA_id'], $row['home_team'], $matchId, true, (int)$row['home_score'], (int)$row['away_score'], $playerEvents, $statusStr);
                $awayLineup = psBuildLineupFromClubSquad($conn, (int)$row['timeB_id'], $row['away_team'], $matchId, false, (int)$row['away_score'], (int)$row['home_score'], $playerEvents, $statusStr);

                if (!empty($homeLineup['starters']) || !empty($awayLineup['starters'])) {
                    $lineups['has_lineups'] = true;
                    $lineups['home'] = $homeLineup;
                    $lineups['away'] = $awayLineup;
                }
            }
        } catch (\Throwable $e) {}
        
        $matchData = [
            'id' => (int)$row['id'],
            'championship' => $champName,
            'rodada' => $rodadaName,
            'match_date' => $matchDateFormatted,
            'match_time' => $matchTimeFormatted,
            'stadium' => $row['stadium'] ?: 'Estádio não informado',
            'home_id' => (int)$row['timeA_id'],
            'home_team' => $row['home_team'],
            'home_logo' => $homeLogo,
            'home_score' => ($row['status'] == 1) ? (int)$row['home_score'] : 0,
            'home_penalties' => ($row['home_penalties'] !== null) ? (int)$row['home_penalties'] : null,
            'home_scorers' => implode(', ', $homeGoalsList),
            'away_id' => (int)$row['timeB_id'],
            'away_team' => $row['away_team'],
            'away_logo' => $awayLogo,
            'away_score' => ($row['status'] == 1) ? (int)$row['away_score'] : 0,
            'away_penalties' => ($row['away_penalties'] !== null) ? (int)$row['away_penalties'] : null,
            'away_scorers' => implode(', ', $awayGoalsList),
            'status' => $statusStr,
            'gender' => (int)($row['championship_gender'] ?? 0)
        ];
        
        echo json_encode([
            'success' => true,
            'match' => $matchData,
            'events' => $events,
            'lineups' => $lineups
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 2. Fallback: buscar em poltrona_matches
    $stmtPoltrona = $conn->prepare("SELECT * FROM poltrona_matches WHERE id = ?");
    $stmtPoltrona->execute([$matchId]);
    $pMatch = $stmtPoltrona->fetch(PDO::FETCH_ASSOC);
    
    if ($pMatch) {
        $stmtEvents = $conn->prepare("SELECT * FROM poltrona_match_events WHERE match_id = ? ORDER BY id ASC");
        $stmtEvents->execute([$matchId]);
        $rawEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
        $events = [];
        $playerEvents = [];

        // Inferir gênero da partida raspada por campeonato e tabela demográfica gen_nomes
        $allScorers = trim(($pMatch['home_scorers'] ?? '') . ', ' . ($pMatch['away_scorers'] ?? ''));
        $namesToInfer = [];
        if (!empty($allScorers)) {
            foreach (explode(',', $allScorers) as $sc) {
                $cleanedSc = trim($sc);
                if (!empty($cleanedSc)) $namesToInfer[] = $cleanedSc;
            }
        }
        foreach ($rawEvents as $ev) {
            if (!empty($ev['player_name'])) {
                $namesToInfer[] = $ev['player_name'];
            }
        }

        $inferredGender = psInferMatchGender($conn, $pMatch['championship'] ?? '', $namesToInfer);
        $pMatch['gender'] = $inferredGender;

        // Buscar escalações a partir do elenco dos clubes respeitando o gênero inferido
        $homeScore = (int)($pMatch['home_score'] ?? 0);
        $awayScore = (int)($pMatch['away_score'] ?? 0);
        $pStatus = $pMatch['status'] ?? 'previous';

        $homeLineup = psBuildLineupFromClubSquad($conn, 0, $pMatch['home_team'], $matchId, true, $homeScore, $awayScore, $playerEvents, $pStatus, $inferredGender);
        $awayLineup = psBuildLineupFromClubSquad($conn, 0, $pMatch['away_team'], $matchId, false, $awayScore, $homeScore, $playerEvents, $pStatus, $inferredGender);

        $lineups = [
            'has_lineups' => (!empty($homeLineup['starters']) || !empty($awayLineup['starters'])),
            'home' => $homeLineup,
            'away' => $awayLineup
        ];

        // Preparar listas para resolução precisa de lado (mandante / visitante)
        $cleanScorers = function($scorersStr) {
            $arr = [];
            foreach (explode(',', (string)$scorersStr) as $s) {
                $cleaned = mb_strtolower(trim(preg_replace('/\s*\(.*?\)/', '', $s)), 'UTF-8');
                if ($cleaned !== '') $arr[] = $cleaned;
            }
            return $arr;
        };

        $homeScorersArr = $cleanScorers($pMatch['home_scorers'] ?? '');
        $awayScorersArr = $cleanScorers($pMatch['away_scorers'] ?? '');

        $homePlayerNames = [];
        foreach (array_merge($homeLineup['starters'] ?? [], $homeLineup['bench'] ?? []) as $hp) {
            if (!empty($hp['name'])) $homePlayerNames[] = mb_strtolower(trim($hp['name']), 'UTF-8');
        }
        $awayPlayerNames = [];
        foreach (array_merge($awayLineup['starters'] ?? [], $awayLineup['bench'] ?? []) as $ap) {
            if (!empty($ap['name'])) $awayPlayerNames[] = mb_strtolower(trim($ap['name']), 'UTF-8');
        }

        $homeTeamLower = mb_strtolower(trim($pMatch['home_team']), 'UTF-8');
        $awayTeamLower = mb_strtolower(trim($pMatch['away_team']), 'UTF-8');

        foreach ($rawEvents as $ev) {
            $evTeam = trim($ev['team_name'] ?? '');
            $evPlayer = trim($ev['player_name'] ?? '');
            $evDesc = trim($ev['description'] ?? '');

            $isHome = null;

            // 1. Por nome do time se preenchido
            if (!empty($evTeam)) {
                if (strcasecmp($evTeam, $pMatch['home_team']) === 0) {
                    $isHome = true;
                } elseif (strcasecmp($evTeam, $pMatch['away_team']) === 0) {
                    $isHome = false;
                }
            }

            // 2. Por lista de marcadores de gols
            if ($isHome === null && !empty($evPlayer)) {
                $cleanEvPlayer = mb_strtolower(trim(preg_replace('/\s*\(.*?\)/', '', $evPlayer)), 'UTF-8');
                $inHome = in_array($cleanEvPlayer, $homeScorersArr);
                $inAway = in_array($cleanEvPlayer, $awayScorersArr);
                if ($inHome && !$inAway) $isHome = true;
                elseif ($inAway && !$inHome) $isHome = false;
            }

            // 3. Por escalação/elenco do time
            if ($isHome === null && !empty($evPlayer)) {
                $cleanEvPlayer = mb_strtolower(trim($evPlayer), 'UTF-8');
                $inHomeSquad = in_array($cleanEvPlayer, $homePlayerNames);
                $inAwaySquad = in_array($cleanEvPlayer, $awayPlayerNames);
                if ($inHomeSquad && !$inAwaySquad) $isHome = true;
                elseif ($inAwaySquad && !$inHomeSquad) $isHome = false;
            }

            // 4. Por menções no texto da descrição
            if ($isHome === null && !empty($evDesc)) {
                $descLower = mb_strtolower($evDesc, 'UTF-8');
                $homeMentions = preg_match('/\b(para o|para a|do|da|gol do|gol da)\s+' . preg_quote($homeTeamLower, '/') . '\b/iu', $descLower);
                $awayMentions = preg_match('/\b(para o|para a|do|da|gol do|gol da)\s+' . preg_quote($awayTeamLower, '/') . '\b/iu', $descLower);

                if ($homeMentions && !$awayMentions) $isHome = true;
                elseif ($awayMentions && !$homeMentions) $isHome = false;
                elseif (mb_stripos($descLower, $homeTeamLower) !== false && mb_stripos($descLower, $awayTeamLower) === false) $isHome = true;
                elseif (mb_stripos($descLower, $awayTeamLower) !== false && mb_stripos($descLower, $homeTeamLower) === false) $isHome = false;
            }

            // Padrão se ainda indefinido: mandante (home)
            if ($isHome === null) {
                $isHome = true;
            }

            $evType = $ev['type'] ?: 'event';
            $evDesc = $ev['description'] ?: '';

            if ($evType === 'lance-cartao') {
                if (stripos($evDesc, 'vermelho') !== false) {
                    $evType = 'red-card';
                } else {
                    $evType = 'yellow-card';
                }
            } elseif ($evType === 'lance-gol') {
                if (stripos($evDesc, 'contra') !== false) {
                    $evType = 'own-goal';
                } else {
                    $evType = 'goal';
                }
            }

            $finalTeamName = $evTeam ?: ($isHome ? $pMatch['home_team'] : $pMatch['away_team']);

            $events[] = [
                'minute' => !empty($ev['minute']) ? (rtrim($ev['minute'], "'") . "'") : '',
                'type' => $evType,
                'team_name' => $finalTeamName,
                'player_name' => $ev['player_name'] ?: '',
                'description' => $evDesc,
                'side' => $isHome ? 'home' : 'away',
                'is_home' => $isHome
            ];
        }

        echo json_encode([
            'success' => true,
            'match' => $pMatch,
            'events' => $events,
            'lineups' => $lineups
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Partida não encontrada']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
