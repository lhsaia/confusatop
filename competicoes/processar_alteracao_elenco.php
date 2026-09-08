<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado. Por favor faça login.']);
    exit;
}

if ($_SESSION['emTestes'] ?? false) {
    echo json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem realizar alterações de elenco.']);
    exit;
}

$idUsuario = $_SESSION['user_id'];
$idCompeticao = isset($_POST['id_competicao']) ? intval($_POST['id_competicao']) : 0;
$idTime = isset($_POST['id_time']) ? intval($_POST['id_time']) : 0;
$acao = isset($_POST['acao']) ? trim($_POST['acao']) : ''; // 'substituir', 'adicionar', 'remover'
$idJogadorSaiu = isset($_POST['id_jogador_saiu']) ? intval($_POST['id_jogador_saiu']) : 0;
$idJogadorEntrou = isset($_POST['id_jogador_entrou']) ? intval($_POST['id_jogador_entrou']) : 0;
$slotPos = isset($_POST['slot_pos']) ? intval($_POST['slot_pos']) : 0; // 1 a 23

if ($idCompeticao <= 0 || $idTime <= 0 || !in_array($acao, ['substituir', 'adicionar', 'remover'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos enviados para a requisição.']);
    exit;
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/config/sqliteDatabase.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/competicao_clube.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

// 1. Verificar se o usuário é dono do país do clube ou admin
$stmtVerificaDono = $db->prepare("SELECT c.Nome, p.dono FROM clube c INNER JOIN paises p ON c.Pais = p.id WHERE c.ID = ? LIMIT 1");
$stmtVerificaDono->execute([$idTime]);
$clubeInfo = $stmtVerificaDono->fetch(PDO::FETCH_ASSOC);

$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
if (!$clubeInfo || (!$isAdmin && intval($clubeInfo['dono']) !== intval($idUsuario))) {
    echo json_encode(['success' => false, 'error' => 'Você não possui permissão para gerenciar o elenco deste clube.']);
    exit;
}

// 2. Verificar regras da competição (alteracoeselenco = 1, datas válidas, limite de jogadores adicionais)
// OBS: Remoção de atleta ('remover') é permitida a qualquer momento. Apenas inclusões e substituições dependem da janela e cota.
$opts = $competicaoObj->getOptions($idCompeticao);
$maxAlteracoes = intval($opts['jogadoresadicionais'] ?? 0);
$stmtCountAlt = $db->prepare("SELECT COUNT(*) as total FROM competicao_alteracoes_log WHERE id_competicao = ? AND id_time = ? AND tipo_acao IN ('substituir', 'adicionar')");
$stmtCountAlt->execute([$idCompeticao, $idTime]);
$totalRealizadas = intval($stmtCountAlt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

if ($acao === 'substituir' || $acao === 'adicionar') {
    if (!$opts || empty($opts['alteracoeselenco'])) {
        echo json_encode(['success' => false, 'error' => 'Esta competição não permite inscrições ou substituições de elenco adicionais.']);
        exit;
    }

    $hoje = date('Y-m-d');
    if (!empty($opts['inicioalteracoes']) && $opts['inicioalteracoes'] !== '0000-00-00' && $hoje < $opts['inicioalteracoes']) {
        echo json_encode(['success' => false, 'error' => 'A janela de alterações ainda não começou (Início em ' . date('d/m/Y', strtotime($opts['inicioalteracoes'])) . ').']);
        exit;
    }
    if (!empty($opts['fimalteracoes']) && $opts['fimalteracoes'] !== '0000-00-00' && $hoje > $opts['fimalteracoes']) {
        echo json_encode(['success' => false, 'error' => 'A janela de alterações já foi encerrada (Fim em ' . date('d/m/Y', strtotime($opts['fimalteracoes'])) . ').']);
        exit;
    }

    if ($totalRealizadas >= $maxAlteracoes) {
        echo json_encode(['success' => false, 'error' => "Limite de alterações de elenco atingido ($totalRealizadas / $maxAlteracoes trocas/inscrições realizadas)."]);
        exit;
    }
}

// 3. Conectar ao SQLite da competição
$db3Path = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/{$idCompeticao}-database.db3";
if (!file_exists($db3Path)) {
    echo json_encode(['success' => false, 'error' => 'Banco de dados da competição não encontrado.']);
    exit;
}

$sqliteDb = new SQLiteDatabase();
$sqliteDb->fileName = $db3Path;
$sdb = $sqliteDb->getConnection();
if (!$sdb) {
    echo json_encode(['success' => false, 'error' => 'Falha ao conectar ao banco de dados SQLite da competição.']);
    exit;
}

// Obter linha de elenco atual no SQLite
$stmtElenco = $sdb->prepare("SELECT * FROM elenco WHERE Clube = ? LIMIT 1");
$stmtElenco->execute([$idTime]);
$elencoRow = $stmtElenco->fetch(PDO::FETCH_ASSOC);

if (!$elencoRow) {
    echo json_encode(['success' => false, 'error' => 'Registro de elenco do clube não encontrado na competição.']);
    exit;
}

// Coletar IDs atuais inscritos (Jogador1 a Jogador23)
$jogadoresInscritos = [];
for ($i = 1; $i <= 23; $i++) {
    $col = "Jogador" . $i;
    $jId = intval($elencoRow[$col] ?? 0);
    if ($jId > 0) {
        $jogadoresInscritos[$i] = $jId;
    }
}

// Validações específicas de cada ação
if ($acao === 'substituir') {
    if ($idJogadorSaiu <= 0 || $idJogadorEntrou <= 0) {
        echo json_encode(['success' => false, 'error' => 'Selecione o jogador que sairá e o que entrará.']);
        exit;
    }
    if ($idJogadorSaiu === $idJogadorEntrou) {
        echo json_encode(['success' => false, 'error' => 'O jogador que entra deve ser diferente do que sai.']);
        exit;
    }
    if (!in_array($idJogadorSaiu, $jogadoresInscritos)) {
        echo json_encode(['success' => false, 'error' => 'O jogador a ser substituído não está inscrito no elenco desta competição.']);
        exit;
    }
    // Verificar se o jogador que está entrando já foi desinscrito/removido anteriormente desta competição
    $stmtCheckRemovido = $db->prepare("SELECT COUNT(*) as total FROM competicao_alteracoes_log WHERE id_competicao = ? AND id_time = ? AND id_jogador_saiu = ?");
    $stmtCheckRemovido->execute([$idCompeticao, $idTime, $idJogadorEntrou]);
    if (intval($stmtCheckRemovido->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0) {
        echo json_encode(['success' => false, 'error' => 'Este atleta já participou e foi removido desta competição, portanto não pode ser inscrito novamente.']);
        exit;
    }

    if (in_array($idJogadorEntrou, $jogadoresInscritos)) {
        echo json_encode(['success' => false, 'error' => 'O novo jogador já está inscrito nesta competição.']);
        exit;
    }
    // Encontrar o slot de saída
    $targetSlot = array_search($idJogadorSaiu, $jogadoresInscritos);
    if ($targetSlot === false) {
        echo json_encode(['success' => false, 'error' => 'Slot do jogador não identificado.']);
        exit;
    }
} elseif ($acao === 'adicionar') {
    if ($idJogadorEntrou <= 0) {
        echo json_encode(['success' => false, 'error' => 'Selecione o jogador a ser inscrito.']);
        exit;
    }
    // Verificar se o jogador que está entrando já foi desinscrito/removido anteriormente desta competição
    $stmtCheckRemovido = $db->prepare("SELECT COUNT(*) as total FROM competicao_alteracoes_log WHERE id_competicao = ? AND id_time = ? AND id_jogador_saiu = ?");
    $stmtCheckRemovido->execute([$idCompeticao, $idTime, $idJogadorEntrou]);
    if (intval($stmtCheckRemovido->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0) {
        echo json_encode(['success' => false, 'error' => 'Este atleta já participou e foi removido desta competição, portanto não pode ser inscrito novamente.']);
        exit;
    }

    if (in_array($idJogadorEntrou, $jogadoresInscritos)) {
        echo json_encode(['success' => false, 'error' => 'Este jogador já está inscrito nesta competição.']);
        exit;
    }
    if (count($jogadoresInscritos) >= 23) {
        echo json_encode(['success' => false, 'error' => 'O elenco já possui o limite máximo de 23 jogadores inscritos. Utilize a opção Substituir.']);
        exit;
    }
    // Encontrar o primeiro slot vago (ou usar o slotPos se fornecido)
    $targetSlot = ($slotPos >= 1 && $slotPos <= 23 && !isset($jogadoresInscritos[$slotPos])) ? $slotPos : null;
    if ($targetSlot === null) {
        for ($i = 1; $i <= 23; $i++) {
            if (!isset($jogadoresInscritos[$i])) {
                $targetSlot = $i;
                break;
            }
        }
    }
    if ($targetSlot === null) {
        echo json_encode(['success' => false, 'error' => 'Nenhum slot vago encontrado no elenco.']);
        exit;
    }
} elseif ($acao === 'remover') {
    if ($idJogadorSaiu <= 0) {
        echo json_encode(['success' => false, 'error' => 'Selecione o jogador a ser removido.']);
        exit;
    }
    if (!in_array($idJogadorSaiu, $jogadoresInscritos)) {
        echo json_encode(['success' => false, 'error' => 'O jogador a ser removido não está inscrito nesta competição.']);
        exit;
    }
    $targetSlot = array_search($idJogadorSaiu, $jogadoresInscritos);
    if ($targetSlot === false) {
        echo json_encode(['success' => false, 'error' => 'Slot do jogador não identificado.']);
        exit;
    }
}

// 4. Executar operações no SQLite
$sdb->beginTransaction();

try {
    // Se há jogador entrando, importar seus dados completos do MySQL para o SQLite
    if ($idJogadorEntrou > 0) {
        $jogadorObj = new Jogador($db);
        $stmtJ = $jogadorObj->exportacao($idJogadorEntrou);
        $jRow = $stmtJ->fetch(PDO::FETCH_ASSOC);

        if (!$jRow) {
            throw new Exception("Dados do jogador #$idJogadorEntrou não encontrados no banco principal.");
        }

        // posicaojogador
        $stmtPos = $sdb->prepare("INSERT OR REPLACE INTO posicaojogador VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $sp = $jRow['StringPosicoes'] ?? '000000000000000';
        $stmtPos->execute([
            $jRow['idJogador'],
            $sp[0] ?? 0, $sp[1] ?? 0, $sp[2] ?? 0, $sp[3] ?? 0, $sp[4] ?? 0,
            $sp[5] ?? 0, $sp[6] ?? 0, $sp[7] ?? 0, $sp[8] ?? 0, $sp[9] ?? 0,
            $sp[10] ?? 0, $sp[11] ?? 0, $sp[12] ?? 0, $sp[13] ?? 0, $sp[14] ?? 0
        ]);

        // jogador
        $stmtJog = $sdb->prepare("INSERT OR REPLACE INTO jogador VALUES (?, ?, ?, ?, '0', '0', ?, ?)");
        $stmtJog->execute([
            $jRow['idJogador'],
            $jRow['nomeJogador'],
            $jRow['Idade'],
            $jRow['Nivel'],
            $jRow['Mentalidade'],
            $jRow['CobradorFalta']
        ]);

        // nacionalidades
        $nac = !empty($jRow['Nacionalidade']) ? $jRow['Nacionalidade'] : '-';
        $stmtNac = $sdb->prepare("INSERT OR REPLACE INTO nacionalidades VALUES (?, ?)");
        $stmtNac->execute([$jRow['idJogador'], $nac]);

        // atributos
        if (isset($sp[0]) && $sp[0] == 1) { // Goleiro
            $stmtAtrG = $sdb->prepare("INSERT OR REPLACE INTO atributosgoleiro VALUES (?, ?, ?, ?, ?, ?, ?, '1', '1')");
            $stmtAtrG->execute([
                $jRow['idJogador'],
                $jRow['Reflexos'],
                $jRow['Seguranca'],
                $jRow['Saidas'],
                $jRow['JogoAereo'],
                $jRow['Lancamentos'],
                $jRow['DefesaPenaltis']
            ]);
        } else { // Jogador de linha
            $stmtAtrJ = $sdb->prepare("INSERT OR REPLACE INTO atributosjogador VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', '1')");
            $stmtAtrJ->execute([
                $jRow['idJogador'],
                $jRow['Marcacao'],
                $jRow['Desarme'],
                $jRow['VisaoJogo'],
                $jRow['Movimentacao'],
                $jRow['Cruzamentos'],
                $jRow['Cabeceamento'],
                $jRow['Tecnica'],
                $jRow['ControleBola'],
                $jRow['Finalizacao'],
                $jRow['FaroGol'],
                $jRow['Velocidade'],
                $jRow['Forca']
            ]);
        }
    }

    // Atualizar a coluna correspondente no elenco (Jogador1 a Jogador23)
    $targetCol = "Jogador" . $targetSlot;
    $newVal = ($acao === 'remover') ? 0 : $idJogadorEntrou;

    $stmtUpdElenco = $sdb->prepare("UPDATE elenco SET {$targetCol} = ? WHERE Clube = ?");
    $stmtUpdElenco->execute([$newVal, $idTime]);

    // Se saiu jogador, desescalar ele da tabela escalacao se ele estava nos 11 ou capitão/pênaltis
    if ($idJogadorSaiu > 0) {
        $stmtEsc = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = ? LIMIT 1");
        $stmtEsc->execute([$idTime]);
        $escRow = $stmtEsc->fetch(PDO::FETCH_ASSOC);

        if ($escRow) {
            $updEscFields = [];
            $updEscParams = [];
            for ($k = 1; $k <= 11; $k++) {
                if (intval($escRow['Jogador' . $k] ?? 0) === $idJogadorSaiu) {
                    $updEscFields[] = "Jogador{$k} = 0";
                }
            }
            if (intval($escRow['Capitao'] ?? 0) === $idJogadorSaiu) {
                $updEscFields[] = "Capitao = 0";
            }
            if (intval($escRow['Penalti1'] ?? 0) === $idJogadorSaiu) {
                $updEscFields[] = "Penalti1 = 0";
            }
            if (intval($escRow['Penalti2'] ?? 0) === $idJogadorSaiu) {
                $updEscFields[] = "Penalti2 = 0";
            }
            if (intval($escRow['Penalti3'] ?? 0) === $idJogadorSaiu) {
                $updEscFields[] = "Penalti3 = 0";
            }

            if (!empty($updEscFields)) {
                $sqlEscUpd = "UPDATE escalacao SET " . implode(', ', $updEscFields) . " WHERE Clube = ?";
                $updEscParams[] = $idTime;
                $stmtExecEsc = $sdb->prepare($sqlEscUpd);
                $stmtExecEsc->execute($updEscParams);
            }
        }
    }

    $sdb->commit();
} catch (Exception $e) {
    $sdb->rollBack();
    echo json_encode(['success' => false, 'error' => 'Erro ao processar alteração no SQLite: ' . $e->getMessage()]);
    exit;
}

// 5. Gravar log da alteração no MySQL para controlar a cota
$stmtLog = $db->prepare("INSERT INTO competicao_alteracoes_log (id_competicao, id_time, tipo_acao, id_jogador_saiu, id_jogador_entrou) VALUES (?, ?, ?, ?, ?)");
$stmtLog->execute([
    $idCompeticao,
    $idTime,
    $acao,
    ($idJogadorSaiu > 0 ? $idJogadorSaiu : 0),
    ($idJogadorEntrou > 0 ? $idJogadorEntrou : 0)
]);

$restantes = $maxAlteracoes - ($totalRealizadas + 1);

echo json_encode([
    'success' => true,
    'mensagem' => 'Alteração de elenco realizada com sucesso!',
    'alteracoes_restantes' => max(0, $restantes),
    'max_alteracoes' => $maxAlteracoes
]);
