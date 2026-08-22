<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['admin_status'] !== 1) {
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
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="admin-gradient-title">PoltronaScore Scraper Admin</h1>
            <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Acompanhe as execuções do robô de captura e atualize as partidas em tempo real.</p>
        </div>
        <div>
            <button id="btnRunScraper" class="admin-btn admin-btn-primary">
                <span class="material-symbols-outlined">sync</span>
                Executar Scraper Agora
            </button>
        </div>
    </div>

    <!-- Match Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Partidas Ao Vivo</div>
            <div style="font-size: 28px; font-weight: 700; color: #ef4444;"><?= $matchCounts['live'] ?? 0 ?></div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Próximas Partidas</div>
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?= $matchCounts['next'] ?? 0 ?></div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Partidas Anteriores</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?= $matchCounts['previous'] ?? 0 ?></div>
        </div>
        <div style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="color: #94a3b8; font-size: 14px; margin-bottom: 8px;">Última Execução</div>
            <div style="font-size: 16px; font-weight: 700; color: #f8fafc; margin-top: 12px;">
                <?php if ($lastRun): ?>
                    <?= date('d/M H:i:s', strtotime($lastRun['started_at'])) ?>
                    <span class="admin-badge <?= $lastRun['success'] ? 'admin-badge-success' : 'admin-badge-danger' ?>" style="margin-left: 6px;">
                        <?= $lastRun['success'] ? 'OK' : 'ERRO' ?>
                    </span>
                <?php else: ?>
                    Nenhuma executada
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scraper History -->
    <div style="background: rgba(15, 23, 42, 0.4); padding: 24px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px;">
        <h2 style="font-size: 20px; font-weight: 600; color: #f8fafc; margin-bottom: 15px;">Últimas 10 Execuções</h2>
        <div class="admin-table-container" style="border: none !important; background: transparent !important; margin-bottom: 0 !important;">
            <table class="admin-table">
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
                                    <span class="admin-badge <?= $run['success'] ? 'admin-badge-success' : 'admin-badge-danger' ?>">
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

    <div style="text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; justify-content: center; gap: 15px;">
        <a href="/admin/index.php" class="admin-btn admin-btn-secondary">Voltar ao Painel Admin</a>
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
