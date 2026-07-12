<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

ini_set('display_errors', 0);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

class VidasGameAPI {
    private $db;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            $this->db = null;
        }

        if (!$this->db) {
            try {
                $this->db = new PDO("mysql:host=127.0.0.1:3307;dbname=confusa_trn;charset=utf8mb4", "root", "", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (Exception $e) {
                echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
                exit;
            }
        }

        // Initialize table for rankings if not exists
        $this->initRankingTable();
    }

    private function initRankingTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS `ranking_7vidas` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `usuario_id` INT NOT NULL,
                    `modo` VARCHAR(30) NOT NULL,
                    `nivel_medio` FLOAT NOT NULL,
                    `vitorias` INT NOT NULL,
                    `resultado_final` VARCHAR(30) NOT NULL,
                    `data_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $this->db->exec($sql);

            // Safe legacy column check
            try {
                $check = $this->db->query("SHOW COLUMNS FROM `ranking_7vidas` LIKE 'usuario_nome'");
                if ($check && $check->rowCount() > 0) {
                    $this->db->exec("DROP TABLE `ranking_7vidas`");
                    $this->db->exec($sql);
                }
            } catch (Exception $colEx) {
                // Ignore column check errors if any
            }
        } catch (Exception $e) {
            // Silence table creation errors
        }
    }

    public function run() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            case 'get_clubs':
                $this->getClubs();
                break;
            case 'roll_club':
                $this->rollClub();
                break;
            case 'get_opponents':
                $this->getOpponents();
                break;
            case 'save_result':
                $this->saveResult();
                break;
            case 'get_rankings':
                $this->getRankings();
                break;
            default:
                echo json_encode(["error" => "Invalid action"]);
                break;
        }
    }

    private function getClubs() {
        try {
            $query = "
                SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                FROM clube c
                JOIN liga l ON c.liga = l.id
                JOIN paises p ON c.pais = p.id
                INNER JOIN (
                    SELECT cj.clube
                    FROM contratos_jogador cj
                    WHERE cj.tipoContrato = 0
                    GROUP BY cj.clube
                    HAVING COUNT(*) >= 11
                ) roster ON c.ID = roster.clube
                WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4)
                ORDER BY c.Nome ASC
            ";
            $stmt = $this->db->query($query);
            $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($clubs);
        } catch (Exception $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    private function rollClub() {
        try {
            $sameLeagueId = isset($_GET['same_league_id']) ? intval($_GET['same_league_id']) : 0;
            $excludeClubId = isset($_GET['exclude_club_id']) ? intval($_GET['exclude_club_id']) : 0;

            if ($sameLeagueId > 0) {
                $query = "
                    SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, c.liga as liga_id, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                    FROM clube c
                    JOIN liga l ON c.liga = l.id
                    JOIN paises p ON c.pais = p.id
                    INNER JOIN (
                        SELECT cj.clube
                        FROM contratos_jogador cj
                        WHERE cj.tipoContrato = 0
                        GROUP BY cj.clube
                        HAVING COUNT(*) >= 11
                    ) roster ON c.ID = roster.clube
                    WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4) 
                      AND c.liga = ? AND c.ID != ?
                    ORDER BY RAND()
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$sameLeagueId, $excludeClubId]);
                $club = $stmt->fetch(PDO::FETCH_ASSOC);

                // Fallback if no other clubs are active in this league
                if (!$club) {
                    $query = "
                        SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, c.liga as liga_id, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                        FROM clube c
                        JOIN liga l ON c.liga = l.id
                        JOIN paises p ON c.pais = p.id
                        INNER JOIN (
                            SELECT cj.clube
                            FROM contratos_jogador cj
                            WHERE cj.tipoContrato = 0
                            GROUP BY cj.clube
                            HAVING COUNT(*) >= 11
                        ) roster ON c.ID = roster.clube
                        WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4) AND c.liga = ?
                        ORDER BY RAND()
                        LIMIT 1
                    ";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$sameLeagueId]);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                $query = "
                    SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, c.liga as liga_id, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                    FROM clube c
                    JOIN liga l ON c.liga = l.id
                    JOIN paises p ON c.pais = p.id
                    INNER JOIN (
                        SELECT cj.clube
                        FROM contratos_jogador cj
                        WHERE cj.tipoContrato = 0
                        GROUP BY cj.clube
                        HAVING COUNT(*) >= 11
                    ) roster ON c.ID = roster.clube
                    WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4) AND c.ID != ?
                    ORDER BY RAND()
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$excludeClubId]);
                $club = $stmt->fetch(PDO::FETCH_ASSOC);

                // Fallback if only 1 club exists in the whole database pool
                if (!$club) {
                    $query = "
                        SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, c.liga as liga_id, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                        FROM clube c
                        JOIN liga l ON c.liga = l.id
                        JOIN paises p ON c.pais = p.id
                        INNER JOIN (
                            SELECT cj.clube
                            FROM contratos_jogador cj
                            WHERE cj.tipoContrato = 0
                            GROUP BY cj.clube
                            HAVING COUNT(*) >= 11
                        ) roster ON c.ID = roster.clube
                        WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4)
                        ORDER BY RAND()
                        LIMIT 1
                    ";
                    $stmt = $this->db->query($query);
                    $club = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$club) {
                echo json_encode(["error" => "No clubs found"]);
                return;
            }

            $playersQuery = "
                SELECT j.ID as id, j.Nome as nome, (j.Nivel + cj.ModificadorNivel) as nivel, j.StringPosicoes as stringPosicoes
                FROM contratos_jogador cj
                JOIN jogador j ON cj.jogador = j.ID
                WHERE cj.clube = ? AND cj.tipoContrato = 0
            ";
            $playersStmt = $this->db->prepare($playersQuery);
            $playersStmt->execute([$club['id']]);
            $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

            $club['players'] = $players;
            echo json_encode($club);
        } catch (Exception $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    private function getOpponents() {
        try {
            $query = "
                SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, p.nome as pais_nome, p.id as pais_id, p.bandeira as pais_bandeira
                FROM clube c
                JOIN liga l ON c.liga = l.id
                JOIN paises p ON c.pais = p.id
                INNER JOIN (
                    SELECT cj.clube
                    FROM contratos_jogador cj
                    WHERE cj.tipoContrato = 0
                    GROUP BY cj.clube
                    HAVING COUNT(*) >= 11
                ) roster ON c.ID = roster.clube
                WHERE l.tier = 1 AND l.Sexo = 0 AND p.ativo = 1 AND p.federacao IN (1, 2, 3, 4)
            ";
            $stmt = $this->db->query($query);
            $all_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            shuffle($all_clubs);
            $opponents = array_slice($all_clubs, 0, min(50, count($all_clubs)));

            foreach ($opponents as &$opp) {
                $playersQuery = "
                    SELECT (j.Nivel + cj.ModificadorNivel) as nivel
                    FROM contratos_jogador cj
                    JOIN jogador j ON cj.jogador = j.ID
                    WHERE cj.clube = ? AND cj.tipoContrato = 0
                ";
                $pStmt = $this->db->prepare($playersQuery);
                $pStmt->execute([$opp['id']]);
                $levels = $pStmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($levels) > 0) {
                    $opp['average_rating'] = round(array_sum($levels) / count($levels));
                    rsort($levels);
                    $top11 = array_slice($levels, 0, 11);
                    $opp['top11_rating'] = round(array_sum($top11) / count($top11));
                } else {
                    $opp['average_rating'] = 50;
                    $opp['top11_rating'] = 50;
                }
            }

            echo json_encode($opponents);
        } catch (Exception $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    private function saveResult() {
        try {

            // Only allow logged in users to save rankings
            if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
                echo json_encode(["error" => "Usuário não logado. Resultados não foram salvos no ranking global."]);
                return;
            }

            $user_id = intval($_SESSION['user_id']);
            $modo = isset($_POST['modo']) ? trim($_POST['modo']) : 'classico';
            $nivel_medio = isset($_POST['nivel_medio']) ? floatval($_POST['nivel_medio']) : 50.0;
            $vitorias = isset($_POST['vitorias']) ? intval($_POST['vitorias']) : 0;
            $resultado = isset($_POST['resultado_final']) ? trim($_POST['resultado_final']) : 'ELIMINADO';

            $sql = "
                INSERT INTO ranking_7vidas (usuario_id, modo, nivel_medio, vitorias, resultado_final)
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $modo, $nivel_medio, $vitorias, $resultado]);

            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    private function getRankings() {
        try {
            // Join with usuarios table to get real name
            $sql = "
                SELECT u.nome as usuario_nome, r1.modo, r1.nivel_medio, r1.vitorias, r1.resultado_final, DATE_FORMAT(r1.data_registro, '%d/%m/%Y') as data
                FROM ranking_7vidas r1
                JOIN usuarios u ON r1.usuario_id = u.id
                WHERE r1.id = (
                    SELECT r2.id
                    FROM ranking_7vidas r2
                    WHERE r2.usuario_id = r1.usuario_id
                    ORDER BY r2.vitorias DESC, r2.nivel_medio ASC, r2.id ASC
                    LIMIT 1
                )
                ORDER BY r1.vitorias DESC, r1.nivel_medio ASC
                LIMIT 50
            ";
            $stmt = $this->db->query($sql);
            $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rankings);
        } catch (Exception $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}

$api = new VidasGameAPI();
$api->run();
?>
