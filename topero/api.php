<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', false);
error_reporting(0);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'bootstrap';

// Função auxiliar para calcular distância euclidiana/haversine simplificada
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos(max(-1.0, min(1.0, $dist)));
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return $miles * 1.609344; // km
}

if ($action === 'bootstrap') {
    // 1. Carregar países ativos e ranqueáveis no CONFUSA (ranqueavel = 0 e ativo = 1)
    $queryPaises = "SELECT 
                        p.id, p.nome, p.sigla, p.bandeira, p.federacao, p.latitude, p.longitude, p.ranqueavel,
                        f.nome as nomeFederacao, f.id as idFederacao
                    FROM paises p
                    LEFT JOIN federacoes f ON f.id = p.federacao
                    WHERE p.ranqueavel = 0 AND p.ativo = 1
                    ORDER BY p.nome ASC";
    $stmtP = $db->prepare($queryPaises);
    $stmtP->execute();
    $paises = [];
    $paisesPorId = [];
    while ($row = $stmtP->fetch(PDO::FETCH_ASSOC)) {
        $row['id'] = (int)$row['id'];
        $row['latitude'] = $row['latitude'] !== null ? (float)$row['latitude'] : null;
        $row['longitude'] = $row['longitude'] !== null ? (float)$row['longitude'] : null;
        $paises[] = $row;
        $paisesPorId[$row['id']] = $row;
    }

    // 2. Carregar ligas cadastradas de países ranqueáveis e ativos
    $queryLigas = "SELECT 
                        l.id, l.nome, l.tier, l.limite_idade, l.logo, l.pais as idPais, l.Sexo as sexo,
                        p.nome as nomePais, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.federacao as idFederacao,
                        f.nome as nomeFederacao
                   FROM liga l
                   INNER JOIN paises p ON l.pais = p.id
                   LEFT JOIN federacoes f ON f.id = p.federacao
                   WHERE (l.status = 0 OR l.status IS NULL) 
                     AND p.ranqueavel = 0 
                     AND p.ativo = 1
                   ORDER BY l.tier ASC, l.nome ASC";
    $stmtL = $db->prepare($queryLigas);
    $stmtL->execute();
    $ligas = [];
    $filtroSexo = isset($_GET['sexo']) ? (int)$_GET['sexo'] : null;

    $paisesComLiga = [];
    $paisesComLigaPorSexo = [0 => [], 1 => []];
    while ($row = $stmtL->fetch(PDO::FETCH_ASSOC)) {
        $row['id'] = (int)$row['id'];
        $row['tier'] = (int)$row['tier'];
        $row['idPais'] = (int)$row['idPais'];
        $row['sexo'] = (int)$row['sexo'];
        $ligas[] = $row;
        $paisesComLiga[$row['idPais']] = true;
        $paisesComLigaPorSexo[$row['sexo']][$row['idPais']] = true;
    }

    // 3. Carregar clubes de ligas cadastradas em países ranqueáveis e ativos
    $queryClubes = "SELECT 
                        c.ID as id, c.Nome as nome, c.TresLetras as sigla, c.Escudo as escudo,
                        c.Pais as idPais, c.liga as idLiga, COALESCE(c.Sexo, l.Sexo, 0) as sexo,
                        p.nome as nomePais, p.sigla as siglaPais, p.bandeira as bandeiraPais,
                        l.nome as nomeLiga, l.tier as tierLiga
                    FROM clube c
                    INNER JOIN liga l ON c.liga = l.id
                    INNER JOIN paises p ON c.Pais = p.id
                    WHERE (c.status = 0 OR c.status IS NULL) 
                      AND (l.status = 0 OR l.status IS NULL) 
                      AND c.liga > 0
                      AND p.ranqueavel = 0
                      AND p.ativo = 1
                    ORDER BY l.tier ASC, c.Nome ASC";
    $stmtC = $db->prepare($queryClubes);
    $stmtC->execute();
    $clubes = [];
    while ($row = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $row['id'] = (int)$row['id'];
        $row['idPais'] = (int)$row['idPais'];
        $row['idLiga'] = (int)$row['idLiga'];
        $row['tierLiga'] = (int)$row['tierLiga'];
        $row['sexo'] = (int)$row['sexo'];
        $clubes[] = $row;
    }

    // 4. Calcular força das seleções nacionais (baseado na média de nível dos atletas do país em ligas Tier 1 da CONFUSA)
    $queryForcaSelecoes = "SELECT 
                               j.Pais as idPais,
                               COALESCE(j.sexo, 0) as sexo,
                               COUNT(j.ID) as totalJogadores,
                               ROUND(AVG(j.Nivel)) as mediaNivel,
                               MAX(j.Nivel) as maxNivel
                           FROM jogador j
                           INNER JOIN contratos_jogador cj ON cj.jogador = j.ID AND cj.tipoContrato IN (0, 1, 2)
                           INNER JOIN clube c ON cj.clube = c.ID
                           INNER JOIN liga l ON c.liga = l.ID
                           WHERE l.tier = 1 AND (c.status = 0 OR c.status IS NULL) AND (l.status = 0 OR l.status IS NULL)
                           GROUP BY j.Pais, COALESCE(j.sexo, 0)";
    $stmtFS = $db->prepare($queryForcaSelecoes);
    $stmtFS->execute();
    $forcaPorPais = [];
    while ($row = $stmtFS->fetch(PDO::FETCH_ASSOC)) {
        $pId = (int)$row['idPais'];
        $sexoP = (int)$row['sexo'];
        if (!isset($forcaPorPais[$pId])) {
            $forcaPorPais[$pId] = [0 => null, 1 => null];
        }
        $forcaPorPais[$pId][$sexoP] = (int)$row['mediaNivel'];
    }

    // Atribui força da seleção aos países
    foreach ($paises as &$p) {
        $pId = $p['id'];
        $p['forcaSelecaoMasc'] = (isset($forcaPorPais[$pId]) && $forcaPorPais[$pId][0] !== null) ? $forcaPorPais[$pId][0] : 64;
        $p['forcaSelecaoFem'] = (isset($forcaPorPais[$pId]) && $forcaPorPais[$pId][1] !== null) ? $forcaPorPais[$pId][1] : 62;
    }
    unset($p);

    // 5. Mapear para cada país qual o país com liga mais próximo para Masc (0) e Fem (1)
    $calcularMapeamento = function($listaPaisesComLiga) use ($paises, $paisesPorId, $ligas) {
        $mapa = [];
        foreach ($paises as $p) {
            $idOrigem = $p['id'];
            if (isset($listaPaisesComLiga[$idOrigem])) {
                $mapa[$idOrigem] = [
                    'temLiga' => true,
                    'idPaisMaisProximo' => $idOrigem,
                    'nomePaisMaisProximo' => $p['nome'],
                    'distanciaKm' => 0
                ];
                continue;
            }

            $latOrigem = $p['latitude'];
            $lonOrigem = $p['longitude'];
            $fedOrigem = $p['federacao'];

            $melhorId = null;
            $menorDist = PHP_FLOAT_MAX;

            foreach ($listaPaisesComLiga as $idComLiga => $val) {
                $pDestino = isset($paisesPorId[$idComLiga]) ? $paisesPorId[$idComLiga] : null;
                if (!$pDestino) continue;

                if ($latOrigem !== null && $lonOrigem !== null && $pDestino['latitude'] !== null && $pDestino['longitude'] !== null) {
                    $d = calcularDistancia($latOrigem, $lonOrigem, $pDestino['latitude'], $pDestino['longitude']);
                    if ($d < $menorDist) {
                        $menorDist = $d;
                        $melhorId = $idComLiga;
                    }
                } else if ($melhorId === null) {
                    if ($fedOrigem && $fedOrigem == $pDestino['federacao']) {
                        $melhorId = $idComLiga;
                    }
                }
            }

            if ($melhorId === null && count($listaPaisesComLiga) > 0) {
                $keys = array_keys($listaPaisesComLiga);
                $melhorId = $keys[0];
            }

            $mapa[$idOrigem] = [
                'temLiga' => false,
                'idPaisMaisProximo' => $melhorId,
                'nomePaisMaisProximo' => isset($paisesPorId[$melhorId]) ? $paisesPorId[$melhorId]['nome'] : '',
                'distanciaKm' => ($menorDist < PHP_FLOAT_MAX) ? round($menorDist) : null
            ];
        }
        return $mapa;
    };

    $mapeamentoVizinho = $calcularMapeamento($paisesComLiga);
    $mapeamentoVizinhoMasc = $calcularMapeamento($paisesComLigaPorSexo[0]);
    $mapeamentoVizinhoFem = $calcularMapeamento($paisesComLigaPorSexo[1]);

    echo json_encode([
        'success' => true,
        'user' => [
            'logged' => (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true),
            'id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'nome' => isset($_SESSION['nome']) ? $_SESSION['nome'] : null
        ],
        'paises' => $paises,
        'ligas' => $ligas,
        'clubes' => $clubes,
        'mapeamentoVizinho' => $mapeamentoVizinho,
        'mapeamentoVizinhoMasc' => $mapeamentoVizinhoMasc,
        'mapeamentoVizinhoFem' => $mapeamentoVizinhoFem
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'salvar_carreira') {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit;
    }

    $createTableQuery = "CREATE TABLE IF NOT EXISTS topero_carreiras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        nome_jogador VARCHAR(100) NOT NULL,
        numero INT NOT NULL,
        posicao VARCHAR(20) NOT NULL,
        id_pais_origem INT NOT NULL,
        id_ultimo_clube INT NULL,
        idade_final INT NOT NULL,
        ovr_maximo INT NOT NULL,
        partidas_totais INT NOT NULL,
        gols_totais INT NOT NULL,
        assistencias_totais INT NOT NULL,
        gols_sofridos INT NOT NULL DEFAULT 0,
        clean_sheets INT NOT NULL DEFAULT 0,
        titulos_totais INT NOT NULL,
        bolas_ouro INT NOT NULL DEFAULT 0,
        detalhes_json LONGTEXT NOT NULL,
        data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (id_usuario),
        INDEX (titulos_totais),
        INDEX (gols_totais)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($createTableQuery);

    // Garante colunas adicionais se tabela já existia
    try {
        $db->exec("ALTER TABLE topero_carreiras ADD COLUMN IF NOT EXISTS sexo TINYINT NOT NULL DEFAULT 0;");
        $db->exec("ALTER TABLE topero_carreiras ADD COLUMN IF NOT EXISTS gols_sofridos INT NOT NULL DEFAULT 0;");
        $db->exec("ALTER TABLE topero_carreiras ADD COLUMN IF NOT EXISTS clean_sheets INT NOT NULL DEFAULT 0;");
    } catch (Exception $e) {}

    $sql = "INSERT INTO topero_carreiras 
            (id_usuario, nome_jogador, numero, sexo, posicao, id_pais_origem, id_ultimo_clube, idade_final, ovr_maximo, partidas_totais, gols_totais, assistencias_totais, gols_sofridos, clean_sheets, titulos_totais, bolas_ouro, detalhes_json)
            VALUES (:uid, :nome, :num, :sexo, :pos, :pais, :clube, :idade, :ovr, :jogos, :gols, :assists, :gols_sof, :cs, :titulos, :bolas, :json)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':nome', $input['nome_jogador'] ?? 'Jogador');
    $stmt->bindValue(':num', $input['numero'] ?? 10, PDO::PARAM_INT);
    $stmt->bindValue(':sexo', $input['sexo'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':pos', $input['posicao'] ?? 'ST');
    $stmt->bindValue(':pais', $input['id_pais_origem'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':clube', $input['id_ultimo_clube'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':idade', $input['idade_final'] ?? 36, PDO::PARAM_INT);
    $stmt->bindValue(':ovr', $input['ovr_maximo'] ?? 75, PDO::PARAM_INT);
    $stmt->bindValue(':jogos', $input['partidas_totais'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':gols', $input['gols_totais'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':assists', $input['assistencias_totais'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':gols_sof', $input['gols_sofridos'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':cs', $input['clean_sheets'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':titulos', $input['titulos_totais'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':bolas', $input['bolas_ouro'] ?? 0, PDO::PARAM_INT);
    $stmt->bindValue(':json', json_encode($input['detalhes'] ?? [], JSON_UNESCAPED_UNICODE));

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco.']);
    }
    exit;
}

if ($action === 'minhas_carreiras') {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
        exit;
    }
    try {
        $sql = "SELECT c.id, c.nome_jogador, c.numero, c.posicao, c.sexo, c.idade_final, c.ovr_maximo,
                       c.partidas_totais, c.gols_totais, c.assistencias_totais, c.gols_sofridos, c.clean_sheets,
                       c.titulos_totais, c.bolas_ouro, c.data_criacao,
                       p.nome as nomePais, p.bandeira as bandeiraPais
                FROM topero_carreiras c
                LEFT JOIN paises p ON c.id_pais_origem = p.id
                WHERE c.id_usuario = :uid
                ORDER BY c.data_criacao DESC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $carreiras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'carreiras' => $carreiras], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'carreiras' => []]);
    }
    exit;
}

if ($action === 'carregar_carreira') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    try {
        $sql = "SELECT c.*, p.nome as nomePais, p.bandeira as bandeiraPais, u.nome as nomeUsuario
                FROM topero_carreiras c
                LEFT JOIN paises p ON c.id_pais_origem = p.id
                LEFT JOIN usuario u ON c.id_usuario = u.id
                WHERE c.id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($c) {
            $c['detalhes'] = json_decode($c['detalhes_json'], true);
            unset($c['detalhes_json']);
            echo json_encode(['success' => true, 'carreira' => $c], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Carreira não encontrada.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao carregar carreira.']);
    }
    exit;
}

if ($action === 'ranking_carreiras') {
    try {
        $sql = "SELECT c.id, c.nome_jogador, c.numero, c.posicao, c.idade_final, c.ovr_maximo,
                       c.partidas_totais, c.gols_totais, c.assistencias_totais, c.titulos_totais, c.bolas_ouro,
                       c.data_criacao, p.nome as nomePais, p.bandeira as bandeiraPais, u.nome as nomeUsuario
                FROM topero_carreiras c
                LEFT JOIN paises p ON c.id_pais_origem = p.id
                LEFT JOIN usuario u ON c.id_usuario = u.id
                ORDER BY c.titulos_totais DESC, c.bolas_ouro DESC, c.ovr_maximo DESC, c.gols_totais DESC
                LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rank = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'ranking' => $rank], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'ranking' => []]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
