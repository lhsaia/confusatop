<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['admin_status'] ?? 0) !== 1) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/usuarios.php';

$database = new Database();
$db = $database->getConnection();
$userObj = new Usuario($db);

$feedback_msg = '';
$feedback_type = '';

// Processar requisições AJAX para alternância instantânea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $newStatus = (int)($_POST['status'] ?? 0);

    if ($targetUserId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de usuário inválido.']);
        exit;
    }

    $success = $userObj->definirPermissaoDb3($targetUserId, $newStatus);
    if ($success) {
        echo json_encode(['success' => true, 'new_status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao persistir no banco de dados.']);
    }
    exit;
}

// Processar formulário tradicional (fallback POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_permissoes'])) {
    $permissoesEnviadas = $_POST['permissoes'] ?? [];
    $todosUsuarios = $userObj->listarUsuariosPermissaoDb3();

    $atualizados = 0;
    foreach ($todosUsuarios as $u) {
        $uId = (int)$u['id'];
        $deveTerAcesso = isset($permissoesEnviadas[$uId]) ? 1 : 0;
        if ((int)$u['permissao_importar_db3'] !== $deveTerAcesso) {
            $userObj->definirPermissaoDb3($uId, $deveTerAcesso);
            $atualizados++;
        }
    }

    $feedback_msg = "Permissões atualizadas com sucesso! ({$atualizados} alterações registradas)";
    $feedback_type = "success";
}

$listaUsuarios = $userObj->listarUsuariosPermissaoDb3();

