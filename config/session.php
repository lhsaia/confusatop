<?php
// Set session save path to a local directory within the project to avoid shared hosting GC purging it
$session_dir = $_SERVER['DOCUMENT_ROOT'] . '/session_data';

if (!file_exists($session_dir)) {
    mkdir($session_dir, 0700, true);
    // Secure the directory from direct HTTP access
    file_put_contents($session_dir . '/.htaccess', "Deny from all\n");
    file_put_contents($session_dir . '/index.html', "");
}

ini_set('session.save_path', $session_dir);

// Set session garbage collection and cookie lifetime to 30 days
$session_lifetime = 60 * 60 * 24 * 30; // 30 days
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

// Configure session cookie params for security and longevity
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'domain' => '', // Empty string keeps it on current domain
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    // Fallback for older PHP versions
    session_set_cookie_params($session_lifetime, '/; SameSite=Lax', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
}

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
