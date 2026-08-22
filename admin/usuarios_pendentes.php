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
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="inscricoes-container">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 25px;">
        <div>
            <h1 class="admin-gradient-title-amber">Solicitações de Inscrição</h1>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 14px;">Aprove novos membros e gerencie solicitações pendentes</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/index.php" class="admin-btn admin-btn-secondary">Voltar ao Painel</a>
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

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 200px;">Data da Solicitação</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Países Informados</th>
                    <th style="text-align: center; width: 220px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendentes)): ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #64748b;">Nenhuma nova solicitação de inscrição pendente.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendentes as $req): ?>
                        <tr>
                            <td style="color: #94a3b8; font-family: monospace; white-space: nowrap;">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['data_solicitacao']))) ?>
                            </td>
                            <td style="font-weight: 600; color: #f8fafc;">
                                <?= htmlspecialchars($req['nome']) ?>
                            </td>
                            <td style="color: #38bdf8;">
                                <?= htmlspecialchars($req['email']) ?>
                            </td>
                            <td style="color: #cbd5e1;">
                                <?= htmlspecialchars($req['paises']) ?>
                            </td>
                            <!-- Ações -->
                            <td style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <a href="/admin/criar_usuario.php?solicitacao_id=<?= $req['id'] ?>&nomereal=<?= urlencode($req['nome']) ?>&email=<?= urlencode($req['email']) ?>" 
                                   class="admin-btn admin-btn-success" style="padding: 6px 12px !important; font-size: 13px !important;">Aprovar</a>
                                
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja reprovar a inscrição de <?= htmlspecialchars($req['nome']) ?>?');">
                                    <input type="hidden" name="action" value="reprovar">
                                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn-danger" style="padding: 6px 12px !important; font-size: 13px !important;">Reprovar</button>
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

    <div class="admin-table-container" style="opacity: 0.85;">
        <table class="admin-table" style="font-size: 13px !important;">
            <thead>
                <tr style="color: #64748b;">
                    <th>Data</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th style="text-align: center; width: 120px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #475569;">Nenhum histórico disponível.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historico as $req): 
                        $statusClass = 'admin-badge-secondary';
                        if ($req['status'] === 'aprovado') {
                            $statusClass = 'admin-badge-success';
                        } elseif ($req['status'] === 'reprovado') {
                            $statusClass = 'admin-badge-danger';
                        }
                    ?>
                        <tr>
                            <td style="color: #64748b; font-family: monospace;">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['data_solicitacao']))) ?>
                            </td>
                            <td style="color: #94a3b8; font-weight: 500;">
                                <?= htmlspecialchars($req['nome']) ?>
                            </td>
                            <td style="color: #475569;">
                                <?= htmlspecialchars($req['email']) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="admin-badge <?= $statusClass ?>">
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
