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
            COALESCE(cl.nome, '') as championship_name,
            cl.ano as championship_year
        FROM jogos_clube j
        LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id AND j.simulador_interno = 1
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
        
        // 1. Tentar buscar em jogos_clube_eventos
        try {
            $stmtEv = $conn->prepare("SELECT * FROM jogos_clube_eventos WHERE id_jogo = ? ORDER BY tempo DESC, minutos DESC, id_evento DESC");
            $stmtEv->execute([$matchId]);
            while ($evRow = $stmtEv->fetch(PDO::FETCH_ASSOC)) {
                $tipoNum = (int)$evRow['tipo'];
                $tipoStr = 'event';
                $desc = 'Lance';
                switch ($tipoNum) {
                    case 1:
                        $tipoStr = 'goal';
                        $desc = 'Gol';
                        break;
                    case 2:
                        $tipoStr = 'yellow-card';
                        $desc = 'Cartão Amarelo';
                        break;
                    case 3:
                        $tipoStr = 'red-card';
                        $desc = 'Cartão Vermelho';
                        break;
                    case 4:
                        $tipoStr = 'own-goal';
                        $desc = 'Gol Contra';
                        break;
                }

                $minuto = ($evRow['minutos'] !== null && $evRow['minutos'] !== '') ? (int)$evRow['minutos'] : null;
                $minutoStr = ($minuto !== null) ? $minuto . "'" : '';
                if ((int)$evRow['tempo'] === 3 || (int)$evRow['tempo'] === 4) {
                    $minutoStr .= ($minutoStr ? ' (Prorr.)' : 'Prorrogação');
                }

                $pName = trim((string)($evRow['nome_jogador'] ?? ''));
                $tName = trim((string)($evRow['nome_time'] ?? ''));
                $tId = (int)($evRow['id_time'] ?? 0);
                $isHome = ($tId > 0 && $tId === (int)$row['timeA_id']) || (!empty($tName) && $tName === $row['home_team']);
                $side = $isHome ? 'home' : 'away';

                $events[] = [
                    'minute' => $minutoStr,
                    'type' => $tipoStr,
                    'team_name' => $tName,
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
        } catch (\Throwable $e) {}

        // 2. Se vazio, tentar carregar a partir do arquivo .hyl
        if (empty($events) && !empty($row['path'])) {
            $baseRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : dirname(__DIR__, 2);
            $cleanPath = basename($row['path'], '.hyl');
            $hylFiles = glob($baseRoot . "/competicoes/hexacolor/Partidas/*/*/" . $cleanPath . ".hyl");
            if (empty($hylFiles)) {
                $hylFiles = glob($baseRoot . "/full hexa suite/Hexacolor YMT/Partidas/*/*/" . $cleanPath . ".hyl");
            }
            if (!empty($hylFiles) && file_exists($hylFiles[0])) {
                $hylData = json_decode(file_get_contents($hylFiles[0]), true);
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
                    // Ordenar eventos hyl de forma decrescente (tempo DESC, minutos DESC)
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
                        if ($tempoRaw > 4) continue; // Pênaltis pós-jogo

                        if ($tipoNum > 0) {
                            $pId = (int)($ev['idJogador'] ?? 0);
                            $pName = $hylPlayerNames[$pId] ?? '';
                            if (empty($pName) && $pId > 0) {
                                try {
                                    $stP = $conn->prepare("SELECT Nome FROM jogador WHERE ID = ? LIMIT 1");
                                    $stP->execute([$pId]);
                                    $pName = (string)$stP->fetchColumn();
                                } catch (\Throwable $e) {}
                            }

                            $teamNum = (int)($ev['time'] ?? 1);
                            $isHome = ($teamNum === 1);
                            $side = $isHome ? 'home' : 'away';
                            $nomeTm = $isHome ? $row['home_team'] : $row['away_team'];
                            $minuto = isset($ev['minutos']) ? (int)$ev['minutos'] : null;
                            $minutoStr = ($minuto !== null) ? $minuto . "'" : '';
                            if ($tempoRaw === 3 || $tempoRaw === 4) {
                                $minutoStr .= ($minutoStr ? ' (Prorr.)' : 'Prorrogação');
                            }

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
        
        $matchData = [
            'id' => (int)$row['id'],
            'championship' => $champName,
            'rodada' => $rodadaName,
            'match_date' => $matchDateFormatted,
            'match_time' => $matchTimeFormatted,
            'stadium' => $row['stadium'] ?: 'Estádio não informado',
            'home_team' => $row['home_team'],
            'home_logo' => $homeLogo,
            'home_score' => ($row['status'] == 1) ? (int)$row['home_score'] : 0,
            'home_penalties' => ($row['home_penalties'] !== null) ? (int)$row['home_penalties'] : null,
            'home_scorers' => implode(', ', $homeGoalsList),
            'away_team' => $row['away_team'],
            'away_logo' => $awayLogo,
            'away_score' => ($row['status'] == 1) ? (int)$row['away_score'] : 0,
            'away_penalties' => ($row['away_penalties'] !== null) ? (int)$row['away_penalties'] : null,
            'away_scorers' => implode(', ', $awayGoalsList),
            'status' => $statusStr
        ];
        
        echo json_encode([
            'success' => true,
            'match' => $matchData,
            'events' => $events
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
        foreach ($rawEvents as $ev) {
            $isHome = (!empty($ev['team_name']) && trim($ev['team_name']) === trim($pMatch['home_team']));
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

            $events[] = [
                'minute' => !empty($ev['minute']) ? (rtrim($ev['minute'], "'") . "'") : '',
                'type' => $evType,
                'team_name' => $ev['team_name'] ?: '',
                'player_name' => $ev['player_name'] ?: '',
                'description' => $evDesc,
                'side' => $isHome ? 'home' : 'away',
                'is_home' => $isHome
            ];
        }
        
        echo json_encode([
            'success' => true,
            'match' => $pMatch,
            'events' => $events
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
