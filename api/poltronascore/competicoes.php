<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados MySQL.");
    }
    
    // Obter todas as competições de clubes cadastradas no CONFUSA.top (competicao_lista)
    $query = "
        SELECT 
            c.id,
            c.nome,
            c.ano,
            c.logo,
            c.tipo,
            COUNT(j.id) as total_matches,
            SUM(CASE WHEN j.status = 1 THEN 1 ELSE 0 END) as finished_matches,
            SUM(CASE WHEN j.status = 0 THEN 1 ELSE 0 END) as next_matches
        FROM competicao_lista c
        INNER JOIN jogos_clube j ON j.competicao_id = c.id
        GROUP BY c.id, c.nome, c.ano, c.logo, c.tipo
        HAVING total_matches > 0
        ORDER BY c.ano DESC, c.id DESC
    ";
    
    $stmt = $conn->query($query);
    $competitions = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $compId = (int)$row['id'];
        $displayName = $row['nome'] . (!empty($row['ano']) ? ' ' . $row['ano'] : '');
        
        // Logo da competição
        $compLogo = '';
        if (!empty($row['logo']) && $row['logo'] !== '0.png') {
            $compLogo = (strpos($row['logo'], 'http') === 0) ? $row['logo'] : '/images/competicoes/' . basename($row['logo']);
        }
        
        // Obter amostra de clubes participantes da competição
        $sampleTeams = [];
        try {
            $stmtTeams = $conn->prepare("
                SELECT DISTINCT cl.ID as id, cl.Nome as team_name, cl.Escudo as logo_url
                FROM (
                    SELECT timeA_id as time_id FROM jogos_clube WHERE competicao_id = ? AND timeA_id > 0
                    UNION
                    SELECT timeB_id as time_id FROM jogos_clube WHERE competicao_id = ? AND timeB_id > 0
                ) jt
                INNER JOIN clube cl ON cl.ID = jt.time_id
                LIMIT 6
            ");
            $stmtTeams->execute([$compId, $compId]);
            while ($tRow = $stmtTeams->fetch(PDO::FETCH_ASSOC)) {
                $logo = '';
                if (!empty($tRow['logo_url']) && $tRow['logo_url'] !== '0.png') {
                    $logo = (strpos($tRow['logo_url'], 'http') === 0) ? $tRow['logo_url'] : '/images/escudos/' . basename($tRow['logo_url']);
                }
                $sampleTeams[] = [
                    'team_id' => (int)$tRow['id'],
                    'team_name' => $tRow['team_name'],
                    'logo_url' => $logo
                ];
            }

            if (count($sampleTeams) < 6) {
                $db3File = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') 
                    ? $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $compId . "-database.db3"
                    : dirname(__DIR__, 2) . "/competicoes/databases/" . $compId . "-database.db3";
                if (file_exists($db3File)) {
                    $sdb = new PDO("sqlite:" . $db3File);
                    $limitLeft = 6 - count($sampleTeams);
                    $stSqlite = $sdb->query("SELECT ID, Nome, Escudo FROM clube LIMIT $limitLeft");
                    if ($stSqlite) {
                        while ($sRow = $stSqlite->fetch(PDO::FETCH_ASSOC)) {
                            $logo = '';
                            $rawEsc = trim($sRow['Escudo'] ?? '');
                            if (!empty($rawEsc) && $rawEsc !== '0.png' && $rawEsc !== 'Imagens/EscudoPadrao.png') {
                                if (strpos($rawEsc, 'http') === 0) {
                                    $logo = $rawEsc;
                                } elseif (strpos($rawEsc, 'cache_images/') === 0) {
                                    $logo = '/competicoes/hexacolor/' . $rawEsc;
                                } else {
                                    $escClean = str_replace(['../../images/escudos/', '../images/escudos/'], '', $rawEsc);
                                    $logo = '/images/escudos/' . basename($escClean);
                                }
                            }
                            $sampleTeams[] = [
                                'team_id' => -1 * abs((int)$sRow['ID']),
                                'team_name' => $sRow['Nome'],
                                'logo_url' => $logo
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}
        
        $competitions[] = [
            'id' => $compId,
            'name' => $displayName,
            'raw_name' => $row['nome'],
            'year' => $row['ano'],
            'logo' => $compLogo,
            'total_matches' => (int)$row['total_matches'],
            'finished_matches' => (int)$row['finished_matches'],
            'next_matches' => (int)$row['next_matches'],
            'sample_teams' => $sampleTeams
        ];
    }
    
    echo json_encode([
        'success' => true,
        'competitions' => $competitions
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
