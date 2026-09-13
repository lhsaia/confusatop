<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

$clubId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clubNameParam = trim($_GET['nome'] ?? '');

if ($clubId <= 0 && empty($clubNameParam)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID ou nome do clube não informado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    $posicoesMap = [
        0 => ['sigla' => 'G', 'nome' => 'Goleiro', 'cat' => 'goleiros'],
        1 => ['sigla' => 'LD', 'nome' => 'Lateral Direito', 'cat' => 'defensores'],
        2 => ['sigla' => 'Z', 'nome' => 'Zagueiro', 'cat' => 'defensores'],
        3 => ['sigla' => 'LE', 'nome' => 'Lateral Esquerdo', 'cat' => 'defensores'],
        4 => ['sigla' => 'V', 'nome' => 'Volante', 'cat' => 'meio_campistas'],
        5 => ['sigla' => 'MD', 'nome' => 'Meia Direita', 'cat' => 'meio_campistas'],
        6 => ['sigla' => 'MC', 'nome' => 'Meio-Campo', 'cat' => 'meio_campistas'],
        7 => ['sigla' => 'ME', 'nome' => 'Meia Esquerda', 'cat' => 'meio_campistas'],
        8 => ['sigla' => 'MA', 'nome' => 'Meia Atacante', 'cat' => 'meio_campistas'],
        9 => ['sigla' => 'PD', 'nome' => 'Ponta Direita', 'cat' => 'atacantes'],
        10 => ['sigla' => 'CA', 'nome' => 'Centroavante', 'cat' => 'atacantes'],
        11 => ['sigla' => 'PE', 'nome' => 'Ponta Esquerda', 'cat' => 'atacantes'],
        12 => ['sigla' => 'SA', 'nome' => 'Segundo Atacante', 'cat' => 'atacantes'],
        13 => ['sigla' => 'AD', 'nome' => 'Ala Direito', 'cat' => 'defensores'],
        14 => ['sigla' => 'AE', 'nome' => 'Ala Esquerdo', 'cat' => 'defensores'],
    ];

    if ($clubId > 0) {
        $stmtC = $conn->prepare("
            SELECT c.*, e.Nome as estadio_nome, e.Capacidade as estadio_capacidade, e.foto as estadio_foto,
                   p.nome as pais_nome, p.bandeira as pais_bandeira
            FROM clube c
            LEFT JOIN estadio e ON e.ID = c.Estadio
            LEFT JOIN paises p ON p.id = c.Pais
            WHERE c.ID = ?
            LIMIT 1
        ");
        $stmtC->execute([$clubId]);
    } else {
        $stmtC = $conn->prepare("
            SELECT c.*, e.Nome as estadio_nome, e.Capacidade as estadio_capacidade, e.foto as estadio_foto,
                   p.nome as pais_nome, p.bandeira as pais_bandeira
            FROM clube c
            LEFT JOIN estadio e ON e.ID = c.Estadio
            LEFT JOIN paises p ON p.id = c.Pais
            WHERE c.Nome = ? OR c.Nome LIKE ?
            LIMIT 1
        ");
        $stmtC->execute([$clubNameParam, "%$clubNameParam%"]);
    }

    $club = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$club) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Clube não encontrado']);
        exit;
    }

    $clubId = (int)$club['ID'];

    // 1. Escudo e Bandeira
    $logoUrl = '';
    if (!empty($club['Escudo']) && $club['Escudo'] !== '0.png') {
        $logoUrl = (strpos($club['Escudo'], 'http') === 0) ? $club['Escudo'] : '/images/escudos/' . basename($club['Escudo']);
    }

    $flagUrl = '';
    if (!empty($club['pais_bandeira']) && $club['pais_bandeira'] !== 'flag.png') {
        $flagUrl = (strpos($club['pais_bandeira'], 'http') === 0) ? $club['pais_bandeira'] : '/images/bandeiras/' . basename($club['pais_bandeira']);
    }

    // 2. Estádio
    $stadiumPhoto = '';
    if (!empty($club['estadio_foto'])) {
        $stadiumPhoto = (strpos($club['estadio_foto'], 'http') === 0) ? $club['estadio_foto'] : '/images/estadios/' . basename($club['estadio_foto']);
    }

    $stadiumData = [
        'name' => $club['estadio_nome'] ?: 'Estádio não informado',
        'capacity' => (int)($club['estadio_capacidade'] ?? 0),
        'capacity_formatted' => number_format((int)($club['estadio_capacidade'] ?? 0), 0, ',', '.'),
        'photo' => $stadiumPhoto
    ];

    // 3. Elenco Completo
    $stmtR = $conn->prepare("
        SELECT j.ID, j.Nome, j.foto, j.Nivel, j.valor, j.StringPosicoes, j.Nascimento,
               cj.posicaoBase, cj.numeroCamisa, cj.titularidade, cj.salario,
               p.nome as pais_nome, p.bandeira as pais_bandeira
        FROM contratos_jogador cj
        INNER JOIN jogador j ON j.ID = cj.jogador
        LEFT JOIN paises p ON p.id = j.Pais
        WHERE cj.clube = ?
        ORDER BY j.Nivel DESC, j.ID ASC
    ");
    $stmtR->execute([$clubId]);
    $rawSquad = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $squadGrouped = [
        'goleiros' => [],
        'defensores' => [],
        'meio_campistas' => [],
        'atacantes' => []
    ];

    $totalAge = 0;
    $totalLevel = 0;
    $totalValue = 0;
    $validAgeCount = 0;

    $now = new DateTime();

    foreach ($rawSquad as $p) {
        $pId = (int)$p['ID'];
        $sp = str_pad((string)($p['StringPosicoes'] ?? ''), 15, '0');
        
        $pPositions = [];
        $pPrimary = '';
        $pCat = 'meio_campistas';

        for ($i = 0; $i < 15; $i++) {
            if (isset($sp[$i]) && $sp[$i] === '1') {
                $pPos = $posicoesMap[$i] ?? ['sigla' => 'LIN', 'nome' => 'Linha', 'cat' => 'meio_campistas'];
                $pPositions[] = $pPos['sigla'];
                if (empty($pPrimary)) {
                    $pPrimary = $pPos['sigla'];
                    $pCat = $pPos['cat'];
                }
            }
        }

        if (empty($pPositions)) {
            $pPositions[] = 'LIN';
            $pPrimary = 'LIN';
        }

        // Foto
        $pPhoto = '';
        if (!empty($p['foto']) && $p['foto'] !== 'default.webp' && $p['foto'] !== '0.png') {
            $pPhoto = (strpos($p['foto'], 'http') === 0) ? $p['foto'] : '/images/jogadores/' . basename($p['foto']);
        }

        // Idade
        $pAge = null;
        if (!empty($p['Nascimento']) && $p['Nascimento'] !== '0000-00-00') {
            $dob = new DateTime($p['Nascimento']);
            $pAge = $now->diff($dob)->y;
            $totalAge += $pAge;
            $validAgeCount++;
        }

        $pNivel = (int)$p['Nivel'];
        $pValor = (int)$p['valor'];
        $totalLevel += $pNivel;
        $totalValue += $pValor;

        $pFlag = '';
        if (!empty($p['pais_bandeira']) && $p['pais_bandeira'] !== 'flag.png') {
            $pFlag = (strpos($p['pais_bandeira'], 'http') === 0) ? $p['pais_bandeira'] : '/images/bandeiras/' . basename($p['pais_bandeira']);
        }

        $playerFormatted = [
            'id' => $pId,
            'name' => $p['Nome'],
            'photo' => $pPhoto,
            'level' => $pNivel,
            'age' => $pAge,
            'country' => $p['pais_nome'] ?: '',
            'flag' => $pFlag,
            'primary_position' => $pPrimary,
            'positions' => $pPositions,
            'jersey_number' => $p['numeroCamisa'] ? (int)$p['numeroCamisa'] : null,
            'starter' => (bool)$p['titularidade'],
            'value' => $pValor,
            'value_formatted' => '$ ' . number_format($pValor, 0, ',', '.')
        ];

        if (isset($squadGrouped[$pCat])) {
            $squadGrouped[$pCat][] = $playerFormatted;
        } else {
            $squadGrouped['meio_campistas'][] = $playerFormatted;
        }
    }

    $squadCount = count($rawSquad);
    $avgAge = ($validAgeCount > 0) ? round($totalAge / $validAgeCount, 1) : null;
    $avgLevel = ($squadCount > 0) ? round($totalLevel / $squadCount, 1) : 0;

    // 4. Calendário de Jogos (Anteriores e Próximos)
    $stmtMatches = $conn->prepare("
        SELECT 
            j.id, j.data, j.timeA_id, j.timeA_nome, j.timeA_gols,
            j.timeB_id, j.timeB_nome, j.timeB_gols, j.competicao_id,
            j.status,
            COALESCE(NULLIF(j.estadio_nome, ''), eDirect.Nome, eHome.Nome, '') as estadio,
            COALESCE(cl.nome, li.nome, cc.nome, 'Competição') as competicao_nome,
            cA.Escudo as escudoA, cB.Escudo as escudoB
        FROM jogos_clube j
        LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id AND j.simulador_interno = 1
        LEFT JOIN liga li ON li.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 0
        LEFT JOIN campeonatos_clube cc ON cc.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 1
        LEFT JOIN clube cA ON cA.ID = j.timeA_id
        LEFT JOIN clube cB ON cB.ID = j.timeB_id
        LEFT JOIN estadio eDirect ON eDirect.ID = j.estadio_id
        LEFT JOIN estadio eHome ON eHome.ID = cA.Estadio
        WHERE j.timeA_id = ? OR j.timeB_id = ?
        ORDER BY j.data DESC, j.id DESC
    ");
    $stmtMatches->execute([$clubId, $clubId]);
    $allMatches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

    $previousMatches = [];
    $nextMatches = [];

    foreach ($allMatches as $m) {
        $isHome = ((int)$m['timeA_id'] === $clubId);
        $opponent = $isHome ? $m['timeB_nome'] : $m['timeA_nome'];
        $opponentId = $isHome ? (int)$m['timeB_id'] : (int)$m['timeA_id'];
        $opponentLogoRaw = $isHome ? $m['escudoB'] : $m['escudoA'];
        
        $oppLogo = '';
        if (!empty($opponentLogoRaw) && $opponentLogoRaw !== '0.png') {
            $oppLogo = (strpos($opponentLogoRaw, 'http') === 0) ? $opponentLogoRaw : '/images/escudos/' . basename($opponentLogoRaw);
        }

        $myScore = $isHome ? (int)$m['timeA_gols'] : (int)$m['timeB_gols'];
        $oppScore = $isHome ? (int)$m['timeB_gols'] : (int)$m['timeA_gols'];

        $matchDateFmt = !empty($m['data']) ? date('d/m/Y', strtotime($m['data'])) : '';
        $matchTimeFmt = !empty($m['data']) ? date('H:i', strtotime($m['data'])) : '';

        if ($m['status'] == 1 && count($previousMatches) < 6) {
            $res = 'E';
            if ($myScore > $oppScore) $res = 'V';
            elseif ($myScore < $oppScore) $res = 'D';

            $previousMatches[] = [
                'id' => (int)$m['id'],
                'date' => $matchDateFmt,
                'time' => $matchTimeFmt,
                'competition' => $m['competicao_nome'] ?: 'Competição',
                'opponent' => $opponent ?: 'Adversário',
                'opponent_id' => $opponentId,
                'opponent_logo' => $oppLogo,
                'home' => $isHome,
                'score' => "$myScore - $oppScore",
                'result' => $res,
                'stadium' => $m['estadio'] ?: 'Estádio'
            ];
        } elseif ($m['status'] == 0 && count($nextMatches) < 6) {
            $nextMatches[] = [
                'id' => (int)$m['id'],
                'date' => $matchDateFmt,
                'time' => $matchTimeFmt,
                'competition' => $m['competicao_nome'] ?: 'Competição',
                'opponent' => $opponent ?: 'Adversário',
                'opponent_id' => $opponentId,
                'opponent_logo' => $oppLogo,
                'home' => $isHome,
                'stadium' => $m['estadio'] ?: 'Estádio'
            ];
        }
    }

    // Compact formatted market value (e.g., $ 12.5M, $ 850k)
    $compactValue = '$ 0';
    if ($totalValue >= 1000000000) {
        $compactValue = '$ ' . round($totalValue / 1000000000, 1) . 'B';
    } elseif ($totalValue >= 1000000) {
        $compactValue = '$ ' . round($totalValue / 1000000, 1) . 'M';
    } elseif ($totalValue >= 1000) {
        $compactValue = '$ ' . round($totalValue / 1000, 0) . 'k';
    } else {
        $compactValue = '$ ' . $totalValue;
    }

    echo json_encode([
        'success' => true,
        'club' => [
            'id' => $clubId,
            'name' => $club['Nome'],
            'code' => $club['TresLetras'] ?: '',
            'logo' => $logoUrl,
            'city' => ($club['cidade'] && $club['cidade'] !== 'Cidade') ? $club['cidade'] : '',
            'foundation' => ($club['fundacao'] && $club['fundacao'] !== 'Fundação') ? $club['fundacao'] : '',
            'nickname' => ($club['apelido'] && $club['apelido'] !== 'Apelido') ? $club['apelido'] : '',
            'country' => $club['pais_nome'] ?: '',
            'flag' => $flagUrl,
            'stadium' => $stadiumData,
            'stats' => [
                'squad_count' => $squadCount,
                'average_age' => $avgAge,
                'average_level' => $avgLevel,
                'total_market_value' => $totalValue,
                'total_market_value_formatted' => '$ ' . number_format($totalValue, 0, ',', '.'),
                'total_market_value_compact' => $compactValue
            ],
            'squad' => $squadGrouped,
            'matches' => [
                'previous' => $previousMatches,
                'next' => array_reverse($nextMatches)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
