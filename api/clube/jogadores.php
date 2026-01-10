<?php
header('Content-Type: application/json');
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// DEBUG: Log request
file_put_contents(__DIR__ . '/debug_search.log', date('Y-m-d H:i:s') . " - URI: " . $_SERVER['REQUEST_URI'] . " - Q: [" . $search_term . "] - TEAM: $team_id\n", FILE_APPEND);

if ($team_id > 0) {
    if (empty($search_term)) {
        // --- SCENARIO 1: DEFAULT LOAD (No search term) ---
        // Just return the current squad as before, but include flag info for consistency
        $query = "SELECT DISTINCT j.ID, j.Nome, p.bandeira 
                  FROM contratos_jogador c 
                  JOIN jogador j ON c.jogador = j.ID 
                  LEFT JOIN paises p ON j.Pais = p.id
                  WHERE c.clube = :team_id AND c.tipoContrato IN (0, 1, 2)
                  ORDER BY j.Nome ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':team_id', $team_id);
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
    } else {
        // --- SCENARIO 2: SEARCH (With search term) ---
        // Search ALL players matches the name
        // Group them into 'Jogadores Atuais' (if they have a contract with this team) and 'Banco de Dados' (others)
        
        $termLike = "%" . $search_term . "%";
        
        // We use a UNION or a conditional logic. Conditional logic in one query is often easier for sorting.
        // We check if they are in the current squad (is_current = 1)
        /*
          Logic:
          1. Join `jogador` with `paises` for flags.
          2. Left Join `contratos_jogador` ON specific team & valid contract types to check if "Current".
          3. Order by is_current DESC, Rome ASC.
        */
        
        try {
            $query = "SELECT j.ID, j.Nome, p.bandeira,
                        CASE 
                            WHEN c.jogador IS NOT NULL THEN 1 
                            ELSE 0 
                        END as is_current
                      FROM jogador j
                      LEFT JOIN paises p ON j.Pais = p.id
                      LEFT JOIN contratos_jogador c ON j.ID = c.jogador AND c.clube = :team_id AND c.tipoContrato IN (0, 1, 2)
                      WHERE j.Nome LIKE :term
                      ORDER BY is_current DESC, j.Nome ASC
                      LIMIT 50"; // Limit results for performance

            $stmt = $db->prepare($query);
            $stmt->bindParam(':team_id', $team_id);
            $stmt->bindParam(':term', $termLike);
            $stmt->execute();
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/debug_search.log', date('Y-m-d H:i:s') . " - SQL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            echo json_encode([]);
            exit;
        }
        
        $current_players = [];
        $other_players = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = [
                'id' => $row['ID'],
                'text' => $row['Nome'],
                'bandeira' => $row['bandeira']
            ];
            
            if ($row['is_current'] == 1) {
                $current_players[] = $item;
            } else {
                $other_players[] = $item;
            }
        }
        
        // Construct Select2 Optgroup format
        $final_results = [];
        
        if (!empty($current_players)) {
            $final_results[] = [
                'text' => 'Jogadores Atuais',
                'children' => $current_players
            ];
        }
        
        if (!empty($other_players)) {
            $final_results[] = [
                'text' => 'Banco de Dados',
                'children' => $other_players
            ];
        }
        
        echo json_encode($final_results);
    }

} else {
    echo json_encode([]);
}