$page_title = "Permissões de Importação DB3";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 class="admin-gradient-title">Permissões de Importação DB3</h1>
            <p style="margin: 6px 0 0 0; color: #94a3b8; font-size: 14px;">
                Libere ou revogue o acesso à ferramenta de importação SQLite (.db3) na área de cada usuário.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/index.php" class="admin-btn admin-btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                Voltar ao Painel
            </a>
        </div>
    </div>

    <?php if (!empty($feedback_msg)): ?>
        <div style="background: <?= $feedback_type === 'success' ? '#064e3b' : '#7f1d1d' ?>; border: 1px solid <?= $feedback_type === 'success' ? '#059669' : '#dc2626' ?>; color: <?= $feedback_type === 'success' ? '#a7f3d0' : '#fca5a5' ?>; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($feedback_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Barra de busca e filtros -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div style="position: relative; flex: 1; max-width: 400px; min-width: 250px;">
            <input type="text" id="filtroUsuario" class="admin-input" placeholder="Buscar por nome, usuário, e-mail ou país..." style="padding-left: 36px;" />
            <span class="material-symbols-outlined" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;">search</span>
        </div>
        <div style="color: #94a3b8; font-size: 13px; display: flex; align-items: center; gap: 12px;">
            <span><strong style="color: #38bdf8;" id="totalUsuarios"><?= count($listaUsuarios) ?></strong> usuários</span>
            <span>&bull;</span>
            <span><strong style="color: #34d399;" id="totalLiberados"><?= count(array_filter($listaUsuarios, function($u) { return (int)$u['admin_status'] === 1 || (int)$u['permissao_importar_db3'] === 1; })) ?></strong> com acesso</span>
        </div>
    </div>

    <form method="POST" action="/admin/permissoes_db3.php" id="formPermissoes">
        <div class="admin-table-container">
            <table class="admin-table" id="tabelaUsuarios">
                <thead>
                    <tr>
                        <th style="width: 28%;">Usuário</th>
                        <th style="width: 24%;">E-mail</th>
                        <th style="width: 28%;">Países Sob Gestão</th>
                        <th style="width: 20%; text-align: center;">Permissão DB3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaUsuarios as $u): 
                        $isAdmin = ((int)$u['admin_status'] === 1);
                        $hasPerm = ((int)$u['permissao_importar_db3'] === 1);
                        $podeImportar = $isAdmin || $hasPerm;
                        $paisesList = !empty($u['nomes_paises']) ? explode(', ', $u['nomes_paises']) : [];
                    ?>
                        <tr class="linha-usuario" data-search="<?= strtolower(htmlspecialchars($u['nome'] . ' ' . $u['nomeusuario'] . ' ' . $u['email'] . ' ' . ($u['nomes_paises'] ?? ''))) ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #38bdf8; font-size: 13px; flex-shrink: 0;">
                                        <?= strtoupper(substr($u['nome'] ?: $u['nomeusuario'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #f8fafc; font-size: 14px; display: block;">
                                            <?= htmlspecialchars($u['nome'] ?: $u['nomeusuario']) ?>
                                        </strong>
                                        <span style="color: #94a3b8; font-size: 12px;">@<?= htmlspecialchars($u['nomeusuario']) ?></span>
                                        <?php if ($isAdmin): ?>
                                            <span class="admin-badge admin-badge-warning" style="margin-left: 6px; font-size: 10px;">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="color: #cbd5e1; font-size: 13px;"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($paisesList)): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php foreach ($paisesList as $paisNome): ?>
                                            <span class="country-tag"><?= htmlspecialchars($paisNome) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #64748b; font-size: 12px; font-style: italic;">Nenhum país vinculado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($isAdmin): ?>
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span class="admin-badge admin-badge-success" style="font-size: 11px;">
                                            <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">verified</span>
                                            Admin (Irrestrito)
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <label class="admin-switch" title="Ativar ou desativar importação para este usuário">
                                        <input type="checkbox" 
                                               name="permissoes[<?= (int)$u['id'] ?>]" 
                                               value="1" 
                                               class="switch-perm-db3" 
                                               data-user-id="<?= (int)$u['id'] ?>"
                                               <?= $hasPerm ? 'checked' : '' ?> />
                                        <span class="admin-switch-slider"></span>
                                    </label>
                                    <span class="status-indicator-<?= (int)$u['id'] ?>" style="display: block; font-size: 11px; margin-top: 4px; color: <?= $hasPerm ? '#34d399' : '#94a3b8' ?>;">
                                        <?= $hasPerm ? 'Liberado' : 'Bloqueado' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 15px; margin-top: 10px;">
            <button type="submit" name="salvar_permissoes" value="1" class="admin-btn admin-btn-primary">
                <span class="material-symbols-outlined">save</span>
                Salvar Todas as Permissões
            </button>
        </div>
    </form>
</div>

<!-- Toast Notificação AJAX -->
<div id="toastNotification" style="position: fixed; bottom: 30px; right: 30px; background: #0f172a; border: 1px solid #38bdf8; color: #f8fafc; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; box-shadow: 0 10px 25px rgba(0,0,0,0.5); display: none; align-items: center; gap: 10px; z-index: 9999; transition: all 0.3s ease;">
    <span class="material-symbols-outlined" style="color: #38bdf8; font-size: 20px;">check_circle</span>
    <span id="toastMessage">Permissão atualizada!</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro de busca em tempo real
    const inputFiltro = document.getElementById('filtroUsuario');
    const linhas = document.querySelectorAll('.linha-usuario');
    
    inputFiltro.addEventListener('input', function() {
        const termo = this.value.toLowerCase().trim();
        linhas.forEach(linha => {
            const texto = linha.getAttribute('data-search') || '';
            if (termo === '' || texto.includes(termo)) {
                linha.style.display = '';
            } else {
                linha.style.display = 'none';
            }
        });
    });

    // Toast Helper
    const toast = document.getElementById('toastNotification');
    const toastMsg = document.getElementById('toastMessage');
    let toastTimer = null;

    function showToast(message, isError = false) {
        if (toastTimer) clearTimeout(toastTimer);
        toastMsg.textContent = message;
        toast.style.borderColor = isError ? '#ef4444' : '#38bdf8';
        toast.querySelector('.material-symbols-outlined').style.color = isError ? '#ef4444' : '#38bdf8';
        toast.querySelector('.material-symbols-outlined').textContent = isError ? 'error' : 'check_circle';
        toast.style.display = 'flex';
        toast.style.opacity = '1';

        toastTimer = setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => { toast.style.display = 'none'; }, 300);
        }, 3000);
    }

    // AJAX Toggle Switches
    const switches = document.querySelectorAll('.switch-perm-db3');
    switches.forEach(sw => {
        sw.addEventListener('change', function() {
            const userId = this.getAttribute('data-user-id');
            const isChecked = this.checked;
            const newStatus = isChecked ? 1 : 0;
            const labelStatus = document.querySelector('.status-indicator-' + userId);

            if (labelStatus) {
                labelStatus.textContent = 'Salvando...';
                labelStatus.style.color = '#fbbf24';
            }

            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('user_id', userId);
            formData.append('status', newStatus);

            fetch('/admin/permissoes_db3.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (labelStatus) {
                        labelStatus.textContent = isChecked ? 'Liberado' : 'Bloqueado';
                        labelStatus.style.color = isChecked ? '#34d399' : '#94a3b8';
                    }
                    showToast(isChecked ? 'Permissão concedida com sucesso!' : 'Permissão revogada com sucesso!');
                    
                    // Atualiza contador no topo
                    const totalAtivos = document.querySelectorAll('.switch-perm-db3:checked').length + document.querySelectorAll('.admin-badge-warning').length;
                    const elTot = document.getElementById('totalLiberados');
                    if (elTot) elTot.textContent = totalAtivos;
                } else {
                    throw new Error(data.error || 'Erro ao atualizar');
                }
            })
            .catch(err => {
                sw.checked = !isChecked; // Reverte
                if (labelStatus) {
                    labelStatus.textContent = !isChecked ? 'Liberado' : 'Bloqueado';
                    labelStatus.style.color = !isChecked ? '#34d399' : '#94a3b8';
                }
                showToast('Erro: ' + err.message, true);
            });
        });
    });
});
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
