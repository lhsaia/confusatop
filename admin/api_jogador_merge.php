<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas administradores logados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['admin_status'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$jogadorObj = new Jogador($db);

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

// 1. Ação de Buscar dados detalhados de um atleta para o card
if ($action === 'get') {
    $idJogador = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
    if ($idJogador <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de jogador inválido.']);
        exit;
    }

    $dados = $jogadorObj->readInfo($idJogador);
    if (empty($dados) || empty($dados['nome'])) {
        echo json_encode(['success' => false, 'error' => 'Atleta não encontrado.']);
        exit;
    }

    // Obter dados extras do jogador
    $stmtExtra = $db->prepare("SELECT externalID, referencia, CobradorFalta, Mentalidade, Sexo, Condicao, Progressao, Determinacao, DeterminacaoOriginal FROM jogador WHERE ID = ? LIMIT 1");
    $stmtExtra->execute([$idJogador]);
    $extra = $stmtExtra->fetch(PDO::FETCH_ASSOC) ?: [];

    // Nome da mentalidade e cobrador de falta
    $nomeMentalidade = '-';
    if (!empty($extra['Mentalidade'])) {
        $stmtM = $db->prepare("SELECT Nome FROM mentalidade WHERE ID = ?");
        $stmtM->execute([$extra['Mentalidade']]);
        $nomeMentalidade = $stmtM->fetchColumn() ?: '-';
    }

    $nomeCobrador = '-';
    if (!empty($extra['CobradorFalta'])) {
        $stmtCob = $db->prepare("SELECT Nome FROM cobrador WHERE ID = ?");
        $stmtCob->execute([$extra['CobradorFalta']]);
        $nomeCobrador = $stmtCob->fetchColumn() ?: '-';
    }

    // Parsing das posições (stringPosicoes de 15 caracteres)
    $nomesPosicoes = [
        1 => 'G', 2 => 'LD', 3 => 'LE', 4 => 'Z', 5 => 'AD', 6 => 'AE',
        7 => 'V', 8 => 'MD', 9 => 'ME', 10 => 'MC', 11 => 'PD', 12 => 'PE',
        13 => 'MA', 14 => 'Am', 15 => 'Aa'
    ];
    $posicoesArray = [];
    $strPos = $dados['stringPosicoes'] ?? '';
    for ($i = 0; $i < strlen($strPos) && $i < 15; $i++) {
        if ($strPos[$i] === '1' && isset($nomesPosicoes[$i + 1])) {
            $posicoesArray[] = $nomesPosicoes[$i + 1];
        }
    }

    // Buscar contratos ativos (Clube, Empréstimo e Seleção)
    $stmtContratos = $db->prepare("
        SELECT cj.*, cl.Nome as nomeClube, cl.Escudo as escudoClube 
        FROM contratos_jogador cj 
        LEFT JOIN clube cl ON cj.clube = cl.ID 
        WHERE cj.jogador = ?
    ");
    $stmtContratos->execute([$idJogador]);
    $todosContratos = $stmtContratos->fetchAll(PDO::FETCH_ASSOC);

    // Contagem de transferências
    $stmtTransf = $db->prepare("SELECT COUNT(*) FROM transferencias WHERE jogador = ? AND status_execucao = 1");
    $stmtTransf->execute([$idJogador]);
    $totalTransf = (int)$stmtTransf->fetchColumn();

    // Foto formatada
    $fotoFormatada = '';
    if (!empty($dados['foto'])) {
        $fotoFormatada = (strpos($dados['foto'], 'http') === 0 || strpos($dados['foto'], '/') === 0) 
            ? $dados['foto'] 
            : '/images/jogadores/' . $dados['foto'];
    }

    $resposta = [
        'success' => true,
        'id' => $idJogador,
        'nome' => $dados['nome'],
        'nascimento' => $dados['nascimento'],
        'idade' => $dados['idade'] ?? 0,
        'data_falecimento' => $dados['data_falecimento'] ?? null,
        'pais' => $dados['Pais'] ?? '',
        'idPais' => $dados['idPais'] ?? 0,
        'bandeiraPais' => $dados['bandeiraPais'] ?? '',
        'foto' => $fotoFormatada,
        'nivel' => (int)($dados['Nivel'] ?? 0),
        'valor' => (float)($dados['valor'] ?? 0),
        'valor_formatado' => number_format((float)($dados['valor'] ?? 0), 2, ',', '.'),
        'posicoes' => $posicoesArray,
        'stringPosicoes' => $strPos,
        'mentalidade' => $nomeMentalidade,
        'cobradorFalta' => $nomeCobrador,
        'sexo' => ((int)($extra['Sexo'] ?? 0) === 1) ? 'Feminino' : 'Masculino',
        'externalID' => $extra['externalID'] ?? null,
        'referencia' => $extra['referencia'] ?? null,
        'determinacao' => (int)($extra['Determinacao'] ?? 0),
        'clube' => $dados['time'] ?? 'Sem clube',
        'escudoTime' => $dados['escudoTime'] ?? '',
        'liga' => $dados['liga'] ?? '',
        'fimContrato' => $dados['fimContrato'] ?? '',
        'salario' => (float)($dados['salario'] ?? 0),
        'numeroCamisa' => $dados['numeroCamisa'] ?? null,
        'contratos' => $todosContratos,
        'stats' => [
            'jogosTime' => (int)($dados['jogosTime'] ?? 0),
            'golsTime' => (int)($dados['golsTime'] ?? 0),
            'amarelosTime' => (int)($dados['amarelosTime'] ?? 0),
            'vermelhosTime' => (int)($dados['vermelhosTime'] ?? 0),
            'golsSelecao' => (int)($dados['golsSelecao'] ?? 0),
            'amarelosSelecao' => (int)($dados['amarelosSelecao'] ?? 0),
            'vermelhosSelecao' => (int)($dados['vermelhosSelecao'] ?? 0),
            'transferencias' => $totalTransf
        ],
        'atributos' => [
            // Linha
            'Marcacao' => (int)($dados['Marcacao'] ?? 0),
            'Desarme' => (int)($dados['Desarme'] ?? 0),
            'VisaoJogo' => (int)($dados['VisaoJogo'] ?? 0),
            'Movimentacao' => (int)($dados['Movimentacao'] ?? 0),
            'Cruzamentos' => (int)($dados['Cruzamentos'] ?? 0),
            'Cabeceamento' => (int)($dados['Cabeceamento'] ?? 0),
            'Tecnica' => (int)($dados['Tecnica'] ?? 0),
            'ControleBola' => (int)($dados['ControleBola'] ?? 0),
            'Finalizacao' => (int)($dados['Finalizacao'] ?? 0),
            'FaroGol' => (int)($dados['FaroGol'] ?? 0),
            'Velocidade' => (int)($dados['Velocidade'] ?? 0),
            'Forca' => (int)($dados['Forca'] ?? 0),
            // Goleiro
            'Reflexos' => (int)($dados['Reflexos'] ?? 0),
            'Seguranca' => (int)($dados['Seguranca'] ?? 0),
            'Saidas' => (int)($dados['Saidas'] ?? 0),
            'JogoAereo' => (int)($dados['JogoAereo'] ?? 0),
            'Lancamentos' => (int)($dados['Lancamentos'] ?? 0),
            'DefesaPenaltis' => (int)($dados['DefesaPenaltis'] ?? 0)
        ]
    ];

    echo json_encode($resposta);
    exit;
}

// 2. Ação de Realizar o Merge
if ($action === 'merge') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método inválido.']);
        exit;
    }

    $idPrincipal = (int)($_POST['id_principal'] ?? 0);
    $idSecundario = (int)($_POST['id_secundario'] ?? 0);
    $idUsuario = (int)($_SESSION['user_id'] ?? 0);
    $nomeUsuario = (string)($_SESSION['nomereal'] ?? ($_SESSION['username'] ?? 'Admin'));

    if ($idPrincipal <= 0 || $idSecundario <= 0 || $idPrincipal === $idSecundario) {
        echo json_encode(['success' => false, 'error' => 'Selecione dois atletas distintos para realizar o merge.']);
        exit;
    }

    $resultado = $jogadorObj->mergeAtletas($idPrincipal, $idSecundario, $idUsuario, $nomeUsuario);
    echo json_encode($resultado);
    exit;
}

