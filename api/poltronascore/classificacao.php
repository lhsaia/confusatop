<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados MySQL.");
    }
    
    $compId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $compParam = trim($_GET['championship'] ?? '');
    
    if ($compId <= 0 && !empty($compParam)) {
        if (is_numeric($compParam)) {
            $compId = (int)$compParam;
        } else {
            $stmtFind = $conn->prepare("
                SELECT c.id 
                FROM competicao_lista c
                INNER JOIN jogos_clube j ON j.competicao_id = c.id AND j.simulador_interno = 1
                WHERE c.nome LIKE ? OR CONCAT(c.nome, ' ', c.ano) LIKE ?
                ORDER BY c.ano DESC, c.id DESC
                LIMIT 1
            ");
            $stmtFind->execute(["%$compParam%", "%$compParam%"]);
            $compId = (int)$stmtFind->fetchColumn();
        }
    }
    
    if ($compId <= 0) {
        $stmtLatest = $conn->query("
            SELECT c.id 
            FROM competicao_lista c
            INNER JOIN jogos_clube j ON j.competicao_id = c.id AND j.simulador_interno = 1
            GROUP BY c.id
            ORDER BY c.ano DESC, c.id DESC
            LIMIT 1
        ");
        $compId = (int)$stmtLatest->fetchColumn();
    }
    
    if ($compId <= 0) {
        echo json_encode([
            'success' => true,
            'competition_id' => 0,
            'championship' => '',
            'competition_type' => 0,
            'has_standings' => false,
            'has_bracket' => false,
            'has_groups' => false,
            'groups' => [],
            'standings' => [],
            'bracket' => [],
            'rounds' => []
        ]);
        exit;
    }
    
    // 1. Informações da competição
    $stmtComp = $conn->prepare("SELECT id, nome, ano, logo, tipo FROM competicao_lista WHERE id = ?");
    $stmtComp->execute([$compId]);
    $compInfo = $stmtComp->fetch(PDO::FETCH_ASSOC);
    
    $compDisplayName = $compInfo ? ($compInfo['nome'] . (!empty($compInfo['ano']) ? ' ' . $compInfo['ano'] : '')) : "Competição #$compId";
    $compTipo = isset($compInfo['tipo']) ? (int)$compInfo['tipo'] : 0; // 0 = Misto, 1 = Mata-mata, 2 = Pontos Corridos
    
    // 2. Partidas da competição (estritamente simulador_interno = 1)
    $stmtMatches = $conn->prepare("
        SELECT 
            j.id,
            j.timeA_id,
            j.timeA_nome,
            j.timeA_gols,
            j.timeB_id,
            j.timeB_nome,
            j.timeB_gols,
            j.timeA_penaltis,
            j.timeB_penaltis,
            j.data,
            j.fase,
            j.grupo,
            j.estadio_nome,
            j.status
        FROM jogos_clube j
        WHERE j.competicao_id = ? AND j.simulador_interno = 1
        ORDER BY j.data ASC, j.id ASC
    ");
    $stmtMatches->execute([$compId]);
    $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Escudos e nomes de clubes
    $teamIds = [];
    foreach ($matches as $m) {
        if ($m['timeA_id'] > 0) $teamIds[(int)$m['timeA_id']] = true;
        if ($m['timeB_id'] > 0) $teamIds[(int)$m['timeB_id']] = true;
    }
    
    $teamLogos = [];
    $teamNames = [];
    if (!empty($teamIds)) {
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        try {
            $stmtTeams = $conn->prepare("SELECT ID as id, Nome as nome, TresLetras as sigla, Escudo as escudo FROM clube WHERE ID IN ($placeholders)");
            $stmtTeams->execute(array_keys($teamIds));
            while ($t = $stmtTeams->fetch(PDO::FETCH_ASSOC)) {
                $logoUrl = '';
                if (!empty($t['escudo']) && $t['escudo'] !== '0.png') {
                    $logoUrl = (strpos($t['escudo'], 'http') === 0) ? $t['escudo'] : '/images/escudos/' . basename($t['escudo']);
                }
                $teamLogos[(int)$t['id']] = $logoUrl;
                $teamNames[(int)$t['id']] = $t['nome'];
            }
        } catch (\Throwable $e) {}
    }
    
    $faseNamesMap = [
        10 => "32-avos de Final",
        9  => "16-avos de Final",
        1  => "Fase Preliminar",
        2  => "Fase de Grupos",
        3  => "Oitavas de Final",
        4  => "Quartas de Final",
        5  => "Semifinal",
        6  => "Decisão do 3º Lugar",
        7  => "Repescagem",
        8  => "Final"
    ];
    
    $groups = [];
    $teamForms = [];
    $bracketPhases = [];
    $rounds = [];
    
    $hasGroupMatches = false;
    $hasKnockoutMatches = false;
    
    foreach ($matches as $m) {
        $faseInt = (int)$m['fase'];
        if ($faseInt === 2 || ($compTipo === 2 && $faseInt <= 2)) {
            $hasGroupMatches = true;
        }
        if ($faseInt > 2 || $compTipo === 1) {
            $hasKnockoutMatches = true;
        }
    }
    
    foreach ($matches as $m) {
        $idA = (int)$m['timeA_id'];
        $idB = (int)$m['timeB_id'];
        $nameA = !empty($teamNames[$idA]) ? $teamNames[$idA] : ($m['timeA_nome'] ?: ($idA > 0 ? "Time $idA" : "A definir"));
        $nameB = !empty($teamNames[$idB]) ? $teamNames[$idB] : ($m['timeB_nome'] ?: ($idB > 0 ? "Time $idB" : "A definir"));
        $logoA = $teamLogos[$idA] ?? '';
        $logoB = $teamLogos[$idB] ?? '';
        $faseInt = (int)$m['fase'];
        
        $grpKey = !empty($m['grupo']) ? 'Grupo ' . strtoupper(trim($m['grupo'])) : 'Geral';
        $isFinished = ($m['status'] == 1);
        $statusLabel = $isFinished ? 'previous' : 'next';
        
        $matchDateFormatted = !empty($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '';
        $matchTimeFormatted = !empty($m['data']) ? date('H:i', strtotime($m['data'])) : '';
        
        $golsA = $isFinished ? (int)$m['timeA_gols'] : null;
        $golsB = $isFinished ? (int)$m['timeB_gols'] : null;
        $penA = ($m['timeA_penaltis'] !== null && $m['timeA_penaltis'] !== '') ? (int)$m['timeA_penaltis'] : null;
        $penB = ($m['timeB_penaltis'] !== null && $m['timeB_penaltis'] !== '') ? (int)$m['timeB_penaltis'] : null;
        
        // Determinar vencedor da partida
        $winner = null;
        if ($isFinished) {
            if ($golsA > $golsB) {
                $winner = 'home';
            } elseif ($golsB > $golsA) {
                $winner = 'away';
            } elseif ($penA !== null && $penB !== null) {
                if ($penA > $penB) $winner = 'home';
                elseif ($penB > $penA) $winner = 'away';
            }
        }
        
        $matchFormatted = [
            'id' => (int)$m['id'],
            'home_id' => $idA,
            'home_team' => $nameA,
            'home_logo' => $logoA,
            'home_score' => $golsA,
            'home_penalties' => $penA,
            'away_id' => $idB,
            'away_team' => $nameB,
            'away_logo' => $logoB,
            'away_score' => $golsB,
            'away_penalties' => $penB,
            'winner' => $winner,
            'match_date' => $matchDateFormatted,
            'match_time' => $matchTimeFormatted,
            'stadium' => $m['estadio_nome'] ?: '',
            'status' => $statusLabel
        ];
        
        // 1. Processar Classificação (apenas se for fase de grupos/pontos corridos E competição NÃO for mata-mata pura)
        if ($compTipo !== 1 && ($faseInt === 2 || ($compTipo === 2 && $faseInt <= 2))) {
            if (!isset($groups[$grpKey])) {
                $groups[$grpKey] = [];
            }
            if ($idA > 0 && !isset($groups[$grpKey][$idA])) {
                $groups[$grpKey][$idA] = [
                    'id' => $idA,
                    'team' => $nameA,
                    'logo' => $logoA,
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_diff' => 0,
                    'points' => 0,
                    'percentage' => 0,
                    'form' => []
                ];
            }
            if ($idB > 0 && !isset($groups[$grpKey][$idB])) {
                $groups[$grpKey][$idB] = [
                    'id' => $idB,
                    'team' => $nameB,
                    'logo' => $logoB,
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_diff' => 0,
                    'points' => 0,
                    'percentage' => 0,
                    'form' => []
                ];
            }
            
            if ($isFinished && $idA > 0 && $idB > 0) {
                $groups[$grpKey][$idA]['played']++;
                $groups[$grpKey][$idB]['played']++;
                $groups[$grpKey][$idA]['goals_for'] += $golsA;
                $groups[$grpKey][$idA]['goals_against'] += $golsB;
                $groups[$grpKey][$idB]['goals_for'] += $golsB;
                $groups[$grpKey][$idB]['goals_against'] += $golsA;
                
                if ($golsA > $golsB) {
                    $groups[$grpKey][$idA]['won']++;
                    $groups[$grpKey][$idA]['points'] += 3;
                    $groups[$grpKey][$idB]['lost']++;
                    $teamForms[$idA][] = ['result' => 'V', 'opponent' => $nameB, 'score' => "$golsA-$golsB"];
                    $teamForms[$idB][] = ['result' => 'D', 'opponent' => $nameA, 'score' => "$golsB-$golsA"];
                } elseif ($golsA < $golsB) {
                    $groups[$grpKey][$idB]['won']++;
                    $groups[$grpKey][$idB]['points'] += 3;
                    $groups[$grpKey][$idA]['lost']++;
                    $teamForms[$idA][] = ['result' => 'D', 'opponent' => $nameB, 'score' => "$golsA-$golsB"];
                    $teamForms[$idB][] = ['result' => 'V', 'opponent' => $nameA, 'score' => "$golsB-$golsA"];
                } else {
                    $groups[$grpKey][$idA]['drawn']++;
                    $groups[$grpKey][$idA]['points'] += 1;
                    $groups[$grpKey][$idB]['drawn']++;
                    $groups[$grpKey][$idB]['points'] += 1;
                    $teamForms[$idA][] = ['result' => 'E', 'opponent' => $nameB, 'score' => "$golsA-$golsB"];
                    $teamForms[$idB][] = ['result' => 'E', 'opponent' => $nameA, 'score' => "$golsB-$golsA"];
                }
            }
        }
        
        // 2. Processar Mata-Mata / Chaveamento (fase > 2 ou tipo = 1)
        if ($faseInt > 2 || ($compTipo === 1 && $faseInt !== 2)) {
            $bracketPhases[$faseInt][] = $matchFormatted;
        }
        
        // 3. Processar Rodadas & Jogos
        $faseTitle = $faseNamesMap[$faseInt] ?? ('Fase ' . $faseInt);
        if (!empty($m['grupo'])) {
            $faseTitle .= ' - Grupo ' . strtoupper(trim($m['grupo']));
        }
        $rounds[$faseTitle][] = $matchFormatted;
    }
    
    // Processar e ordenar cada grupo da classificação
    $sortedGroups = [];
    foreach ($groups as $grpName => $teamsList) {
        foreach ($teamsList as $tId => &$teamData) {
            $teamData['goal_diff'] = $teamData['goals_for'] - $teamData['goals_against'];
            $maxPts = $teamData['played'] * 3;
            $teamData['percentage'] = $maxPts > 0 ? round(($teamData['points'] / $maxPts) * 100, 1) : 0;
            $fList = $teamForms[$tId] ?? [];
            $teamData['form'] = array_slice($fList, -5);
        }
        unset($teamData);
        
        $tableArr = array_values($teamsList);
        usort($tableArr, function($a, $b) {
            if ($a['points'] !== $b['points']) return $b['points'] <=> $a['points'];
            if ($a['won'] !== $b['won']) return $b['won'] <=> $a['won'];
            if ($a['goal_diff'] !== $b['goal_diff']) return $b['goal_diff'] <=> $a['goal_diff'];
            if ($a['goals_for'] !== $b['goals_for']) return $b['goals_for'] <=> $a['goals_for'];
            return strcmp($a['team'], $b['team']);
        });
        
        foreach ($tableArr as $idx => &$row) {
            $row['position'] = $idx + 1;
        }
        unset($row);
        
        $sortedGroups[$grpName] = $tableArr;
    }
    
    $flatStandings = isset($sortedGroups['Geral']) ? $sortedGroups['Geral'] : (count($sortedGroups) === 1 ? reset($sortedGroups) : []);
    
    // Ordenar fases do chaveamento de mata-mata na ordem cronológica de disputa
    $faseChronologicalOrder = [10 => 1, 9 => 2, 1 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 7, 7 => 8, 8 => 9];
    uksort($bracketPhases, function($a, $b) use ($faseChronologicalOrder) {
        $oa = $faseChronologicalOrder[$a] ?? (100 + $a);
        $ob = $faseChronologicalOrder[$b] ?? (100 + $b);
        return $oa - $ob;
    });
    
    $structuredBracket = [];
    foreach ($bracketPhases as $fId => $fMatches) {
        $structuredBracket[] = [
            'phase_id' => $fId,
            'phase_name' => $faseNamesMap[$fId] ?? ("Fase " . $fId),
            'matches' => $fMatches
        ];
    }
    
    // Determinar quais abas devem ser exibidas
    $hasStandings = ($compTipo !== 1 && (!empty($flatStandings) || !empty($sortedGroups)));
    $hasBracket = !empty($structuredBracket);
    
    echo json_encode([
        'success' => true,
        'competition_id' => $compId,
        'championship' => $compDisplayName,
        'competition_type' => $compTipo,
        'has_standings' => $hasStandings,
        'has_bracket' => $hasBracket,
        'has_groups' => count($sortedGroups) > 1,
        'groups' => $sortedGroups,
        'standings' => $flatStandings,
        'bracket' => $structuredBracket,
        'rounds' => $rounds
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
