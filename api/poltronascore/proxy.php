<?php
declare(strict_types=1);

header("Access-Control-Allow-Origin: *");

if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "Missing URL";
    exit;
}

$url = $_GET['url'];

// Security: Only allow requests to the trusted external matches server
if (strpos($url, 'http://52.203.150.214:8080/') !== 0) {
    http_response_code(403);
    echo "Forbidden host target";
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");

$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $data !== false) {
    header("Content-Type: " . ($contentType ?: "image/png"));
    // Enable browser caching for 7 days since logos rarely change
    header("Cache-Control: public, max-age=604800"); 
    echo $data;
} else {
    http_response_code(404);
    echo "Image not found or target server error";
}