// 3. Ação de Buscar Possíveis Duplicatas
if ($action === 'duplicatas') {
    $duplicatas = $jogadorObj->buscarPossiveisDuplicatas();
    echo json_encode(['success' => true, 'duplicatas' => $duplicatas]);
    exit;
}

// 4. Ação de Listar Logs de Merges Realizados
if ($action === 'logs') {
    $logs = $jogadorObj->listarLogsMerge(50);
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

// 5. Ação de Buscar Atletas via AJAX (Autocomplete / On-Demand)
if ($action === 'search') {
    $termo = trim((string)($_GET['q'] ?? ($_POST['q'] ?? '')));
    $idDireto = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

    if ($idDireto > 0) {
        $stmt = $db->prepare("
            SELECT j.ID, j.Nome, j.Nivel, j.Nascimento, p.nome as nomePais, p.bandeira as bandeiraPais, c.Nome as nomeClube
            FROM jogador j
            LEFT JOIN paises p ON j.Pais = p.id
            LEFT JOIN contratos_jogador cj ON j.ID = cj.jogador AND cj.tipoContrato = 0
            LEFT JOIN clube c ON cj.clube = c.ID
            WHERE j.ID = ?
            LIMIT 1
        ");
        $stmt->execute([$idDireto]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($termo !== '') {
        $likeTermo = '%' . $termo . '%';
        $stmt = $db->prepare("
            SELECT j.ID, j.Nome, j.Nivel, j.Nascimento, p.nome as nomePais, p.bandeira as bandeiraPais, c.Nome as nomeClube
            FROM jogador j
            LEFT JOIN paises p ON j.Pais = p.id
            LEFT JOIN contratos_jogador cj ON j.ID = cj.jogador AND cj.tipoContrato = 0
            LEFT JOIN clube c ON cj.clube = c.ID
            WHERE j.Nome LIKE ? OR j.ID = ?
            ORDER BY j.Nome ASC
            LIMIT 30
        ");
        $stmt->execute([$likeTermo, (int)$termo]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Se vazio, traz os primeiros 25 mais recentes
        $stmt = $db->query("
            SELECT j.ID, j.Nome, j.Nivel, j.Nascimento, p.nome as nomePais, p.bandeira as bandeiraPais, c.Nome as nomeClube
            FROM jogador j
            LEFT JOIN paises p ON j.Pais = p.id
            LEFT JOIN contratos_jogador cj ON j.ID = cj.jogador AND cj.tipoContrato = 0
            LEFT JOIN clube c ON cj.clube = c.ID
            ORDER BY j.ID DESC
            LIMIT 25
        ");
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $formatados = array_map(function($j) {
        $pais = $j['nomePais'] ?: 'Sem País';
        $clube = $j['nomeClube'] ?: 'Sem clube';
        return [
            'value' => (int)$j['ID'],
            'text' => '#' . $j['ID'] . ' - ' . $j['Nome'] . ' (' . $pais . ' | OVR: ' . (int)$j['Nivel'] . ' | ' . $clube . ')',
            'nome' => $j['Nome'],
            'pais' => $pais,
            'nivel' => (int)$j['Nivel'],
            'clube' => $clube
        ];
    }, $resultados);

    echo json_encode(['success' => true, 'results' => $formatados]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ação não reconhecida.']);
