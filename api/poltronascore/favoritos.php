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
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    $rawMatches = trim($_GET['matches'] ?? '');
    $rawClubs = trim($_GET['clubs'] ?? '');
    $rawComps = trim($_GET['competitions'] ?? '');
    $rawPlayers = trim($_GET['players'] ?? '');

    $matchesIds = array_filter(array_map('intval', explode(',', $rawMatches)));
    $clubsIds = array_filter(array_map('intval', explode(',', $rawClubs)));
    $compsIds = array_filter(array_map('intval', explode(',', $rawComps)));
    $playersIds = array_filter(array_map('intval', explode(',', $rawPlayers)));

    $response = [
        'success' => true,
        'matches' => [],
        'clubs' => [],
        'competitions' => [],
        'players' => []
    ];

    // 1. Buscar Partidas Favoritas e Partidas de Clubes Favoritos
    if (!empty($matchesIds) || !empty($clubsIds)) {
        $whereClauses = [];
        $params = [];

        if (!empty($matchesIds)) {
            $inMatches = implode(',', array_fill(0, count($matchesIds), '?'));
            $whereClauses[] = "j.id IN ($inMatches)";
            $params = array_merge($params, $matchesIds);
        }

        if (!empty($clubsIds)) {
            $inClubs = implode(',', array_fill(0, count($clubsIds), '?'));
            $whereClauses[] = "j.timeA_id IN ($inClubs) OR j.timeB_id IN ($inClubs)";
            $params = array_merge($params, $clubsIds, $clubsIds);
        }

        $sqlMatches = "
            SELECT 
                j.id, j.competicao_id, j.rodada_id, j.data, j.hora, j.status,
                j.timeA_id, j.timeA_nome, j.timeA_gols, j.timeA_penaltis,
                j.timeB_id, j.timeB_nome, j.timeB_gols, j.timeB_penaltis,
                j.estadio,
                cl.nome as competicao_nome,
                cr.nome as rodada_nome,
                cA.Escudo as timeA_escudo,
                cB.Escudo as timeB_escudo
            FROM jogos_clube j
            LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id
            LEFT JOIN competicao_rodada cr ON cr.id = j.rodada_id
            LEFT JOIN clube cA ON cA.ID = j.timeA_id
            LEFT JOIN clube cB ON cB.ID = j.timeB_id
            WHERE " . implode(' OR ', $whereClauses) . "
            ORDER BY j.data DESC, j.hora DESC, j.id DESC
            LIMIT 30
        ";

        $stmtM = $conn->prepare($sqlMatches);
        $stmtM->execute($params);

        while ($m = $stmtM->fetch(PDO::FETCH_ASSOC)) {
            $statusStr = 'previous';
            if ((int)$m['status'] === 0) {
                $statusStr = 'next';
            } elseif ((int)$m['status'] === 2) {
                $statusStr = 'live';
            }

            $logoA = '';
            if (!empty($m['timeA_escudo']) && $m['timeA_escudo'] !== '0.png') {
                $logoA = (strpos($m['timeA_escudo'], 'http') === 0) ? $m['timeA_escudo'] : '/images/escudos/' . basename($m['timeA_escudo']);
            }
            $logoB = '';
            if (!empty($m['timeB_escudo']) && $m['timeB_escudo'] !== '0.png') {
                $logoB = (strpos($m['timeB_escudo'], 'http') === 0) ? $m['timeB_escudo'] : '/images/escudos/' . basename($m['timeB_escudo']);
            }

            $response['matches'][] = [
                'id' => (int)$m['id'],
                'competition' => $m['competicao_nome'] ?: 'Competição',
                'rodada' => $m['rodada_nome'] ?: '',
                'date' => !empty($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '',
                'time' => !empty($m['hora']) ? substr($m['hora'], 0, 5) : '',
                'status' => $statusStr,
                'home_id' => (int)$m['timeA_id'],
                'home_team' => $m['timeA_nome'],
                'home_logo' => $logoA,
                'home_score' => ($m['status'] != 0) ? (int)$m['timeA_gols'] : null,
                'away_id' => (int)$m['timeB_id'],
                'away_team' => $m['timeB_nome'],
                'away_logo' => $logoB,
                'away_score' => ($m['status'] != 0) ? (int)$m['timeB_gols'] : null,
                'stadium' => $m['estadio'] ?: ''
            ];
        }
    }

    // 2. Buscar Clubes Favoritos
    if (!empty($clubsIds)) {
        $inClubs = implode(',', array_fill(0, count($clubsIds), '?'));
        $stmtC = $conn->prepare("
            SELECT c.ID, c.Nome, c.Escudo, p.nome as pais_nome, p.bandeira as pais_bandeira
            FROM clube c
            LEFT JOIN paises p ON p.id = c.Pais
            WHERE c.ID IN ($inClubs)
            ORDER BY c.Nome ASC
        ");
        $stmtC->execute($clubsIds);

        while ($c = $stmtC->fetch(PDO::FETCH_ASSOC)) {
            $escudo = '';
            if (!empty($c['Escudo']) && $c['Escudo'] !== '0.png') {
                $escudo = (strpos($c['Escudo'], 'http') === 0) ? $c['Escudo'] : '/images/escudos/' . basename($c['Escudo']);
            }
            $flag = '';
            if (!empty($c['pais_bandeira']) && $c['pais_bandeira'] !== 'flag.png') {
                $flag = (strpos($c['pais_bandeira'], 'http') === 0) ? $c['pais_bandeira'] : '/images/bandeiras/' . basename($c['pais_bandeira']);
            }

            $response['clubs'][] = [
                'id' => (int)$c['ID'],
                'name' => $c['Nome'],
                'logo' => $escudo,
                'country' => $c['pais_nome'] ?: '',
                'flag' => $flag
            ];
        }
    }

    // 3. Buscar Competições Favoritas
    if (!empty($compsIds)) {
        $inComps = implode(',', array_fill(0, count($compsIds), '?'));
        $stmtCp = $conn->prepare("
            SELECT cl.id, cl.nome, cl.logo, p.nome as pais_nome, p.bandeira as pais_bandeira,
                   (SELECT COUNT(*) FROM competicao_times ct WHERE ct.competicao_id = cl.id) as total_times
            FROM competicao_lista cl
            LEFT JOIN paises p ON p.id = cl.pais_id
            WHERE cl.id IN ($inComps)
            ORDER BY cl.nome ASC
        ");
        $stmtCp->execute($compsIds);

        while ($cp = $stmtCp->fetch(PDO::FETCH_ASSOC)) {
            $logo = '';
            if (!empty($cp['logo']) && $cp['logo'] !== 'default.webp') {
                $logo = (strpos($cp['logo'], 'http') === 0) ? $cp['logo'] : '/images/competicoes/' . basename($cp['logo']);
            }
            $flag = '';
            if (!empty($cp['pais_bandeira']) && $cp['pais_bandeira'] !== 'flag.png') {
                $flag = (strpos($cp['pais_bandeira'], 'http') === 0) ? $cp['pais_bandeira'] : '/images/bandeiras/' . basename($cp['pais_bandeira']);
            }

            $response['competitions'][] = [
                'id' => (int)$cp['id'],
                'name' => $cp['nome'],
                'logo' => $logo,
                'country' => $cp['pais_nome'] ?: '',
                'flag' => $flag,
                'total_teams' => (int)$cp['total_times']
            ];
        }
    }

    // 4. Buscar Jogadores Favoritos
    if (!empty($playersIds)) {
        $inPlayers = implode(',', array_fill(0, count($playersIds), '?'));
        $stmtP = $conn->prepare("
            SELECT j.ID, j.Nome, j.foto, j.Nivel, j.StringPosicoes,
                   p.nome as pais_nome, p.bandeira as pais_bandeira,
                   cl.Nome as clube_nome, cl.Escudo as clube_escudo, cl.ID as clube_id
            FROM jogador j
            LEFT JOIN paises p ON p.id = j.Pais
            LEFT JOIN contratos_jogador cj ON cj.jogador = j.ID
            LEFT JOIN clube cl ON cl.ID = cj.clube
            WHERE j.ID IN ($inPlayers)
            ORDER BY j.Nome ASC
        ");
        $stmtP->execute($playersIds);

        $posicoesMap = [
            0 => 'G', 1 => 'LD', 2 => 'Z', 3 => 'LE', 4 => 'V',
            5 => 'MD', 6 => 'MC', 7 => 'ME', 8 => 'MA', 9 => 'PD',
            10 => 'CA', 11 => 'PE', 12 => 'SA', 13 => 'AD', 14 => 'AE'
        ];

        while ($p = $stmtP->fetch(PDO::FETCH_ASSOC)) {
            $photo = '';
            if (!empty($p['foto']) && $p['foto'] !== 'default.webp' && $p['foto'] !== '0.png') {
                $photo = (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '/images/jogadores/' . basename($p['foto']);
            }
            $flag = '';
            if (!empty($p['pais_bandeira']) && $p['pais_bandeira'] !== 'flag.png') {
                $flag = (strpos($p['pais_bandeira'], 'http') === 0) ? $p['pais_bandeira'] : '/images/bandeiras/' . basename($p['pais_bandeira']);
            }
            $cLogo = '';
            if (!empty($p['clube_escudo']) && $p['clube_escudo'] !== '0.png') {
                $cLogo = (strpos($p['clube_escudo'], 'http') === 0) ? $p['clube_escudo'] : '/images/escudos/' . basename($p['clube_escudo']);
            }

            $sp = str_pad((string)($p['StringPosicoes'] ?? ''), 15, '0');
            $pos = 'LIN';
            for ($i = 0; $i < 15; $i++) {
                if (isset($sp[$i]) && $sp[$i] === '1') {
                    $pos = $posicoesMap[$i] ?? 'LIN';
                    break;
                }
            }

            $response['players'][] = [
                'id' => (int)$p['ID'],
                'name' => $p['Nome'],
                'photo' => $photo,
                'level' => (int)$p['Nivel'],
                'position' => $pos,
                'country' => $p['pais_nome'] ?: '',
                'flag' => $flag,
                'club_id' => (int)($p['clube_id'] ?? 0),
                'club_name' => $p['clube_nome'] ?: '',
                'club_logo' => $cLogo
            ];
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
