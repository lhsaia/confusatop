<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: /index.php");
    exit();
}

$idCompeticao = isset($_GET['comp']) ? intval($_GET['comp']) : 0;
$idTime       = isset($_GET['team']) ? intval($_GET['team']) : 0;

if (!$idCompeticao || !$idTime) {
    die("Competição ou Time não informados.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$infoComp = $competicaoObj->readInfo($idCompeticao);
$donoCompeticao = isset($infoComp['dono']) ? $infoComp['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$userLogado = $_SESSION['user_id'];

// Verificar o dono do time no SQLite
$sqliteDb = new SQLiteDatabase();
$sqliteDb->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
$sdb = $sqliteDb->getConnection();

// Obter o Nome do Time e o dono do País diretamente do MariaDB principal
$donoTime = 0;
$nomeTime = "Time";

$stmtMariaClube = $db->prepare("SELECT c.nome as NomeClube, p.dono as idDonoPais FROM clube c LEFT JOIN paises p ON c.Pais = p.id WHERE c.id = :idTime LIMIT 1");
$stmtMariaClube->bindParam(':idTime', $idTime);
$stmtMariaClube->execute();
$mariaClube = $stmtMariaClube->fetch(PDO::FETCH_ASSOC);

if ($mariaClube) {
    $nomeTime = $mariaClube['NomeClube'];
    $donoTime = intval($mariaClube['idDonoPais']);
} else {
    // Fallback básico para obter o nome do clube do SQLite caso não seja um ID do Portal
    $stmtClube = $sdb->prepare("SELECT Nome FROM clube WHERE ID = :id");
    $stmtClube->bindParam(':id', $idTime);
    $stmtClube->execute();
    $clubeRow = $stmtClube->fetch(PDO::FETCH_ASSOC);
    if ($clubeRow) {
        $nomeTime = $clubeRow['Nome'];
    }
}

// Simulamos a variável clube antiga contendo apenas o Nome
$clube = ['Nome' => $nomeTime];

// Permissão: Apenas Admin, Dono da Competição ou Dono do Time
if (!$isAdmin && $userLogado != $donoCompeticao && $userLogado != $donoTime) {
    die("Acesso negado. Apenas o responsável pelo time ou o administrador podem alterar a escalação.");
}

// Processar formulário de escalação (POST)
$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_escalacao'])) {
    // Salvar indisponíveis manuais na sessão
    $indisponiveisKey = 'indisponiveis_' . $idCompeticao . '_' . $idTime;
    $_SESSION[$indisponiveisKey] = isset($_POST['indisponiveis']) ? array_map('intval', $_POST['indisponiveis']) : [];
    // Salvar posições escolhidas na sessão
    $posicoesEscolhidasKey = 'posicoes_' . $idCompeticao . '_' . $idTime;
    $_SESSION[$posicoesEscolhidasKey] = isset($_POST['posicao_jogador']) ? $_POST['posicao_jogador'] : [];

    $titulares = isset($_POST['titulares']) ? $_POST['titulares'] : array();
    $capitao = isset($_POST['capitao']) ? intval($_POST['capitao']) : 0;
    
    if (count($titulares) !== 11) {
        $mensagem = "<div style='background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color: #fca5a5; padding: 12px 16px; border-radius:8px; margin-bottom: 15px;'>Selecione exatamente 11 titulares (selecionados: " . count($titulares) . ").</div>";
    } else {
        try {
            // Obter o registro atual da escalação para saber as posições táticas (Pos1 a Pos11)
            $stmtCurrentEsc = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
            $stmtCurrentEsc->bindParam(':clube', $idTime);
            $stmtCurrentEsc->execute();
            $currentEsc = $stmtCurrentEsc->fetch(PDO::FETCH_ASSOC);

            if (!$currentEsc) {
                throw new Exception("Escalação do clube não encontrada no banco de dados.");
            }

            // Mapeamento de posições abreviadas para colunas na tabela posicaojogador
            $posMap = [
                'G' => 'Goleiro',
                'Z' => 'Zagueiro',
                'LD' => 'LateralDireito',
                'LE' => 'LateralEsquerdo',
                'V' => 'Volante',
                'MC' => 'MeiaCentral',
                'MD' => 'MeiaDireita',
                'ME' => 'MeiaEsquerda',
                'MA' => 'MeiaCentral',
                'A' => 'Atacante',
                'Aa' => 'Atacante',
                'PE' => 'Atacante',
                'PD' => 'Atacante',
                'AD' => 'LateralDireito',
                'AE' => 'LateralEsquerdo',
            ];

            // Obter as posições preferidas dos titulares selecionados
            $playersPositions = [];
            $inClause = implode(',', array_map('intval', $titulares));
            $stmtPos = $sdb->query("SELECT * FROM posicaojogador WHERE Jogador IN ($inClause)");
            while ($row = $stmtPos->fetch(PDO::FETCH_ASSOC)) {
                $pId = (int)$row['Jogador'];
                $playersPositions[$pId] = [];
                if ($row['Goleiro'] == 1) $playersPositions[$pId][] = 'Goleiro';
                if ($row['Zagueiro'] == 1) $playersPositions[$pId][] = 'Zagueiro';
                if ($row['LateralDireito'] == 1) $playersPositions[$pId][] = 'LateralDireito';
                if ($row['LateralEsquerdo'] == 1) $playersPositions[$pId][] = 'LateralEsquerdo';
                if ($row['Volante'] == 1) $playersPositions[$pId][] = 'Volante';
                if ($row['MeiaCentral'] == 1) $playersPositions[$pId][] = 'MeiaCentral';
                if ($row['MeiaDireita'] == 1) $playersPositions[$pId][] = 'MeiaDireita';
                if ($row['MeiaEsquerda'] == 1) $playersPositions[$pId][] = 'MeiaEsquerda';
                if ($row['Atacante'] == 1) $playersPositions[$pId][] = 'Atacante';
            }

            // Garantir que todos tenham alguma entrada no array
            foreach ($titulares as $tId) {
                if (!isset($playersPositions[$tId])) {
                    $playersPositions[$tId] = ['Atacante'];
                }
            }

            $tacticalPositions = [];
            for ($i = 1; $i <= 11; $i++) {
                $tacticalPositions[$i] = $currentEsc['Pos' . $i];
            }

            $assigned = [];
            $playerUsed = [];

            // Função recursiva para fazer o matching dos jogadores com as posições táticas
            function assignPositions($posIdx, $tacticalPositions, $posMap, $playersPositions, &$assigned, &$playerUsed) {
                if ($posIdx > 11) {
                    return true;
                }
                
                $tacticalPos = $tacticalPositions[$posIdx];
                $preferredCol = isset($posMap[$tacticalPos]) ? $posMap[$tacticalPos] : 'Atacante';
                
                // Tenta atribuir um jogador livre que tenha essa posição ativada
                foreach ($playersPositions as $pId => $activeCols) {
                    if (!isset($playerUsed[$pId]) && in_array($preferredCol, $activeCols)) {
                        $playerUsed[$pId] = true;
                        $assigned[$posIdx] = $pId;
                        if (assignPositions($posIdx + 1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed)) {
                            return true;
                        }
                        unset($playerUsed[$pId]);
                        unset($assigned[$posIdx]);
                    }
                }
                
                // Fallback: atribui qualquer jogador livre para essa posição
                foreach (array_keys($playersPositions) as $pId) {
                    if (!isset($playerUsed[$pId])) {
                        $playerUsed[$pId] = true;
                        $assigned[$posIdx] = $pId;
                        if (assignPositions($posIdx + 1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed)) {
                            return true;
                        }
                        unset($playerUsed[$pId]);
                        unset($assigned[$posIdx]);
                    }
                }
                
                return false;
            }

            assignPositions(1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed);

            if (!in_array($capitao, $titulares)) {
                $capitao = $titulares[0];
            }

            // Preservar os pênaltis apenas se os jogadores de pênalti ainda estiverem entre os titulares selecionados
            $pen1 = isset($currentEsc['Penalti1']) && in_array($currentEsc['Penalti1'], $titulares) ? $currentEsc['Penalti1'] : $capitao;
            $pen2 = isset($currentEsc['Penalti2']) && in_array($currentEsc['Penalti2'], $titulares) ? $currentEsc['Penalti2'] : $capitao;
            $pen3 = isset($currentEsc['Penalti3']) && in_array($currentEsc['Penalti3'], $titulares) ? $currentEsc['Penalti3'] : $capitao;

            // Construir e executar o UPDATE na tabela escalacao do SQLite
            $updateFields = [];
            for ($i = 1; $i <= 11; $i++) {
                $updateFields[] = "Jogador{$i} = :jog{$i}";
            }
            $updateFields[] = "Capitao = :capitao";
            $updateFields[] = "Penalti1 = :pen1";
            $updateFields[] = "Penalti2 = :pen2";
            $updateFields[] = "Penalti3 = :pen3";

            $stmtUpdate = $sdb->prepare("UPDATE escalacao SET " . implode(', ', $updateFields) . " WHERE Clube = :clube");
            for ($i = 1; $i <= 11; $i++) {
                $stmtUpdate->bindValue(':jog' . $i, $assigned[$i], PDO::PARAM_INT);
            }
            $stmtUpdate->bindValue(':capitao', $capitao, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':pen1', $pen1, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':pen2', $pen2, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':pen3', $pen3, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':clube', $idTime, PDO::PARAM_INT);
            $stmtUpdate->execute();
            
            $mensagem = "<div style='color: #10b981; margin-bottom: 15px;'>Escalação salva com sucesso!</div>";
        } catch (Exception $e) {
            $mensagem = "<div style='color: #ef4444; margin-bottom: 15px;'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
    }
}

// 1. Obter os jogadores vinculados a este Clube pela tabela elenco
$stmtElencoIds = $sdb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
$stmtElencoIds->bindParam(':clube', $idTime);
$stmtElencoIds->execute();
$elencoRow = $stmtElencoIds->fetch(PDO::FETCH_ASSOC);

$playerIds = [];
if ($elencoRow) {
    for ($i = 1; $i <= 23; $i++) {
        if (!empty($elencoRow['Jogador' . $i])) {
            $playerIds[] = (int)$elencoRow['Jogador' . $i];
        }
    }
}

// 2. Obter titulares e capitão atuais pela tabela escalacao
$stmtEscalacao = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
$stmtEscalacao->bindParam(':clube', $idTime);
$stmtEscalacao->execute();
$escalacaoRow = $stmtEscalacao->fetch(PDO::FETCH_ASSOC);

$titularesIds = [];
$capitaoId = 0;
if ($escalacaoRow) {
    for ($i = 1; $i <= 11; $i++) {
        if (!empty($escalacaoRow['Jogador' . $i])) {
            $titularesIds[] = (int)$escalacaoRow['Jogador' . $i];
        }
    }
    $capitaoId = (int)$escalacaoRow['Capitao'];
}

// 3. Obter status de lesão (global na tabela jogador) e suspensão (tabela competicao_suspensos) do MariaDB
$statusMaria = [];
if (!empty($playerIds)) {
    $inClause = implode(',', array_map('intval', $playerIds));
    $queryStatus = "SELECT j.ID, 
                           IF(j.lesionado_ate IS NOT NULL AND j.lesionado_ate >= CURDATE(), 1, 0) as lesionado,
                           COALESCE(cs.suspenso, 0) as suspenso 
                    FROM jogador j 
                    LEFT JOIN competicao_suspensos cs ON j.ID = cs.id_jogador AND cs.id_competicao = :comp
                    WHERE j.ID IN ($inClause)";
    $stmtStatus = $db->prepare($queryStatus);
    $stmtStatus->bindParam(':comp', $idCompeticao, PDO::PARAM_INT);
    $stmtStatus->execute();
    while ($row = $stmtStatus->fetch(PDO::FETCH_ASSOC)) {
        $statusMaria[(int)$row['ID']] = [
            'lesionado' => (int)$row['lesionado'],
            'suspenso' => (int)$row['suspenso']
        ];
    }
}

// 4. Buscar os dados dos jogadores do elenco no SQLite
$elenco = [];
if (!empty($playerIds)) {
    $inClause = implode(',', $playerIds);
    // Busca Nome, Nivel e StringPosicoes do MariaDB (posicaojogador não existe; posições ficam em StringPosicoes)
    $queryElenco = "SELECT ID, Nome, Nivel, StringPosicoes FROM jogador WHERE ID IN ({$inClause})";
    $stmtElenco = $db->prepare($queryElenco);
    $stmtElenco->execute();
    $elencoRaw = $stmtElenco->fetchAll(PDO::FETCH_ASSOC);

    // Mapeamento de índice (1-based) de StringPosicoes → sigla
    $posMapIdx = [1=>'G',2=>'LD',3=>'LE',4=>'Z',5=>'AD',6=>'AE',7=>'V',8=>'MD',9=>'ME',10=>'MC',11=>'PD',12=>'PE',13=>'MA',14=>'Am',15=>'Aa'];
    foreach ($elencoRaw as &$row) {
        $sp = $row['StringPosicoes'] ?? '';
        $posicoesDisponiveis = [];
        for ($pi = 1; $pi <= 15; $pi++) {
            if (isset($sp[$pi-1]) && $sp[$pi-1] === '1') {
                $posicoesDisponiveis[] = $posMapIdx[$pi];
            }
        }
        if (empty($posicoesDisponiveis)) $posicoesDisponiveis = ['A'];
        $row['PosicaoBase'] = $posicoesDisponiveis[0];
        $row['PosicoesDisponiveis'] = $posicoesDisponiveis;
    }
    unset($row);

    // Indisponíveis manuais: vêm do POST ou de cookie de sessão por time
    $indisponiveisKey = 'indisponiveis_' . $idCompeticao . '_' . $idTime;
    $indisponiveisManual = isset($_SESSION[$indisponiveisKey]) ? $_SESSION[$indisponiveisKey] : [];
    // Posições escolhidas manualmente
    $posicoesEscolhidasKey = 'posicoes_' . $idCompeticao . '_' . $idTime;
    $posicoesEscolhidas = isset($_SESSION[$posicoesEscolhidasKey]) ? $_SESSION[$posicoesEscolhidasKey] : [];

    foreach ($elencoRaw as $row) {
        $pId = (int)$row['ID'];
        $row['Titular'] = in_array($pId, $titularesIds) ? 1 : 0;
        $row['Capitao'] = ($pId === $capitaoId) ? 1 : 0;
        $row['Lesionado'] = isset($statusMaria[$pId]) ? $statusMaria[$pId]['lesionado'] : 0;
        $row['Suspenso'] = isset($statusMaria[$pId]) ? $statusMaria[$pId]['suspenso'] : 0;
        $row['IndisponiveisManual'] = in_array($pId, $indisponiveisManual) ? 1 : 0;
        // Posição escolhida pelo usuário para este jogador
        $row['PosicaoEscolhida'] = isset($posicoesEscolhidas[$pId]) ? $posicoesEscolhidas[$pId] : $row['PosicaoBase'];
        $elenco[] = $row;
    }

    // Ordenar elenco: Titular primeiro, depois por Nível decrescente
    usort($elenco, function($a, $b) {
        if ($a['Titular'] != $b['Titular']) {
            return $b['Titular'] - $a['Titular'];
        }
        return $b['Nivel'] - $a['Nivel'];
    });
}

$page_title = "Escalação Pré-Jogo - " . $clube['Nome'];
$css_filename = "home_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
require_once $_SERVER['DOCUMENT_ROOT'] . "/elements/header.php";
?>

<main style="padding-top: 80px; padding-bottom: 60px; background: linear-gradient(135deg, #0f0f2e 0%, #090d16 100%);">
<div style="padding: 20px; color: #e2e8f0; max-width: 1100px; margin: 0 auto;">
    <h2 style="margin-bottom: 5px; font-family: 'Kanit', sans-serif; color: #fff; font-size: 1.6rem;">⚽ Escalação Pré-Jogo: <?php echo htmlspecialchars($clube['Nome']); ?></h2>
    <p style="color: #94a3b8; margin-bottom: 20px;">Selecione os 11 titulares, o capitão e as posições antes da simulação.</p>
    
    <?php echo $mensagem; ?>

    <form method="POST">
        <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 20px; margin-bottom: 20px; backdrop-filter: blur(12px); overflow-x: auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; color: #fbbf24; font-family: 'Kanit', sans-serif;">Elenco do Clube</h3>
                <span id="contador-titulares" style="background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.4); color: #a5b4fc; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">0/11 titulares selecionados</span>
            </div>
            <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 700px;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.12); color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 8px 10px;">Titular</th>
                        <th style="padding: 8px 10px;">Cap.</th>
                        <th style="padding: 8px 10px; min-width: 160px;">Jogador</th>
                        <th style="padding: 8px 10px; min-width: 120px;">Posição</th>
                        <th style="padding: 8px 10px;">Nível</th>
                        <th style="padding: 8px 10px;">Indisp.</th>
                        <th style="padding: 8px 10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elenco as $j): 
                        $isDesfalqueAuto = ($j['Suspenso'] == 1 || $j['Lesionado'] == 1);
                        $isIndisp = $j['IndisponiveisManual'] == 1;
                        $isBloqueado = $isDesfalqueAuto || $isIndisp;
                        $statusBadge = "";
                        if ($j['Suspenso'] == 1) {
                            $statusBadge .= "<span style='background: #ef4444; color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>SUSPENSO</span>";
                        }
                        if ($j['Lesionado'] == 1) {
                            $statusBadge .= "<span style='background: #f59e0b; color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>LESIONADO</span>";
                        }
                        if (!$isDesfalqueAuto && !$isIndisp) {
                            $statusBadge = "<span style='background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>DISPONÍVEL</span>";
                        }
                        if ($isIndisp && !$isDesfalqueAuto) {
                            $statusBadge = "<span style='background: rgba(100,116,139,0.3); color: #94a3b8; border: 1px solid rgba(100,116,139,0.4); padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>INDISP.</span>";
                        }
                        $rowOpacity = $isBloqueado ? 'opacity:0.45;' : '';
                    ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); <?php echo $rowOpacity; ?>">
                            <td style="padding: 8px 10px; text-align: center;">
                                <input type="checkbox" name="titulares[]" value="<?php echo $j['ID']; ?>"
                                    <?php echo ($j['Titular'] == 1 && !$isBloqueado) ? 'checked' : ''; ?>
                                    <?php echo $isBloqueado ? 'disabled' : ''; ?>
                                    class="chk-titular" data-desfalque="<?php echo $isBloqueado ? '1' : '0'; ?>">
                            </td>
                            <td style="padding: 8px 10px; text-align: center;">
                                <input type="radio" name="capitao" value="<?php echo $j['ID']; ?>"
                                    <?php echo ($j['Capitao'] == 1 && !$isBloqueado) ? 'checked' : ''; ?>
                                    <?php echo $isBloqueado ? 'disabled' : ''; ?>>
                            </td>
                            <td style="padding: 8px 10px; font-weight: 600; color: #e2e8f0;">
                                <?php echo htmlspecialchars($j['Nome']); ?>
                            </td>
                            <td style="padding: 8px 10px;">
                                <?php if (!$isBloqueado && count($j['PosicoesDisponiveis']) > 1): ?>
                                    <select name="posicao_jogador[<?php echo $j['ID']; ?>]"
                                        style="background: rgba(30,41,59,0.9); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 4px 8px; font-size: 0.82rem; width: 100%;">
                                        <?php foreach ($j['PosicoesDisponiveis'] as $pos): ?>
                                            <option value="<?php echo $pos; ?>" <?php echo ($j['PosicaoEscolhida'] === $pos) ? 'selected' : ''; ?>>
                                                <?php echo $pos; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.85rem;"><?php echo htmlspecialchars($j['PosicaoBase']); ?></span>
                                    <input type="hidden" name="posicao_jogador[<?php echo $j['ID']; ?>]" value="<?php echo htmlspecialchars($j['PosicaoBase']); ?>">
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 10px; color: #fbbf24; font-weight: bold; text-align: center;">
                                <?php echo $j['Nivel']; ?>
                            </td>
                            <td style="padding: 8px 10px; text-align: center;">
                                <?php if (!$isDesfalqueAuto): ?>
                                    <input type="checkbox" name="indisponiveis[]" value="<?php echo $j['ID']; ?>"
                                        <?php echo $isIndisp ? 'checked' : ''; ?>
                                        class="chk-indisp" title="Marcar como indisponível para este jogo"
                                        style="cursor:pointer; accent-color: #ef4444;">
                                <?php else: ?>
                                    <span style="color: #475569;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 10px;">
                                <?php echo $statusBadge; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 4px;">
            <button type="submit" name="salvar_escalacao"
                style="width: fit-content; white-space: nowrap; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; letter-spacing: 0.3px;">
                💾 Salvar Escalação
            </button>
            <a href="/competicoes/listajogos.php?id=<?php echo $idCompeticao; ?>"
                style="display: inline-block; width: fit-content; white-space: nowrap; padding: 12px 20px; background: rgba(255,255,255,0.07); color: #94a3b8; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.95rem;">
                ← Voltar para Jogos
            </a>
        </div>
    </form>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function atualizarLimite() {
        var marcados = document.querySelectorAll('.chk-titular:checked').length;
        document.querySelectorAll('.chk-titular').forEach(function(chk) {
            if (chk.dataset.desfalque === '1') return;
            chk.disabled = (!chk.checked && marcados >= 11);
        });
        var contador = document.getElementById('contador-titulares');
        if (contador) {
            contador.textContent = marcados + '/11 titulares selecionados';
            contador.style.background = marcados === 11 ? 'rgba(16,185,129,0.2)' : 'rgba(99,102,241,0.2)';
            contador.style.borderColor = marcados === 11 ? 'rgba(16,185,129,0.4)' : 'rgba(99,102,241,0.4)';
            contador.style.color = marcados === 11 ? '#6ee7b7' : '#a5b4fc';
        }
    }
    document.querySelectorAll('.chk-titular').forEach(function(chk) {
        chk.addEventListener('change', atualizarLimite);
    });

    // Indisponível manual: ao marcar, desabilita checkbox de titular e capitão da linha
    document.querySelectorAll('.chk-indisp').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var row = chk.closest('tr');
            var chkTitular = row.querySelector('.chk-titular');
            var radioCapitao = row.querySelector('input[type=radio]');
            if (chk.checked) {
                if (chkTitular && chkTitular.checked) chkTitular.checked = false;
                if (chkTitular) { chkTitular.disabled = true; chkTitular.dataset.desfalque = '1'; }
                if (radioCapitao) radioCapitao.disabled = true;
                row.style.opacity = '0.45';
            } else {
                if (chkTitular) { chkTitular.disabled = false; chkTitular.dataset.desfalque = '0'; }
                if (radioCapitao) radioCapitao.disabled = false;
                row.style.opacity = '1';
            }
            atualizarLimite();
        });
    });

    atualizarLimite();
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php"; ?>
