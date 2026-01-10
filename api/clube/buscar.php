<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

$database = new Database();
$db = $database->getConnection();
$time = new Time($db);

$q = isset($_GET['q']) ? $_GET['q'] : '';

// Direct query for search as Time class might not have a generic search
$query = "SELECT c.ID, c.Nome, c.Escudo, p.Bandeira as bandeira, p.Nome as PaisNome 
          FROM clube c 
          LEFT JOIN paises p ON c.Pais = p.id 
          WHERE c.Nome LIKE :q 
          LIMIT 20";
$stmt = $db->prepare($query);
$search = "%{$q}%";
$stmt->bindParam(':q', $search);
$stmt->execute();

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id' => $row['ID'],
        'text' => $row['Nome'],
        'escudo' => $row['Escudo'],
        'bandeira' => $row['bandeira'],
        'pais' => $row['PaisNome']
    ];
}

echo json_encode(['results' => $results]);
