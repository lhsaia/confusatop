<?php
/**
 * Script de Diagnóstico e Avanço de Fases de Mata-mata
 * Acesso via navegador: /competicoes/debug_avancar_matamata.php?id=SEU_ID&fase=10
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../objetos/competicao_clube.php';

header('Content-Type: text/html; charset=utf-8');

$idCompeticao = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faseAtual = isset($_GET['fase']) ? (int)$_GET['fase'] : 10; // Padrão 10 (32-avos)

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Mata-mata - CONFUSA</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0b0f19; color: #e2e8f0; padding: 25px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        h1, h2, h3 { color: #38bdf8; margin-top: 0; }
        .success { color: #34d399; font-weight: bold; }
        .danger { color: #f87171; font-weight: bold; }
        .warning { color: #fbbf24; font-weight: bold; }
        .info { color: #38bdf8; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; }
        th, td { border: 1px solid #334155; padding: 8px 12px; text-align: left; }
        th { background: #0f172a; color: #94a3b8; }
        tr:nth-child(even) { background: #182234; }
        .btn { display: inline-block; background: #0284c7; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; margin-right: 10px; }
        .btn:hover { opacity: 0.9; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; border: 1px solid #334155; overflow-x: auto; color: #a5f3fc; }
    </style>
</head>
<body>

<div class="card">
    <h1>🏆 Diagnóstico & Avanço de Mata-Mata</h1>
    <form method="GET" action="">
        <label>ID da Competição: <input type="number" name="id" value="<?= htmlspecialchars($idCompeticao) ?>" style="padding:6px; background:#0f172a; color:#fff; border:1px solid #334155; border-radius:4px;"></label>
        <label style="margin-left:15px;">Fase Atual: 
            <select name="fase" style="padding:6px; background:#0f172a; color:#fff; border:1px solid #334155; border-radius:4px;">
                <option value="10" <?= $faseAtual == 10 ? 'selected' : '' ?>>10 (32-avos de final)</option>
                <option value="9" <?= $faseAtual == 9 ? 'selected' : '' ?>>9 (16-avos de final)</option>
                <option value="3" <?= $faseAtual == 3 ? 'selected' : '' ?>>3 (Oitavas de final)</option>
                <option value="4" <?= $faseAtual == 4 ? 'selected' : '' ?>>4 (Quartas de final)</option>
                <option value="5" <?= $faseAtual == 5 ? 'selected' : '' ?>>5 (Semifinal)</option>
            </select>
        </label>
        <button type="submit" class="btn" style="border:none; cursor:pointer; margin-left:15px;">🔍 Analisar</button>
    </form>
</div>

<?php
if ($idCompeticao <= 0) {
    echo "<div class='card'><p class='warning'>⚠️ Por favor, informe o ID da competição no formulário acima.</p></div></body></html>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$competicaoObj = new Competicao_clube($db);

$nextFaseMap = [
    10 => 9,  // 32-avos -> 16-avos
    9  => 3,  // 16-avos -> Oitavas
    3  => 4,  // Oitavas -> Quartas
    4  => 5,  // Quartas -> Semifinal
    5  => 8   // Semifinal -> Final
];

$proximaFase = $nextFaseMap[$faseAtual] ?? null;

echo "<div class='card'>";
echo "<h2>1. Verificação de Dados da Competição #{$idCompeticao}</h2>";

// 1. Verificar se a competição existe
$stmtComp = $db->prepare("SELECT * FROM competicao_lista WHERE id = :id");
$stmtComp->execute([':id' => $idCompeticao]);
$comp = $stmtComp->fetch(PDO::FETCH_ASSOC);

if (!$comp) {
    echo "<p class='danger'>❌ Competição #{$idCompeticao} não foi encontrada na tabela <code>competicao_lista</code>.</p>";
} else {
    echo "<p class='success'>✅ Competição encontrada: <strong>" . htmlspecialchars($comp['nome']) . "</strong> (Tipo: {$comp['tipo']})</p>";
}

// 2. Verificar Próxima Fase
if (!$proximaFase) {
    echo "<p class='danger'>❌ Fase atual ({$faseAtual}) não possui mapeamento para próxima fase.</p>";
} else {
    echo "<p class='info'>ℹ️ Transição mapeada: Fase <strong>{$faseAtual}</strong> ➔ Próxima Fase: <strong>{$proximaFase}</strong></p>";
}

// 3. Verificar se já existem jogos na próxima fase
$stmtProxFase = $db->prepare("SELECT COUNT(*) as total FROM jogos_clube WHERE competicao_id = :id AND fase = :fase");
$stmtProxFase->execute([':id' => $idCompeticao, ':fase' => $proximaFase]);
$totProxFase = (int)$stmtProxFase->fetch(PDO::FETCH_ASSOC)['total'];

if ($totProxFase > 0) {
    echo "<p class='warning'>⚠️ <strong>Atenção:</strong> Já existem <strong>{$totProxFase}</strong> jogo(s) cadastrados na próxima fase ({$proximaFase}). Isso bloqueia a geração automática.</p>";
} else {
    echo "<p class='success'>✅ Nenhum jogo existente na próxima fase ({$proximaFase}). Liberado para criação.</p>";
}

// 4. Carregar jogos da fase atual
$stmtJogos = $db->prepare("SELECT * FROM jogos_clube WHERE competicao_id = :id AND fase = :fase ORDER BY id ASC");
$stmtJogos->execute([':id' => $idCompeticao, ':fase' => $faseAtual]);
$jogosFase = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Jogos da Fase Atual ({$faseAtual}): Total " . count($jogosFase) . "</h3>";

if (empty($jogosFase)) {
    echo "<p class='danger'>❌ Nenhum jogo encontrado na fase {$faseAtual}.</p>";
} else {
    echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time Mandante</th>
                    <th>Placar</th>
                    <th>Time Visitante</th>
                    <th>Pênaltis</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Validação</th>
                </tr>
            </thead>
            <tbody>";
    
    $todosSimulados = true;
    $tempoValido = true;
    $now = time();

    foreach ($jogosFase as $jg) {
        $simulado = ((int)$jg['status'] === 1);
        if (!$simulado) $todosSimulados = false;

        $dtMatch = !empty($jg['data']) ? strtotime($jg['data']) : $now;
        $temPenaltis = ($jg['timeA_penaltis'] !== null && $jg['timeB_penaltis'] !== null);
        $durMatchSec = $temPenaltis ? (150 * 60) : (120 * 60);
        $passouTempo = ($now >= ($dtMatch + $durMatchSec));
        if (!$passouTempo) $tempoValido = false;

        $nomeA = !empty($jg['timeA_nome']) ? $jg['timeA_nome'] : "ID " . $jg['timeA_id'];
        $nomeB = !empty($jg['timeB_nome']) ? $jg['timeB_nome'] : "ID " . $jg['timeB_id'];
        $placar = "{$jg['timeA_gols']} x {$jg['timeB_gols']}";
        $penaltis = $temPenaltis ? "({$jg['timeA_penaltis']} x {$jg['timeB_penaltis']})" : "-";

        $statusBadge = $simulado ? "<span class='success'>Concluído (1)</span>" : "<span class='danger'>Pendente (0)</span>";
        $tempoBadge = $passouTempo ? "<span class='success'>Tempo OK</span>" : "<span class='warning'>Tempo Real Incompleto</span>";

        echo "<tr>
                <td>{$jg['id']}</td>
                <td>" . htmlspecialchars($nomeA) . "</td>
                <td>{$placar}</td>
                <td>" . htmlspecialchars($nomeB) . "</td>
                <td>{$penaltis}</td>
                <td>{$statusBadge}</td>
                <td>{$jg['data']}</td>
                <td>{$tempoBadge}</td>
              </tr>";
    }
    echo "</tbody></table>";
}

echo "</div>";

// 5. Execução do Avanço
echo "<div class='card'>";
echo "<h2>2. Execução do Avanço</h2>";

if (isset($_GET['exec']) && $_GET['exec'] == '1') {
    echo "<p class='info'>⏳ Processando avanço...</p>";
    try {
        $res = $competicaoObj->verificarEAvancarMataMata($idCompeticao, $faseAtual);
        if ($res) {
            echo "<p class='success'>🎉 <strong>SUCESSO!</strong> A próxima fase ({$proximaFase}) foi gerada com êxito no banco de dados.</p>";
        } else {
            echo "<p class='danger'>❌ A função <code>verificarEAvancarMataMata</code> retornou <strong>FALSE</strong>. Verifique se algum dos requisitos acima falhou (jogos não concluídos, fase futura já existente ou falta de chave).</p>";
        }
    } catch (\Throwable $e) {
        echo "<p class='danger'>💥 <strong>ERRO FATAL:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<p>Clique no botão abaixo para tentar disparar o avanço dos confrontos:</p>";
    echo "<a href='?id={$idCompeticao}&fase={$faseAtual}&exec=1' class='btn'>🚀 Executar Avanço da Fase</a>";
}

echo "</div>";
?>

</body>
</html>
