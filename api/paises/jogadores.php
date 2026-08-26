<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$country_id = isset($_GET['country_id']) ? (int)$_GET['country_id'] : (isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0);
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($country_id > 0) {
    if (empty($search_term)) {
        $query = "SELECT DISTINCT j.ID, j.Nome, p.bandeira 
                  FROM jogador j 
                  LEFT JOIN paises p ON j.Pais = p.id
                  WHERE j.Pais = :country_id
                  ORDER BY j.Nome ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':country_id', $country_id, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'id' => $row['ID'],
                'text' => $row['Nome'],
                'bandeira' => $row['bandeira']
            ];
        }
        echo json_encode($results);
        exit;
    } else {
        $termLike = "%" . $search_term . "%";
        try {
            $query = "SELECT j.ID, j.Nome, p.bandeira,
                        CASE 
                            WHEN j.Pais = :country_id THEN 1 
                            ELSE 0 
                        END as is_current
                      FROM jogador j
                      LEFT JOIN paises p ON j.Pais = p.id
                      WHERE j.Nome LIKE :term
                      ORDER BY is_current DESC, j.Nome ASC
                      LIMIT 40";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':country_id', $country_id, PDO::PARAM_INT);
            $stmt->bindParam(':term', $termLike, PDO::PARAM_STR);
            $stmt->execute();

            $currentGroup = [];
            $databaseGroup = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item = [
                    'id' => $row['ID'],
                    'text' => $row['Nome'],
                    'bandeira' => $row['bandeira']
                ];

                if ($row['is_current'] == 1) {
                    $currentGroup[] = $item;
                } else {
                    $databaseGroup[] = $item;
                }
            }

            $output = [];
            if (!empty($currentGroup)) {
                $output[] = [
                    'text' => 'Jogadores da Seleção',
                    'children' => $currentGroup
                ];
            }
            if (!empty($databaseGroup)) {
                $output[] = [
                    'text' => 'Outros Jogadores no Banco de Dados',
                    'children' => $databaseGroup
                ];
            }

            echo json_encode($output);
            exit;

        } catch(PDOException $e) {
            echo json_encode([]);
            exit;
        }
    }
} else {
    // If no country is selected, global search
    if (!empty($search_term)) {
        $termLike = "%" . $search_term . "%";
        $query = "SELECT j.ID, j.Nome, p.bandeira
                  FROM jogador j
                  LEFT JOIN paises p ON j.Pais = p.id
                  WHERE j.Nome LIKE :term
                  ORDER BY j.Nome ASC
                  LIMIT 30";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':term', $termLike, PDO::PARAM_STR);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'id' => $row['ID'],
                'text' => $row['Nome'],
                'bandeira' => $row['bandeira']
            ];
        }
        echo json_encode($results);
        exit;
    } else {
        echo json_encode([]);
        exit;
    }
}
