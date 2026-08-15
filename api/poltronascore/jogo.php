<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/lib/scraper.php' 
    : dirname(__DIR__, 2) . '/lib/scraper.php';

$scraperInit = new PoltronaScraper();

$matchId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid match ID']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fetch match details
    $stmt = $conn->prepare("SELECT * FROM poltrona_matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Match not found']);
        exit;
    }
    
    // Fetch match timeline events
    $stmtEvents = $conn->prepare("SELECT * FROM poltrona_match_events WHERE match_id = ? ORDER BY id DESC");
    $stmtEvents->execute([$matchId]);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'match' => $match,
        'events' => $events
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
