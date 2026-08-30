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

$feedback_msg = '';
$feedback_type = '';

// Processar pedido de impersonação diretamente pelo painel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['impersonate_user_id'])) {
    $targetUserId = (int)$_POST['impersonate_user_id'];
    if ($targetUserId > 0) {
        $stmtTarget = $db->prepare("SELECT id, nomeusuario, nome, admin_status, avatar, emTeste FROM usuarios WHERE id = ? LIMIT 1");
        $stmtTarget->execute([$targetUserId]);
        $targetUser = $stmtTarget->fetch(PDO::FETCH_ASSOC);

        if ($targetUser) {
            // Guarda dados do admin original se não for uma impersonação encadeada
            if (empty($_SESSION['impersonated'])) {
                $_SESSION['admin_original_id'] = $_SESSION['user_id'];
                $_SESSION['admin_original_user'] = $_SESSION['username'];
            }

            $_SESSION['user_id'] = (int)$targetUser['id'];
            $_SESSION['username'] = $targetUser['nomeusuario'];
            $_SESSION['nomereal'] = $targetUser['nome'];
            $_SESSION['admin_status'] = 1; // Mantém permissão de admin para a sessão
            $_SESSION['loggedin'] = true;
            $_SESSION['impersonated'] = true;
            $_SESSION['avatar'] = $targetUser['avatar'] ?? null;
            $_SESSION['emTestes'] = (bool)($targetUser['emTeste'] ?? 0);

            if (!$_SESSION['emTestes']) {
                $stmtToken = $db->prepare("SELECT mcp_token FROM usuarios WHERE id = ?");
                $stmtToken->execute([$_SESSION['user_id']]);
                $mcpToken = $stmtToken->fetchColumn();
                if (empty($mcpToken)) {
                    $newToken = bin2hex(random_bytes(16));
                    $stmtUpdateToken = $db->prepare("UPDATE usuarios SET mcp_token = ? WHERE id = ?");
                    $stmtUpdateToken->execute([$newToken, $_SESSION['user_id']]);
                }
            }

            header("Location: /usuario/index.php");
            exit;
        } else {
            $feedback_msg = 'Usuário selecionado não foi encontrado.';
            $feedback_type = 'danger';
        }
    }
}

// Buscar lista de usuários disponíveis para impersonar
$stmtUsers = $db->query("SELECT id, nomeusuario, nome, email FROM usuarios ORDER BY nome ASC");
$listaUsuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Painel do Administrador";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 30px; text-align: center;">
        <h1 class="admin-gradient-title">Área do Administrador</h1>
        <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Gerencie as configurações internas, usuários e logs do portal Confusa.top</p>
    </div>

    <?php if (!empty($feedback_msg)): ?>
        <div class="alert alert-<?php echo $feedback_type; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($feedback_msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['impersonated'])): ?>
        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 16px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: #fbbf24; font-size: 28px;">supervised_user_circle</span>
                <div>
                    <strong style="color: #fbbf24; font-size: 15px;">Você está personificando: <?php echo htmlspecialchars($_SESSION['nomereal'] ?? $_SESSION['username']); ?></strong>
                    <p style="margin: 3px 0 0 0; color: #cbd5e1; font-size: 13px;">Deseja encerrar o acesso temporário e retornar ao seu usuário original?</p>
                </div>
            </div>
            <a href="/admin/stop_impersonation.php" class="admin-btn admin-btn-danger" style="text-decoration: none;">
                <span class="material-symbols-outlined">undo</span>
                Parar Impersonação
            </a>
        </div>
    <?php endif; ?>

    <!-- Bloco de Personificação Rápida -->
    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(56, 189, 248, 0.25); border-left: 4px solid #38bdf8; border-radius: 10px; padding: 22px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <span class="material-symbols-outlined" style="font-size: 28px; color: #38bdf8;">switch_account</span>
            <h2 style="font-size: 20px; color: #f8fafc; font-weight: 600; margin: 0;">Personificar Usuário (Impersonate)</h2>
        </div>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 16px;">
            Selecione qualquer usuário cadastrado para navegar no sistema com a visão e as permissões dele.
        </p>
        <form method="POST" action="/admin/index.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 260px;">
                <select name="impersonate_user_id" class="admin-select" required style="padding: 10px 14px; font-size: 14px;">
                    <option value="">-- Selecione o usuário para impersonar --</option>
                    <?php foreach ($listaUsuarios as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nome'] . ' (@' . $u['nomeusuario'] . ') - ' . $u['email']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary" style="white-space: nowrap;">
                <span class="material-symbols-outlined">login</span>
                Impersonar Usuário
            </button>
            <?php if (!empty($_SESSION['impersonated'])): ?>
                <a href="/admin/stop_impersonation.php" class="admin-btn admin-btn-secondary" style="white-space: nowrap;">
                    <span class="material-symbols-outlined">undo</span>
                    Deixar de Impersonar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cards Grid -->
    <div class="admin-grid">
        
        <!-- Card 1: Logs de Erro -->
        <a href="/admin/logs.php" class="admin-card" style="border-top: 3px solid #ef4444 !important;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #ef4444;">terminal</span>
                <h2>Logs de Erros</h2>
            </div>
            <p>
                Monitore erros de PHP gerados em tempo real no site. Permite filtrar por severidade, buscar termos e limpar arquivos de log.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: var(--admin-accent-cyan); font-weight: 600; font-size: 14px;">Acessar logs &rarr;</span>
        </a>

        <!-- Card 2: Solicitações de Inscrição -->
        <a href="/admin/usuarios_pendentes.php" class="admin-card" style="border-top: 3px solid #f59e0b !important;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #f59e0b;">how_to_reg</span>
                <h2>Solicitações de Inscrição</h2>
            </div>
            <p>
                Aprove ou reprove novos usuários que solicitaram cadastro. Os dados inseridos no formulário são preenchidos automaticamente.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: var(--admin-accent-indigo); font-weight: 600; font-size: 14px;">Gerenciar inscrições &rarr;</span>
        </a>

        <!-- Card 3: Criar Usuário Manualmente -->
        <a href="/admin/criar_usuario.php" class="admin-card" style="border-top: 3px solid #10b981 !important;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #10b981;">person_add</span>
                <h2>Criar Usuário</h2>
            </div>
            <p>
                Cadastre novos administradores ou membros diretamente, inserindo usuário, e-mail, nome real e fazendo a vinculação de países.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: var(--admin-accent-emerald); font-weight: 600; font-size: 14px;">Criar novo usuário &rarr;</span>
        </a>

        <!-- Card 4: PoltronaScore Scraper -->
        <a href="/admin/poltronascore.php" class="admin-card" style="border-top: 3px solid #c084fc !important;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #c084fc;">sync_alt</span>
                <h2>PoltronaScore Scraper</h2>
            </div>
            <p>
                Gerencie o processo de captura automática de partidas (scraper), acompanhe logs de execução e force atualizações manuais do placar.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: #c084fc; font-weight: 600; font-size: 14px;">Gerenciar Scraper &rarr;</span>
        </a>

    </div>
    
    <div style="margin-top: 40px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="/" class="admin-btn admin-btn-secondary">Voltar para a Home</a>
    </div>
</div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
