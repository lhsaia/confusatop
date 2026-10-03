<?php
header('Content-Type: application/json; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$estadio = new Estadio($db);
$usuario = new Usuario($db);

$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$capacidade = isset($_POST['capacidade']) ? (int)$_POST['capacidade'] : 0;
$clima = isset($_POST['clima']) ? (int)$_POST['clima'] : 0;
$pais = isset($_POST['pais']) ? (int)$_POST['pais'] : 0;
$altitude = !empty($_POST['altitude']) ? 1 : 0;
$caldeirao = !empty($_POST['caldeirao']) ? 1 : 0;

if (empty($nome)) {
    echo json_encode(['success' => false, 'message' => 'O nome do estádio é obrigatório.']);
    exit;
}

if ($capacidade < 1) {
    echo json_encode(['success' => false, 'message' => 'Informe uma capacidade válida para o estádio.']);
    exit;
}

if ($clima < 1) {
    echo json_encode(['success' => false, 'message' => 'Selecione o clima do estádio.']);
    exit;
}

if ($pais < 1) {
    echo json_encode(['success' => false, 'message' => 'Selecione o país do estádio.']);
    exit;
}

$estadio->nome = trim(html_entity_decode((string)$nome, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$estadio->capacidade = $capacidade;
$estadio->clima = $clima;
$estadio->pais = $pais;
$estadio->altitude = $altitude;
$estadio->caldeirao = $caldeirao;
$estadio->foto = null;

if ($estadio->create()) {
    $newId = $db->lastInsertId();
    if (isset($_SESSION['user_id'])) {
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }
    echo json_encode([
        'success' => true,
        'id' => (int)$newId,
        'nome' => $estadio->nome,
        'capacidade' => $estadio->capacidade,
        'pais' => $estadio->pais,
        'message' => 'Estádio criado com sucesso!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Houve um erro ao salvar o estádio no banco de dados.']);
}
