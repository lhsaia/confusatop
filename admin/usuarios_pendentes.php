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

$message = '';
$messageType = '';

// Processar Ações (Rejeitar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $solicitacaoId = (int)($_POST['id'] ?? 0);
    
    if ($solicitacaoId > 0) {
        if ($action === 'reprovar') {
            try {
                $stmt = $db->prepare("UPDATE `solicitacoes_cadastro` SET status = 'reprovado' WHERE id = ?");
                $stmt->execute([$solicitacaoId]);
                $message = "Solicitação recusada com sucesso.";
                $messageType = "success";
            } catch (PDOException $e) {
                $message = "Erro ao atualizar solicitação: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Ler solicitações do banco
$pendentes = [];
$historico = [];

try {
    // Garantir que a tabela existe
    $db->exec("CREATE TABLE IF NOT EXISTS `solicitacoes_cadastro` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nome` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `paises` TEXT,
        `data_solicitacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `status` VARCHAR(20) DEFAULT 'pendente'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Buscar pendentes
    $stmt = $db->query("SELECT * FROM `solicitacoes_cadastro` WHERE status = 'pendente' ORDER BY data_solicitacao DESC");
    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar histórico (aprovados ou reprovados)
    $stmt_hist = $db->query("SELECT * FROM `solicitacoes_cadastro` WHERE status != 'pendente' ORDER BY data_solicitacao DESC LIMIT 100");
    $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Erro ao carregar banco de dados: " . $e->getMessage();
    $messageType = "error";
}

$page_title = "Solicitações de Inscrição";
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
/* Resetar cores zebradas de tabelas herdadas do indexRanking.css */
table tbody tr {
    background-color: transparent !important;
}
</style>

<div class="inscricoes-container" style="max-width: 1100px; margin: 80px auto 40px auto; padding: 25px; font-family: 'Outfit', 'Inter', sans-serif; color: #e2e8f0; background: #1e293b; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); border: 1px solid #334155;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 20px; margin-bottom: 25px;">
        <div>
            <h1 style="margin: 0; font-size: 28px; color: #f8fafc; font-weight: 700; background: linear-gradient(to right, #f59e0b, #eab308); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Solicitações de Inscrição</h1>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 14px;">Aprove novos membros e gerencie solicitações pendentes</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/index.php" style="background: #334155; color: #f8fafc; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; text-align: center; font-size: 14px; transition: background 0.2s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#334155'">Voltar ao Painel</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div style="background: <?= $messageType === 'success' ? '#064e3b' : '#7f1d1d' ?>; border: 1px solid <?= $messageType === 'success' ? '#059669' : '#dc2626' ?>; color: <?= $messageType === 'success' ? '#a7f3d0' : '#fca5a5' ?>; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Seção de Pendentes -->
    <h2 style="font-size: 20px; color: #f8fafc; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
        Novas Solicitações (Aguardando Aprovação)
    </h2>

    <div style="overflow-x: auto; border: 1px solid #334155; border-radius: 8px; background: #1e293b; margin-bottom: 40px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="background: #0f172a; border-bottom: 1px solid #334155; color: #94a3b8;">
                    <th style="padding: 12px 16px;">Data da Solicitação</th>
                    <th style="padding: 12px 16px;">Nome</th>
                    <th style="padding: 12px 16px;">E-mail</th>
                    <th style="padding: 12px 16px;">Países Informados</th>
                    <th style="padding: 12px 16px; text-align: center; width: 220px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendentes)): ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #64748b;">Nenhuma nova solicitação de inscrição pendente.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendentes as $req): ?>
                        <tr style="border-bottom: 1px solid #334155; transition: background 0.15s;" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px 16px; color: #94a3b8; font-family: monospace; white-space: nowrap;">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['data_solicitacao']))) ?>
                            </td>
                            <td style="padding: 12px 16px; font-weight: 600; color: #f8fafc;">
                                <?= htmlspecialchars($req['nome']) ?>
                            </td>
                            <td style="padding: 12px 16px; color: #38bdf8;">
                                <?= htmlspecialchars($req['email']) ?>
                            </td>
                            <td style="padding: 12px 16px; color: #cbd5e1;">
                                <?= htmlspecialchars($req['paises']) ?>
                            </td>
                            <!-- Ações -->
                            <td style="padding: 12px 16px; display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <a href="/admin/criar_usuario.php?solicitacao_id=<?= $req['id'] ?>&nomereal=<?= urlencode($req['nome']) ?>&email=<?= urlencode($req['email']) ?>" 
                                   style="background: #10b981; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 13px; transition: background 0.2s;"
                                   onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">Aprovar</a>
                                
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja reprovar a inscrição de <?= htmlspecialchars($req['nome']) ?>?');">
                                    <input type="hidden" name="action" value="reprovar">
                                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                    <button type="submit" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s;"
                                            onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">Reprovar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Seção de Histórico -->
    <h2 style="font-size: 18px; color: #94a3b8; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        Histórico Recente (Aprovados / Reprovados)
    </h2>

    <div style="overflow-x: auto; border: 1px solid #334155; border-radius: 8px; background: #111827; opacity: 0.85;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background: #0b0f19; border-bottom: 1px solid #334155; color: #64748b;">
                    <th style="padding: 10px 14px;">Data</th>
                    <th style="padding: 10px 14px;">Nome</th>
                    <th style="padding: 10px 14px;">E-mail</th>
                    <th style="padding: 10px 14px; text-align: center; width: 120px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #475569;">Nenhum histórico disponível.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historico as $req): 
                        $statusColor = '#64748b';
                        $statusBg = '#334155';
                        if ($req['status'] === 'aprovado') {
                            $statusColor = '#a7f3d0';
                            $statusBg = '#064e3b';
                        } elseif ($req['status'] === 'reprovado') {
                            $statusColor = '#fca5a5';
                            $statusBg = '#7f1d1d';
                        }
                    ?>
                        <tr style="border-bottom: 1px solid #1f2937;">
                            <td style="padding: 10px 14px; color: #64748b; font-family: monospace;">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['data_solicitacao']))) ?>
                            </td>
                            <td style="padding: 10px 14px; color: #94a3b8; font-weight: 500;">
                                <?= htmlspecialchars($req['nome']) ?>
                            </td>
                            <td style="padding: 10px 14px; color: #475569;">
                                <?= htmlspecialchars($req['email']) ?>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; white-space: nowrap;">
                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: <?= $statusColor ?>; background: <?= $statusBg ?>;">
                                    <?= htmlspecialchars($req['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
