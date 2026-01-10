<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/campeonato_clube.php");

session_start();
$dono = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$database = new Database();
$db = $database->getConnection();

$type = isset($_GET['type']) ? (int)$_GET['type'] : 0; // 0 = Liga, 1 = Copa
$q = isset($_GET['q']) ? $_GET['q'] : '';
$q = "%".$q."%";

$results = [];

if ($type == 0) {
    // Ligas: O dono vem através do país vinculado (liga.pais -> paises.dono)
    $query = "SELECT l.id, l.nome 
              FROM liga l 
              INNER JOIN paises p ON l.pais = p.id 
              WHERE (p.dono = :dono OR p.dono = 0) AND l.nome LIKE :q 
              ORDER BY l.nome";
    $stmt = $db->prepare($query);
} else {
    // Copas (Publicas ou do usuário)
    $query = "SELECT id, nome FROM campeonatos_clube WHERE (dono = 0 OR dono = :dono) AND nome LIKE :q ORDER BY nome";
    $stmt = $db->prepare($query);
}

$stmt->bindParam(':dono', $dono);
$stmt->bindParam(':q', $q);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id' => $row['id'],
        'text' => $row['nome']
    ];
}

echo json_encode(['results' => $results]);
