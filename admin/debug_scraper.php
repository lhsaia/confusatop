<?php
declare(strict_types=1);

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['admin_status'] !== 1) {
    header('Location: /index.php');
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== POLTRONASCORE PRODUCTION DEBUG ===\n\n";

// 1. Check PHP Extensions
echo "1. Checking PHP Extensions:\n";
$extensions = ['curl', 'dom', 'pdo', 'pdo_mysql'];
foreach ($extensions as $ext) {
    echo "  - $ext: " . (extension_loaded($ext) ? "INSTALLED" : "MISSING ❌") . "\n";
}

// 2. Check Database Connection
echo "\n2. Checking Database Connection:\n";
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "  - Connection: SUCCESSFUL\n";
        
        // Show current tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  - Current Tables: " . implode(', ', $tables) . "\n";
    } else {
        echo "  - Connection: FAILED (Returned null)\n";
    }
} catch (Exception $e) {
    echo "  - Connection: FAILED with error: " . $e->getMessage() . " ❌\n";
}

// 3. Test Table Creation
echo "\n3. Testing Table Creation:\n";
try {
    echo "  - Attempting to create poltrona_teams...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS poltrona_teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        logo_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "  - poltrona_teams: OK\n";
    
    echo "  - Attempting to create poltrona_matches...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS poltrona_matches (
        id INT PRIMARY KEY,
        home_team VARCHAR(100) NOT NULL,
        away_team VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL
    )");
    echo "  - poltrona_matches: OK\n";
    
    echo "  - Attempting to create poltrona_scraper_runs...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS poltrona_scraper_runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0
    )");
    echo "  - poltrona_scraper_runs: OK\n";
} catch (Exception $e) {
    echo "  - Table Creation: FAILED with error: " . $e->getMessage() . " ❌\n";
}

// 4. Test External Connectivity
echo "\n4. Testing External Connectivity:\n";
$testUrl = "http://52.203.150.214:8080/CONFUSALive/matches?when=live";
echo "  - Fetching URL: $testUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response !== false) {
    echo "  - HTTP Status Code: $httpCode\n";
    echo "  - Response Length: " . strlen($response) . " bytes\n";
    if ($httpCode === 200) {
        echo "  - Connectivity: SUCCESSFUL\n";
    } else {
        echo "  - Connectivity: FAILED (Non-200 Status) ❌\n";
    }
} else {
    echo "  - Connectivity: FAILED with curl error: $curlError ❌\n";
}

// 5. Test Run Scraper
echo "\n5. Testing Scraper Execution:\n";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/scraper.php';
try {
    $scraper = new PoltronaScraper();
    echo "  - Running scraper...\n";
    $res = $scraper->run();
    echo "  - Scraper output:\n";
    print_r($res);
} catch (Exception $e) {
    echo "  - Scraper run: CRASHED with error: " . $e->getMessage() . " ❌\n";
}
