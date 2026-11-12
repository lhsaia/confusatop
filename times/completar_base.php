<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

if ($_SESSION['emTestes'] ?? false) {
    echo json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem gerar jogadores via Hexagen.']);
    exit;
}

$idClube = (int)($_POST['clube'] ?? 0);
$acao = trim((string)($_POST['acao'] ?? 'diagnostico')); // 'diagnostico' ou 'executar'

if ($idClube <= 0) {
    echo json_encode(['success' => false, 'error' => 'Clube inválido.']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/paises.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';

$database = new Database();
$db = $database->getConnection();

$paisObj = new Pais($db);
$timeObj = new Time($db);
$jogadorObj = new Jogador($db);

// 1. Obter informações do clube e verificar permissão do dono
$infoClube = $timeObj->readInfo($idClube);
if (empty($infoClube) || empty($infoClube['id'])) {
    echo json_encode(['success' => false, 'error' => 'Clube não encontrado.']);
    exit;
}

$idPais = (int)($infoClube['pais_id'] ?? 0);
$donoPais = (int)($infoClube['donoPais'] ?? 0);
$sexo = (int)($infoClube['sexo'] ?? 0);
$nomeClube = $infoClube['Nome'] ?? 'Clube';

$isAdmin = (!empty($_SESSION['admin']) && (int)$_SESSION['admin'] === 1);
if (!$isAdmin && $donoPais !== (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para gerenciar a base deste clube.']);
    exit;
}

// 2. Mapeamento de posições do sistema (Sigla => ID)
$mapSiglaParaId = [
    'G'   => 1,
    'LD'  => 2,
    'Z'   => 3,
    'LE'  => 4,
    'L'   => 5,
    'V'   => 6,
    'MD'  => 7,
    'MC'  => 8,
    'ME'  => 9,
    'MAD' => 10,
    'MAC' => 11,
    'MAE' => 12,
    'PD'  => 13,
    'CA'  => 14,
    'PE'  => 15
];

// Nomes legíveis das siglas para exibição amigável
$nomesPosicoes = [
    'G'   => 'Goleiro',
    'LD'  => 'Lateral Direito',
    'Z'   => 'Zagueiro',
    'LE'  => 'Lateral Esquerdo',
    'L'   => 'Líbero',
    'V'   => 'Volante',
    'MD'  => 'Meia Direito',
    'MC'  => 'Meia Central',
    'ME'  => 'Meia Esquerdo',
    'MAD' => 'Meia-Atacante Direito',
    'MAC' => 'Meia-Armador',
    'MAE' => 'Meia-Atacante Esquerdo',
    'PD'  => 'Ponta Direita',
    'CA'  => 'Centroavante',
    'PE'  => 'Ponta Esquerda'
];

// Carregar posições do banco para garantir compatibilidade caso haja IDs extras
try {
    $stmtPosAll = $db->query("SELECT ID, Sigla, Nome FROM posicoes WHERE ID >= 1 ORDER BY ID ASC");
    while ($rPos = $stmtPosAll->fetch(PDO::FETCH_ASSOC)) {
        $sig = trim($rPos['Sigla']);
        $mapSiglaParaId[$sig] = (int)$rPos['ID'];
        if (!empty($rPos['Nome'])) {
            $nomesPosicoes[$sig] = $rPos['Nome'];
        }
    }
} catch (Exception $e) {
    // Mantém mapa padrão
}

// 3. Buscar elenco atual do clube (apenas contratos profissionais tipoContrato = 0)
$queryElenco = "
    SELECT 
        j.ID as idJogador, 
        j.StringPosicoes, 
        c.posicaoBase, 
        c.titularidade,
        p.Sigla as siglaPosicaoBase
    FROM contratos_jogador c
    INNER JOIN jogador j ON c.jogador = j.ID
    LEFT JOIN posicoes p ON p.ID = c.posicaoBase
    WHERE c.clube = :clubeId AND c.tipoContrato = 0
";
$stmtElenco = $db->prepare($queryElenco);
$stmtElenco->bindParam(':clubeId', $idClube, PDO::PARAM_INT);
$stmtElenco->execute();
$jogadoresAtuais = $stmtElenco->fetchAll(PDO::FETCH_ASSOC);

$totalJogadoresAtual = count($jogadoresAtuais);

// 4. Identificar titulares atuais e suas posições base
$titulares = [];
foreach ($jogadoresAtuais as $jog) {
    if ((int)$jog['titularidade'] === 1 && !empty($jog['siglaPosicaoBase'])) {
        $titulares[] = trim($jog['siglaPosicaoBase']);
    }
}

// 5. Definir Meta Ideal de Posições
$metaPosicoes = [];
$tamanhoAlvo = 22;

if (count($titulares) === 11) {
    // Opção 2 (Prioridade): Baseado na formação dos 11 titulares atuais
    // Regra:
    // - Para cada vaga titular: 2 jogadores (1 titular + 1 reserva imediato)
    // - Para Goleiro: pelo menos 3 (titular + 2 reservas)
    foreach ($titulares as $sigla) {
        $metaPosicoes[$sigla] = ($metaPosicoes[$sigla] ?? 0) + 2;
    }
    // Garantir pelo menos 3 goleiros
    if (($metaPosicoes['G'] ?? 0) < 3) {
        $metaPosicoes['G'] = 3;
    }
} else {
    // Opção 1 (Fallback): Esquema padrão equilibrado de 22 jogadores
    $metaPosicoes = [
        'G'  => 3,
        'LD' => 2,
        'Z'  => 4,
        'LE' => 2,
        'V'  => 3,
        'MC' => 3,
        'MD' => 1,
        'ME' => 1,
        'MAC'=> 2, // Meia-Armador / MA
        'CA' => 3  // Atacantes / Centroavantes
    ];
}

// 6. Contabilizar cobertura atual por posição no elenco
// Contagem primária por posicaoBase definida e suporte por StringPosicoes
$contagemPorPosicao = [];
foreach ($metaPosicoes as $sigla => $meta) {
    $contagemPorPosicao[$sigla] = 0;
}

foreach ($jogadoresAtuais as $jog) {
    $siglaBase = trim((string)$jog['siglaPosicaoBase']);
    if (!empty($siglaBase) && isset($metaPosicoes[$siglaBase])) {
        $contagemPorPosicao[$siglaBase]++;
        continue;
    }

    // Se não tiver posicaoBase definida, verificar na StringPosicoes se cobre alguma posição da meta
    $strPos = (string)($jog['StringPosicoes'] ?? '');
    $atribuido = false;
    foreach ($metaPosicoes as $sigla => $meta) {
        $posId = $mapSiglaParaId[$sigla] ?? 0;
        if ($posId > 0 && isset($strPos[$posId - 1]) && $strPos[$posId - 1] === '1') {
            // Se ainda precisa nessa posição, conta aqui
            if ($contagemPorPosicao[$sigla] < $meta) {
                $contagemPorPosicao[$sigla]++;
                $atribuido = true;
                break;
            }
        }
    }
    if (!$atribuido && !empty($siglaBase)) {
        $contagemPorPosicao[$siglaBase] = ($contagemPorPosicao[$siglaBase] ?? 0) + 1;
    }
}

// 7. Calcular Carências (Posições que faltam)
$posicoesFaltantes = [];
foreach ($metaPosicoes as $sigla => $meta) {
    $atual = $contagemPorPosicao[$sigla] ?? 0;
    if ($atual < $meta) {
        $dif = $meta - $atual;
        for ($k = 0; $k < $dif; $k++) {
            $posicoesFaltantes[] = $sigla;
        }
    }
}

// Se o elenco tiver carências específicas mas o total de contratações ultrapassar 25, limitar a 25 máximo
$vagasAte25 = max(0, 25 - $totalJogadoresAtual);
if (count($posicoesFaltantes) > $vagasAte25) {
    $posicoesFaltantes = array_slice($posicoesFaltantes, 0, $vagasAte25);
}

// Agrupamento resumido para o usuário (ex: "1 Goleiro, 2 Zagueiros, 1 Lateral Esquerdo")
$resumoCarencias = [];
$contagemFaltantes = array_count_values($posicoesFaltantes);
foreach ($contagemFaltantes as $sig => $qtd) {
    $nomePos = $nomesPosicoes[$sig] ?? $sig;
    $resumoCarencias[] = "{$qtd}x {$sig} ({$nomePos})";
}

// 8. Se a ação for apenas DIAGNÓSTICO, retornar para o modal
if ($acao === 'diagnostico') {
    echo json_encode([
        'success'           => true,
        'clubeId'           => $idClube,
        'clubeNome'         => $nomeClube,
        'totalAtual'        => $totalJogadoresAtual,
        'carenciasTotal'    => count($posicoesFaltantes),
        'posicoesFaltantes' => $posicoesFaltantes,
        'resumoFormatado'   => implode(', ', $resumoCarencias),
        'jaCompleto'        => (count($posicoesFaltantes) === 0)
    ]);
    exit;
}

// 9. Se a ação for EXECUTAR, criar os jogadores jovens correspondentes
if ($acao === 'executar') {
    if (count($posicoesFaltantes) === 0) {
        echo json_encode(['success' => false, 'error' => 'O elenco já possui cobertura ideal ou atingiu o limite de atletas.']);
        exit;
    }

    // Configurações do jovem da base via Hexagen
    $idadeMin = 17;
    $idadeMax = 20;

    // Nível calculado a partir da média do clube
    $nivelMedClube = (float)$timeObj->mediaNiveis($idClube);
    if ($nivelMedClube <= 0) {
        $nivelMedClube = 55;
    }
    $nivelMin = max(20, round($nivelMedClube - 15));
    $nivelMed = max(25, round($nivelMedClube - 10));
    $nivelMax = min(75, round($nivelMedClube + 5));

    $criados = 0;
    $erros = [];

    // Dados demográficos do país
    $origemNomes = $paisObj->sorteioDemografico($idPais, 0, $sexo);
    $origemSobrenomes = $paisObj->sorteioDemografico($idPais, 1, $sexo);
    $indiceMiscigenacao = $paisObj->verificarMiscigenacao($idPais, $origemNomes);
    $ocorrenciaNomeDuplo = $paisObj->verificarNomeDuplo($idPais, $origemNomes);

    foreach ($posicoesFaltantes as $siglaPos) {
        $codPos = $mapSiglaParaId[$siglaPos] ?? null;

        // Instanciar novo objeto Jogador limpo
        $novoJogador = new Jogador($db);
        $novoJogador->randomPlayer(
            $codPos,
            $idPais,
            $origemNomes,
            $origemSobrenomes,
            $idadeMin,
            $idadeMax,
            $nivelMin,
            $nivelMax,
            $nivelMed,
            0,
            $ocorrenciaNomeDuplo,
            $indiceMiscigenacao,
            $sexo
        );

        $novoJogador->condicao = 'true';
        $novoJogador->determinacaoOriginal = $novoJogador->determinacao;
        $novoJogador->sexo = $sexo;

        if ($novoJogador->create(true)) {
            $idCriado = (int)$db->lastInsertId();
            // Inserir como Suplente (titularidade = -1) e sem atrapalhar a escalação titular
            if ($novoJogador->transferir($idCriado, $idClube, 0, 0, -1, $codPos)) {
                $criados++;
            } else {
                $erros[] = "Erro ao vincular jogador {$novoJogador->nome} ao clube.";
            }
        } else {
            $erros[] = "Erro ao criar jogador da posição {$siglaPos}.";
        }
    }

    if ($criados > 0) {
        echo json_encode([
            'success'   => true,
            'criados'   => $criados,
            'mensagem'  => "{$criados} jogador(es) da base foram promovidos com sucesso para o {$nomeClube}!",
            'erros'     => $erros
        ]);
    } else {
        echo json_encode([
            'success'   => false,
            'error'     => 'Não foi possível gerar os jogadores da base: ' . implode('; ', $erros)
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
