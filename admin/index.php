<?php
declare(strict_types=1);

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['admin_status'] !== '1') {
    header('Location: /index.php');
    exit;
}

$page_title = "Painel do Administrador";
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
/* Resetar estilos globais de h1 herdados do indexRanking.css */
h1 {
    float: none !important;
    position: static !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
}
</style>

<div class="admin-dashboard-container" style="max-width: 1000px; margin: 80px auto 40px auto; padding: 30px; font-family: 'Outfit', 'Inter', sans-serif; color: #e2e8f0; background: #1e293b; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); border: 1px solid #334155;">
    <div style="border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 32px; color: #f8fafc; font-weight: 700; background: linear-gradient(to right, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Área do Administrador</h1>
        <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Gerencie as configurações internas, usuários e logs do portal Confusa.top</p>
    </div>

    <!-- Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        
        <!-- Card 1: Logs de Erro -->
        <a href="/admin/logs.php" style="text-decoration: none; display: flex; flex-direction: column; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 24px; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; color: inherit; cursor: pointer;" 
           onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#38bdf8'; this.style.boxShadow='0 10px 15px -3px rgba(56, 189, 248, 0.15)';" 
           onmouseout="this.style.transform='none'; this.style.borderColor='#334155'; this.style.boxShadow='none';">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #ef4444;">terminal</span>
                <h2 style="margin: 0; font-size: 20px; color: #f8fafc; font-weight: 600;">Logs de Erros</h2>
            </div>
            <p style="margin: 0; font-size: 14px; color: #94a3b8; line-height: 1.5; flex-grow: 1;">
                Monitore erros de PHP gerados em tempo real no site. Permite filtrar por severidade, buscar termos e limpar arquivos de log.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: #38bdf8; font-weight: 600; font-size: 14px;">Acessar logs &rarr;</span>
        </a>

        <!-- Card 2: Solicitações de Inscrição -->
        <a href="/admin/usuarios_pendentes.php" style="text-decoration: none; display: flex; flex-direction: column; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 24px; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; color: inherit; cursor: pointer;"
           onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#818cf8'; this.style.boxShadow='0 10px 15px -3px rgba(129, 140, 248, 0.15)';" 
           onmouseout="this.style.transform='none'; this.style.borderColor='#334155'; this.style.boxShadow='none';">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #f59e0b;">how_to_reg</span>
                <h2 style="margin: 0; font-size: 20px; color: #f8fafc; font-weight: 600;">Solicitações de Inscrição</h2>
            </div>
            <p style="margin: 0; font-size: 14px; color: #94a3b8; line-height: 1.5; flex-grow: 1;">
                Aprove ou reprove novos usuários que solicitaram cadastro. Os dados inseridos no formulário são preenchidos automaticamente.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: #818cf8; font-weight: 600; font-size: 14px;">Gerenciar inscrições &rarr;</span>
        </a>

        <!-- Card 3: Criar Usuário Manualmente -->
        <a href="/admin/criar_usuario.php" style="text-decoration: none; display: flex; flex-direction: column; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 24px; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; color: inherit; cursor: pointer;"
           onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#10b981'; this.style.boxShadow='0 10px 15px -3px rgba(16, 185, 129, 0.15)';" 
           onmouseout="this.style.transform='none'; this.style.borderColor='#334155'; this.style.boxShadow='none';">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #10b981;">person_add</span>
                <h2 style="margin: 0; font-size: 20px; color: #f8fafc; font-weight: 600;">Criar Usuário</h2>
            </div>
            <p style="margin: 0; font-size: 14px; color: #94a3b8; line-height: 1.5; flex-grow: 1;">
                Cadastre novos administradores ou membros diretamente, inserindo usuário, e-mail, nome real e fazendo a vinculação de países.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: #10b981; font-weight: 600; font-size: 14px;">Criar novo usuário &rarr;</span>
        </a>

        <!-- Card 4: PoltronaScore Scraper -->
        <a href="/admin/poltronascore.php" style="text-decoration: none; display: flex; flex-direction: column; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 24px; transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s; color: inherit; cursor: pointer;"
           onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#e9d5ff'; this.style.boxShadow='0 10px 15px -3px rgba(233, 213, 255, 0.15)';" 
           onmouseout="this.style.transform='none'; this.style.borderColor='#334155'; this.style.boxShadow='none';">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #c084fc;">sync_alt</span>
                <h2 style="margin: 0; font-size: 20px; color: #f8fafc; font-weight: 600;">PoltronaScore Scraper</h2>
            </div>
            <p style="margin: 0; font-size: 14px; color: #94a3b8; line-height: 1.5; flex-grow: 1;">
                Gerencie o processo de captura automática de partidas (scraper), acompanhe logs de execução e force atualizações manuais do placar.
            </p>
            <span style="display: inline-block; margin-top: 16px; color: #c084fc; font-weight: 600; font-size: 14px;">Gerenciar Scraper &rarr;</span>
        </a>

    </div>
    
    <div style="margin-top: 40px; text-align: center; border-top: 1px solid #334155; padding-top: 20px;">
        <a href="/" style="background: #334155; color: #f8fafc; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#334155'">Voltar para a Home</a>
    </div>
</div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
