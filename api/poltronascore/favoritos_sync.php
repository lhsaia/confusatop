<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    // Auto-criação da tabela caso ainda não exista no servidor de produção/ambiente
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `usuario_favoritos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `tipo` ENUM('matches', 'clubs', 'competitions', 'players') NOT NULL,
            `item_id` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_user_fav` (`user_id`, `tipo`, `item_id`),
            KEY `idx_user_fav` (`user_id`, `tipo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $isLoggedIn = !empty($_SESSION['loggedin']) && !empty($_SESSION['user_id']);
    $userId = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
    $username = $isLoggedIn ? ($_SESSION['nomereal'] ?? $_SESSION['username'] ?? 'Usuário') : '';

    $method = $_SERVER['REQUEST_METHOD'];

    if (!$isLoggedIn) {
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'message' => 'Nenhum usuário logado na sessão atual.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedTypes = ['matches', 'clubs', 'competitions', 'players'];

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT tipo, item_id FROM usuario_favoritos WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $favs = [
            'matches' => [],
            'clubs' => [],
            'competitions' => [],
            'players' => []
        ];

        foreach ($rows as $r) {
            $t = $r['tipo'];
            if (isset($favs[$t])) {
                $favs[$t][] = (int)$r['item_id'];
            }
        }

        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user_id' => $userId,
            'username' => $username,
            'favorites' => $favs
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $action = $data['action'] ?? 'sync';

        if ($action === 'toggle') {
            $tipo = $data['tipo'] ?? '';
            $itemId = (int)($data['item_id'] ?? 0);

            if (!in_array($tipo, $allowedTypes, true) || $itemId <= 0) {
                throw new Exception("Parâmetros inválidos para toggle.");
            }

            // Check if exists
            $checkStmt = $conn->prepare("SELECT id FROM usuario_favoritos WHERE user_id = ? AND tipo = ? AND item_id = ?");
            $checkStmt->execute([$userId, $tipo, $itemId]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                $delStmt = $conn->prepare("DELETE FROM usuario_favoritos WHERE user_id = ? AND tipo = ? AND item_id = ?");
                $delStmt->execute([$userId, $tipo, $itemId]);
                $isFav = false;
            } else {
                $insStmt = $conn->prepare("INSERT IGNORE INTO usuario_favoritos (user_id, tipo, item_id) VALUES (?, ?, ?)");
                $insStmt->execute([$userId, $tipo, $itemId]);
                $isFav = true;
            }

            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'action' => 'toggle',
                'tipo' => $tipo,
                'item_id' => $itemId,
                'is_favorite' => $isFav
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'remove') {
            $tipo = $data['tipo'] ?? '';
            $itemId = (int)($data['item_id'] ?? 0);

            if (in_array($tipo, $allowedTypes, true) && $itemId > 0) {
                $delStmt = $conn->prepare("DELETE FROM usuario_favoritos WHERE user_id = ? AND tipo = ? AND item_id = ?");
                $delStmt->execute([$userId, $tipo, $itemId]);
            }

            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'action' => 'remove',
                'tipo' => $tipo,
                'item_id' => $itemId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'sync') {
            $localFavs = $data['favorites'] ?? [];

            // Inserir os favoritos locais no banco (Merge)
            $insStmt = $conn->prepare("INSERT IGNORE INTO usuario_favoritos (user_id, tipo, item_id) VALUES (?, ?, ?)");
            foreach ($allowedTypes as $typeKey) {
                if (!empty($localFavs[$typeKey]) && is_array($localFavs[$typeKey])) {
                    foreach ($localFavs[$typeKey] as $rawItem) {
                        $id = is_array($rawItem) ? (int)($rawItem['id'] ?? 0) : (int)$rawItem;
                        if ($id > 0) {
                            $insStmt->execute([$userId, $typeKey, $id]);
                        }
                    }
                }
            }

            // Buscar todos os favoritos consolidados
            $stmt = $conn->prepare("SELECT tipo, item_id FROM usuario_favoritos WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $mergedFavs = [
                'matches' => [],
                'clubs' => [],
                'competitions' => [],
                'players' => []
            ];

            foreach ($rows as $r) {
                $t = $r['tipo'];
                if (isset($mergedFavs[$t])) {
                    $mergedFavs[$t][] = (int)$r['item_id'];
                }
            }

            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user_id' => $userId,
                'username' => $username,
                'favorites' => $mergedFavs
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
