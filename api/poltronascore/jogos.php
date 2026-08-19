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
    
    // 1. Get Live Matches (ordered chronologically)
    $stmtLive = $conn->query("SELECT * FROM poltrona_matches WHERE status = 'live' ORDER BY match_date ASC, match_time ASC, id ASC");
    $liveMatches = $stmtLive->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Get Next Matches (ordered chronologically ASC - soonest first)
    $stmtNext = $conn->query("SELECT * FROM poltrona_matches WHERE status = 'next' ORDER BY match_date ASC, match_time ASC, id ASC");
    $nextMatches = $stmtNext->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Get Previous Matches (we will sort them robustly in PHP next)
    $stmtPrev = $conn->query("SELECT * FROM poltrona_matches WHERE status = 'previous'");
    $prevMatches = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);
    
    // Sort previous matches chronologically (DESC - most recent first)
    usort($prevMatches, function($a, $b) {
        $dateA = explode('/', $a['match_date'] ?? '');
        $dateB = explode('/', $b['match_date'] ?? '');
        
        $dayA = isset($dateA[0]) ? (int)$dateA[0] : 0;
        $monthA = isset($dateA[1]) ? (int)$dateA[1] : 0;
        
        $dayB = isset($dateB[0]) ? (int)$dateB[0] : 0;
        $monthB = isset($dateB[1]) ? (int)$dateB[1] : 0;
        
        if ($monthA !== $monthB) {
            return $monthB <=> $monthA;
        }
        if ($dayA !== $dayB) {
            return $dayB <=> $dayA;
        }
        
        $timeA = $a['match_time'] ?? '00:00';
        $timeB = $b['match_time'] ?? '00:00';
        if ($timeA !== $timeB) {
            return strcmp($timeB, $timeA);
        }
        
        return $b['id'] <=> $a['id'];
    });
    
    // Group helper function (for live and next - by championship)
    $groupByChampionship = function($matches) {
        $grouped = [];
        foreach ($matches as $match) {
            $champKey = $match['championship'];
            if (!empty($match['rodada'])) {
                $champKey .= ' - ' . $match['rodada'];
            }
            if (empty($champKey)) {
                $champKey = "Outras Competições";
            }
            $grouped[$champKey][] = $match;
        }
        return $grouped;
    };

    // Group helper function (for previous - by date)
    $groupByDate = function($matches) {
        $grouped = [];
        foreach ($matches as $match) {
            $dateKey = $match['match_date'];
            if (empty($dateKey)) {
                $dateKey = "Sem Data";
            }
            $grouped[$dateKey][] = $match;
        }
        return $grouped;
    };
    
    // Get last scrape status
    $stmtStatus = $conn->query("SELECT started_at, success FROM poltrona_scraper_runs ORDER BY id DESC LIMIT 1");
    $statusData = $stmtStatus->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'last_update' => $statusData ? $statusData['started_at'] : null,
        'last_update_success' => $statusData ? (bool)$statusData['success'] : false,
        'data' => [
            'live' => $groupByChampionship($liveMatches),
            'next' => $groupByChampionship($nextMatches),
            'previous' => $groupByDate($prevMatches)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
