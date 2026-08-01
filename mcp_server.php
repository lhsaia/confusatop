<?php
// Enable CORS for MCP Client connections
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Id, X-Confusatop-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// -------------------------------------------------------------
// Debug Logger
// -------------------------------------------------------------
$sessionDir = __DIR__ . '/session_data';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}

function mcp_log($message) {
    global $sessionDir;
    file_put_contents($sessionDir . '/mcp_debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

$rawBody = file_get_contents('php://input');
mcp_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . " | Body: " . $rawBody);

// -------------------------------------------------------------
// Authentication Check & Database Initialisation
// -------------------------------------------------------------
$headers = getallheaders();

// Normalize header casing
$clientToken = null;
foreach ($headers as $key => $value) {
    if (strcasecmp($key, 'X-Confusatop-Token') === 0) {
        $clientToken = $value;
        break;
    }
}

// Fallback to GET/POST parameter if headers are not available (e.g. ChatGPT Plugins UI)
if (empty($clientToken)) {
    $clientToken = $_GET['token'] ?? $_POST['token'] ?? null;
}

if (empty($clientToken)) {
    mcp_log("Auth Failed: Missing Token");
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized - Missing X-Confusatop-Token header or 'token' query parameter."]);
    exit(0);
}

// Load database connection
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/objetos/jogador.php';
require_once __DIR__ . '/objetos/time.php';

$database = new Database();
$db = $database->getConnection();

// Query user by token
$queryUser = "SELECT id FROM usuarios WHERE mcp_token = ? LIMIT 1";
$stmtUser = $db->prepare($queryUser);
$stmtUser->execute([$clientToken]);
$userId = $stmtUser->fetchColumn();

if (!$userId) {
    mcp_log("Auth Failed: Invalid Token '" . $clientToken . "'");
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized - Invalid token."]);
    exit(0);
}

$usuarioLogado = intval($userId);
mcp_log("Auth Success: User ID " . $usuarioLogado);

// Helper function to resolve ID or Name to a valid Club ID
function obterIdClube($db, $clubeIdentificador, $usuarioLogado) {
    if (empty($clubeIdentificador)) {
        // Fallback to user's club
        $query = "SELECT c.ID FROM clube c LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuarioLogado]);
        return intval($stmt->fetchColumn());
    }
    
    if (is_numeric($clubeIdentificador)) {
        return intval($clubeIdentificador);
    }
    
    // Resolve by name
    $query = "SELECT ID FROM clube WHERE Nome LIKE ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute(['%' . $clubeIdentificador . '%']);
    $id = $stmt->fetchColumn();
    
    return $id ? intval($id) : 0;
}

// -------------------------------------------------------------
// 1. SSE Connection Setup (GET)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Disable PHP output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_implicit_flush(true);

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Prevent buffering in Nginx
    header('Content-Encoding: none'); // Prevent gzip compression in Apache/Nginx

    // Generate a unique session ID
    $sessionId = bin2hex(random_bytes(16));
    
    // The absolute path where the client should POST messages
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $endpointUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/mcp_server.php?sessionId=' . $sessionId . '&token=' . urlencode($clientToken);

    // Send the endpoint event
    echo "event: endpoint\n";
    echo "data: " . $endpointUrl . "\n\n";
    ob_flush();
    flush();

    // Loop to stream messages back to client
    $queueFile = $sessionDir . '/mcp_queue_' . $sessionId . '.json';
    
    set_time_limit(120);
    $startTime = time();
    $lastHeartbeat = time();

    while (time() - $startTime < 110) {
        if (file_exists($queueFile)) {
            $content = file_get_contents($queueFile);
            if (!empty($content)) {
                $messages = json_decode($content, true);
                if (is_array($messages)) {
                    foreach ($messages as $msg) {
                        echo "event: message\n";
                        echo "data: " . json_encode($msg) . "\n\n";
                    }
                }
                file_put_contents($queueFile, '');
                ob_flush();
                flush();
            }
        }
        
        // Send keep-alive heartbeat only every 15 seconds
        if (time() - $lastHeartbeat >= 15) {
            echo ":\n\n";
            ob_flush();
            flush();
            $lastHeartbeat = time();
        }

        usleep(500000); // 500ms sleep (2 checks per second)
    }

    if (file_exists($queueFile)) {
        unlink($queueFile);
    }
    exit(0);
}

