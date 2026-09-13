<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/database.php' 
    : dirname(__DIR__, 2) . '/config/database.php';

$playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$playerNameParam = trim($_GET['nome'] ?? '');

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
        2 => ['sigla' => 'Z', 'nome' => 'Zagueiro', 'cat' => 'Defensor'],
        3 => ['sigla' => 'LE', 'nome' => 'Lateral Esquerdo', 'cat' => 'Defensor'],
        4 => ['sigla' => 'V', 'nome' => 'Volante', 'cat' => 'Meio-Campista'],
        5 => ['sigla' => 'MD', 'nome' => 'Meia Direita', 'cat' => 'Meio-Campista'],
        6 => ['sigla' => 'MC', 'nome' => 'Meio-Campo', 'cat' => 'Meio-Campista'],
        7 => ['sigla' => 'ME', 'nome' => 'Meia Esquerda', 'cat' => 'Meio-Campista'],
        8 => ['sigla' => 'MA', 'nome' => 'Meia Atacante', 'cat' => 'Meio-Campista'],
        9 => ['sigla' => 'PD', 'nome' => 'Ponta Direita', 'cat' => 'Atacante'],
        10 => ['sigla' => 'CA', 'nome' => 'Centroavante', 'cat' => 'Atacante'],
        11 => ['sigla' => 'PE', 'nome' => 'Ponta Esquerda', 'cat' => 'Atacante'],
        12 => ['sigla' => 'SA', 'nome' => 'Segundo Atacante', 'cat' => 'Atacante'],
        13 => ['sigla' => 'AD', 'nome' => 'Ala Direito', 'cat' => 'Defensor'],
        14 => ['sigla' => 'AE', 'nome' => 'Ala Esquerdo', 'cat' => 'Defensor'],
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
    } else {
        $stmtP = $conn->prepare("
            SELECT j.*, p.nome as nome_pais, p.bandeira as bandeira_pais, m.Nome as nome_mentalidade
            FROM jogador j
            LEFT JOIN paises p ON p.id = j.Pais
            LEFT JOIN mentalidade m ON m.ID = j.Mentalidade
            WHERE j.Nome = ? OR j.Nome LIKE ?
            LIMIT 1
        ");
        $stmtP->execute([$playerNameParam, "%$playerNameParam%"]);
    }

    $player = $stmtP->fetch(PDO::FETCH_ASSOC);

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

    // 7. Estatísticas da Carreira & Partidas Recentes
    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT id_jogo) as total_partidas_eventos,
            SUM(CASE WHEN tipo = 1 THEN 1 ELSE 0 END) as total_gols,
            SUM(CASE WHEN tipo = 2 THEN 1 ELSE 0 END) as total_amarelos,
            SUM(CASE WHEN tipo = 3 THEN 1 ELSE 0 END) as total_vermelhos
        FROM jogos_clube_eventos
        WHERE id_jogador = ? OR (id_jogador = 0 AND nome_jogador = ?)
    ");
    $stmtStats->execute([$playerId, $player['Nome']]);
    $statsRow = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $stmtTotalMatches = $conn->prepare("
        SELECT COUNT(DISTINCT id_partida) 
        FROM jogos_clube_escalacao 
        WHERE id_jogador = ? OR (id_jogador = 0 AND nome_jogador = ?)
    ");
    $stmtTotalMatches->execute([$playerId, $player['Nome']]);
    $totalEscalacoes = (int)$stmtTotalMatches->fetchColumn();

    $careerStats = [
        'matches' => max($totalEscalacoes, (int)($statsRow['total_partidas_eventos'] ?? 0)),
        'goals' => (int)($statsRow['total_gols'] ?? 0),
        'yellow_cards' => (int)($statsRow['total_amarelos'] ?? 0),
        'red_cards' => (int)($statsRow['total_vermelhos'] ?? 0)
    ];

    // Helpers para busca de .hyj e notas
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

    // 8. Últimas Partidas e Média de Notas
    $recentMatches = [];
    $allRatings = [];

    try {
        $stmtRecent = $conn->prepare("
            SELECT 
                j.id, j.data, j.timeA_id, j.timeA_nome, j.timeA_gols,
                j.timeB_id, j.timeB_nome, j.timeB_gols, j.competicao_id, j.path,
                cl.nome as competicao_nome,
                esc.titular, esc.posicao as posicao_jogo
            FROM jogos_clube_escalacao esc
            INNER JOIN jogos_clube j ON j.id = esc.id_partida
            LEFT JOIN competicao_lista cl ON cl.id = j.competicao_id
            WHERE esc.id_jogador = ? OR (esc.id_jogador = 0 AND esc.nome_jogador = ?)
            ORDER BY j.data DESC, j.id DESC
            LIMIT 15
        ");
        $stmtRecent->execute([$playerId, $player['Nome']]);
        $matchIndex = 0;
        while ($rm = $stmtRecent->fetch(PDO::FETCH_ASSOC)) {
            $matchRating = null;
            if (!empty($rm['path'])) {
                $hyjPath = psFindHyjFile($rm['path']);
                if ($hyjPath && file_exists($hyjPath)) {
                    $hyjJson = @file_get_contents($hyjPath);
                    if ($hyjJson) {
                        $hyjData = json_decode($hyjJson, true);
                        $allJog = array_merge($hyjData['time1']['jogadores'] ?? [], $hyjData['time2']['jogadores'] ?? []);
                        foreach ($allJog as $jObj) {
                            $jId = (int)($jObj['idJogador'] ?? 0);
                            if (($jId > 0 && $jId === $playerId) || (!empty($jObj['nome']) && $jObj['nome'] === $player['Nome'])) {
                                if (isset($jObj['nota']) && (float)$jObj['nota'] > 0) {
                                    $matchRating = round((float)$jObj['nota'], 1);
                                    $allRatings[] = (float)$jObj['nota'];
                                }
                                break;
                            }
                        }
                    }
                }
            }

            $isTeamA = ($clubData && $clubData['id'] === (int)$rm['timeA_id']);
            $opponent = $isTeamA ? $rm['timeB_nome'] : $rm['timeA_nome'];
            $myScore = $isTeamA ? (int)$rm['timeA_gols'] : (int)$rm['timeB_gols'];
            $oppScore = $isTeamA ? (int)$rm['timeB_gols'] : (int)$rm['timeA_gols'];

            if ($matchRating === null) {
                $matchRating = psCalculatePlayerRating($playerId, (int)$player['Nivel'], $rm['posicao_jogo'] ?: $posicaoPrincipal, (bool)$rm['titular'], 0, 0, 0, $myScore, $oppScore, (int)$rm['id']);
                $allRatings[] = $matchRating;
            }

            if ($matchIndex < 5) {
                $res = 'E';
                if ($myScore > $oppScore) $res = 'V';
                elseif ($myScore < $oppScore) $res = 'D';

                $recentMatches[] = [
                    'match_id' => (int)$rm['id'],
                    'date' => !empty($rm['data']) ? date('d/m/Y', strtotime($rm['data'])) : '',
                    'competition' => $rm['competicao_nome'] ?: 'Competição',
                    'opponent' => $opponent ?: 'Adversário',
                    'score' => "$myScore - $oppScore",
                    'result' => $res,
                    'starter' => (bool)$rm['titular'],
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
