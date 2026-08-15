<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['admin_status'] !== '1') {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/scraper.php';

$scraper = new PoltronaScraper();
$db = new Database();
$conn = $db->getConnection();

// Fetch last run details
$stmtLast = $conn->query("SELECT * FROM poltrona_scraper_runs ORDER BY id DESC LIMIT 1");
$lastRun = $stmtLast->fetch(PDO::FETCH_ASSOC);

// Fetch recent 10 runs
$stmtRecent = $conn->query("SELECT * FROM poltrona_scraper_runs ORDER BY id DESC LIMIT 10");
$recentRuns = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

// Count matches
$stmtMatches = $conn->query("SELECT status, COUNT(*) as qty FROM poltrona_matches GROUP BY status");
$matchCounts = [];
while ($row = $stmtMatches->fetch(PDO::FETCH_ASSOC)) {
    $matchCounts[$row['status']] = $row['qty'];
}

$page_title = "PoltronaScore Scraper Admin";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<style>
body, html {
    background-color: #0f172a !important;
    background-image: none !important;
    color: #e2e8f0 !important;
}
/* Reset global H1 and layouts inherited from indexRanking.css */
h1 {
    float: none !important;
    position: static !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    text-align: left !important;
    width: auto !important;
}
h2, h3 {
    margin: 0;
    float: none !important;
    position: static !important;
    display: block !important;
    color: #f8fafc !important;
}
.admin-title {
    font-size: 32px !important;
    font-weight: 700 !important;
    background: linear-gradient(to right, #38bdf8, #818cf8) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    display: inline-block !important;
}
.admin-dashboard-container {
    max-width: 1000px;
    margin: 80px auto 40px auto;
    padding: 30px;
    font-family: 'Outfit', 'Inter', sans-serif;
    color: #e2e8f0 !important;
    background: #1e293b !important;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    border: 1px solid #334155 !important;
}
.btn-primary {
    background: linear-gradient(to right, #38bdf8, #818cf8) !important;
    border: none !important;
    color: #0f172a !important;
    padding: 12px 24px !important;
    font-weight: 700 !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    transition: transform 0.2s, opacity 0.2s !important;
    font-size: 15px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    text-decoration: none !important;
}
.btn-primary:hover {
    transform: translateY(-2px) !important;
    opacity: 0.9 !important;
}
.status-badge {
    padding: 4px 8px !important;
    border-radius: 4px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    display: inline-block !important;
}
.status-success {
    background-color: rgba(16, 185, 129, 0.2) !important;
    color: #10b981 !important;
}
.status-failure {
    background-color: rgba(239, 68, 68, 0.2) !important;
    color: #ef4444 !important;
}
.status-running {
    background-color: rgba(245, 158, 11, 0.2) !important;
    color: #f59e0b !important;
}
.table-runs {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-top: 15px !important;
    background: transparent !important;
}
.table-runs tr {
    background: transparent !important;
}
.table-runs tbody tr {
    background: transparent !important;
}
.table-runs tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.02) !important;
}
.table-runs th, .table-runs td {
    padding: 12px !important;
    text-align: left !important;
    border-bottom: 1px solid #334155 !important;
}
.table-runs th {
    color: #94a3b8 !important;
    background: transparent !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    text-transform: uppercase !important;
}
.table-runs td {
    font-size: 14px !important;
    color: #e2e8f0 !important;
    background: transparent !important;
}
</style>

<div class="admin-dashboard-container" style="max-width: 1000px; margin: 80px auto 40px auto; padding: 30px; font-family: 'Outfit', 'Inter', sans-serif; color: #e2e8f0; background: #1e293b; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); border: 1px solid #334155;">
    <div style="border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="admin-title">PoltronaScore Scraper Admin</h1>
            <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Acompanhe as execuções do robô de captura e atualize as partidas em tempo real.</p>
        </div>
        <div>
            <button id="btnRunScraper" class="btn-primary">
                <span class="material-symbols-outlined">sync</span>
                Executar Scraper Agora
            </button>
        </div>
    </div>

    <!-- Match Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #151f32; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Partidas Ao Vivo</div>
            <div style="font-size: 28px; font-weight: 700; color: #ef4444;"><?= $matchCounts['live'] ?? 0 ?></div>
        </div>
        <div style="background: #151f32; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Próximas Partidas</div>
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?= $matchCounts['next'] ?? 0 ?></div>
        </div>
        <div style="background: #151f32; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Partidas Anteriores</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?= $matchCounts['previous'] ?? 0 ?></div>
        </div>
        <div style="background: #151f32; padding: 20px; border-radius: 8px; border: 1px solid #334155;">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Última Execução</div>
            <div style="font-size: 16px; font-weight: 700; color: #f8fafc; margin-top: 12px;">
                <?php if ($lastRun): ?>
                    <?= date('d/M H:i:s', strtotime($lastRun['started_at'])) ?>
                    <span class="status-badge <?= $lastRun['success'] ? 'status-success' : 'status-failure' ?>">
                        <?= $lastRun['success'] ? 'OK' : 'ERRO' ?>
                    </span>
                <?php else: ?>
                    Nenhuma executada
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scraper History -->
    <div style="background: #151f32; padding: 24px; border-radius: 8px; border: 1px solid #334155; margin-bottom: 30px;">
        <h2 style="font-size: 20px; font-weight: 600; color: #f8fafc; margin-bottom: 15px;">Últimas 10 Execuções</h2>
        <div style="overflow-x: auto;">
            <table class="table-runs">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Duração</th>
                        <th>Itens</th>
                        <th>Status</th>
                        <th>Mensagem/Erro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRuns)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">Nenhuma execução encontrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRuns as $run): ?>
                            <tr>
                                <td>#<?= $run['id'] ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($run['started_at'])) ?></td>
                                <td><?= $run['finished_at'] ? date('H:i:s', strtotime($run['finished_at'])) : '-' ?></td>
                                <td><?= $run['duration_ms'] ? number_format($run['duration_ms'] / 1000, 2) . 's' : '-' ?></td>
                                <td><?= $run['items_found'] ?></td>
                                <td>
                                    <span class="status-badge <?= $run['success'] ? 'status-success' : 'status-failure' ?>">
                                        <?= $run['success'] ? 'Sucesso' : 'Erro' ?>
                                    </span>
                                </td>
                                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: <?= $run['success'] ? '#94a3b8' : '#ef4444' ?>;">
                                    <?= htmlspecialchars($run['error_message'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="text-align: center; border-top: 1px solid #334155; padding-top: 20px; display: flex; justify-content: center; gap: 15px;">
        <a href="/admin/index.php" style="background: #334155; color: #f8fafc; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#334155'">Voltar ao Painel Admin</a>
    </div>
</div>

<script>
document.getElementById('btnRunScraper').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Executando...';
    
    // Inject style dynamically for rotation animation
    if (!document.getElementById('spin-style')) {
        const style = document.createElement('style');
        style.id = 'spin-style';
        style.innerHTML = '@keyframes spin { 100% { transform: rotate(-360deg); } }';
        document.head.appendChild(style);
    }

    fetch('/cron/scraper.php?trigger=1')
        .then(response => {
            if (!response.ok) throw new Error('Falha na requisição');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Scraper executado com sucesso! Capturadas ' + data.items_found + ' partidas.');
            } else {
                alert('Scraper executado, mas retornou erro: ' + (data.error || 'Erro desconhecido'));
            }
            window.location.reload();
        })
        .catch(err => {
            alert('Erro de conexão ou falha ao executar scraper: ' + err.message);
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerHTML = originalText;
        });
});
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
