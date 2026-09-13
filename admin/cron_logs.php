<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['admin_status'] !== 1) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
$dbObj = new Database();
$db = $dbObj->getConnection();

$rootPath = dirname(__DIR__);
$logFiles = [
    'exec' => [
        'name' => 'cron_exec.log (Log Estruturado do PHP)',
        'path' => $rootPath . '/competicoes/cron_exec.log',
        'desc' => 'Histórico detalhado de disparos do script PHP, runtime, versão do PHP e partidas processadas.'
    ],
    'output' => [
        'name' => 'cron_output.log (Saída do Terminal/Cron)',
        'path' => $rootPath . '/competicoes/cron_output.log',
        'desc' => 'Saída padrão (stdout/stderr) redirecionada pelo agendador do cPanel/Linux.'
    ],
    'engine' => [
        'name' => 'simulation_debug.log (Engine Java)',
        'path' => $rootPath . '/competicoes/hexacolor/simulation_debug.log',
        'desc' => 'Log de comandos brutos e respostas diretas do motor de simulação HexacolorYMTv2.jar.'
    ]
];

$selectedLog = $_GET['log'] ?? 'exec';
if (!isset($logFiles[$selectedLog])) {
    $selectedLog = 'exec';
}

$feedbackMsg = '';
$feedbackType = '';

// AJAX: Executar simulação agora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_POST['ajax_action'] === 'run_simulation') {
        ob_start();
        $cronScript = $rootPath . '/competicoes/cron_simular_jogos.php';
        if (file_exists($cronScript)) {
            // Executar via CLI se possível ou require com buffer
            $output = '';
            if (function_exists('shell_exec')) {
                $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $phpBin = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
                $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($cronScript) . ' 2>&1';
                $output = (string)shell_exec($cmd);
            }
            if (empty($output)) {
                $_GET['cron_key'] = 'admin_manual_' . time();
                include $cronScript;
                $output = ob_get_clean();
            } else {
                ob_end_clean();
            }
            echo json_encode([
                'success' => true,
                'output' => $output,
                'time' => date('d/m/Y H:i:s')
            ]);
        } else {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Arquivo cron_simular_jogos.php não encontrado.'
            ]);
        }
        exit;
    }
    
    if ($_POST['ajax_action'] === 'get_log') {
        $targetKey = $_POST['log_key'] ?? 'exec';
        if (isset($logFiles[$targetKey]) && file_exists($logFiles[$targetKey]['path'])) {
            $content = file_get_contents($logFiles[$targetKey]['path']);
            echo json_encode([
                'success' => true,
                'content' => $content,
                'size' => filesize($logFiles[$targetKey]['path']),
                'modified' => date('d/m/Y H:i:s', filemtime($logFiles[$targetKey]['path']))
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'content' => '(Arquivo de log vazio ou ainda não criado)',
                'size' => 0,
                'modified' => '-'
            ]);
        }
        exit;
    }
    exit;
}

// Ação de Limpar Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_log') {
    $targetKey = $_POST['log_key'] ?? '';
    if (isset($logFiles[$targetKey])) {
        $filePath = $logFiles[$targetKey]['path'];
        if (file_exists($filePath)) {
            if (@file_put_contents($filePath, '') !== false) {
                $feedbackMsg = 'Arquivo ' . $logFiles[$targetKey]['name'] . ' foi limpo com sucesso!';
                $feedbackType = 'success';
            } else {
                $feedbackMsg = 'Não foi possível limpar o arquivo. Verifique as permissões.';
                $feedbackType = 'danger';
            }
        } else {
            @file_put_contents($filePath, '');
            $feedbackMsg = 'Arquivo de log criado e limpo!';
            $feedbackType = 'success';
        }
    }
}

// Estatísticas do Banco de Dados
$totalPendentes24h = 0;
$totalPendentesGeral = 0;
$totalSimulados = 0;
$proximoJogoData = null;

