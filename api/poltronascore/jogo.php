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
            j.estadio_nome as stadium,
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
        
        // Buscar eventos se houver tabela jogos_clube_eventos
        $events = [];
        try {
            $stmtEv = $conn->prepare("SELECT * FROM jogos_clube_eventos WHERE match_id = ? ORDER BY minuto ASC, id ASC");
            $stmtEv->execute([$matchId]);
            while ($evRow = $stmtEv->fetch(PDO::FETCH_ASSOC)) {
                $events[] = [
                    'minute' => $evRow['minuto'] . "'",
                    'type' => $evRow['tipo'],
                    'team_name' => $evRow['time_nome'] ?? '',
                    'player_name' => $evRow['jogador_nome'] ?? '',
                    'description' => $evRow['descricao'] ?? ''
                ];
            }
        } catch (\Throwable $e) {}
        
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
            'home_scorers' => '',
            'away_team' => $row['away_team'],
            'away_logo' => $awayLogo,
            'away_score' => ($row['status'] == 1) ? (int)$row['away_score'] : 0,
            'away_penalties' => ($row['away_penalties'] !== null) ? (int)$row['away_penalties'] : null,
            'away_scorers' => '',
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
        $stmtEvents = $conn->prepare("SELECT * FROM poltrona_match_events WHERE match_id = ? ORDER BY id DESC");
        $stmtEvents->execute([$matchId]);
        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
        
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