// -------------------------------------------------------------
// 2. Handling Messages (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionId = $_GET['sessionId'] ?? '';

    $rawInput = file_get_contents('php://input');
    $request = json_decode($rawInput, true);

    if (!$request || !isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON-RPC request"]);
        exit(0);
    }

    $requestId = $request['id'] ?? null;
    $method = $request['method'] ?? '';

    $responseResult = null;
    $error = null;

    try {
        if ($method === 'initialize') {
            $responseResult = [
                "protocolVersion" => "2024-11-05",
                "capabilities" => [
                    "tools" => new stdClass()
                ],
                "serverInfo" => [
                    "name" => "Confusatop",
                    "version" => "1.0.0",
                    "description" => "Acesse consultas de elencos, análises táticas e mercado de jogadores no Confusatop.",
                    "iconUrl" => "https://confusa.top/apple-touch-icon.png"
                ],
                "instructions" => "Você é o assistente oficial do jogo Confusatop. Para responder a perguntas sobre elencos, táticas, jogadores, busca no mercado ou clubes, use SEMPRE e EXCLUSIVAMENTE as ferramentas locais fornecidas por este servidor. NÃO use navegação na web (web search/browse) ou conhecimentos gerais para responder a dados específicos do jogo, pois as informações do Confusatop são internas e estão apenas no banco de dados acessível pelas ferramentas."
            ];
        } elseif ($method === 'tools/list') {
            $tools = [
                [
                    "name" => "listar_clubes",
                    "description" => "Retorna a lista de clubes cadastrados no Confusatop. Exibe o nome do dono de cada clube e permite filtrar por parte do nome.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "nome" => [
                                "type" => "string",
                                "description" => "Opcional. Nome ou parte do nome do clube para filtrar."
                            ]
                        ]
                    ]
                ],
                [
                    "name" => "obter_meu_clube",
                    "description" => "Retorna os detalhes e ID do clube pertencente ao usuário autenticado atual, com o respectivo dono.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => new stdClass()
                    ]
                ],
                [
                    "name" => "obter_elenco",
                    "description" => "Retorna a lista de jogadores de um clube específico, incluindo todos os seus atributos, posições e nacionalidade. Se precisar comparar dois times, use a ferramenta comparar_elencos.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "clube" => [
                                "type" => ["integer", "string"],
                                "description" => "Opcional. ID (número) ou Nome (texto) do clube. Se omitido, busca o clube do usuário autenticado."
                            ]
                        ]
                    ]
                ],
                [
                    "name" => "pesquisar_mercado",
                    "description" => "Realiza uma busca avançada de jogadores no mercado de transferências interno do Confusatop (jogadores à venda/disponíveis no jogo).",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "nome" => ["type" => "string", "description" => "Busca por parte do nome."],
                            "posicao" => ["type" => "string", "description" => "Opcional. Posição (ex: G, Z, LD, LE, V, MC, MA, A, PD, PE)."],
                            "nivel_min" => ["type" => "integer", "description" => "Nível mínimo do jogador."],
                            "nivel_max" => ["type" => "integer", "description" => "Nível máximo do jogador."],
                            "idade_min" => ["type" => "integer", "description" => "Idade mínima."],
                            "idade_max" => ["type" => "integer", "description" => "Idade máxima."],
                            "valor_max" => ["type" => "number", "description" => "Valor máximo do passe."]
                        ]
                    ]
                ],
                [
                    "name" => "analisar_taticas",
                    "description" => "Analisa o elenco de um clube e sugere as escalações táticas ideais com base nos atributos dos jogadores.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "clube" => [
                                "type" => ["integer", "string"],
                                "description" => "Opcional. ID (número) ou Nome (texto) do clube. Se omitido, busca o clube do usuário autenticado."
                            ]
                        ]
                    ]
                ],
                [
                    "name" => "comparar_elencos",
                    "description" => "Busca e compara os elencos de dois clubes informando seus níveis e posições. Use esta ferramenta SEMPRE que o usuário pedir para comparar dois times.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "clube_a" => [
                                "type" => ["integer", "string"],
                                "description" => "ID ou Nome do primeiro clube para comparação."
                            ],
                            "clube_b" => [
                                "type" => ["integer", "string"],
                                "description" => "ID ou Nome do segundo clube para comparação."
                            ]
                        ],
                        "required" => ["clube_a", "clube_b"]
                    ]
                ]
            ];

            $responseResult = ["tools" => $tools];
        } elseif ($method === 'tools/call') {
            $toolName = $request['params']['name'] ?? '';
            $arguments = $request['params']['arguments'] ?? [];

            if ($toolName === 'listar_clubes') {
                $nome = $arguments['nome'] ?? '';
                $query = "SELECT c.ID, c.Nome, c.TresLetras as Sigla, p.nome as Pais, u.nome as Dono 
                          FROM clube c 
                          LEFT JOIN paises p ON c.Pais = p.id 
                          LEFT JOIN usuarios u ON p.dono = u.id ";
                
                if (!empty($nome)) {
                    $query .= "WHERE c.Nome LIKE ? ORDER BY c.Nome ASC";
                    $stmt = $db->prepare($query);
                    $stmt->execute(['%' . $nome . '%']);
                } else {
                    $query .= "ORDER BY c.Nome ASC";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                }
                $clubes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($clubes)) {
                    $text = "Nenhum clube encontrado com o nome '" . htmlspecialchars($nome) . "'.";
                } else {
                    $text = "Lista de clubes encontrados:\n";
                    foreach ($clubes as $c) {
                        $donoStr = $c['Dono'] ? "Dono: {$c['Dono']}" : "Sem dono";
                        $text .= "- ID: {$c['ID']} | {$c['Nome']} ({$c['Sigla']}) | País: {$c['Pais']} | {$donoStr}\n";
                    }
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'obter_meu_clube') {
                $query = "SELECT c.ID, c.Nome, c.TresLetras as Sigla, p.nome as Pais, u.nome as Dono 
                          FROM clube c 
                          LEFT JOIN paises p ON c.Pais = p.id 
                          LEFT JOIN usuarios u ON p.dono = u.id
                          WHERE p.dono = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$usuarioLogado]);
                $clubes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($clubes)) {
                    $text = "Nenhum clube encontrado pertencente à sua conta (ID de Usuário: {$usuarioLogado}).";
                } else {
                    $text = "Seus clubes cadastrados:\n\n";
                    foreach ($clubes as $c) {
                        $text .= "- ID: {$c['ID']} | Nome: {$c['Nome']} ({$c['Sigla']}) | País: {$c['Pais']} | Dono: {$c['Dono']}\n";
                    }
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'obter_elenco') {
                $clubeArg = $arguments['clube'] ?? $arguments['clube_id'] ?? null;
                $clube_id = obterIdClube($db, $clubeArg, $usuarioLogado);

                if ($clube_id <= 0) {
                    throw new Exception("Não foi possível identificar o clube informado.");
                }

                $query = "SELECT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, Mentalidade, CobradorFalta, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, DeterminacaoOriginal, posicoes.Sigla as siglaPosicao, p.nome as Nacionalidade 
                          FROM jogador a 
                          LEFT JOIN contratos_jogador c ON c.jogador = a.ID 
                          LEFT JOIN posicoes ON posicoes.ID = c.posicaoBase 
                          LEFT JOIN paises p ON a.Pais = p.id
                          WHERE c.clube = ? AND c.tipoContrato = 0 
                          ORDER BY c.titularidade DESC, c.posicaoBase ASC";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$clube_id]);
                $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($jogadores)) {
                    $text = "Nenhum jogador encontrado para o clube ID {$clube_id}.";
                } else {
                    $text = "Elenco do clube (ID {$clube_id}):\n\n";
                    foreach ($jogadores as $j) {
                        $posicao = $j['siglaPosicao'] ?: 'Sem Posição Base';
                        $text .= "- ID: {$j['idJogador']} | {$j['nomeJogador']} | Nível: {$j['Nivel']} | Idade: {$j['Idade']} anos | Posição: {$posicao} | Nacionalidade: {$j['Nacionalidade']}\n";
                        $text .= "  Atributos: Vel={$j['Velocidade']} For={$j['Forca']} Tec={$j['Tecnica']} Marc={$j['Marcacao']} Des={$j['Desarme']} Vis={$j['VisaoJogo']} Cruz={$j['Cruzamentos']} Cab={$j['Cabeceamento']} Contr={$j['ControleBola']} Fin={$j['Finalizacao']} Faro={$j['FaroGol']} Det={$j['Determinacao']}\n";
                    }
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'pesquisar_mercado') {
                $nome = $arguments['nome'] ?? null;
                $posicao = $arguments['posicao'] ?? null;
                $nivel_min = isset($arguments['nivel_min']) ? intval($arguments['nivel_min']) : 1;
                $nivel_max = isset($arguments['nivel_max']) ? intval($arguments['nivel_max']) : 999;
                $idade_min = isset($arguments['idade_min']) ? intval($arguments['idade_min']) : 15;
                $idade_max = isset($arguments['idade_max']) ? intval($arguments['idade_max']) : 50;
                $valor_max = isset($arguments['valor_max']) ? floatval($arguments['valor_max']) : 99999999999;

                $query = "SELECT j.ID, j.Nome, j.Nivel, FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) as Idade, j.valor, j.StringPosicoes, cl.Nome as ClubeNome, u.nome as DonoClube, p.nome as Nacionalidade
                          FROM jogador j
                          LEFT JOIN paises p ON j.Pais = p.id
                          LEFT JOIN contratos_jogador c ON j.ID = c.jogador AND c.tipoContrato = 0
                          LEFT JOIN clube cl ON c.clube = cl.ID
                          LEFT JOIN paises pl ON cl.Pais = pl.id
                          LEFT JOIN usuarios u ON pl.dono = u.id
                          WHERE j.Nivel BETWEEN ? AND ?
                            AND FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) BETWEEN ? AND ?
                            AND j.valor <= ?";
                
                $params = [$nivel_min, $nivel_max, $idade_min, $idade_max, $valor_max];
                
                if ($nome) {
                    $query .= " AND j.Nome LIKE ?";
                    $params[] = '%' . $nome . '%';
                }

                $mapeamentoPosicoes = ['G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];
                if ($posicao) {
                    $posicaoMap = [
                        'goleiro' => 'G', 'g' => 'G',
                        'lateral direito' => 'LD', 'ld' => 'LD',
                        'lateral esquerdo' => 'LE', 'le' => 'LE',
                        'zagueiro' => 'Z', 'z' => 'Z',
                        'ala direita' => 'AD', 'ad' => 'AD',
                        'ala esquerda' => 'AE', 'ae' => 'AE',
                        'volante' => 'V', 'v' => 'V',
                        'meia direita' => 'MD', 'md' => 'MD',
                        'meia esquerda' => 'ME', 'me' => 'ME',
                        'meia central' => 'MC', 'meia' => 'MC', 'mc' => 'MC',
                        'ponta direita' => 'PD', 'pd' => 'PD',
                        'ponta esquerda' => 'PE', 'pe' => 'PE',
                        'meia atacante' => 'MA', 'ma' => 'MA',
                        'atacante recuado' => 'Am', 'am' => 'Am',
                        'centroavante' => 'Aa', 'atacante' => 'Aa', 'aa' => 'Aa', 'a' => 'Aa'
                    ];
                    
                    $posKey = strtolower(trim($posicao));
                    $posSigla = $posicaoMap[$posKey] ?? null;

                    if ($posSigla) {
                        $index = array_search($posSigla, $mapeamentoPosicoes);
                        if ($index !== false) {
                            $query .= " AND SUBSTRING(j.StringPosicoes, ?, 1) = '1'";
                            $params[] = $index + 1;
                        }
                    }
                }
                
                $query .= " ORDER BY j.Nivel DESC LIMIT 30";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($jogadores)) {
                    $text = "Nenhum jogador encontrado com as características fornecidas.";
                } else {
                    $text = "Resultados da pesquisa no mercado:\n\n";
                    foreach ($jogadores as $j) {
                        $clubeStr = $j['ClubeNome'] ? "Clube: {$j['ClubeNome']}" : "Sem Clube";
                        $donoStr = $j['DonoClube'] ? " (Dono: {$j['DonoClube']})" : "";
                        
                        // Parse StringPosicoes to readable list
                        $posStr = $j['StringPosicoes'];
                        $posList = [];
                        for ($i = 0; $i < 15; $i++) {
                            if (isset($posStr[$i]) && $posStr[$i] === '1') {
                                $posList[] = $mapeamentoPosicoes[$i];
                            }
                        }
                        $posText = implode(', ', $posList) ?: 'Nenhuma';

                        $text .= "- ID: {$j['ID']} | {$j['Nome']} | Nível: {$j['Nivel']} | Posições: {$posText} | Idade: {$j['Idade']} anos | Nacionalidade: {$j['Nacionalidade']} | Valor: R$ " . number_format($j['valor'], 2, ',', '.') . " | {$clubeStr}{$donoStr}\n";
                    }
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'analisar_taticas') {
                $clubeArg = $arguments['clube'] ?? $arguments['clube_id'] ?? null;
                $clube_id = obterIdClube($db, $clubeArg, $usuarioLogado);

                if ($clube_id <= 0) {
                    throw new Exception("Não foi possível identificar o clube informado.");
                }

                $query = "SELECT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao
                          FROM jogador a 
                          LEFT JOIN contratos_jogador c ON c.jogador = a.ID AND c.tipoContrato = 0
                          WHERE c.clube = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$clube_id]);
                $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($jogadores)) {
                    $text = "Nenhum jogador encontrado para o clube ID {$clube_id} para análise tática.";
                } else {
                    $text = "Elenco completo para análise tática (Clube ID {$clube_id}):\n\n";
                    foreach ($jogadores as $j) {
                        $posicoesLegiveis = [];
                        $posStr = $j['StringPosicoes'];
                        $mapeamento = ['G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];
                        for ($i = 0; $i < 15; $i++) {
                            if (isset($posStr[$i]) && $posStr[$i] === '1') {
                                $posicoesLegiveis[] = $mapeamento[$i];
                            }
                        }
                        $posList = implode(', ', $posicoesLegiveis) ?: 'Nenhuma';

                        $text .= "- {$j['nomeJogador']} (ID: {$j['idJogador']})\n";
                        $text .= "  Nível: {$j['Nivel']} | Idade: {$j['Idade']} | Posições em que atua: {$posList}\n";
                        $text .= "  Atributos-Chave: Velocidade={$j['Velocidade']}, Marcação={$j['Marcacao']}, Finalização={$j['Finalizacao']}, Passe/Visão={$j['VisaoJogo']}, Força={$j['Forca']}, Reflexo(Goleiro)={$j['Reflexos']}\n\n";
                    }
                    $text .= "Com base nessa lista, recomende um esquema tático principal (ex: 4-4-2, 4-3-3, 3-5-2) e justifique a escolha. Monte a escalação com os 11 titulares ideais para as posições.";
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'comparar_elencos') {
                $clubeA_arg = $arguments['clube_a'];
                $clubeB_arg = $arguments['clube_b'];

                $clubeA_id = obterIdClube($db, $clubeA_arg, $usuarioLogado);
                $clubeB_id = obterIdClube($db, $clubeB_arg, $usuarioLogado);

                if ($clubeA_id <= 0 || $clubeB_id <= 0) {
                    throw new Exception("Não foi possível identificar um ou ambos os clubes informados.");
                }

                // Query details for Club A
                $queryA = "SELECT a.Nome as nomeJogador, (Nivel + c.ModificadorNivel) as Nivel, posicoes.Sigla as siglaPosicao 
                           FROM jogador a 
                           LEFT JOIN contratos_jogador c ON c.jogador = a.ID 
                           LEFT JOIN posicoes ON posicoes.ID = c.posicaoBase 
                           WHERE c.clube = ? AND c.tipoContrato = 0";
                $stmtA = $db->prepare($queryA);
                $stmtA->execute([$clubeA_id]);
                $jogadoresA = $stmtA->fetchAll(PDO::FETCH_ASSOC);

                // Query details for Club B
                $stmtB = $db->prepare($queryA);
                $stmtB->execute([$clubeB_id]);
                $jogadoresB = $stmtB->fetchAll(PDO::FETCH_ASSOC);

                $text = "Dados do Clube A (ID {$clubeA_id}):\n";
                foreach ($jogadoresA as $j) {
                    $text .= "- {$j['nomeJogador']} | Nível: {$j['Nivel']} | Posição: {$j['siglaPosicao']}\n";
                }

                $text .= "\nDados do Clube B (ID {$clubeB_id}):\n";
                foreach ($jogadoresB as $j) {
                    $text .= "- {$j['nomeJogador']} | Nível: {$j['Nivel']} | Posição: {$j['siglaPosicao']}\n";
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } else {
                $error = [
                    "code" => -32601,
                    "message" => "Method not found: {$toolName}"
                ];
            }
        } else {
            $error = [
                "code" => -32601,
                "message" => "Method not found: {$method}"
            ];
        }
    } catch (Exception $e) {
        $error = [
            "code" => -32000,
            "message" => $e->getMessage()
        ];
    }

    // Build JSON-RPC response
    $jsonRpcResponse = [
        "jsonrpc" => "2.0",
        "id" => $requestId
    ];
    if ($error) {
        $jsonRpcResponse['error'] = $error;
    } else {
        $jsonRpcResponse['result'] = $responseResult;
    }

    // If sessionId is present, write to queue for SSE.
    // If sessionId is NOT present, respond directly to the POST request!
    if (!empty($sessionId)) {
        $queueFile = $sessionDir . '/mcp_queue_' . $sessionId . '.json';
        $existing = [];
        if (file_exists($queueFile) && !empty(file_get_contents($queueFile))) {
            $existing = json_decode(file_get_contents($queueFile), true) ?: [];
        }
        $existing[] = $jsonRpcResponse;
        file_put_contents($queueFile, json_encode($existing));

        // Respond to POST with 202 Accepted
        header('Content-Type: application/json');
        http_response_code(202);
        echo json_encode(["status" => "accepted"]);
        exit(0);
    } else {
        mcp_log("Responding directly to POST without SSE (sessionId empty)");
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($jsonRpcResponse);
        exit(0);
    }
}
