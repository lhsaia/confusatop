<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

$playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$playerNameParam = trim($_GET['nome'] ?? '');
$clubIdParam = isset($_GET['clube']) ? (int)$_GET['clube'] : (isset($_GET['time']) ? (int)$_GET['time'] : (isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0));

if ($playerId <= 0 && empty($playerNameParam)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID ou nome do jogador não informado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }

    $posicoesMap = [
        0 => ['sigla' => 'G', 'nome' => 'Goleiro', 'cat' => 'Goleiro'],
        1 => ['sigla' => 'LD', 'nome' => 'Lateral Direito', 'cat' => 'Defensor'],
        2 => ['sigla' => 'LE', 'nome' => 'Lateral Esquerdo', 'cat' => 'Defensor'],
        3 => ['sigla' => 'Z', 'nome' => 'Zagueiro', 'cat' => 'Defensor'],
        4 => ['sigla' => 'AD', 'nome' => 'Ala Direito', 'cat' => 'Defensor'],
        5 => ['sigla' => 'AE', 'nome' => 'Ala Esquerdo', 'cat' => 'Defensor'],
        6 => ['sigla' => 'V', 'nome' => 'Volante', 'cat' => 'Meio-Campista'],
        7 => ['sigla' => 'MD', 'nome' => 'Meia Direita', 'cat' => 'Meio-Campista'],
        8 => ['sigla' => 'ME', 'nome' => 'Meia Esquerda', 'cat' => 'Meio-Campista'],
        9 => ['sigla' => 'MC', 'nome' => 'Meio-Campo', 'cat' => 'Meio-Campista'],
        10 => ['sigla' => 'PD', 'nome' => 'Ponta Direita', 'cat' => 'Atacante'],
        11 => ['sigla' => 'PE', 'nome' => 'Ponta Esquerda', 'cat' => 'Atacante'],
        12 => ['sigla' => 'MA', 'nome' => 'Meia Atacante', 'cat' => 'Meio-Campista'],
        13 => ['sigla' => 'Am', 'nome' => 'Atacante de Movimentação', 'cat' => 'Atacante'],
        14 => ['sigla' => 'Aa', 'nome' => 'Atacante de Área', 'cat' => 'Atacante'],
    ];

    $mentalidadesMap = [
        1 => 'Mascarado',
        2 => 'Sangue Frio',
        3 => 'Pacificador',
        4 => 'Neutro',
        5 => 'Líder',
        6 => 'Provocador',
        7 => 'Explosivo',
        8 => 'Raçudo'
    ];

    $player = null;

    if ($playerId > 0) {
        $stmtP = $conn->prepare("
            SELECT j.*, p.nome as nome_pais, p.bandeira as bandeira_pais, m.Nome as nome_mentalidade
            FROM jogador j
            LEFT JOIN paises p ON p.id = j.Pais
            LEFT JOIN mentalidade m ON m.ID = j.Mentalidade
            WHERE j.ID = ?
            LIMIT 1
        ");
        $stmtP->execute([$playerId]);
        $player = $stmtP->fetch(PDO::FETCH_ASSOC);
    }

    if (!$player && !empty($playerNameParam) && $clubIdParam > 0) {
        $cleanName = stripslashes(trim($playerNameParam));
        $stmtP = $conn->prepare("
            SELECT j.*, p.nome as nome_pais, p.bandeira as bandeira_pais, m.Nome as nome_mentalidade
            FROM jogador j
            INNER JOIN contratos_jogador cj ON cj.jogador = j.ID AND cj.clube = ?
            LEFT JOIN paises p ON p.id = j.Pais
            LEFT JOIN mentalidade m ON m.ID = j.Mentalidade
            WHERE CONVERT(j.Nome USING utf8mb4) = ? OR CONVERT(j.Nome USING utf8mb4) LIKE ?
            ORDER BY 
                CASE 
                    WHEN CONVERT(j.Nome USING utf8mb4) = ? THEN 0
                    WHEN CONVERT(j.Nome USING utf8mb4) LIKE ? THEN 1
                    ELSE 2
                END,
                j.ID ASC
            LIMIT 1
        ");
        $stmtP->execute([
            $clubIdParam,
            $cleanName, 
            "%$cleanName%",
            $cleanName, 
            "$cleanName%"
        ]);
        $player = $stmtP->fetch(PDO::FETCH_ASSOC);
    }

    if (!$player && !empty($playerNameParam)) {
        $cleanName = stripslashes(trim($playerNameParam));
        $stmtP = $conn->prepare("
            SELECT j.*, p.nome as nome_pais, p.bandeira as bandeira_pais, m.Nome as nome_mentalidade
            FROM jogador j
            LEFT JOIN paises p ON p.id = j.Pais
            LEFT JOIN mentalidade m ON m.ID = j.Mentalidade
            WHERE CONVERT(j.Nome USING utf8mb4) = ? OR CONVERT(j.Nome USING utf8mb4) LIKE ?
            ORDER BY 
                CASE 
                    WHEN CONVERT(j.Nome USING utf8mb4) = ? THEN 0
                    WHEN CONVERT(j.Nome USING utf8mb4) LIKE ? THEN 1
                    ELSE 2
                END,
                j.ID ASC
            LIMIT 1
        ");
        $stmtP->execute([
            $cleanName, 
            "%$cleanName%",
            $cleanName, 
            "$cleanName%"
        ]);
        $player = $stmtP->fetch(PDO::FETCH_ASSOC);
    }

    // Fallback: buscar nos bancos SQLite das competições se não encontrado no MariaDB
    if (!$player) {
        $dbDir = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') 
            ? $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" 
            : dirname(__DIR__, 2) . "/competicoes/databases/";
        if (is_dir($dbDir)) {
            $files = glob($dbDir . "*-database.db3");
            foreach ($files as $f) {
                try {
                    $sdb = new PDO("sqlite:" . $f);
                    $sdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $sRow = null;
                    if ($playerId != 0) {
                        try {
                            $pIdAbs = abs($playerId);
                            $st = $sdb->prepare("SELECT * FROM jogador WHERE ID = :id1 OR ID = :id2 LIMIT 1");
                            if ($st) {
                                $st->execute([':id1' => $playerId, ':id2' => ($playerId < 0 ? $pIdAbs : -$pIdAbs)]);
                                $sRow = $st->fetch(PDO::FETCH_ASSOC);
                            }
                        } catch (\Throwable $e) {}
                    }
                    if (!$sRow && !empty($playerNameParam)) {
                        try {
                            $cleanName = stripslashes(trim($playerNameParam));
                            $st = $sdb->prepare("SELECT * FROM jogador WHERE Nome = ? OR Nome LIKE ? LIMIT 1");
                            if ($st) {
                                $st->execute([$cleanName, "%$cleanName%"]);
                                $sRow = $st->fetch(PDO::FETCH_ASSOC);
                            }
                        } catch (\Throwable $e) {}
                    }
                    if ($sRow) {
                        $jId = (int)$sRow['ID'];
                        // Obter mapa de posições
                        $strPos = '000000000000000';
                        try {
                            $stPos = $sdb->prepare("SELECT * FROM posicaojogador WHERE Jogador = ? LIMIT 1");
                            if ($stPos) {
                                $stPos->execute([$jId]);
                                $posRow = $stPos->fetch(PDO::FETCH_ASSOC);
                                if ($posRow) {
                                    $posOrder = ['G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];
                                    $strPos = '';
                                    foreach ($posOrder as $po) {
                                        $strPos .= (!empty($posRow[$po]) && (int)$posRow[$po] === 1) ? '1' : '0';
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}

                        // Atributos de jogador de linha
                        $attRow = [];
                        try {
                            $stAtt = $sdb->prepare("SELECT * FROM atributosjogador WHERE Jogador = ? LIMIT 1");
                            if ($stAtt) {
                                $stAtt->execute([$jId]);
                                $attRow = $stAtt->fetch(PDO::FETCH_ASSOC) ?: [];
                            }
                        } catch (\Throwable $e) {}

                        // Atributos de goleiro
                        $gkRow = [];
                        try {
                            $stGk = $sdb->prepare("SELECT * FROM atributosgoleiro WHERE Goleiro = ? OR Jogador = ? LIMIT 1");
                            if ($stGk) {
                                $stGk->execute([$jId, $jId]);
                                $gkRow = $stGk->fetch(PDO::FETCH_ASSOC) ?: [];
                            }
                        } catch (\Throwable $e) {}

                        // País e Bandeira
                        $sqlitePais = '';
                        $sqliteBandeira = '';
                        try {
                            $stNac = $sdb->prepare("SELECT Nacionalidade FROM nacionalidades WHERE ID_Jogador = ? LIMIT 1");
                            if ($stNac) {
                                $stNac->execute([$jId]);
                                $nacStr = $stNac->fetchColumn();
                                if ($nacStr) {
                                    $sqlitePais = $nacStr;
                                    if ($conn) {
                                        $stBp = $conn->prepare("SELECT bandeira FROM paises WHERE nome = ? OR sigla = ? LIMIT 1");
                                        $stBp->execute([$nacStr, $nacStr]);
                                        $flagB = $stBp->fetchColumn();
                                        if ($flagB) $sqliteBandeira = $flagB;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}

                        // Clube a partir do elenco no SQLite
                        $sqliteClubeId = 0;
                        $sqliteClubeNome = '';
                        $sqliteClubeEscudo = '';
                        try {
                            $stElenco = $sdb->query("SELECT * FROM elenco");
                            if ($stElenco) {
                                while ($elR = $stElenco->fetch(PDO::FETCH_ASSOC)) {
                                    for ($k = 1; $k <= 23; $k++) {
                                        if (isset($elR["Jogador$k"]) && (int)$elR["Jogador$k"] === $jId) {
                                            $sqliteClubeId = (int)$elR['Clube'];
                                            break 2;
                                        }
                                    }
                                }
                            }
                            if ($sqliteClubeId > 0) {
                                $stCl = $sdb->prepare("SELECT Nome, Escudo FROM clube WHERE ID = ? LIMIT 1");
                                if ($stCl) {
                                    $stCl->execute([$sqliteClubeId]);
                                    $clRow = $stCl->fetch(PDO::FETCH_ASSOC);
                                    if ($clRow) {
                                        $sqliteClubeNome = $clRow['Nome'];
                                        $sqliteClubeEscudo = $clRow['Escudo'];
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}

                        $player = array_merge($attRow, $gkRow, [
                            'ID' => $jId,
                            'Nome' => $sRow['Nome'],
                            'Idade' => (int)($sRow['Idade'] ?? 24),
                            'Nivel' => (int)($sRow['Nivel'] ?? 50),
                            'Potencial' => (int)($sRow['Potencial'] ?? $sRow['Nivel'] ?? 50),
                            'Mentalidade' => (int)($sRow['Mentalidade'] ?? 4),
                            'StringPosicoes' => $strPos,
                            'foto' => '',
                            'pais_nome' => $sqlitePais,
                            'sqlite_pais' => $sqlitePais,
                            'bandeira_pais' => $sqliteBandeira,
                            'sqlite_bandeira' => $sqliteBandeira,
                            'sqlite_clube_id' => $sqliteClubeId,
                            'sqlite_clube_nome' => $sqliteClubeNome,
                            'sqlite_clube_escudo' => $sqliteClubeEscudo,
                            'nome_mentalidade' => ''
                        ]);
                        break;
                    }
                } catch (\Throwable $e) {}
            }
        }
    }

    if (!$player) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Jogador não encontrado']);
        exit;
    }

    $playerId = (int)$player['ID'];
    $mentalityName = !empty($player['nome_mentalidade']) ? $player['nome_mentalidade'] : ($mentalidadesMap[(int)($player['Mentalidade'] ?? 4)] ?? 'Neutro');

    // 1. Posições do jogador
    $sp = str_pad((string)($player['StringPosicoes'] ?? ''), 15, '0');
    $posicoes = [];
    $categoria = 'Jogador';
    $posicaoPrincipal = '';

    for ($i = 0; $i < 15; $i++) {
        if (isset($sp[$i]) && $sp[$i] === '1') {
            $infoPos = $posicoesMap[$i] ?? ['sigla' => 'LIN', 'nome' => 'Linha', 'cat' => 'Jogador'];
            $posicoes[] = $infoPos['sigla'];
            if (empty($posicaoPrincipal)) {
                $posicaoPrincipal = $infoPos['sigla'];
                $categoria = $infoPos['cat'];
            }
        }
    }

    if (empty($posicoes)) {
        $posicoes[] = 'LIN';
        $posicaoPrincipal = 'LIN';
    }

    $isGoleiro = ($posicoes[0] === 'G');

    // 2. Foto do atleta
    $photoUrl = '';
    if (!empty($player['foto']) && $player['foto'] !== 'default.webp' && $player['foto'] !== '0.png') {
        $photoUrl = (strpos($player['foto'], 'http') === 0) ? $player['foto'] : '/images/jogadores/' . basename($player['foto']);
    }

    // 3. Bandeira do país
    $flagUrl = '';
    if (!empty($player['bandeira_pais']) && $player['bandeira_pais'] !== 'flag.png') {
        $flagUrl = (strpos($player['bandeira_pais'], 'http') === 0) ? $player['bandeira_pais'] : '/images/bandeiras/' . basename($player['bandeira_pais']);
    }

    // 4. Idade
    $idade = null;
    $nascimentoFmt = '';
    if (!empty($player['Nascimento']) && $player['Nascimento'] !== '0000-00-00') {
        $nascimentoFmt = date('d/m/Y', strtotime($player['Nascimento']));
        $dob = new DateTime($player['Nascimento']);
        $now = new DateTime();
        $idade = $now->diff($dob)->y;
    }

    // 5. Clube Atual
    $clubData = null;
    $stmtC = $conn->prepare("
        SELECT c.*, cl.Nome as clube_nome, cl.Escudo as clube_escudo, cl.ID as clube_id
        FROM contratos_jogador c
        INNER JOIN clube cl ON cl.ID = c.clube
        WHERE c.jogador = ?
        LIMIT 1
    ");
    $stmtC->execute([$playerId]);
    $contract = $stmtC->fetch(PDO::FETCH_ASSOC);

    if ($contract) {
        $clubeEscudo = '';
        if (!empty($contract['clube_escudo']) && $contract['clube_escudo'] !== '0.png') {
            $clubeEscudo = (strpos($contract['clube_escudo'], 'http') === 0) ? $contract['clube_escudo'] : '/images/escudos/' . basename($contract['clube_escudo']);
        }
        $clubData = [
            'id' => (int)$contract['clube_id'],
            'name' => $contract['clube_nome'],
            'logo' => $clubeEscudo,
            'jersey_number' => $contract['numeroCamisa'] ? (int)$contract['numeroCamisa'] : null,
            'wage' => (int)($contract['salario'] ?? 0),
            'contract_end' => (!empty($contract['encerramento']) && $contract['encerramento'] !== '0000-00-00') ? date('d/m/Y', strtotime($contract['encerramento'])) : 'Indeterminado'
        ];
    }

    if (!$clubData && !empty($player['sqlite_clube_id']) && !empty($player['sqlite_clube_nome'])) {
        $clEsc = '';
        if (!empty($player['sqlite_clube_escudo']) && $player['sqlite_clube_escudo'] !== '0.png') {
            $clEsc = (strpos($player['sqlite_clube_escudo'], 'http') === 0) ? $player['sqlite_clube_escudo'] : '/images/escudos/' . basename($player['sqlite_clube_escudo']);
        }
        $clubData = [
            'id' => (int)$player['sqlite_clube_id'],
            'name' => $player['sqlite_clube_nome'],
            'logo' => $clEsc,
            'jersey_number' => null,
            'wage' => 0,
            'contract_end' => 'Indeterminado'
        ];
    }

    // 6. Atributos Normalizados
    $attributes = [
        'technical' => [
            'tecnica' => round((float)($player['Tecnica'] ?? 0), 1),
            'controle_bola' => round((float)($player['ControleBola'] ?? 0), 1),
            'finalizacao' => round((float)($player['Finalizacao'] ?? 0), 1),
            'faro_gol' => round((float)($player['FaroGol'] ?? 0), 1),
            'visao_jogo' => round((float)($player['VisaoJogo'] ?? 0), 1),
            'cruzamentos' => round((float)($player['Cruzamentos'] ?? 0), 1),
            'cabeceamento' => round((float)($player['Cabeceamento'] ?? 0), 1),
            'desarme' => round((float)($player['Desarme'] ?? 0), 1),
            'marcacao' => round((float)($player['Marcacao'] ?? 0), 1),
        ],
        'physical_mental' => [
            'velocidade' => round((float)($player['Velocidade'] ?? 0), 1),
            'forca' => round((float)($player['Forca'] ?? 0), 1),
        ],
        'goalkeeper' => [
            'reflexos' => round((float)($player['Reflexos'] ?? 0), 1),
            'seguranca' => round((float)($player['Seguranca'] ?? 0), 1),
            'saidas' => round((float)($player['Saidas'] ?? 0), 1),
            'jogo_aereo' => round((float)($player['JogoAereo'] ?? 0), 1),
            'lancamentos' => round((float)($player['Lancamentos'] ?? 0), 1),
            'defesa_penaltis' => round((float)($player['DefesaPenaltis'] ?? 0), 1),
        ]
    ];

    // Coletar clubes associados ao atleta (clube atual, transferências, partidas com ID explícito ou SQLite)
    $playerClubs = [];
    if (!empty($clubData['id'])) {
        $playerClubs[] = (int)$clubData['id'];
    }
    if (!empty($player['sqlite_clube_id'])) {
        $playerClubs[] = (int)$player['sqlite_clube_id'];
    }
    if ($playerId > 0 && $conn) {
        try {
            $stTrans = $conn->prepare("SELECT DISTINCT clubeOrigem, clubeDestino FROM transferencias WHERE jogador = ?");
            $stTrans->execute([$playerId]);
            while ($tr = $stTrans->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($tr['clubeOrigem']) && (int)$tr['clubeOrigem'] > 0) $playerClubs[] = (int)$tr['clubeOrigem'];
                if (!empty($tr['clubeDestino']) && (int)$tr['clubeDestino'] > 0) $playerClubs[] = (int)$tr['clubeDestino'];
            }
        } catch (\Throwable $e) {}

        try {
            $stEscClubs = $conn->prepare("SELECT DISTINCT id_time FROM jogos_clube_escalacao WHERE id_jogador = ? AND id_time > 0");
            $stEscClubs->execute([$playerId]);
            while ($cid = $stEscClubs->fetchColumn()) {
                if ($cid > 0) $playerClubs[] = (int)$cid;
            }
        } catch (\Throwable $e) {}
    }
    $playerClubs = array_values(array_unique(array_filter($playerClubs)));

    // Montar filtros de segurança para evitar que homônimos de outros clubes/países apareçam no perfil
    $whereEvents = "id_jogador = :pId";
    $whereEsc = "esc.id_jogador = :pId";
    $whereTotalMatches = "id_jogador = :pId";

    if (!empty($playerClubs)) {
        $inClubs = implode(',', $playerClubs);
        $whereEvents = "(id_jogador = :pId OR (id_jogador = 0 AND CONVERT(nome_jogador USING utf8mb4) = :pName AND id_time IN ($inClubs)))";
        $whereEsc = "(esc.id_jogador = :pId OR (esc.id_jogador = 0 AND CONVERT(esc.nome_jogador USING utf8mb4) = :pName AND esc.id_time IN ($inClubs)))";
        $whereTotalMatches = "(id_jogador = :pId OR (id_jogador = 0 AND CONVERT(nome_jogador USING utf8mb4) = :pName AND id_time IN ($inClubs)))";
    } elseif ($playerId <= 0) {
        // Atleta sem ID no MariaDB e sem clubes conhecidos: nunca vincular partidas arbitrárias de terceiros
        $whereEvents = "1 = 0";
        $whereEsc = "1 = 0";
        $whereTotalMatches = "1 = 0";
    }

    // 7. Estatísticas da Carreira & Partidas Recentes
    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT id_jogo) as total_partidas_eventos,
            SUM(CASE WHEN tipo = 1 THEN 1 ELSE 0 END) as total_gols,
            SUM(CASE WHEN tipo = 2 THEN 1 ELSE 0 END) as total_amarelos,
            SUM(CASE WHEN tipo = 3 THEN 1 ELSE 0 END) as total_vermelhos
        FROM jogos_clube_eventos
        WHERE $whereEvents
    ");
    $paramsStats = [':pId' => $playerId];
    if (!empty($playerClubs)) $paramsStats[':pName'] = $player['Nome'];
    $stmtStats->execute($paramsStats);
    $statsRow = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $stmtTotalMatches = $conn->prepare("
        SELECT COUNT(DISTINCT id_partida) 
        FROM jogos_clube_escalacao 
        WHERE $whereTotalMatches
          AND (titular = 1 OR entrada_tempo > 0 OR entrada_minuto > 0)
    ");
    $paramsTotal = [':pId' => $playerId];
    if (!empty($playerClubs)) $paramsTotal[':pName'] = $player['Nome'];
    $stmtTotalMatches->execute($paramsTotal);
    $totalEscalacoes = (int)$stmtTotalMatches->fetchColumn();

    $careerStats = [
        'matches' => max($totalEscalacoes, (int)($statsRow['total_partidas_eventos'] ?? 0)),
        'goals' => (int)($statsRow['total_gols'] ?? 0),
        'yellow_cards' => (int)($statsRow['total_amarelos'] ?? 0),
        'red_cards' => (int)($statsRow['total_vermelhos'] ?? 0)
    ];

    // Helpers para busca de .hyj e notas
    if (!function_exists('psFindHyjFile')) {
        function psFindHyjFile($cleanName) {
            static $fileCache = null;
            if ($fileCache === null) {
                $fileCache = [];
                $baseRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                    ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                    : dirname(__DIR__, 2);
                
                $searchDirs = [
                    $baseRoot . '/competicoes/hexacolor/Partidas',
                    $baseRoot . '/full hexa suite/Hexacolor YMT/Partidas'
                ];

                foreach ($searchDirs as $dir) {
                    if (!is_dir($dir)) continue;
                    try {
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
                        foreach ($it as $file) {
                            if ($file->isDir()) continue;
                            $fn = strtolower($file->getFilename());
                            if (substr($fn, -4) === '.hyj') {
                                $baseKey = strtolower(basename($fn, '.hyj'));
                                if (!isset($fileCache[$baseKey])) {
                                    $fileCache[$baseKey] = $file->getPathname();
                                }
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }

            $key = strtolower(basename(basename($cleanName, '.hyj'), '.hyl'));
            return $fileCache[$key] ?? null;
        }
    }

    if (!function_exists('psCalculatePlayerRating')) {
        function psCalculatePlayerRating($pId, $pLevel, $pPos, $isStarter, $goalsScored, $yellowCards, $redCards, $teamGoals, $oppGoals, $matchId) {
        $base = 6.2 + ($pLevel - 50) * 0.02;
        if ($isStarter) $base += 0.3;

        if ($teamGoals > $oppGoals) $base += 0.4;
        elseif ($teamGoals < $oppGoals) $base -= 0.3;

        $base += ($goalsScored * 1.0);
        $base -= ($yellowCards * 0.5);
        $base -= ($redCards * 1.5);

        $isDef = in_array(strtoupper($pPos), ['G', 'Z', 'LD', 'LE', 'V', 'DF', 'CB', 'RB', 'LB', 'DM']);
        if ($isDef) {
            if ($oppGoals === 0) $base += 0.5;
            else $base -= ($oppGoals * 0.15);
        }

        $seed = ($pId * 37 + $matchId * 53) % 11;
        $base += ($seed - 5) * 0.06;

        return max(4.5, min(9.9, round($base, 1)));
    }
}

    // 8. Últimas Partidas e Média de Notas
    $recentMatches = [];
    $allRatings = [];

    try {
        $stmtRecent = $conn->prepare("
            SELECT 
                j.id, j.data, j.timeA_id, COALESCE(cA.Nome, j.timeA_nome) as timeA_nome, j.timeA_gols,
                j.timeB_id, COALESCE(cB.Nome, j.timeB_nome) as timeB_nome, j.timeB_gols, j.competicao_id, j.path,
                COALESCE(cl.nome, li.nome, cc.nome, 'Competição') as competicao_nome,
                esc.id_time as jogador_time_id, esc.titular, esc.posicao as posicao_jogo,
                esc.entrada_tempo as jogador_entrada_tempo, esc.entrada_minuto as jogador_entrada_minuto
            FROM jogos_clube_escalacao esc
            INNER JOIN jogos_clube j ON j.id = esc.id_partida
            LEFT JOIN clube cA ON cA.ID = j.timeA_id
            LEFT JOIN clube cB ON cB.ID = j.timeB_id
            LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id AND j.simulador_interno = 1
            LEFT JOIN liga li ON li.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 0
            LEFT JOIN campeonatos_clube cc ON cc.id = j.competicao_id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 1
            WHERE $whereEsc
            ORDER BY j.data DESC, j.id DESC
            LIMIT 15
        ");
        $paramsRecent = [':pId' => $playerId];
        if (!empty($playerClubs)) $paramsRecent[':pName'] = $player['Nome'];
        $stmtRecent->execute($paramsRecent);
        $matchIndex = 0;
        while ($rm = $stmtRecent->fetch(PDO::FETCH_ASSOC)) {
            // Ignorar partidas que ainda não concluíram no tempo real
            $matchTs = !empty($rm['data']) ? strtotime($rm['data']) : 0;
            $durMatchSec = 120 * 60;
            $hasPassed = ($matchTs > 0) ? (time() >= ($matchTs + $durMatchSec)) : true;
            if (!$hasPassed) {
                continue;
            }

            $timeAId = (int)$rm['timeA_id'];
            $timeBId = (int)$rm['timeB_id'];
            $jogTimeId = (int)($rm['jogador_time_id'] ?? 0);

            $teamAName = $rm['timeA_nome'] ?? '';
            $teamBName = $rm['timeB_nome'] ?? '';

            // Se algum nome de time estiver vazio e for competição interna, checar SQLite
            if ((empty($teamAName) || empty($teamBName)) && !empty($rm['competicao_id'])) {
                $compId = (int)$rm['competicao_id'];
                $db3File = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') 
                    ? $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $compId . "-database.db3"
                    : dirname(__DIR__, 2) . "/competicoes/databases/" . $compId . "-database.db3";
                if (file_exists($db3File)) {
                    try {
                        $sdb = new PDO("sqlite:" . $db3File);
                        if (empty($teamAName)) {
                            $st = $sdb->prepare("SELECT Nome FROM clube WHERE ID = ? OR ID = ?");
                            $st->execute([$timeAId, abs($timeAId)]);
                            $tName = $st->fetchColumn();
                            if ($tName) $teamAName = $tName;
                        }
                        if (empty($teamBName)) {
                            $st = $sdb->prepare("SELECT Nome FROM clube WHERE ID = ? OR ID = ?");
                            $st->execute([$timeBId, abs($timeBId)]);
                            $tName = $st->fetchColumn();
                            if ($tName) $teamBName = $tName;
                        }
                    } catch (\Throwable $e) {}
                }
            }

            if (empty($teamAName)) $teamAName = $timeAId ? 'Time ' . abs($timeAId) : 'Time A';
            if (empty($teamBName)) $teamBName = $timeBId ? 'Time ' . abs($timeBId) : 'Time B';

            $isTeamA = false;
            if ($jogTimeId !== 0) {
                if ($jogTimeId === $timeAId) {
                    $isTeamA = true;
                } elseif ($jogTimeId === $timeBId) {
                    $isTeamA = false;
                } else {
                    $isTeamA = ($clubData && $clubData['id'] === $timeAId);
                }
            } else {
                $isTeamA = ($clubData && $clubData['id'] === $timeAId);
            }

            $opponent = $isTeamA ? $teamBName : $teamAName;
            $myScore = $isTeamA ? (int)$rm['timeA_gols'] : (int)$rm['timeB_gols'];
            $oppScore = $isTeamA ? (int)$rm['timeB_gols'] : (int)$rm['timeA_gols'];

            $isStarter = !empty($rm['titular']);
            $subInMin = (isset($rm['jogador_entrada_minuto']) && is_numeric($rm['jogador_entrada_minuto']) && (int)$rm['jogador_entrada_minuto'] > 0) 
                ? (int)$rm['jogador_entrada_minuto'] 
                : null;
            $subInTempo = (isset($rm['jogador_entrada_tempo']) && is_numeric($rm['jogador_entrada_tempo']) && (int)$rm['jogador_entrada_tempo'] > 0) 
                ? (int)$rm['jogador_entrada_tempo'] 
                : null;
            $hasEnteredFromDb = ($subInMin !== null) || ($subInTempo !== null);

            $foundInHyj = false;
            $hyjMinutes = 0;
            $hyjRating = null;

            if (!empty($rm['path'])) {
                $hyjPath = psFindHyjFile($rm['path']);
                if ($hyjPath && file_exists($hyjPath)) {
                    $hyjJson = @file_get_contents($hyjPath);
                    if ($hyjJson) {
                        $hyjData = json_decode($hyjJson, true);
                        $targetTeamJogadores = $isTeamA ? ($hyjData['time1']['jogadores'] ?? []) : ($hyjData['time2']['jogadores'] ?? []);
                        if (empty($targetTeamJogadores)) {
                            $targetTeamJogadores = array_merge($hyjData['time1']['jogadores'] ?? [], $hyjData['time2']['jogadores'] ?? []);
                        }
                        foreach ($targetTeamJogadores as $jObj) {
                            $jId = (int)($jObj['idJogador'] ?? 0);
                            if (($jId > 0 && $jId === $playerId) || (!empty($jObj['nome']) && $jObj['nome'] === $player['Nome'])) {
                                $foundInHyj = true;
                                $hyjMinutes = (int)($jObj['minutos'] ?? 0);
                                if (isset($jObj['nota']) && (float)$jObj['nota'] > 0) {
                                    $hyjRating = round((float)$jObj['nota'], 1);
                                }
                                break;
                            }
                        }
                    }
                }
            }

            // O jogador só atuou se foi titular, se entrou em campo ou se o simulador registrou minutos jogados
            $hasPlayed = false;
            if ($isStarter) {
                $hasPlayed = true;
            } elseif ($foundInHyj) {
                $hasPlayed = ($hyjMinutes > 0 || $hasEnteredFromDb);
            } else {
                $hasPlayed = $hasEnteredFromDb;
            }

            $matchRating = null;
            if ($hasPlayed) {
                if ($hyjRating !== null) {
                    $matchRating = $hyjRating;
                } else {
                    $matchRating = psCalculatePlayerRating($playerId, (int)$player['Nivel'], $rm['posicao_jogo'] ?: $posicaoPrincipal, $isStarter, 0, 0, 0, $myScore, $oppScore, (int)$rm['id']);
                }
                $allRatings[] = $matchRating;
            } else {
                // Jogador foi reserva e NÃO entrou em campo: NUNCA deve ter nota
                $matchRating = null;
            }

            if ($matchIndex < 5) {
                $res = 'E';
                if ($myScore > $oppScore) $res = 'V';
                elseif ($myScore < $oppScore) $res = 'D';

                $recentMatches[] = [
                    'match_id' => (int)$rm['id'],
                    'date' => !empty($rm['data']) ? date('d/m/Y', strtotime($rm['data'])) : '',
                    'competition' => $rm['competicao_nome'] ?: 'Competição',
                    'opponent' => $opponent,
                    'score' => "$myScore - $oppScore",
                    'result' => $res,
                    'starter' => $isStarter,
                    'sub_in' => $subInMin,
                    'has_played' => $hasPlayed,
                    'position' => $rm['posicao_jogo'] ?: $posicaoPrincipal,
                    'rating' => $matchRating
                ];
            }
            $matchIndex++;
        }
    } catch (\Throwable $e) {}

    if (!empty($allRatings)) {
        $avgRating = round(array_sum($allRatings) / count($allRatings), 1);
    } else {
        $avgRating = round(5.5 + ((int)$player['Nivel'] * 0.025), 1);
    }
    $careerStats['average_rating'] = $avgRating;

    echo json_encode([
        'success' => true,
        'player' => [
            'id' => $playerId,
            'name' => $player['Nome'],
            'photo' => $photoUrl,
            'birth_date' => $nascimentoFmt,
            'age' => $idade,
            'country' => $player['nome_pais'] ?: 'País não informado',
            'flag' => $flagUrl,
            'level' => (int)$player['Nivel'],
            'value' => (int)$player['valor'],
            'value_formatted' => '$ ' . number_format((int)$player['valor'], 0, ',', '.'),
            'primary_position' => $posicaoPrincipal,
            'positions' => $posicoes,
            'category' => $categoria,
            'is_goalkeeper' => $isGoleiro,
            'mentality' => $mentalityName,
            'mentality_id' => (int)($player['Mentalidade'] ?? 4),
            'club' => $clubData,
            'attributes' => $attributes,
            'career_stats' => $careerStats,
            'average_rating' => $avgRating,
            'recent_matches' => $recentMatches
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
