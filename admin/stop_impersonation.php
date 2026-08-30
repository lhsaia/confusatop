<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

$adminId = 0;
if (!empty($_SESSION['admin_original_id'])) {
    $adminId = (int)$_SESSION['admin_original_id'];
} elseif (!empty($_SESSION['admin_original_user']) && isset($usuario)) {
    $adminId = (int)$usuario->ID($_SESSION['admin_original_user']);
}

// Fallback: se não encontrou na sessão, pega o admin no banco
if ($adminId <= 0 && isset($db)) {
    $stmtFallback = $db->query("SELECT id FROM usuarios WHERE admin_status = 1 ORDER BY id ASC LIMIT 1");
    $adminId = (int)($stmtFallback->fetchColumn() ?: 0);
}

if ($adminId > 0 && isset($db)) {
    $stmtAdmin = $db->prepare("SELECT id, nomeusuario, nome, admin_status, avatar, emTeste FROM usuarios WHERE id = ? LIMIT 1");
    $stmtAdmin->execute([$adminId]);
    $rowAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if ($rowAdmin && (int)$rowAdmin['admin_status'] === 1) {
        $_SESSION['user_id'] = (int)$rowAdmin['id'];
        $_SESSION['username'] = $rowAdmin['nomeusuario'];
        $_SESSION['nomereal'] = $rowAdmin['nome'];
        $_SESSION['admin_status'] = 1;
        $_SESSION['loggedin'] = true;
        $_SESSION['impersonated'] = false;
        $_SESSION['avatar'] = $rowAdmin['avatar'] ?? null;
        $_SESSION['emTestes'] = (bool)($rowAdmin['emTeste'] ?? 0);
        unset($_SESSION['admin_original_id'], $_SESSION['admin_original_user']);

        header("Location: /admin/index.php");
        exit;
    }
}

// Se não conseguir restaurar por qualquer razão, limpa a sessão
$_SESSION = array();
session_destroy();
header("Location: /index.php");
exit;

