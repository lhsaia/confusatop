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
    $allNextMatches = $stmtNext->fetchAll(PDO::FETCH_ASSOC);

    // Filter out "next" matches whose date+time has already passed (stale status from source).
    // match_date is "DD/MM", match_time is "HH:MM". Year is inferred as current year (or next if month already passed).
    $nowTs = time();
    $currentYear = (int)date('Y');
    $nextMatches = [];
    $staleNextIds = [];
    foreach ($allNextMatches as $m) {
        $dateParts = explode('/', $m['match_date'] ?? '');
        if (count($dateParts) === 2) {
            $day   = (int)$dateParts[0];
            $month = (int)$dateParts[1];
            $timeParts = explode(':', $m['match_time'] ?? '00:00');
            $hour   = isset($timeParts[0]) ? (int)$timeParts[0] : 0;
            $minute = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
            // If the month already passed this year, assume next year
            $year = ($month < (int)date('n')) ? $currentYear + 1 : $currentYear;
            $matchTs = mktime($hour, $minute, 0, $month, $day, $year);
            if ($matchTs !== false && $matchTs < $nowTs) {
                // This "next" match is in the past — auto-correct status in DB
                $staleNextIds[] = (int)$m['id'];
                // Treat it as previous for this response
                $m['status'] = 'previous';
                // Add to previous list (will be sorted below)
                // We'll collect them into $prevMatches after fetching
                continue;
            }
        }
        $nextMatches[] = $m;
    }
    // Auto-correct stale "next" matches to "previous" in the database
    if (!empty($staleNextIds)) {
        $placeholdersFix = implode(',', array_fill(0, count($staleNextIds), '?'));
        $stmtFix = $conn->prepare("UPDATE poltrona_matches SET status = 'previous' WHERE id IN ($placeholdersFix)");
        $stmtFix->execute($staleNextIds);
    }

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
