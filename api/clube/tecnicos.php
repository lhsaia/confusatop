<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

if ($team_id > 0) {
    // Search for coaches in contratos_tecnico
    $query = "SELECT DISTINCT t.ID, t.Nome 
              FROM contratos_tecnico c 
              JOIN tecnico t ON c.tecnico = t.ID 
              WHERE c.clube = :team_id
              ORDER BY t.Nome ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':team_id', $team_id);
    $stmt->execute();

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['ID'],
            'text' => $row['Nome']
        ];
    }
    echo json_encode($results);
} else {
    echo json_encode([]);
}
