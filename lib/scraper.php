<?php
date_default_timezone_set('America/Sao_Paulo');

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__) . '/config/database.php';

class PoltronaScraper {
    private $db;
    private $baseUrl = "http://52.203.150.214:8080/CONFUSALive/";
    
    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->db->exec("SET time_zone = '-03:00';");
        $this->ensureTablesExist();
    }
    
    private function ensureTablesExist() {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS poltrona_teams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                logo_url VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS poltrona_matches (
                id INT PRIMARY KEY,
                championship VARCHAR(150) NULL,
                rodada VARCHAR(100) NULL,
                home_team VARCHAR(100) NOT NULL,
                away_team VARCHAR(100) NOT NULL,
                home_score INT DEFAULT 0,
                away_score INT DEFAULT 0,
                home_penalties INT DEFAULT NULL,
                away_penalties INT DEFAULT NULL,
                home_scorers TEXT NULL,
                away_scorers TEXT NULL,
                match_date VARCHAR(10) NULL,
                match_time VARCHAR(10) NULL,
                stadium VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL,
                home_logo VARCHAR(255) NULL,
                away_logo VARCHAR(255) NULL,
                home_formation VARCHAR(20) NULL,
                away_formation VARCHAR(20) NULL,
                lineups_scraped TINYINT(1) DEFAULT 0,
                last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS poltrona_match_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                match_id INT NOT NULL,
                event_external_id VARCHAR(50) NULL,
                minute VARCHAR(10) NULL,
                period VARCHAR(50) NULL,
                type VARCHAR(50) NULL,
                team_name VARCHAR(100) NULL,
                player_name VARCHAR(100) NULL,
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (match_id) REFERENCES poltrona_matches(id) ON DELETE CASCADE,
                INDEX idx_match_id (match_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS poltrona_scraper_runs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                finished_at TIMESTAMP NULL,
                success TINYINT(1) DEFAULT 0,
                items_found INT DEFAULT 0,
                error_message TEXT NULL,
                duration_ms INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            // Self-heal/migrate old relative logo URLs in the database to absolute URLs
            $this->db->exec("UPDATE poltrona_matches 
                SET home_logo = CONCAT('http://52.203.150.214:8080/CONFUSALive/', home_logo) 
                WHERE home_logo LIKE 'escudos/%' AND home_logo NOT LIKE 'http%';");
            $this->db->exec("UPDATE poltrona_matches 
                SET away_logo = CONCAT('http://52.203.150.214:8080/CONFUSALive/', away_logo) 
                WHERE away_logo LIKE 'escudos/%' AND away_logo NOT LIKE 'http%';");
            $this->db->exec("UPDATE poltrona_teams 
                SET logo_url = CONCAT('http://52.203.150.214:8080/CONFUSALive/', logo_url) 
                WHERE logo_url LIKE 'escudos/%' AND logo_url NOT LIKE 'http%';");
        } catch (Exception $e) {
            // Fail silently, error will be logged by PDO if critical
        }
    }
    
    private function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$html) {
            return false;
        }
        return $html;
    }
    
    public function run() {
        $startTime = microtime(true);
        $itemsFound = 0;
        $runId = null;
        
        try {
            $runId = $this->startRun();
            
            $statuses = ['live', 'next', 'previous'];
            $allMatchIdsFound = [];
            
            foreach ($statuses as $status) {
                $listUrl = $this->baseUrl . "matches?when=" . $status;
                $html = $this->fetchUrl($listUrl);
                if (!$html) {
                    throw new Exception("Failed to fetch matches list for status: " . $status);
                }
                
                $dom = new DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
                $xpath = new DOMXPath($dom);
                
                // Query all links pointing to match details
                $matchLinks = $xpath->query("//span[@class='rodada-texto']/a[contains(@href, 'match?id=')]");
                
                foreach ($matchLinks as $link) {
                    $href = $link->getAttribute('href');
                    parse_str(parse_url($href, PHP_URL_QUERY), $query);
                    $matchId = isset($query['id']) ? intval($query['id']) : 0;
                    
                    if (!$matchId) continue;
                    
                    $allMatchIdsFound[] = $matchId;
                    $itemsFound++;
                    
                    // Parse the text content of the link
                    // e.g. "08/08 - 20:00 - Carbonera X Asimália - Arena Talheres"
                    $linkText = trim($link->nodeValue);
                    $parts = array_map('trim', explode(' - ', $linkText));
                    
                    $date = isset($parts[0]) ? $parts[0] : '';
                    $time = isset($parts[1]) ? $parts[1] : '';
                    $teamsStr = isset($parts[2]) ? $parts[2] : '';
                    $stadium = isset($parts[3]) ? $parts[3] : '';
                    
                    // Get championship name by walking backwards
                    $championship = '';
                    $curr = $link->parentNode; // span.rodada-texto
                    while ($curr = $curr->previousSibling) {
                        if ($curr instanceof DOMElement && $curr->nodeName === 'span' && strpos($curr->getAttribute('class'), 'campeonato-nome') !== false) {
                            $championship = trim($curr->nodeValue);
                            break;
                        }
                    }
                    
                    // Split championship into championship and rodada if possible
                    $championshipName = $championship;
                    $rodada = '';
                    if (strpos($championship, ' - ') !== false) {
                        $cParts = array_map('trim', explode(' - ', $championship));
                        $championshipName = $cParts[0];
                        $rodada = implode(' - ', array_slice($cParts, 1));
                    }
                    
                    // Check if match already exists in database
                    $stmt = $this->db->prepare("SELECT status FROM poltrona_matches WHERE id = ?");
                    $stmt->execute([$matchId]);
                    $existing = $stmt->fetch();
                    
                    // Logic to decide if we fetch match details:
                    // 1. If it doesn't exist.
                    // 2. If it is live (always update).
                    // 3. If it exists but its status changed (e.g. from next -> live, or live -> previous).
                    $shouldFetchDetails = !$existing || $status === 'live' || $existing['status'] !== $status;
                    
                    if ($shouldFetchDetails) {
                        $this->scrapeMatchDetails($matchId, $championshipName, $rodada, $date, $time, $stadium, $status);
                    } else {
                        // Just update status if needed (though it matches)
                        $stmtUpdate = $this->db->prepare("UPDATE poltrona_matches SET status = ? WHERE id = ?");
                        $stmtUpdate->execute([$status, $matchId]);
                    }
                }
            }
            
            $duration = round((microtime(true) - $startTime) * 1000);
            if ($runId) {
                $this->endRun($runId, true, $itemsFound, $duration);
            }
            return [
                'success' => true,
                'items_found' => $itemsFound,
                'duration_ms' => $duration
            ];
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            if ($runId) {
                $this->endRun($runId, false, $itemsFound, $duration, $e->getMessage());
            }
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ];
        }
    }
    
    private function scrapeMatchDetails($matchId, $championship, $rodada, $date, $time, $stadium, $status) {
        $detailUrl = $this->baseUrl . "match?id=" . $matchId;
        $html = $this->fetchUrl($detailUrl);
        if (!$html) {
            return; // Skip if failed to load detail page
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Extract teams and scores
        $homeTeamNode = $xpath->query("//span[contains(@class, 'nome-time-mandante')]");
        $homeTeam = $homeTeamNode->length ? trim($homeTeamNode->item(0)->nodeValue) : '';
        
        $awayTeamNode = $xpath->query("//span[contains(@class, 'nome-time-visitante')]");
        $awayTeam = $awayTeamNode->length ? trim($awayTeamNode->item(0)->nodeValue) : '';
        
        // If not parsed from details, fall back or default
        if (empty($homeTeam) || empty($awayTeam)) {
            return;
        }
        
        $homeScoreNode = $xpath->query("//span[@id='gols-mandante']");
        $homeScore = $homeScoreNode->length ? intval(trim($homeScoreNode->item(0)->nodeValue)) : 0;
        
        $awayScoreNode = $xpath->query("//span[@id='gols-visitante']");
        $awayScore = $awayScoreNode->length ? intval(trim($awayScoreNode->item(0)->nodeValue)) : 0;
        
        $homePenaltiesNode = $xpath->query("//span[@id='gols-penaltis-mandante']");
        $homePenalties = $homePenaltiesNode->length ? intval(trim($homePenaltiesNode->item(0)->nodeValue)) : null;
        
        $awayPenaltiesNode = $xpath->query("//span[@id='gols-penaltis-visitante']");
        $awayPenalties = $awayPenaltiesNode->length ? intval(trim($awayPenaltiesNode->item(0)->nodeValue)) : null;
        
        // Scorers
        $homeScorersNode = $xpath->query("//span[@id='marcadores-mandante']");
        $homeScorers = $homeScorersNode->length ? trim($homeScorersNode->item(0)->nodeValue) : '';
        
        $awayScorersNode = $xpath->query("//span[@id='marcadores-visitante']");
        $awayScorers = $awayScorersNode->length ? trim($awayScorersNode->item(0)->nodeValue) : '';
        
        // Logos/Shields
        $homeLogoNode = $xpath->query("//img[contains(@class, 'escudo-time-mandante')]");
        $homeLogo = $homeLogoNode->length ? trim($homeLogoNode->item(0)->getAttribute('src')) : '';
        if (!empty($homeLogo) && strpos($homeLogo, 'http') !== 0) {
            $homeLogo = $this->baseUrl . $homeLogo;
        }
        
        $awayLogoNode = $xpath->query("//img[contains(@class, 'escudo-time-visitante')]");
        $awayLogo = $awayLogoNode->length ? trim($awayLogoNode->item(0)->getAttribute('src')) : '';
        if (!empty($awayLogo) && strpos($awayLogo, 'http') !== 0) {
            $awayLogo = $this->baseUrl . $awayLogo;
        }
        
        // Formations
        $homeFormationNode = $xpath->query("//div[contains(@class, 'escalacao-equipe-mandante')]//span[contains(@class, 'formacao-equipe')]");
        $homeFormation = $homeFormationNode->length ? trim($homeFormationNode->item(0)->nodeValue) : null;
        
        $awayFormationNode = $xpath->query("//div[contains(@class, 'escalacao-equipe-visitante')]//span[contains(@class, 'formacao-equipe')]");
        $awayFormation = $awayFormationNode->length ? trim($awayFormationNode->item(0)->nodeValue) : null;
        
        $lineupsScraped = ($homeFormation || $awayFormation) ? 1 : 0;
        
        // Save teams to team table
        $this->saveTeam($homeTeam, $homeLogo);
        $this->saveTeam($awayTeam, $awayLogo);
        
        // Save match
        $stmt = $this->db->prepare("
            INSERT INTO poltrona_matches (
                id, championship, rodada, home_team, away_team, 
                home_score, away_score, home_penalties, away_penalties, 
                home_scorers, away_scorers, match_date, match_time, stadium, 
                status, home_logo, away_logo, home_formation, away_formation, lineups_scraped
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                championship = VALUES(championship),
                rodada = VALUES(rodada),
                home_score = VALUES(home_score),
                away_score = VALUES(away_score),
                home_penalties = VALUES(home_penalties),
                away_penalties = VALUES(away_penalties),
                home_scorers = VALUES(home_scorers),
                away_scorers = VALUES(away_scorers),
                match_date = VALUES(match_date),
                match_time = VALUES(match_time),
                stadium = VALUES(stadium),
                status = VALUES(status),
                home_logo = VALUES(home_logo),
                away_logo = VALUES(away_logo),
                home_formation = VALUES(home_formation),
                away_formation = VALUES(away_formation),
                lineups_scraped = VALUES(lineups_scraped)
        ");
        $stmt->execute([
            $matchId, $championship, $rodada, $homeTeam, $awayTeam,
            $homeScore, $awayScore, $homePenalties, $awayPenalties,
            $homeScorers, $awayScorers, $date, $time, $stadium,
            $status, $homeLogo, $awayLogo, $homeFormation, $awayFormation, $lineupsScraped
        ]);
        
        // Scrape events (timeline / lance-a-lance)
        $eventNodes = $xpath->query("//ul[@id='lance-a-lance']/li");
        if ($eventNodes->length) {
            // Delete old events for this match to refresh
            $stmtDel = $this->db->prepare("DELETE FROM poltrona_match_events WHERE match_id = ?");
            $stmtDel->execute([$matchId]);
            
            $stmtEvent = $this->db->prepare("
                INSERT INTO poltrona_match_events (
                    match_id, event_external_id, minute, period, type, team_name, player_name, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($eventNodes as $eventNode) {
                $eventIdAttr = $eventNode->getAttribute('id'); // e.g. "evento-1468314"
                $eventExtId = str_replace('evento-', '', $eventIdAttr);
                
                $classAttr = $eventNode->getAttribute('class');
                $type = 'lance-normal';
                if (strpos($classAttr, 'lance-importante') !== false) {
                    $type = 'lance-importante';
                } elseif (strpos($classAttr, 'lance-gol') !== false) {
                    $type = 'lance-gol';
                } elseif (strpos($classAttr, 'lance-substituicao') !== false) {
                    $type = 'lance-substituicao';
                } elseif (strpos($classAttr, 'lance-cartao') !== false) {
                    $type = 'lance-cartao';
                }
                
                // Minute and Period
                $minNode = $xpath->query(".//span[@class='minuto-lance']", $eventNode);
                $minute = $minNode->length ? trim($minNode->item(0)->nodeValue) : '';
                
                $periodNode = $xpath->query(".//span[@class='periodo-lance']", $eventNode);
                $period = $periodNode->length ? trim($periodNode->item(0)->nodeValue) : '';
                
                // Team (chapeu)
                $teamNode = $xpath->query(".//span[@class='titulo-chapeu']", $eventNode);
                $teamName = $teamNode->length ? trim($teamNode->item(0)->nodeValue) : null;
                
                // Player Name
                $playerNode = $xpath->query(".//strong[@class='jogador-nome']", $eventNode);
                $playerName = $playerNode->length ? trim($playerNode->item(0)->nodeValue) : null;
                
                // Description
                $descNode = $xpath->query(".//span[@class='descricao-lance']", $eventNode);
                $description = $descNode->length ? trim($descNode->item(0)->nodeValue) : '';
                
                $stmtEvent->execute([
                    $matchId, $eventExtId, $minute, $period, $type, $teamName, $playerName, $description
                ]);
            }
        }
    }
    
    private function saveTeam($name, $logoUrl) {
        if (empty($name)) return;
        
        $stmt = $this->db->prepare("
            INSERT INTO poltrona_teams (name, logo_url) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE logo_url = VALUES(logo_url)
        ");
        $stmt->execute([$name, $logoUrl]);
    }
    
    private function startRun() {
        $stmt = $this->db->prepare("INSERT INTO poltrona_scraper_runs (started_at) VALUES (NOW())");
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    private function endRun($id, $success, $itemsFound, $durationMs, $errorMessage = null) {
        $stmt = $this->db->prepare("
            UPDATE poltrona_scraper_runs 
            SET finished_at = NOW(), success = ?, items_found = ?, duration_ms = ?, error_message = ? 
            WHERE id = ?
        ");
        $stmt->execute([$success ? 1 : 0, $itemsFound, $durationMs, $errorMessage, $id]);
    }
}
