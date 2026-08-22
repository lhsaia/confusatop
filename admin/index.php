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
