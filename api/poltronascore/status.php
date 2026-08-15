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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Fetch last run details
    $stmtLast = $conn->query("SELECT started_at, success, items_found, error_message, duration_ms FROM poltrona_scraper_runs ORDER BY id DESC LIMIT 1");
    $lastRun = $stmtLast->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'last_scrape' => $lastRun ? date('c', strtotime($lastRun['started_at'])) : null,
        'last_scrape_success' => $lastRun ? (bool)$lastRun['success'] : false,
        'items_found' => $lastRun ? intval($lastRun['items_found']) : 0,
        'error_message' => $lastRun ? $lastRun['error_message'] : null,
        'duration_ms' => $lastRun ? intval($lastRun['duration_ms']) : 0
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