try {
    $stmt1 = $db->query("SELECT COUNT(*) FROM jogos_clube WHERE status = 0 AND simulador_interno = 1 AND data <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
    $totalPendentes24h = (int)$stmt1->fetchColumn();

    $stmt2 = $db->query("SELECT COUNT(*) FROM jogos_clube WHERE status = 0 AND simulador_interno = 1");
    $totalPendentesGeral = (int)$stmt2->fetchColumn();

    $stmt3 = $db->query("SELECT COUNT(*) FROM jogos_clube WHERE status = 1 AND simulador_interno = 1");
    $totalSimulados = (int)$stmt3->fetchColumn();

    $stmt4 = $db->query("SELECT MIN(data) FROM jogos_clube WHERE status = 0 AND simulador_interno = 1");
    $proximoJogoData = $stmt4->fetchColumn();
} catch (\Throwable $e) {}

// Carregar conteúdo do log selecionado
$currentFilePath = $logFiles[$selectedLog]['path'];
$logContent = '';
$logSize = 0;
$logModified = '-';

if (file_exists($currentFilePath)) {
    $logContent = (string)file_get_contents($currentFilePath);
    $logSize = filesize($currentFilePath);
    $logModified = date('d/m/Y H:i:s', filemtime($currentFilePath));
}

$page_title = "Cron de Simulação - Logs & Monitoramento";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container" style="max-width: 1200px !important;">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="admin-gradient-title">Cron de Simulação de Jogos</h1>
            <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Monitore as execuções automáticas, analise a saída da engine Java e execute simulações manuais.</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button id="btnRunSimulation" class="admin-btn admin-btn-primary" style="background: linear-gradient(135deg, #0284c7, #0369a1); font-weight: 600;">
                <span class="material-symbols-outlined">play_circle</span>
                Simular Agora (Manual)
            </button>
            <button id="btnRefreshLog" class="admin-btn admin-btn-secondary">
                <span class="material-symbols-outlined">refresh</span>
                Atualizar Log
            </button>
        </div>
    </div>

    <?php if (!empty($feedbackMsg)): ?>
        <div class="alert alert-<?= $feedbackType ?>" style="margin-bottom: 20px;">
            <?= htmlspecialchars($feedbackMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Modal/Box de Execução ao Vivo -->
    <div id="liveOutputBox" style="display: none; background: #090d16; border: 1px solid #38bdf8; border-radius: 10px; padding: 20px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; color: #38bdf8; font-weight: 600;">
                <span class="material-symbols-outlined" style="animation: spin 1.2s linear infinite;">sync</span>
                <span id="liveOutputTitle">Executando Simulação Manual...</span>
            </div>
            <button type="button" onclick="document.getElementById('liveOutputBox').style.display='none';" class="admin-btn admin-btn-secondary" style="padding: 4px 10px; font-size: 12px;">
                Fechar
            </button>
        </div>
        <pre id="liveOutputContent" style="background: rgba(0,0,0,0.5); color: #a5f3fc; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; max-height: 280px; overflow-y: auto; white-space: pre-wrap; margin: 0;"></pre>
    </div>

    <!-- Cards de Estatísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 25px;">
        <div style="background: rgba(255, 255, 255, 0.03); padding: 18px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #f59e0b;">
            <div style="color: #94a3b8; font-size: 13px; font-weight: 500;">Próximas 24 Horas</div>
            <div style="font-size: 26px; font-weight: 700; color: #f59e0b; margin-top: 4px;"><?= $totalPendentes24h ?> jogo(s)</div>
            <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Alvo imediato do Cron</div>
        </div>

        <div style="background: rgba(255, 255, 255, 0.03); padding: 18px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #38bdf8;">
            <div style="color: #94a3b8; font-size: 13px; font-weight: 500;">Total Pendentes</div>
            <div style="font-size: 26px; font-weight: 700; color: #38bdf8; margin-top: 4px;"><?= $totalPendentesGeral ?> jogo(s)</div>
            <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Com simulador interno ativo</div>
        </div>

        <div style="background: rgba(255, 255, 255, 0.03); padding: 18px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #10b981;">
            <div style="color: #94a3b8; font-size: 13px; font-weight: 500;">Total Concluídos</div>
            <div style="font-size: 26px; font-weight: 700; color: #10b981; margin-top: 4px;"><?= $totalSimulados ?> jogo(s)</div>
            <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Já simulados com sucesso</div>
        </div>

        <div style="background: rgba(255, 255, 255, 0.03); padding: 18px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #818cf8;">
            <div style="color: #94a3b8; font-size: 13px; font-weight: 500;">Próxima Partida na Fila</div>
            <div style="font-size: 15px; font-weight: 700; color: #f8fafc; margin-top: 8px;">
                <?= $proximoJogoData ? date('d/m/Y H:i', strtotime($proximoJogoData)) : 'Nenhuma pendente' ?>
            </div>
            <div style="color: #64748b; font-size: 12px; margin-top: 4px;">Data agendada no sistema</div>
        </div>
    </div>

    <!-- Navegação por Abas dos Logs -->
    <div style="display: flex; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; overflow-x: auto; padding-bottom: 2px;">
        <?php foreach ($logFiles as $k => $f): ?>
            <a href="?log=<?= $k ?>" class="admin-btn <?= $selectedLog === $k ? 'admin-btn-primary' : 'admin-btn-secondary' ?>" style="text-decoration: none; border-bottom-left-radius: 0; border-bottom-right-radius: 0; font-size: 13px; padding: 10px 16px;">
                <span class="material-symbols-outlined" style="font-size: 18px;">description</span>
                <?= htmlspecialchars($f['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Header do Log Ativo -->
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px 8px 0 0; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="font-weight: 600; color: #f8fafc; font-size: 14px;">
                <?= htmlspecialchars($logFiles[$selectedLog]['name']) ?>
            </div>
            <div style="color: #94a3b8; font-size: 12px; margin-top: 2px;">
                <?= htmlspecialchars($logFiles[$selectedLog]['desc']) ?>
            </div>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <span style="font-size: 12px; color: #94a3b8;">
                Modificado: <strong id="logModifiedBadge" style="color: #cbd5e1;"><?= $logModified ?></strong> | 
                Tamanho: <strong id="logSizeBadge" style="color: #cbd5e1;"><?= number_format($logSize / 1024, 2) ?> KB</strong>
            </span>
            <form method="POST" onsubmit="return confirm('Deseja realmente limpar este arquivo de log?');" style="margin: 0;">
                <input type="hidden" name="action" value="clear_log">
                <input type="hidden" name="log_key" value="<?= htmlspecialchars($selectedLog) ?>">
                <button type="submit" class="admin-btn admin-btn-danger" style="padding: 6px 12px; font-size: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">delete_sweep</span>
                    Limpar
                </button>
            </form>
        </div>
    </div>

    <!-- Barra de Filtro / Busca no Log -->
    <div style="background: #090d16; border-left: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.08); padding: 10px 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 200px;">
            <span class="material-symbols-outlined" style="color: #64748b; font-size: 20px;">search</span>
            <input type="text" id="logSearchInput" placeholder="Filtrar linhas do log (ex: SUCESSO, ERRO, Partida #, PHP)..." class="admin-input" style="padding: 6px 12px; font-size: 13px; width: 100%;">
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <label style="color: #94a3b8; font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                <input type="checkbox" id="chkAutoScroll" checked> Rolar ao final
            </label>
        </div>
    </div>

    <!-- Terminal Viewer -->
    <div id="terminalContainer" style="background: #06090e; border: 1px solid rgba(255,255,255,0.08); border-top: none; border-radius: 0 0 8px 8px; padding: 20px; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; line-height: 1.6; max-height: 520px; overflow-y: auto; color: #cbd5e1;">
        <pre id="terminalContent" style="margin: 0; white-space: pre-wrap; word-break: break-all; font-family: inherit; font-size: inherit; color: inherit;"><?= !empty($logContent) ? htmlspecialchars($logContent) : '(Arquivo de log vazio ou ainda não criado)' ?></pre>
    </div>

    <div style="margin-top: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; justify-content: center; gap: 15px;">
        <a href="/admin/index.php" class="admin-btn admin-btn-secondary">Voltar ao Painel Admin</a>
    </div>
</div>

<style id="spin-style">
@keyframes spin { 100% { transform: rotate(-360deg); } }
.log-line-success { color: #34d399 !important; font-weight: 600; }
.log-line-error { color: #f87171 !important; font-weight: 600; }
.log-line-header { color: #38bdf8 !important; font-weight: 700; border-bottom: 1px dashed rgba(56, 189, 248, 0.3); }
.log-line-info { color: #93c5fd !important; }
.log-line-highlight { background: rgba(245, 158, 11, 0.2); color: #fbbf24 !important; }
</style>

<script>
(function() {
    const selectedLogKey = <?= json_encode($selectedLog) ?>;
    const terminalContainer = document.getElementById('terminalContainer');
    const terminalContent = document.getElementById('terminalContent');
    const searchInput = document.getElementById('logSearchInput');
    const chkAutoScroll = document.getElementById('chkAutoScroll');
    const btnRefresh = document.getElementById('btnRefreshLog');
    const btnRun = document.getElementById('btnRunSimulation');
    
    let rawLogText = terminalContent.textContent;

    function formatTerminal(text) {
        if (!text || text.trim() === '(Arquivo de log vazio ou ainda não criado)') {
            terminalContent.innerHTML = '<span style="color: #64748b;">(Arquivo de log vazio ou ainda não criado)</span>';
            return;
        }

        const filter = searchInput.value.trim().toLowerCase();
        const lines = text.split('\n');
        let html = '';

        for (let line of lines) {
            if (filter && !line.toLowerCase().includes(filter)) {
                continue;
            }

            let escaped = line.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            
            if (line.includes('[SUCESSO]')) {
                html += `<div class="log-line-success">${escaped}</div>`;
            } else if (line.includes('[ERRO]') || line.includes('[BLOQUEIO 403]') || line.includes('Fatal error') || line.includes('Exception')) {
                html += `<div class="log-line-error">${escaped}</div>`;
            } else if (line.includes('=== DISPARO DO CRON')) {
                html += `<div class="log-line-header">${escaped}</div>`;
            } else if (line.includes('[LIVE]') || line.includes('[MATA-MATA CRON]')) {
                html += `<div class="log-line-info">${escaped}</div>`;
            } else {
                html += `<div>${escaped}</div>`;
            }
        }

        terminalContent.innerHTML = html || '<span style="color: #64748b;">Nenhuma linha corresponde ao filtro de busca.</span>';

        if (chkAutoScroll.checked) {
            terminalContainer.scrollTop = terminalContainer.scrollHeight;
        }
    }

    // Inicializar formatação
    formatTerminal(rawLogText);

    // Filtro em tempo real
    searchInput.addEventListener('input', function() {
        formatTerminal(rawLogText);
    });

    // Atualizar log via AJAX
    function refreshLog() {
        btnRefresh.disabled = true;
        btnRefresh.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Atualizando...';

        const fd = new FormData();
        fd.append('ajax_action', 'get_log');
        fd.append('log_key', selectedLogKey);

        fetch('/admin/cron_logs.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    rawLogText = res.content;
                    formatTerminal(rawLogText);
                    document.getElementById('logModifiedBadge').textContent = res.modified;
                    document.getElementById('logSizeBadge').textContent = (res.size / 1024).toFixed(2) + ' KB';
                }
            })
            .catch(err => console.error('Erro ao atualizar log:', err))
            .finally(() => {
                btnRefresh.disabled = false;
                btnRefresh.innerHTML = '<span class="material-symbols-outlined">refresh</span> Atualizar Log';
            });
    }

    btnRefresh.addEventListener('click', refreshLog);

    // Disparar Simulação Manual
    btnRun.addEventListener('click', function() {
        if (!confirm('Deseja iniciar a simulação manual de partidas agora?')) return;

        btnRun.disabled = true;
        btnRun.innerHTML = '<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Simulando...';

        const box = document.getElementById('liveOutputBox');
        const title = document.getElementById('liveOutputTitle');
        const out = document.getElementById('liveOutputContent');
        
        box.style.display = 'block';
        title.textContent = 'Simulação Manual em Andamento...';
        out.textContent = 'Disparando motor de simulação e conectando aos bancos SQLite/MariaDB...\nAguarde alguns segundos...\n';

        const fd = new FormData();
        fd.append('ajax_action', 'run_simulation');

        fetch('/admin/cron_logs.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    title.textContent = 'Simulação Concluída (' + res.time + ')';
                    out.textContent = res.output || 'Simulação executada com sucesso (nenhuma saída gerada).';
                    refreshLog();
                } else {
                    title.textContent = 'Falha ao Executar Simulação';
                    out.textContent = 'Erro: ' + (res.error || 'Erro desconhecido');
                }
            })
            .catch(err => {
                title.textContent = 'Erro na Requisição';
                out.textContent = 'Erro de comunicação: ' + err.message;
            })
            .finally(() => {
                btnRun.disabled = false;
                btnRun.innerHTML = '<span class="material-symbols-outlined">play_circle</span> Simular Agora (Manual)';
            });
    });
})();
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>