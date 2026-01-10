<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$q = isset($_GET['q']) ? $_GET['q'] : '';

$query = "SELECT ID, Nome FROM estadio WHERE Nome LIKE :q LIMIT 20";
$stmt = $db->prepare($query);
$search = "%{$q}%";
$stmt->bindParam(':q', $search);
$stmt->execute();

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id' => $row['ID'],
        'text' => $row['Nome']
    ];
}

echo json_encode(['results' => $results]);
