<?php
// Centralized PHP error logging configuration
$prodLogDir = '/home/lhsaia/confusa.top/logs';
$prodLogPath = $prodLogDir . '/php_errors.log';

$host = $_SERVER['HTTP_HOST'] ?? '';
$isWindows = (PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\');

// Identifica produção: não é Windows, pasta de logs de prod existe e não é domínio local
$isProduction = !$isWindows 
    && is_dir($prodLogDir) 
    && (strpos($host, 'confusa.top') !== false)
    && (strpos($host, 'local') === false);

$logPath = $isProduction 
    ? $prodLogPath 
    : dirname(__DIR__) . '/php_errors.log';

ini_set('log_errors', '1');
ini_set('error_log', $logPath);
ini_set('display_errors', '0'); // NUNCA exibir erros no output — quebra respostas JSON
ini_set('memory_limit', '256M');
error_reporting(E_ALL);

// Set session save path to a local directory within the project to avoid shared hosting GC purging it
$session_dir = dirname(__DIR__) . '/session_data';
$use_custom_path = false;

if (file_exists($session_dir) && is_writable($session_dir)) {
    $use_custom_path = true;
} else {
    // Try to create it if it doesn't exist
    if (!file_exists($session_dir)) {
        if (@mkdir($session_dir, 0700, true)) {
            // Secure the directory from direct HTTP access
            @file_put_contents($session_dir . '/.htaccess', "Deny from all\n");
            @file_put_contents($session_dir . '/index.html', "");
            if (is_writable($session_dir)) {
                $use_custom_path = true;
            }
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        if ($use_custom_path) {
            ini_set('session.save_path', $session_dir);
        }

        // Set session garbage collection and cookie lifetime to 30 days
        $session_lifetime = 60 * 60 * 24 * 30; // 30 days
        ini_set('session.gc_maxlifetime', (string)$session_lifetime);
        ini_set('session.cookie_lifetime', (string)$session_lifetime);

        // Custom Garbage Collection: 1% de chance de limpar arquivos de sessão com mais de 30 dias
        if ($use_custom_path && mt_rand(1, 100) === 1) {
            $now = time();
            foreach (glob($session_dir . '/sess_*') as $file) {
                if (is_file($file) && ($now - filemtime($file) > $session_lifetime)) {
                    @unlink($file);
                }
            }
        }

        // Normalize host domain to share cookies across www and non-www subdomains
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = explode(':', $host)[0];
        $cookie_domain = null;

        if (filter_var($host, FILTER_VALIDATE_IP) === false && strpos($host, '.') !== false) {
            if (substr($host, 0, 4) === 'www.') {
                $cookie_domain = '.' . substr($host, 4);
            } else {
                $cookie_domain = '.' . $host;
            }
        }

        // Configure session cookie params for security and longevity
        $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => $session_lifetime,
                'path' => '/',
                'domain' => $cookie_domain,
                'secure' => $is_secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            // Fallback for older PHP versions
            session_set_cookie_params(
                $session_lifetime,
                '/; SameSite=Lax',
                $cookie_domain ?: '',
                $is_secure,
                true // httponly
            );
        }

        // Set a custom session name to avoid conflicts and clean up browser cookies
        if (strpos($host, 'local') !== false || $host === 'localhost' || $host === '127.0.0.1') {
            session_name('confusatop_local');
        } else {
            session_name('confusatop');
        }

        // Suppress warning if session data is corrupted (PHP auto-destroys corrupt session object)
        if (!@session_start()) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_id(bin2hex(random_bytes(16)));
                @session_start();
            }
        }
    }
}

/*
// Temporary debug logging to understand exactly what is happening for the failing user
$log_file = dirname(__DIR__) . '/debug_session.txt';
$log_data = [
    'time' => date('Y-m-d H:i:s'),
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'session_id' => session_id(),
    'session_name' => session_name(),
    'cookie_confusatop' => $_COOKIE[session_name()] ?? 'NOT_SET',
    'cookie_phpsessid' => $_COOKIE['PHPSESSID'] ?? 'NOT_SET',
    'session_data_loggedin' => isset($_SESSION['loggedin']) ? $_SESSION['loggedin'] : 'NOT_SET',
    'session_data_user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT_SET',
    'save_path' => session_save_path(),
    'is_writable' => is_writable(session_save_path()),
];
@file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);
*/



