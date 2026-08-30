<?php
declare(strict_types=1);

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['admin_status'] !== 1) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/transferNotifier.php';

$feedback_msg = '';
$feedback_type = '';
$webhook_info = '';

// Identifica configuração do webhook e site
$envWebhook = getenv('DISCORD_WEBHOOK') ?: '';
$envSiteUrl = getenv('SITE_URL') ?: 'https://confusa.top';

if (empty($envWebhook)) {
    $webhook_info = 'A variável DISCORD_WEBHOOK não foi encontrada nas variáveis de ambiente carregadas pelo .env.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customWebhook = trim($_POST['webhook_url'] ?? '');
    $targetWebhook = !empty($customWebhook) ? $customWebhook : $envWebhook;

    if (empty($targetWebhook)) {
        $feedback_msg = 'Nenhuma URL de Webhook informada ou configurada no .env.';
        $feedback_type = 'danger';
    } else {
        $nome = trim($_POST['jogador_nome'] ?? 'Jogador Exemplo');
        $tipo = trim($_POST['tipo_transferencia'] ?? 'Permanente');
        $origem = trim($_POST['origem_nome'] ?? 'Clube de Origem FC');
        $destino = trim($_POST['destino_nome'] ?? 'Clube de Destino AC');
        $valor = trim($_POST['valor'] ?? 'F$ 15.000.000 (15.0 M)');
        $data = trim($_POST['data'] ?? date('d/m/Y'));
        
        $baseSiteUrl = rtrim($_POST['site_url'] ?? $envSiteUrl, '/');
        
        $bandeira = trim($_POST['bandeira_png'] ?? '');
        if ($bandeira && strpos($bandeira, 'http') !== 0) {
            $bandeira = $baseSiteUrl . (strpos($bandeira, '/') === 0 ? '' : '/') . $bandeira;
        }

        $foto = trim($_POST['foto_jogador'] ?? '');
        if ($foto && strpos($foto, 'http') !== 0) {
            $foto = $baseSiteUrl . (strpos($foto, '/') === 0 ? '' : '/') . $foto;
        }

        $origemEscudo = trim($_POST['origem_escudo'] ?? '');
        if ($origemEscudo && strpos($origemEscudo, 'http') !== 0) {
            $origemEscudo = $baseSiteUrl . (strpos($origemEscudo, '/') === 0 ? '' : '/') . $origemEscudo;
        }

        $destinoEscudo = trim($_POST['destino_escudo'] ?? '');
        if ($destinoEscudo && strpos($destinoEscudo, 'http') !== 0) {
            $destinoEscudo = $baseSiteUrl . (strpos($destinoEscudo, '/') === 0 ? '' : '/') . $destinoEscudo;
        }

        $transferData = [
            'nome' => $nome,
            'bandeira_png' => $bandeira,
            'tipo_transferencia' => $tipo,
            'foto' => $foto,
            'origem' => $origem,
            'origem_escudo_png' => $origemEscudo,
            'destino' => $destino,
            'destino_escudo_png' => $destinoEscudo,
            'valor' => $valor,
            'data' => $data
        ];

        try {
            $notifier = new TransferNotifier($targetWebhook);
            $notifier->notify($transferData);
            $feedback_msg = 'Notificação de teste enviada com sucesso para o Discord!';
            $feedback_type = 'success';
        } catch (\Throwable $e) {
            $feedback_msg = 'Erro ao enviar notificação: ' . $e->getMessage();
            $feedback_type = 'danger';
        }
    }
}

$page_title = "Teste de Notificação do Discord";
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
            <h1 class="admin-gradient-title">Simulador de Notificação Discord</h1>
            <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Dispare embeds e testes de transferência diretamente para o webhook do Discord.</p>
        </div>
        <div>
            <a href="/admin/index.php" class="admin-btn admin-btn-secondary">
                <span class="material-symbols-outlined">arrow_back</span>
                Voltar ao Painel
            </a>
        </div>
    </div>

    <?php if (!empty($feedback_msg)): ?>
        <div class="alert alert-<?php echo $feedback_type; ?>" style="margin-bottom: 25px; padding: 14px 18px; border-radius: 8px; font-weight: 500; <?php echo ($feedback_type === 'success') ? 'background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);' : 'background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4);'; ?>">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-outlined"><?php echo ($feedback_type === 'success') ? 'check_circle' : 'error'; ?></span>
                <span><?php echo htmlspecialchars($feedback_msg); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($webhook_info)): ?>
        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 14px 18px; margin-bottom: 25px; color: #fbbf24; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-outlined">warning</span>
            <span><?php echo htmlspecialchars($webhook_info); ?> Você pode informar a URL do webhook manualmente no campo abaixo.</span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/teste_discord.php" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 25px;">
        <h3 style="font-size: 18px; color: #f8fafc; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined" style="color: var(--admin-accent-cyan);">tune</span>
            Parâmetros do Webhook e Transferência
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <div class="admin-form-group" style="grid-column: 1 / -1;">
                <label class="admin-form-label">Discord Webhook URL</label>
                <input type="text" name="webhook_url" class="admin-input" placeholder="https://discord.com/api/webhooks/..." value="<?php echo htmlspecialchars($envWebhook); ?>">
                <small style="color: #94a3b8; font-size: 12px; display: block; margin-top: 4px;">Deixe em branco para usar o <code>DISCORD_WEBHOOK</code> do .env se já estiver configurado.</small>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Site URL (Base para Imagens)</label>
                <input type="text" name="site_url" class="admin-input" placeholder="https://confusa.top" value="<?php echo htmlspecialchars($envSiteUrl); ?>">
                <small style="color: #94a3b8; font-size: 12px; display: block; margin-top: 4px;">Usado para prefixar caminhos relativos de fotos e escudos.</small>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Tipo de Transferência</label>
                <select name="tipo_transferencia" class="admin-select">
                    <option value="Permanente" selected>Permanente (Verde)</option>
                    <option value="Empréstimo">Empréstimo (Azul)</option>
                    <option value="Sem custo">Sem custo (Roxo)</option>
                </select>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Nome do Jogador</label>
                <input type="text" name="jogador_nome" class="admin-input" required value="Neymar Jr (Teste)">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Valor Formatado</label>
                <input type="text" name="valor" class="admin-input" required value="F$ 35.000.000 (35.0 M)">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Clube de Origem</label>
                <input type="text" name="origem_nome" class="admin-input" required value="Santos FC">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Clube de Destino</label>
                <input type="text" name="destino_nome" class="admin-input" required value="Barcelona FC">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Data</label>
                <input type="text" name="data" class="admin-input" required value="<?php echo date('d/m/Y'); ?>">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Foto do Jogador (Opcional)</label>
                <input type="text" name="foto_jogador" class="admin-input" placeholder="/images/jogadores/exemplo.webp ou URL externa" value="">
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Bandeira do País (Opcional)</label>
                <input type="text" name="bandeira_png" class="admin-input" placeholder="/images/bandeiras/br.png ou URL externa" value="">
            </div>

        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 12px 24px; font-size: 15px;">
                <span class="material-symbols-outlined">send</span>
                Disparar Notificação Fake no Discord
            </button>
        </div>
    </form>
</div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
