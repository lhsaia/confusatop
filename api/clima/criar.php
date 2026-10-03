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
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$clima = new Clima($db);
$usuario = new Usuario($db);

$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$pais = isset($_POST['pais']) ? (int)$_POST['pais'] : 0;
$hemisferio = isset($_POST['hemisferio']) ? trim($_POST['hemisferio']) : 'Sul';

$tempVerao = isset($_POST['tempVerao']) ? trim($_POST['tempVerao']) : 'Quente';
$estiloVerao = isset($_POST['estiloVerao']) ? trim($_POST['estiloVerao']) : 'Chuvoso';

$tempOutono = isset($_POST['tempOutono']) ? trim($_POST['tempOutono']) : 'Normal';
$estiloOutono = isset($_POST['estiloOutono']) ? trim($_POST['estiloOutono']) : 'Equilibrado';

$tempInverno = isset($_POST['tempInverno']) ? trim($_POST['tempInverno']) : 'Frio';
$estiloInverno = isset($_POST['estiloInverno']) ? trim($_POST['estiloInverno']) : 'Equilibrado';

$tempPrimavera = isset($_POST['tempPrimavera']) ? trim($_POST['tempPrimavera']) : 'Normal';
$estiloPrimavera = isset($_POST['estiloPrimavera']) ? trim($_POST['estiloPrimavera']) : 'Chuvoso';

if (empty($nome)) {
    echo json_encode(['success' => false, 'message' => 'O nome do clima é obrigatório.']);
    exit;
}

if ($pais < 1) {
    echo json_encode(['success' => false, 'message' => 'Selecione o país do clima.']);
    exit;
}

$validClimateMap = [
    'Muito Frio' => ['options' => ['Neve Forte', 'Neve', 'Neve Ocasional'], 'default' => 'Neve'],
    'Frio' => ['options' => ['Neblina', 'Chuvoso', 'Ventos Fortes'], 'default' => 'Chuvoso'],
    'Normal' => ['options' => ['Chuvoso', 'Equilibrado', 'Ventos Fortes'], 'default' => 'Equilibrado'],
    'Quente' => ['options' => ['Chuvoso', 'Ventos Fortes', 'Seco'], 'default' => 'Ventos Fortes'],
    'Muito Quente' => ['options' => ['Ventos Fortes', 'Seco', 'Árido'], 'default' => 'Seco']
];

function sanitizeClimateSeasonPair($temp, $estilo, $validMap) {
    if (!isset($validMap[$temp])) {
        $temp = 'Normal';
    }
    $allowed = $validMap[$temp]['options'];
    if (!in_array($estilo, $allowed)) {
        $estilo = $validMap[$temp]['default'];
    }
    return [$temp, $estilo];
}

list($tempVerao, $estiloVerao) = sanitizeClimateSeasonPair($tempVerao, $estiloVerao, $validClimateMap);
list($tempOutono, $estiloOutono) = sanitizeClimateSeasonPair($tempOutono, $estiloOutono, $validClimateMap);
list($tempInverno, $estiloInverno) = sanitizeClimateSeasonPair($tempInverno, $estiloInverno, $validClimateMap);
list($tempPrimavera, $estiloPrimavera) = sanitizeClimateSeasonPair($tempPrimavera, $estiloPrimavera, $validClimateMap);

$clima->nome = trim(html_entity_decode((string)$nome, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$clima->pais = $pais;
$clima->hemisferio = $hemisferio;
$clima->tempVerao = $tempVerao;
$clima->estiloVerao = $estiloVerao;
$clima->tempOutono = $tempOutono;
$clima->estiloOutono = $estiloOutono;
$clima->tempInverno = $tempInverno;
$clima->estiloInverno = $estiloInverno;
$clima->tempPrimavera = $tempPrimavera;
$clima->estiloPrimavera = $estiloPrimavera;

if ($clima->create()) {
    $newId = $db->lastInsertId();
    if (isset($_SESSION['user_id'])) {
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }
    echo json_encode([
        'success' => true,
        'id' => (int)$newId,
        'nome' => $clima->nome,
        'pais' => $clima->pais,
        'message' => 'Clima criado com sucesso!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Houve um erro ao salvar o clima no banco de dados.']);
}
