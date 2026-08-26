<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();
$pais = new Pais($db);

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$apiKey = isset($_GET['apiKey']) ? $_GET['apiKey'] : '';
$user_id = false;

if ($is_logged_in || $apiKey === 'interna') {
    $user_id = $_SESSION['id'] ?? 1;
} elseif ($apiKey) {
    $usuario = new Usuario($db);
    $user_id = $usuario->checkApiKey($apiKey);
}

if(!$user_id){
    echo json_encode(['results' => []]);
    exit;
}

$name = isset($_GET['name']) ? $_GET['name'] : (isset($_GET['q']) ? $_GET['q'] : '');
$id = isset($_GET['id']) ? $_GET['id'] : '';
$organizacao = isset($_GET['organizacao']) ? $_GET['organizacao'] : '';
$federacao = isset($_GET['federacao']) ? $_GET['federacao'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dono = isset($_GET['dono']) ? $_GET['dono'] : '';

$where = [];
$params = [];

if ($name) {
    $where[] = "p.nome LIKE :name";
    $params[':name'] = "%{$name}%";
}

if ($id) {
    $where[] = "p.id = :id";
    $params[':id'] = $id;
}

if ($organizacao) {
    if ($organizacao === 'real') {
        $where[] = "char_length(p.sigla) = 2";
    } elseif ($organizacao === 'confusa') {
        $where[] = "(char_length(p.sigla) != 2 OR p.sigla IS NULL) AND p.ranqueavel = 0";
    } elseif ($organizacao === 'nc') {
        $where[] = "(char_length(p.sigla) != 2 OR p.sigla IS NULL) AND p.ranqueavel != 0";
    }
}

if ($federacao) {
    $where[] = "f.nome = :federacao";
    $params[':federacao'] = $federacao;
}

if ($status !== '') {
    if ($status == 0) {
        $where[] = "p.ativo = 1";
    }
    // Se status for 1, traz tanto ativos quanto inativos, portanto não aplicamos filtro de ativo.
}

if ($dono) {
    $where[] = "u.ID = :dono";
    $params[':dono'] = $dono;
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT p.id, p.nome, 
          (CASE when char_length(p.sigla) = 2 then 'real' when p.ranqueavel = 0 then 'confusa' else 'nc' end) as 'organizacao', 
          p.bandeira, f.nome as 'federacao', p.ativo as status, u.ID as 'dono' 
          FROM paises p
          LEFT JOIN usuarios u ON p.dono = u.ID 
          LEFT JOIN federacoes f ON p.federacao = f.id 
          $whereClause
          ORDER BY p.nome ASC";

if (count($params) == 0) {
     $query .= " LIMIT 200";
}

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id' => $row['id'],
        'text' => $row['nome'],
        'organizacao' => $row['organizacao'],
        'bandeira' => $row['bandeira'],
        'federacao' => $row['federacao'],
        'status' => $row['status'],
        'dono' => $row['dono']
    ];
}

echo json_encode(['results' => $results]);
