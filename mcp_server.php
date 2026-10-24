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

// Helper function to resolve coach by ID, Name or Club Contract
function obterIdOuInfoTecnico($db, $tecnicoIdentificador, $idClube = 0) {
    if (empty($tecnicoIdentificador)) {
        if ($idClube > 0) {
            $query = "SELECT t.ID, t.Nome, t.Nivel, t.Mentalidade, t.Estilo, c.modificadorNivel 
                      FROM contratos_tecnico c 
                      JOIN tecnico t ON c.tecnico = t.ID 
                      WHERE c.clube = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$idClube]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res;
        }
        return null;
    }

    if (is_numeric($tecnicoIdentificador)) {
        $query = "SELECT t.ID, t.Nome, t.Nivel, t.Mentalidade, t.Estilo, 0 as modificadorNivel 
                  FROM tecnico t WHERE t.ID = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([intval($tecnicoIdentificador)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    $query = "SELECT t.ID, t.Nome, t.Nivel, t.Mentalidade, t.Estilo, 0 as modificadorNivel 
              FROM tecnico t WHERE t.Nome LIKE ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute(['%' . $tecnicoIdentificador . '%']);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ?: null;
}

// Helper function to resolve national team (seleção)
function obterInfoSelecao($db, $selecaoIdentificador) {
    if (empty($selecaoIdentificador)) {
        return null;
    }

    if (is_numeric($selecaoIdentificador)) {
        $query = "SELECT c.ID, c.Nome, c.Pais as idPais, c.Sexo, c.status as statusSelecao, p.nome as nomePais 
                  FROM clube c 
                  LEFT JOIN paises p ON c.Pais = p.id 
                  WHERE c.ID = ? AND c.status > 0 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([intval($selecaoIdentificador)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) return $res;
    }

    $query = "SELECT c.ID, c.Nome, c.Pais as idPais, c.Sexo, c.status as statusSelecao, p.nome as nomePais 
              FROM clube c 
              LEFT JOIN paises p ON c.Pais = p.id 
              WHERE c.Nome LIKE ? AND c.status > 0 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute(['%' . $selecaoIdentificador . '%']);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) return $res;

    // Search by country name/sigla
    $queryPais = "SELECT id, nome FROM paises WHERE nome LIKE ? OR sigla LIKE ? LIMIT 1";
    $stmtPais = $db->prepare($queryPais);
    $stmtPais->execute(['%' . $selecaoIdentificador . '%', $selecaoIdentificador]);
    $paisRow = $stmtPais->fetch(PDO::FETCH_ASSOC);
    if ($paisRow) {
        $querySel = "SELECT c.ID, c.Nome, c.Pais as idPais, c.Sexo, c.status as statusSelecao, p.nome as nomePais 
                     FROM clube c 
                     LEFT JOIN paises p ON c.Pais = p.id 
                     WHERE c.Pais = ? AND c.status = 1 LIMIT 1";
        $stmtSel = $db->prepare($querySel);
        $stmtSel->execute([$paisRow['id']]);
        $res = $stmtSel->fetch(PDO::FETCH_ASSOC);
        if ($res) return $res;

        $querySelQualquer = "SELECT c.ID, c.Nome, c.Pais as idPais, c.Sexo, c.status as statusSelecao, p.nome as nomePais 
                             FROM clube c 
                             LEFT JOIN paises p ON c.Pais = p.id 
                             WHERE c.Pais = ? AND c.status > 0 LIMIT 1";
        $stmtSelQualquer = $db->prepare($querySelQualquer);
        $stmtSelQualquer->execute([$paisRow['id']]);
        $resQualquer = $stmtSelQualquer->fetch(PDO::FETCH_ASSOC);
        if ($resQualquer) return $resQualquer;
    }

    return null;
}

$mapeamentoPosicoesGlobais = ['G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];

function decodificarPosicoesAtleta($stringPosicoes) {
    global $mapeamentoPosicoesGlobais;
    $posList = [];
    for ($i = 0; $i < 15; $i++) {
        if (isset($stringPosicoes[$i]) && $stringPosicoes[$i] === '1') {
            $posList[] = $mapeamentoPosicoesGlobais[$i];
        }
    }
    return $posList;
}

$mapMentalidadesTecnico = [
    1 => "Retranca / Muito Defensiva (-0.10 Ataque, +0.10 Defesa)",
    2 => "Defensiva (-0.05 Ataque, +0.05 Defesa)",
    3 => "Balanceada / Equilibrada (0.0 Neutro)",
    4 => "Ofensiva (+0.05 Ataque, -0.05 Defesa)",
    5 => "Ataque Total / Muito Ofensiva (+0.10 Ataque, -0.10 Defesa)"
];

$mapEstilosTecnico = [
    1 => "Explorar Contra-Ataques (+0.20 Invasão/Velocidade, -0.10 Finalização, +0.05 DefInvasão - valoriza Am e pontas velozes de transição rápida)",
    2 => "Cadenciar o Jogo / Posse de Bola (+0.15 Finalização, -0.05 Invasão - valoriza meias técnicos de passe e arremates de longe)",
    3 => "Neutro / Equilibrado (0.0)",
    4 => "Atacar pelas Laterais (+0.10 CabD/CabE - valoriza pontas/alas com alto Cruzamento e centroavante cabeceador)",
    5 => "Impôr Ritmo Ofensivo / Pressão Alta (+0.10 Finalização, +0.05 Aéreo, -0.10 DefInvasão - ataque agressivo e chutes constantes)"
];

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
                    "description" => "Analisa o elenco e o técnico de um clube, sugerindo esquemas táticos e a escalação dos 11 titulares ideais com base nas regras e bônus matemáticos do motor de simulação Hexacolor YMT v2.10 (pesos por setor, duelos de atributos e penalidade severa por improvisação).",
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
                    "name" => "sugerir_convocacao_selecao",
                    "description" => "Analisa todos os jogadores elegíveis de um país para a seleção nacional (principal ou de categorias de base), cruza com o perfil do treinador (estilo tático e mentalidade) e com as regras matemáticas do motor Hexacolor YMT v2.10, recomendando a lista de convocados ideal e os 11 titulares para maximizar o rendimento no simulador.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [
                            "selecao" => [
                                "type" => ["integer", "string"],
                                "description" => "ID (número) ou Nome da seleção nacional (ex: 'Brasil', 'Tarmaquestão', 'Itália Sub-20') ou nome/sigla do país."
                            ],
                            "tecnico" => [
                                "type" => ["integer", "string"],
                                "description" => "Opcional. ID ou Nome do técnico a ser considerado. Se omitido, busca o técnico atualmente vinculado à seleção."
                            ],
                            "tamanho_convocacao" => [
                                "type" => "integer",
                                "description" => "Opcional. Quantidade total de atletas a convocar (padrão: 23, máximo: 30)."
                            ]
                        ],
                        "required" => ["selecao"]
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

                // Nome do clube
                $stmtNomeClube = $db->prepare("SELECT Nome FROM clube WHERE ID = ? LIMIT 1");
                $stmtNomeClube->execute([$clube_id]);
                $nomeClube = $stmtNomeClube->fetchColumn() ?: "Clube #{$clube_id}";

                // Obter treinador atual do clube
                $tecnicoInfo = obterIdOuInfoTecnico($db, null, $clube_id);

                $query = "SELECT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao
                          FROM jogador a 
                          LEFT JOIN contratos_jogador c ON c.jogador = a.ID AND c.tipoContrato = 0
                          WHERE c.clube = ?
                          ORDER BY c.titularidade DESC, c.posicaoBase ASC, a.Nivel DESC";
                $stmt = $db->prepare($query);
                $stmt->execute([$clube_id]);
                $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($jogadores)) {
                    $text = "Nenhum jogador encontrado para o clube '{$nomeClube}' (ID {$clube_id}) para análise tática.";
                } else {
                    $text = "=== ANÁLISE TÁTICA AVANÇADA (ENGINE HEXACOLOR YMT v2.10) ===\n";
                    $text .= "Clube: {$nomeClube} (ID: {$clube_id})\n";
                    
                    if ($tecnicoInfo) {
                        $mentIdx = intval($tecnicoInfo['Mentalidade']);
                        $estIdx = intval($tecnicoInfo['Estilo']);
                        $mentDesc = $mapMentalidadesTecnico[$mentIdx] ?? "Balanceada (0.0)";
                        $estDesc = $mapEstilosTecnico[$estIdx] ?? "Equilibrado";
                        $text .= "Treinador: {$tecnicoInfo['Nome']} (Nível: {$tecnicoInfo['Nivel']})\n";
                        $text .= "- Mentalidade do Técnico: {$mentDesc}\n";
                        $text .= "- Estilo do Técnico: {$estDesc}\n\n";
                    } else {
                        $text .= "Treinador: Sem técnico vinculado cadastrado (considere estilo neutro ou recomende o perfil ideal de treinador).\n\n";
                    }

                    $text .= "--- DIRETRIZES DA ENGINE HEXACOLOR YMT (FÍSICA DE SIMULAÇÃO) ---\n";
                    $text .= "1. BÔNUS DE CRIAÇÃO E DEFESA POR POSIÇÃO (Engine):\n";
                    $text .= "   - Goleiro (G): Defesa pura. Reflexos combate Finalização; Saídas combate Faro de Gol e infiltrações; Jogo Aéreo combate Cabeceamento; Segurança reduz rebotes.\n";
                    $text .= "   - Zagueiro (Z): Proteção geral máxima (DefInvasão: 0.40, DefFinalização: 0.35, DefCabeceio: 0.45). Atributos vitais: Marcação, Desarme, Força, Cabeceamento e Velocidade.\n";
                    $text .= "   - Laterais/Alas (LD/LE, AD/AE): Combatem jogadas aéreas do setor (DefCab: 0.60) e geram bolas na área (Cab: 0.45-0.55). Atributos vitais: Velocidade, Cruzamento e Desarme.\n";
                    $text .= "   - Volante (V): Barreira central contra chutes de média distância (DefFinalização: 0.50, maior do jogo!). Atributos vitais: Desarme, Marcação, Força e Visão de Jogo.\n";
                    $text .= "   - Meias (MC / MA): MA tem o maior peso em arremates diretos (Finalização: 0.55); MC é articulador de passe. Atributos vitais: Visão de Jogo (confronta Marcação), Técnica, Controle de Bola e Finalização.\n";
                    $text .= "   - Pontas (PD/PE) e Meias Abertos (MD/ME): Líderes em cruzamentos no motor (CabD/CabE: 0.70). Atributos vitais: Velocidade, Cruzamento, Técnica e Finalização.\n";
                    $text .= "   - 2º Atacante / Movimentação (Am): Maior bônus de infiltração do simulador (Invasão: 0.65). Atributos vitais: Movimentação, Velocidade, Técnica e Faro de Gol.\n";
                    $text .= "   - Centroavante (Aa): Especialista de área e finalização aérea (Finalização: 0.40, Cabeceio: 0.50). Atributos vitais: Finalização, Faro de Gol, Cabeceamento e Força.\n";
                    $text .= "2. REGRA DE IMPROVISAÇÃO ESTRITA: O simulador penaliza severamente atributos de qualquer atleta escalado fora de posições com bit '1' na sua lista de posições. NUNCA improvise jogadores em posições que eles não suportam.\n";
                    $text .= "3. SINERGIA COM O TÉCNICO: A formação tática deve se alinhar ao Estilo e à Mentalidade do treinador para extrair os multiplicadores positivos do motor.\n\n";

                    $text .= "--- ELENCO DISPONÍVEL ---\n";
                    foreach ($jogadores as $j) {
                        $posicoes = decodificarPosicoesAtleta($j['StringPosicoes']);
                        $posListStr = !empty($posicoes) ? implode(', ', $posicoes) : 'Nenhuma';
                        $isGoleiro = in_array('G', $posicoes);

                        $text .= "- {$j['nomeJogador']} (ID: {$j['idJogador']}) | Nível: {$j['Nivel']} | Idade: {$j['Idade']} anos | Posições suportadas: [{$posListStr}]\n";
                        if ($isGoleiro) {
                            $text .= "  Atributos GK: Reflexos={$j['Reflexos']}, Saídas={$j['Saidas']}, JogoAéreo={$j['JogoAereo']}, Segurança={$j['Seguranca']}, DefPenaltis={$j['DefesaPenaltis']}\n";
                        } else {
                            $text .= "  Atributos Linha: Vel={$j['Velocidade']}, For={$j['Forca']}, Marc={$j['Marcacao']}, Des={$j['Desarme']}, Vis={$j['VisaoJogo']}, Cruz={$j['Cruzamentos']}, Cab={$j['Cabeceamento']}, Tec={$j['Tecnica']}, Contr={$j['ControleBola']}, Fin={$j['Finalizacao']}, Faro={$j['FaroGol']}, Mov={$j['Movimentacao']}\n";
                        }
                    }

                    $text .= "\n--- INSTRUÇÕES DE RESPOSTA ---\n";
                    $text .= "Com base nas diretrizes do Hexacolor YMT v2.10 e nos atributos deste elenco:\n";
                    $text .= "1. Sugira o Esquema Tático Ideal (ex: 4-3-3, 3-5-2, 4-2-3-1, 4-4-2, etc.) que maximize as forças do elenco e a sinergia com o treinador.\n";
                    $text .= "2. Escale os 11 Titulares ideais indicando a posição exata de cada um (respeitando estritamente as posições suportadas por cada atleta, sem improvisação).\n";
                    $text .= "3. Justifique a escolha de cada setor (Goleiro, Defesa, Meio-campo e Ataque) com base nos duelos e bônus do simulador.\n";
                    $text .= "4. Indique cobradores ideais (Faltas, Pênaltis e Escanteios) e os principais reservas imediatos para mudanças táticas.\n";
                }

                $responseResult = [
                    "content" => [["type" => "text", "text" => $text]]
                ];
            } elseif ($toolName === 'sugerir_convocacao_selecao') {
                $selecaoArg = $arguments['selecao'] ?? null;
                $tecnicoArg = $arguments['tecnico'] ?? null;
                $tamanhoConvocacao = isset($arguments['tamanho_convocacao']) ? min(30, max(15, intval($arguments['tamanho_convocacao']))) : 23;

                $selInfo = obterInfoSelecao($db, $selecaoArg);
                if (!$selInfo) {
                    throw new Exception("Não foi possível encontrar a seleção nacional informada ('" . htmlspecialchars($selecaoArg) . "'). Verifique o nome da seleção ou do país.");
                }

                $idSelecao = intval($selInfo['ID']);
                $nomeSelecao = $selInfo['Nome'];
                $idPais = intval($selInfo['idPais']);
                $nomePais = $selInfo['nomePais'] ?: $nomeSelecao;
                $sexoSelecao = intval($selInfo['Sexo'] ?? 0);
                $statusSelecao = intval($selInfo['statusSelecao'] ?? 1);

                // Categoria e limite de idade
                $categoriaStr = "Principal (Sem limite de idade)";
                $dataMinima = '1900-01-01';
                $temLimiteIdade = 0;
                if ($statusSelecao === 2) {
                    $categoriaStr = "Olímpica / Sub-23 (<= 23 anos)";
                    $temLimiteIdade = 1;
                    $dataMinima = date('Y-m-d', strtotime("-23 years"));
                } elseif ($statusSelecao === 3) {
                    $categoriaStr = "Sub-20 (<= 20 anos)";
                    $temLimiteIdade = 1;
                    $dataMinima = date('Y-m-d', strtotime("-20 years"));
                } elseif ($statusSelecao === 4) {
                    $categoriaStr = "Sub-18 (<= 18 anos)";
                    $temLimiteIdade = 1;
                    $dataMinima = date('Y-m-d', strtotime("-18 years"));
                }

                // Identificar o técnico
                $tecnicoInfo = null;
                if (!empty($tecnicoArg)) {
                    $tecnicoInfo = obterIdOuInfoTecnico($db, $tecnicoArg);
                }
                if (!$tecnicoInfo) {
                    // Buscar técnico vinculado à seleção em contratos_tecnico
                    $tecnicoInfo = obterIdOuInfoTecnico($db, null, $idSelecao);
                }

                // Consultar todos os jogadores elegíveis do país
                $queryElegiveis = "
                    SELECT 
                        j.ID as idJogador,
                        j.Nome as nomeJogador,
                        TIMESTAMPDIFF(YEAR, j.Nascimento, CURDATE()) as Idade,
                        j.Nivel,
                        j.StringPosicoes,
                        j.Marcacao, j.Desarme, j.VisaoJogo, j.Movimentacao, j.Cruzamentos, j.Cabeceamento,
                        j.Tecnica, j.ControleBola, j.Finalizacao, j.FaroGol, j.Velocidade, j.Forca,
                        j.Reflexos, j.Seguranca, j.Saidas, j.JogoAereo, j.Lancamentos, j.DefesaPenaltis, j.Determinacao,
                        b.Nome as clubeAtual
                    FROM jogador j
                    LEFT JOIN contratos_jogador c ON j.ID = c.jogador AND c.tipoContrato = 0
                    LEFT JOIN clube b ON c.clube = b.ID
                    WHERE j.Pais = :paisId
                      AND j.Sexo = :sexoSelecao
                      AND j.disponibilidade >= 0
                      AND (:temLimite = 0 OR j.Nascimento >= :dataMinima)
                    ORDER BY j.Nivel DESC, j.Nome ASC
                ";
                $stmtEleg = $db->prepare($queryElegiveis);
                $stmtEleg->bindValue(':paisId', $idPais, PDO::PARAM_INT);
                $stmtEleg->bindValue(':sexoSelecao', $sexoSelecao, PDO::PARAM_INT);
                $stmtEleg->bindValue(':temLimite', $temLimiteIdade, PDO::PARAM_INT);
                $stmtEleg->bindValue(':dataMinima', $dataMinima);
                $stmtEleg->execute();
                $todosElegiveis = $stmtEleg->fetchAll(PDO::FETCH_ASSOC);

                if (empty($todosElegiveis)) {
                    $text = "Nenhum atleta elegível encontrado para a seleção '{$nomeSelecao}' ({$nomePais} - {$categoriaStr}).";
                } else {
                    $totalElegiveis = count($todosElegiveis);

                    $text = "=== SUGESTÃO DE CONVOCAÇÃO & ESCALAÇÃO DE SELEÇÃO (HEXACOLOR YMT v2.10) ===\n";
                    $text .= "Seleção: {$nomeSelecao} (País: {$nomePais} | ID: {$idSelecao})\n";
                    $text .= "Categoria: {$categoriaStr} | Vagas a convocar: {$tamanhoConvocacao} atletas (Total de elegíveis: {$totalElegiveis})\n";

                    if ($tecnicoInfo) {
                        $mentIdx = intval($tecnicoInfo['Mentalidade']);
                        $estIdx = intval($tecnicoInfo['Estilo']);
                        $mentDesc = $mapMentalidadesTecnico[$mentIdx] ?? "Balanceada (0.0)";
                        $estDesc = $mapEstilosTecnico[$estIdx] ?? "Equilibrado";
                        $text .= "\nTREINADOR SELECIONADO: {$tecnicoInfo['Nome']} (ID: {$tecnicoInfo['ID']} | Nível: {$tecnicoInfo['Nivel']})\n";
                        $text .= "- Mentalidade: {$mentDesc}\n";
                        $text .= "- Estilo Tático: {$estDesc}\n";
                    } else {
                        $text .= "\nTREINADOR: Não especificado (análise considerando perfil balanceado / recomende o perfil ideal para esta geração de atletas).\n";
                    }

                    $text .= "\n--- REGRAS DE OURO DO SIMULADOR HEXACOLOR YMT ---\n";
                    $text .= "1. COBERTURA TOTAL DE POSIÇÕES: Uma convocação deve ter no mínimo 3 Goleiros e pelo menos 2 atletas naturais para cada posição tática planejada, evitando buracos por lesão/suspensão.\n";
                    $text .= "2. TOLERÂNCIA ZERO À IMPROVISAÇÃO: O simulador penaliza brutalmente atletas escalados fora das posições marcadas com '1' no seu StringPosicoes. Todos os 11 titulares e reservas devem estar em suas posições naturais.\n";
                    $text .= "3. BÔNUS POR SETOR DA ENGINE:\n";
                    $text .= "   - Goleiros (G): Reflexos para chutes diretos, Saídas para infiltrações cara-a-cara, Jogo Aéreo para bolas altas.\n";
                    $text .= "   - Zagueiros (Z): Maior proteção defensiva global (DefInvasão: 0.40, DefFinalização: 0.35, DefCabeceio: 0.45). Atributos: Marcação, Desarme, Cabeceamento, Força, Velocidade.\n";
                    $text .= "   - Laterais/Alas (LD/LE, AD/AE): Protegem laterais (DefCab: 0.60) e geram bolas na área (CabD/CabE: 0.45-0.55). Atributos: Velocidade, Cruzamento, Desarme.\n";
                    $text .= "   - Volantes (V): Bloqueiam finalizações centrais (DefFinalização: 0.50). Atributos: Desarme, Marcação, Força e Visão de Jogo.\n";
                    $text .= "   - Meias (MC/MA): MA arremata de longe (Finalização: 0.55); MC dita passe e cadência. Atributos: Visão de Jogo (assistências), Técnica, Controle de Bola e Finalização.\n";
                    $text .= "   - Pontas (PD/PE): Especialistas em jogada de linha de fundo (CabD/CabE: 0.70). Atributos: Velocidade, Cruzamento, Técnica.\n";
                    $text .= "   - 2º Atacante (Am): Maior bônus de infiltração do jogo (Invasão: 0.65). Atributos: Movimentação, Velocidade, Técnica e Faro.\n";
                    $text .= "   - Centroavante (Aa): Homem-gol no chão e no alto (Finalização: 0.40, Cabeceio: 0.50). Atributos: Finalização, Faro de Gol, Cabeceamento e Força.\n";
                    $text .= "4. SINERGIA COM O TÉCNICO: Se o treinador for de Contra-Ataque, priorize atletas de alta Velocidade e Infiltração (Am, PD/PE); se for de Laterais Ofensivos, priorize alas com alto Cruzamento e atacante de área cabeceador; se for de Posse/Cadenciar, privilegie meias técnicos e finalizadores.\n\n";

                    // Agrupar atletas elegíveis por setores para entregar ao LLM os mais relevantes
                    $goleiros = [];
                    $defensores = [];
                    $laterais = [];
                    $meioCampistas = [];
                    $atacantes = [];

                    foreach ($todosElegiveis as $atleta) {
                        $posicoes = decodificarPosicoesAtleta($atleta['StringPosicoes']);
                        $atleta['posicoesLista'] = $posicoes;

                        if (in_array('G', $posicoes)) {
                            $goleiros[] = $atleta;
                        }
                        if (in_array('Z', $posicoes)) {
                            $defensores[] = $atleta;
                        }
                        if (in_array('LD', $posicoes) || in_array('LE', $posicoes) || in_array('AD', $posicoes) || in_array('AE', $posicoes)) {
                            $laterais[] = $atleta;
                        }
                        if (in_array('V', $posicoes) || in_array('MC', $posicoes) || in_array('MA', $posicoes) || in_array('MD', $posicoes) || in_array('ME', $posicoes)) {
                            $meioCampistas[] = $atleta;
                        }
                        if (in_array('Aa', $posicoes) || in_array('Am', $posicoes) || in_array('PD', $posicoes) || in_array('PE', $posicoes)) {
                            $atacantes[] = $atleta;
                        }
                    }

                    // Função local para formatar lista resumida de atletas para o LLM
                    $formatarSetor = function($lista, $limite, $isGoleiro = false) {
                        $out = "";
                        $count = 0;
                        foreach ($lista as $j) {
                            if ($count++ >= $limite) break;
                            $posStr = implode('/', $j['posicoesLista']) ?: 'Sem Posição';
                            $clubeStr = $j['clubeAtual'] ? " ({$j['clubeAtual']})" : " (Sem clube)";
                            $out .= "- [ID: {$j['idJogador']}] {$j['nomeJogador']}{$clubeStr} | Nível: {$j['Nivel']} | Idade: {$j['Idade']} | Posições: [{$posStr}]\n";
                            if ($isGoleiro) {
                                $out .= "  Atributos GK: Ref={$j['Reflexos']} Sai={$j['Saidas']} JoA={$j['JogoAereo']} Seg={$j['Seguranca']} Pen={$j['DefesaPenaltis']}\n";
                            } else {
                                $out .= "  Atributos: Vel={$j['Velocidade']} For={$j['Forca']} Marc={$j['Marcacao']} Des={$j['Desarme']} Vis={$j['VisaoJogo']} Cruz={$j['Cruzamentos']} Cab={$j['Cabeceamento']} Tec={$j['Tecnica']} Contr={$j['ControleBola']} Fin={$j['Finalizacao']} Faro={$j['FaroGol']} Mov={$j['Movimentacao']}\n";
                            }
                        }
                        return $out;
                    };

                    $text .= "--- PRINCIPAIS CANDIDATOS POR SETOR ---\n";
                    $text .= "### GOLEIROS DISPONÍVEIS:\n" . $formatarSetor($goleiros, 6, true) . "\n";
                    $text .= "### ZAGUEIROS DISPONÍVEIS:\n" . $formatarSetor($defensores, 10, false) . "\n";
                    $text .= "### LATERAIS E ALAS DISPONÍVEIS:\n" . $formatarSetor($laterais, 10, false) . "\n";
                    $text .= "### MEIO-CAMPISTAS E VOLANTES DISPONÍVEIS:\n" . $formatarSetor($meioCampistas, 12, false) . "\n";
                    $text .= "### ATACANTES E PONTAS DISPONÍVEIS:\n" . $formatarSetor($atacantes, 12, false) . "\n";

                    $text .= "--- MISSÃO DO ASSISTENTE ---\n";
                    $text .= "1. LISTA DE CONVOCAÇÃO FINAL ({$tamanhoConvocacao} Atletas):\n";
                    $text .= "   - Selecione exatamente {$tamanhoConvocacao} atletas dentre os candidatos acima, distribuídos de forma equilibrada por setor (ex: 3 Goleiros, 4 Zagueiros, 4 Laterais, 6 a 7 Meias/Volantes, 5 a 6 Atacantes/Pontas).\n";
                    $text .= "   - Garanta que todas as posições tenham titular e reserva competente.\n\n";
                    $text .= "2. ESQUEMA TÁTICO E ESCALAÇÃO DOS 11 TITULARES:\n";
                    $text .= "   - Determine a melhor formação tática (ex: 4-3-3, 3-5-2, 4-2-3-1, 4-4-2) maximizando a sinergia com o Estilo e a Mentalidade do treinador.\n";
                    $text .= "   - Escale os 11 titulares indicando cada jogador em uma posição que ele saiba atuar naturalmente (bit '1' da lista de posições dele, rigorosamente sem improvisação).\n";
                    $text .= "   - Justifique a escolha dos titulares demonstrando como seus atributos superam os adversários nas jogadas do simulador.\n\n";
                    $text .= "3. BATEDORES E ESTRATÉGIA:\n";
                    $text .= "   - Aponte o Capitão, cobrador de pênaltis, faltas e escanteios.\n";
                    $text .= "   - Resuma a estratégia de jogo no simulador (ex: explorar cruzamentos nas costas da zaga, infiltração em velocidade, arremates de longe, solidez defensiva).\n";
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
