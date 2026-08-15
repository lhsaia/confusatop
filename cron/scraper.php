<?php
require_once dirname(__DIR__) . '/lib/scraper.php';

// Only allow command line execution (CLI/CGI) or manual trigger with key/flag
$isCommandLine = (php_sapi_name() === 'cli') || !isset($_SERVER['HTTP_HOST']);
if (!$isCommandLine && !isset($_GET['trigger'])) {
    http_response_code(403);
    die("Access denied.");
}

$scraper = new PoltronaScraper();
$result = $scraper->run();

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
