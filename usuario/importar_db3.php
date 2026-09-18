<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
@set_time_limit(300);
@ini_set('memory_limit', '512M');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Verificar login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/paises.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/usuarios.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/DB3Importer.php';

$database = new Database();
$db = $database->getConnection();
$paisObj = new Pais($db);
$userObj = new Usuario($db);

$userId = (int)$_SESSION['user_id'];
$isAdmin = $userObj->isAdmin($userId);
$podeImportar = $userObj->podeImportarDb3($userId);

// Bloquear usuários sem permissão
if (!$podeImportar) {
    header('Location: /usuario/index.php');
    exit;
}

$error_msg = '';
$final_report = null;

// Cancelar importação pendente
if ((isset($_POST['action']) && $_POST['action'] === 'cancel_db3') || (isset($_GET['cancel']) && $_GET['cancel'] === '1')) {
    if (isset($_SESSION['pending_db3_import']['temp_path']) && file_exists($_SESSION['pending_db3_import']['temp_path'])) {
        @unlink($_SESSION['pending_db3_import']['temp_path']);
    }
    unset($_SESSION['pending_db3_import']);
    header('Location: /usuario/importar_db3.php');
    exit;
}

// Confirmar e Executar Importação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'execute_db3') {
    if (!isset($_SESSION['pending_db3_import'])) {
        $error_msg = 'Nenhuma importação pendente para execução.';
    } else {
        $pending = $_SESSION['pending_db3_import'];
        $tempPath = $pending['temp_path'];
        $idPais = (int)$pending['id_pais'];
        $sexo = (int)$pending['sexo'];

        // Se não for admin, verificar se o país pertence ao usuário
        if (!$isAdmin) {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM paises WHERE id = ? AND dono = ?");
            $stmtCheck->execute([$idPais, $userId]);
            if ((int)$stmtCheck->fetchColumn() === 0) {
                $error_msg = 'Você não possui permissão para importar dados no país selecionado.';
            }
        }

        if (empty($error_msg)) {
            if (!file_exists($tempPath)) {
                $error_msg = 'O arquivo temporário da importação expirou ou não foi encontrado. Por favor, envie novamente.';
                unset($_SESSION['pending_db3_import']);
            } else {
                try {
                    $importer = new DB3Importer($db);
                    $final_report = $importer->processFile($tempPath, $idPais, $sexo, $userId);
                    @unlink($tempPath);
                    unset($_SESSION['pending_db3_import']);
                } catch (Exception $e) {
                    $error_msg = 'Erro ao processar a importação: ' . $e->getMessage();
                }
            }
        }
    }
}

// Analisar e Gerar Conferência Prévia (Preview)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview_db3') {
    $idPais = isset($_POST['id_pais']) ? (int)$_POST['id_pais'] : 0;
    $sexo = isset($_POST['sexo']) ? (int)$_POST['sexo'] : 0;

    // Se não for admin, verificar se o país pertence ao usuário
    if (!$isAdmin) {
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM paises WHERE id = ? AND dono = ?");
        $stmtCheck->execute([$idPais, $userId]);
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $error_msg = 'Você não possui permissão para importar dados no país selecionado.';
        }
    }

    if (empty($error_msg)) {
        if ($idPais <= 0) {
            $error_msg = 'Por favor, selecione um país válido para a importação.';
        } elseif (!isset($_FILES['db3_file']) || $_FILES['db3_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'Erro no envio do arquivo. Verifique se selecionou um arquivo SQLite .db3 válido.';
        } else {
            $uploadedTmp = $_FILES['db3_file']['tmp_name'];
            $originalName = $_FILES['db3_file']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowedExtensions = ['db3', 'sqlite', 'sqlite3', 'db'];
            if (!in_array($ext, $allowedExtensions, true)) {
                $error_msg = "Extensão de arquivo inválida (.{$ext}). São aceitos apenas arquivos SQLite (.db3, .sqlite).";
            } else {
                // Mover para session_data temporário
                $destDir = $_SERVER['DOCUMENT_ROOT'] . '/session_data/';
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0777, true);
                }
                $tempFileName = 'temp_db3_preview_' . $userId . '_' . time() . '.db3';
                $destPath = $destDir . $tempFileName;

                if (!move_uploaded_file($uploadedTmp, $destPath)) {
                    $error_msg = 'Não foi possível armazenar o arquivo temporário no servidor.';
                } else {
                    try {
                        $importer = new DB3Importer($db);
                        $previewData = $importer->previewFile($destPath, $idPais, $sexo, $userId);

                        $_SESSION['pending_db3_import'] = [
                            'temp_path' => $destPath,
                            'id_pais' => $idPais,
                            'sexo' => $sexo,
                            'file_name' => $originalName,
                            'preview' => $previewData,
                        ];
                    } catch (Exception $e) {
                        $error_msg = 'Erro ao analisar o arquivo DB3: ' . $e->getMessage();
                        @unlink($destPath);
                    }
                }
            }
        }
    }
}

// Carregar países disponíveis para o formulário (filtrados por dono se não for admin)
if ($isAdmin) {
    $stmtPaises = $db->query("SELECT id, nome, sigla, bandeira FROM paises WHERE ativo = 1 ORDER BY nome ASC");
    $listaPaises = $stmtPaises->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmtPaises = $db->prepare("SELECT id, nome, sigla, bandeira FROM paises WHERE ativo = 1 AND dono = ? ORDER BY nome ASC");
    $stmtPaises->execute([$userId]);
    $listaPaises = $stmtPaises->fetchAll(PDO::FETCH_ASSOC);
}

$pendingImport = $_SESSION['pending_db3_import'] ?? null;
$preview = $pendingImport['preview'] ?? null;

$page_title = "Importar Banco SQLite (.db3)";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "importar_db3";
$css_login = 'login';
$css_versao = date('h:i:s');

include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>
<link rel="stylesheet" href="/css/importar_db3.css?v=<?php echo $css_versao; ?>">

<main class="db3-import-container">

    <div class="db3-header-box">
        <div>
            <h1 class="db3-title">
                <span class="material-symbols-outlined" style="font-size: 32px; color: #0284c7;">database</span>
                Importar Banco SQLite (.db3)
            </h1>
            <p class="db3-subtitle">Sincronize clubes, jogadores, estádios e elencos de um banco .db3 com verificação por externalID</p>
        </div>
        <div>
            <a href="/usuario/index.php" class="db3-btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                Minha Área
            </a>
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; color: #b91c1c; display: flex; align-items: center; gap: 12px;">
            <span class="material-symbols-outlined" style="font-size: 24px;">error</span>
            <div><strong>Erro:</strong> <?php echo htmlspecialchars($error_msg); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($final_report): ?>
        <!-- ETAPA 3: Relatório Final de Sucesso pós-execução -->
        <div class="db3-card" style="border-top: 4px solid #10b981;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <?php if (!empty($final_report['pais']['bandeira'])): ?>
                        <img src="/images/bandeiras/<?php echo htmlspecialchars($final_report['pais']['bandeira']); ?>" alt="Bandeira" style="width: 38px; height: 26px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.15);">
                    <?php endif; ?>
                    <div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.4rem; color: #0f172a; margin: 0;">
                            Importação Concluída com Sucesso!
                        </h2>
                        <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.9rem;">
                            País: <strong><?php echo htmlspecialchars($final_report['pais']['nome']); ?></strong> &bull; Gênero: <strong><?php echo $final_report['sexo'] === 1 ? 'Feminino' : 'Masculino'; ?></strong>
                        </p>
                    </div>
                </div>
                <a href="/usuario/importar_db3.php" class="db3-btn-primary" style="padding: 9px 18px; font-size: 0.9rem;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">upload_file</span>
                    Nova Importação
                </a>
            </div>

            <!-- Grid de Estatísticas -->
            <div class="db3-stats-grid">
                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                        <span class="material-symbols-outlined">shield</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $final_report['stats']['total_clubes']; ?></div>
                        <div class="db3-stat-lbl">Clubes Sincronizados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <?php echo $final_report['stats']['clubes_criados']; ?> novos &bull; <?php echo $final_report['stats']['clubes_atualizados']; ?> atualizados
                        </div>
                    </div>
                </div>

                <?php if (!empty($final_report['stats']['total_selecoes'])): ?>
                    <div class="db3-stat-card">
                        <div class="db3-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                            <span class="material-symbols-outlined">flag</span>
                        </div>
                        <div>
                            <div class="db3-stat-val"><?php echo $final_report['stats']['total_selecoes']; ?></div>
                            <div class="db3-stat-lbl">Seleções Nacionais</div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                                <?php echo $final_report['stats']['selecoes_criadas']; ?> novas &bull; <?php echo $final_report['stats']['selecoes_atualizadas']; ?> atualizadas
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $final_report['stats']['total_jogadores']; ?></div>
                        <div class="db3-stat-lbl">Jogadores Importados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <?php echo $final_report['stats']['jogadores_criados']; ?> novos &bull; <?php echo $final_report['stats']['jogadores_atualizados']; ?> atualizados
                            <?php if (!empty($final_report['stats']['propostas_enviadas'])): ?>
                                &bull; <span style="color: #d97706; font-weight: 700;"><?php echo $final_report['stats']['propostas_enviadas']; ?> propostas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <span class="material-symbols-outlined">stadium</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $final_report['stats']['total_estadios']; ?></div>
                        <div class="db3-stat-lbl">Estádios Sincronizados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <?php echo $final_report['stats']['estadios_criados']; ?> novos &bull; <?php echo $final_report['stats']['estadios_atualizados']; ?> atualizados
                        </div>
                    </div>
                </div>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
                        <span class="material-symbols-outlined">sports</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $final_report['stats']['total_tecnicos']; ?></div>
                        <div class="db3-stat-lbl">Técnicos Sincronizados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <?php echo $final_report['stats']['tecnicos_criados']; ?> novos &bull; <?php echo $final_report['stats']['tecnicos_atualizados']; ?> atualizados
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Clubes Importados -->
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; color: #1e293b; margin: 24px 0 12px 0;">
                Clubes e Seleções Importados
            </h3>
            <div class="db3-table-wrapper">
                <table class="db3-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">db3 ID</th>
                            <th>Nome do Clube / Seleção</th>
                            <th style="width: 80px;">Sigla</th>
                            <th style="width: 140px;">Tipo</th>
                            <th style="width: 120px;">Ação</th>
                            <th style="width: 140px; text-align: center;">Atletas no Elenco</th>
                            <th style="width: 100px;">ID MySQL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($final_report['clubes_detalhes'] as $clube): ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;"><?php echo $clube['db3_id']; ?></td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?php echo htmlspecialchars($clube['nome']); ?>
                                </td>
                                <td><span style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.8rem;"><?php echo htmlspecialchars($clube['sigla']); ?></span></td>
                                <td>
                                    <?php if (!empty($clube['is_selecao'])): ?>
                                        <span class="badge-selecao badge-selecao-<?php echo $clube['status']; ?>">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">flag</span>
                                            <?php echo htmlspecialchars($clube['tipo_label']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.82rem;">Clube Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($clube['action'] === 'created'): ?>
                                        <span class="db3-badge db3-badge-created">Novo</span>
                                    <?php else: ?>
                                        <span class="db3-badge db3-badge-updated">Atualizado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?php echo $clube['jogadores_count']; ?> atletas</td>
                                <td style="color: #64748b;">#<?php echo $clube['mysql_id']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($final_report['logs'])): ?>
                <div style="margin-top: 24px; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 16px;">
                    <strong style="color: #475569; font-size: 0.9rem;">Mensagens e Avisos do Processamento:</strong>
                    <ul style="margin: 8px 0 0 16px; padding: 0; color: #64748b; font-size: 0.85rem;">
                        <?php foreach ($final_report['logs'] as $log): ?>
                            <li><?php echo htmlspecialchars($log); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($preview): ?>
        <!-- ETAPA 2: Tela de Conferência Prévia (Review) -->
        <div class="db3-card" style="border-top: 4px solid #0284c7;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <?php if (!empty($preview['pais']['bandeira'])): ?>
                        <img src="/images/bandeiras/<?php echo htmlspecialchars($preview['pais']['bandeira']); ?>" alt="Bandeira" style="width: 40px; height: 28px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                    <?php endif; ?>
                    <div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.45rem; color: #0f172a; margin: 0;">
                            Conferência Prévia da Base DB3
                        </h2>
                        <p style="margin: 3px 0 0 0; color: #64748b; font-size: 0.92rem;">
                            Arquivo: <strong><?php echo htmlspecialchars($pendingImport['file_name']); ?></strong> &bull;
                            País: <strong><?php echo htmlspecialchars($preview['pais']['nome']); ?></strong> &bull;
                            Gênero: <strong><?php echo $preview['sexo'] === 1 ? 'Feminino' : 'Masculino'; ?></strong>
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="/usuario/importar_db3.php?cancel=1" class="db3-btn-danger-outline" style="padding: 8px 16px; font-size: 0.88rem;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                        Cancelar
                    </a>
                </div>
            </div>

            <!-- Resumo Estatístico da Prévia -->
            <div class="db3-stats-grid">
                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                        <span class="material-symbols-outlined">shield</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $preview['stats']['total_clubes']; ?></div>
                        <div class="db3-stat-lbl">Clubes Regulares</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <span style="color: #047857; font-weight: 700;"><?php echo $preview['stats']['clubes_criados']; ?> novos</span> &bull; 
                            <span style="color: #0284c7; font-weight: 700;"><?php echo $preview['stats']['clubes_atualizados']; ?> atualizações</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($preview['stats']['total_selecoes'])): ?>
                    <div class="db3-stat-card">
                        <div class="db3-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                            <span class="material-symbols-outlined">flag</span>
                        </div>
                        <div>
                            <div class="db3-stat-val"><?php echo $preview['stats']['total_selecoes']; ?></div>
                            <div class="db3-stat-lbl">Seleções Nacionais</div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                                <span style="color: #047857; font-weight: 700;"><?php echo $preview['stats']['selecoes_criadas']; ?> novas</span> &bull; 
                                <span style="color: #0284c7; font-weight: 700;"><?php echo $preview['stats']['selecoes_atualizadas']; ?> atualizações</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $preview['stats']['total_jogadores']; ?></div>
                        <div class="db3-stat-lbl">Atletas Mapeados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <span style="color: #047857; font-weight: 700;"><?php echo $preview['stats']['jogadores_criados']; ?> novos</span> &bull; 
                            <span style="color: #0284c7; font-weight: 700;"><?php echo $preview['stats']['jogadores_atualizados']; ?> atualizações</span>
                            <?php if (!empty($preview['stats']['jogadores_propostas'])): ?>
                                &bull; <span style="color: #d97706; font-weight: 700;"><?php echo $preview['stats']['jogadores_propostas']; ?> propostas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <span class="material-symbols-outlined">stadium</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $preview['stats']['total_estadios']; ?></div>
                        <div class="db3-stat-lbl">Estádios Mapeados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <span style="color: #047857; font-weight: 700;"><?php echo $preview['stats']['estadios_criados']; ?> novos</span> &bull; 
                            <span style="color: #0284c7; font-weight: 700;"><?php echo $preview['stats']['estadios_atualizados']; ?> atualizações</span>
                        </div>
                    </div>
                </div>

                <div class="db3-stat-card">
                    <div class="db3-stat-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
                        <span class="material-symbols-outlined">sports</span>
                    </div>
                    <div>
                        <div class="db3-stat-val"><?php echo $preview['stats']['total_tecnicos']; ?></div>
                        <div class="db3-stat-lbl">Técnicos Mapeados</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 3px;">
                            <span style="color: #047857; font-weight: 700;"><?php echo $preview['stats']['tecnicos_criados']; ?> novos</span> &bull; 
                            <span style="color: #0284c7; font-weight: 700;"><?php echo $preview['stats']['tecnicos_atualizados']; ?> atualizações</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar de Filtros e Busca Rápida -->
            <div class="db3-toolbar">
                <div class="db3-filter-group">
                    <button type="button" class="db3-filter-btn active" onclick="filterClubs('all', this);">
                        Todos (<?php echo count($preview['clubes']); ?>)
                    </button>
                    <button type="button" class="db3-filter-btn" onclick="filterClubs('created', this);">
                        Novos (<?php echo $preview['stats']['clubes_criados'] + $preview['stats']['selecoes_criadas']; ?>)
                    </button>
                    <button type="button" class="db3-filter-btn" onclick="filterClubs('matched', this);">
                        Atualizações (<?php echo $preview['stats']['clubes_atualizados'] + $preview['stats']['selecoes_atualizadas']; ?>)
                    </button>
                    <?php if (!empty($preview['stats']['total_selecoes'])): ?>
                        <button type="button" class="db3-filter-btn" onclick="filterClubs('selecoes', this);">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">flag</span>
                            Seleções (<?php echo $preview['stats']['total_selecoes']; ?>)
                        </button>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <div class="db3-search-box">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="db3-search-input" placeholder="Buscar clube, seleção ou jogador..." onkeyup="searchPreview(this.value);">
                    </div>

                    <button type="button" class="db3-btn-secondary" style="padding: 7px 12px; font-size: 0.82rem;" onclick="toggleAllAccordions(true);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">unfold_more</span>
                        Expandir Todos
                    </button>
                    <button type="button" class="db3-btn-secondary" style="padding: 7px 12px; font-size: 0.82rem;" onclick="toggleAllAccordions(false);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">unfold_less</span>
                        Recolher Todos
                    </button>
                </div>
            </div>

            <!-- Accordion Compacto de Clubes e Elencos -->
            <div class="db3-accordion" id="db3-accordion-container">
                <?php 
                $clubIndex = 0;
                foreach ($preview['clubes'] as $clube): 
                    $clubIndex++;
                    $isFirst = ($clubIndex === 1);
                    $cAction = $clube['action'];
                ?>
                    <div class="db3-club-item <?php echo $isFirst ? 'open' : ''; ?>" data-action="<?php echo $cAction; ?>" data-is-selecao="<?php echo !empty($clube['is_selecao']) ? '1' : '0'; ?>" data-club-name="<?php echo htmlspecialchars(strtolower($clube['nome'])); ?>">
                        <!-- Header do Clube -->
                        <div class="db3-club-header" onclick="toggleAccordion(this.parentElement);">
                            <div class="db3-club-header-left">
                                <span class="db3-sigla-pill"><?php echo htmlspecialchars($clube['sigla']); ?></span>
                                <h3 class="db3-club-title"><?php echo htmlspecialchars($clube['nome']); ?></h3>
                                <span style="font-size: 0.78rem; color: #94a3b8;">db3 #<?php echo $clube['db3_id']; ?></span>

                                <?php if (!empty($clube['is_selecao'])): ?>
                                    <span class="badge-selecao badge-selecao-<?php echo $clube['status']; ?>" title="<?php echo htmlspecialchars($clube['tipo_label']); ?>">
                                         <span class="material-symbols-outlined" style="font-size: 13px;">flag</span>
                                        <?php echo htmlspecialchars($clube['tipo_label']); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($cAction === 'created'): ?>
                                    <span class="db3-badge db3-badge-created">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">add_circle</span>
                                        Novo
                                    </span>
                                <?php else: ?>
                                    <span class="db3-badge db3-badge-updated" title="ID MySQL: #<?php echo $clube['matched_id']; ?>">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">sync</span>
                                        Atualizar (#<?php echo $clube['matched_id']; ?>: <?php echo htmlspecialchars($clube['matched_nome']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="db3-club-header-right">
                                <?php if ($clube['estadio']): ?>
                                    <span class="db3-meta-pill" title="Estádio">
                                        <span class="material-symbols-outlined" style="font-size: 15px; color: #f59e0b;">stadium</span>
                                        <?php echo htmlspecialchars($clube['estadio']['nome']); ?>
                                        <small style="color: #64748b;">(<?php echo $clube['estadio']['action'] === 'created' ? 'Novo' : 'Atualizado'; ?>)</small>
                                    </span>
                                <?php endif; ?>

                                <?php if ($clube['tecnico']): ?>
                                    <span class="db3-meta-pill" title="Técnico">
                                        <span class="material-symbols-outlined" style="font-size: 15px; color: #8b5cf6;">sports</span>
                                        <?php echo htmlspecialchars($clube['tecnico']['nome']); ?>
                                        <small style="color: #64748b;">(<?php echo $clube['tecnico']['action'] === 'created' ? 'Novo' : 'Atualizado'; ?>)</small>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($clube['is_selecao'])): ?>
                                    <span class="db3-meta-pill" style="font-weight: 700; color: #0f172a; background: #e2e8f0;">
                                        <span class="material-symbols-outlined" style="font-size: 15px; color: #8b5cf6;">groups</span>
                                        <?php echo $clube['stats_jogadores']['total']; ?> convocados
                                        <span style="font-size: 0.72rem; font-weight: 600; color: #64748b;">(<?php echo $clube['stats_jogadores']['convocados']; ?> de clubes locais)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="db3-meta-pill" style="font-weight: 700; color: #0f172a; background: #e2e8f0;">
                                        <span class="material-symbols-outlined" style="font-size: 15px; color: #0284c7;">groups</span>
                                        <?php echo $clube['stats_jogadores']['total']; ?> atletas
                                        <span style="font-size: 0.72rem; font-weight: 600; color: #64748b;">(<?php echo $clube['stats_jogadores']['criados']; ?> novos, <?php echo $clube['stats_jogadores']['atualizados']; ?> atualiz.<?php if (!empty($clube['stats_jogadores']['propostas'])) echo ", <span style='color: #d97706; font-weight: 700;'>{$clube['stats_jogadores']['propostas']} propostas</span>"; ?>)</span>
                                    </span>
                                <?php endif; ?>

                                <span class="material-symbols-outlined db3-chevron">expand_more</span>
                            </div>
                        </div>

                        <!-- Body do Clube: Tabela Densa de Jogadores -->
                        <div class="db3-club-body">
                            <table class="db3-squad-table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">db3 ID</th>
                                        <th>Atleta</th>
                                        <th style="width: 60px;">Idade</th>
                                        <th style="width: 60px;">Nível</th>
                                        <th style="width: 140px;">Posições</th>
                                        <th style="width: 140px;">Função / Escalação</th>
                                        <th style="width: 190px;">Ação no Sistema</th>
                                        <th style="width: 80px;">Nac.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($clube['jogadores'])): ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 14px;">
                                                Nenhum jogador listado no elenco deste clube.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($clube['jogadores'] as $j): ?>
                                            <tr class="player-row" data-player-name="<?php echo htmlspecialchars(strtolower($j['nome'])); ?>">
                                                <td style="color: #64748b; font-weight: 600; font-size: 0.78rem;">#<?php echo $j['db3_id']; ?></td>
                                                <td>
                                                    <strong style="color: #0f172a;"><?php echo htmlspecialchars($j['nome']); ?></strong>
                                                </td>
                                                <td><?php echo $j['idade']; ?>a</td>
                                                <td>
                                                    <span style="font-weight: 700; color: #0284c7;"><?php echo $j['nivel']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($j['is_goleiro']): ?>
                                                        <span class="db3-pos-chip goleiro">G</span>
                                                    <?php endif; ?>
                                                    <?php foreach ($j['posicoes'] as $pos): ?>
                                                        <?php if ($pos !== 'G'): ?>
                                                            <span class="db3-pos-chip"><?php echo htmlspecialchars($pos); ?></span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td>
                                                    <?php if ($j['titular']): ?>
                                                        <span class="db3-role-badge db3-role-titular">
                                                            <span class="material-symbols-outlined" style="font-size: 13px;">check_circle</span>
                                                            Titular (<?php echo htmlspecialchars($j['posicao_base']); ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="db3-role-badge" style="background: #f1f5f9; color: #64748b;">Reserva</span>
                                                    <?php endif; ?>

                                                    <?php if ($j['capitao']): ?>
                                                        <span class="db3-role-badge db3-role-capitao" title="Capitão">© Cap</span>
                                                    <?php endif; ?>

                                                    <?php if ($j['penalti'] > 0): ?>
                                                        <span class="db3-role-badge db3-role-penalti" title="Batedor de Pênalti #<?php echo $j['penalti']; ?>">(P<?php echo $j['penalti']; ?>)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($j['action'] === 'convocado'): ?>
                                                        <span class="db3-badge db3-badge-convocado" title="<?php echo !empty($j['matched_id']) ? "Atleta MySQL #{$j['matched_id']} (Convocado de " . htmlspecialchars($j['origem_clube']) . ")" : "Atleta da base DB3 (Convocado de " . htmlspecialchars($j['origem_clube']) . ")"; ?>">
                                                            <span class="material-symbols-outlined" style="font-size: 13px;">how_to_reg</span>
                                                            Convocado: <?php echo htmlspecialchars($j['origem_clube']); ?>
                                                            <?php if (!empty($j['matched_id'])): ?>
                                                                <span style="opacity: 0.8; font-size: 0.72rem;">(#<?php echo $j['matched_id']; ?>)</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php elseif ($j['action'] === 'proposta'): ?>
                                                        <span class="db3-badge db3-badge-proposta" title="Atleta em clube de outro usuário: será enviada proposta automática para <?php echo htmlspecialchars($j['origem_clube']); ?>">
                                                            <span class="material-symbols-outlined" style="font-size: 13px;">handshake</span>
                                                            Proposta: <?php echo htmlspecialchars($j['origem_clube']); ?>
                                                            <?php if (!empty($j['matched_id'])): ?>
                                                                <span style="opacity: 0.8; font-size: 0.72rem;">(#<?php echo $j['matched_id']; ?>)</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php elseif ($j['action'] === 'created'): ?>
                                                        <span class="db3-badge db3-badge-created">
                                                            <span class="material-symbols-outlined" style="font-size: 12px;">add</span>
                                                            Criar Novo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="db3-badge db3-badge-updated" title="Atleta MySQL: #<?php echo $j['matched_id']; ?> (<?php echo htmlspecialchars($j['matched_nome']); ?>)">
                                                            <span class="material-symbols-outlined" style="font-size: 12px;">sync</span>
                                                            Atualizar #<?php echo $j['matched_id']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="color: #64748b; font-size: 0.78rem;">
                                                    <?php echo htmlspecialchars($j['nacionalidade']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Barra de Ações Inferior -->
            <div class="db3-action-footer">
                <form method="POST" action="/usuario/importar_db3.php">
                    <input type="hidden" name="action" value="cancel_db3">
                    <button type="submit" class="db3-btn-danger-outline">
                        <span class="material-symbols-outlined" style="font-size: 18px;">cancel</span>
                        Cancelar e Descartar
                    </button>
                </form>

                <form method="POST" action="/usuario/importar_db3.php" id="form-execute-db3">
                    <input type="hidden" name="action" value="execute_db3">
                    <button type="submit" class="db3-btn-success" id="btn-execute-db3">
                        <span class="material-symbols-outlined">verified</span>
                        Confirmar e Executar Importação
                    </button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- ETAPA 1: Formulário de Envio Inicial -->
        <div class="db3-card">
            <?php if (empty($listaPaises)): ?>
                <div style="text-align: center; padding: 30px 20px;">
                    <span class="material-symbols-outlined" style="font-size: 48px; color: #94a3b8; margin-bottom: 12px;">public_off</span>
                    <h3 style="color: #1e293b; font-size: 1.2rem; margin: 0 0 8px 0;">Nenhum país disponível</h3>
                    <p style="color: #64748b; font-size: 0.95rem; max-width: 500px; margin: 0 auto 20px auto;">
                        Você ainda não possui nenhum país vinculado sob sua administração para realizar importações de bases SQLite.
                    </p>
                    <a href="/usuario/index.php" class="db3-btn-secondary">Voltar à Minha Área</a>
                </div>
            <?php else: ?>
                <form method="POST" action="/usuario/importar_db3.php" enctype="multipart/form-data" id="form-preview-db3">
                    <input type="hidden" name="action" value="preview_db3">

                    <div class="db3-form-grid">
                        <!-- Seleção de País -->
                        <div class="db3-form-group">
                            <label for="id_pais" class="db3-label">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom; color: #0284c7;">flag</span>
                                País de Destino
                            </label>
                            <select name="id_pais" id="id_pais" class="db3-select" required>
                                <option value="">-- Selecione o país da base --</option>
                                <?php foreach ($listaPaises as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>">
                                        <?php echo htmlspecialchars($p['nome'] . ' (' . $p['sigla'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Seleção de Gênero -->
                        <div class="db3-form-group">
                            <label for="sexo" class="db3-label">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: text-bottom; color: #0284c7;">wc</span>
                                Gênero da Base
                            </label>
                            <select name="sexo" id="sexo" class="db3-select" required>
                                <option value="0" selected>Masculino</option>
                                <option value="1">Feminino</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dropzone de Arquivo SQLite -->
                    <div class="db3-dropzone" id="db3-dropzone-box" onclick="document.getElementById('db3_file').click();">
                        <input type="file" name="db3_file" id="db3_file" accept=".db3,.sqlite,.sqlite3,.db" style="display: none;" required onchange="updateFileLabel(this);">
                        <div class="db3-dropzone-icon">
                            <span class="material-symbols-outlined" style="font-size: 48px;">file_upload</span>
                        </div>
                        <div class="db3-dropzone-text" id="dropzone-text">Clique aqui para selecionar o arquivo .db3</div>
                        <div class="db3-dropzone-hint" id="dropzone-hint">Formatos suportados: .db3, .sqlite (máximo 50MB)</div>
                    </div>

                    <div style="background: rgba(2, 132, 199, 0.05); border-left: 4px solid #0284c7; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;">
                        <h4 style="margin: 0 0 6px 0; color: #0369a1; font-size: 0.95rem;">Etapa Prévia de Conferência:</h4>
                        <ul style="margin: 0 0 0 18px; padding: 0; color: #475569; font-size: 0.88rem; line-height: 1.5;">
                            <li>O sistema fará a leitura do arquivo e comparará todos os clubes e jogadores com o banco de dados.</li>
                            <li>Você visualizará uma tela de conferência detalhada indicando quais registros serão <strong>criados novos</strong>, <strong>atualizados</strong> ou enviados como <strong>proposta</strong>.</li>
                            <li>Nenhuma alteração é gravada no banco até você clicar em <em>Confirmar e Executar</em>.</li>
                        </ul>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                        <a href="/usuario/index.php" class="db3-btn-secondary">Cancelar</a>
                        <button type="submit" class="db3-btn-primary" id="btn-preview-db3">
                            <span class="material-symbols-outlined">preview</span>
                            Analisar e Conferir DB3
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<script>
function updateFileLabel(input) {
    if (input.files && input.files[0]) {
        var fileName = input.files[0].name;
        var fileSizeMB = (input.files[0].size / (1024 * 1024)).toFixed(2);
        document.getElementById('dropzone-text').innerHTML = 'Arquivo selecionado: <strong>' + fileName + '</strong>';
        document.getElementById('dropzone-hint').innerText = 'Tamanho: ' + fileSizeMB + ' MB';
        document.getElementById('db3-dropzone-box').style.borderColor = '#0284c7';
        document.getElementById('db3-dropzone-box').style.background = 'rgba(2, 132, 199, 0.06)';
    }
}

function toggleAccordion(element) {
    element.classList.toggle('open');
}

function toggleAllAccordions(open) {
    var items = document.querySelectorAll('.db3-club-item');
    items.forEach(function(item) {
        if (open) {
            item.classList.add('open');
        } else {
            item.classList.remove('open');
        }
    });
}

function filterClubs(filterType, btn) {
    document.querySelectorAll('.db3-filter-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    btn.classList.add('active');

    var items = document.querySelectorAll('.db3-club-item');
    items.forEach(function(item) {
        var action = item.getAttribute('data-action');
        var isSelecao = (item.getAttribute('data-is-selecao') === '1');

        if (filterType === 'all') {
            item.style.display = 'block';
        } else if (filterType === 'created' && action === 'created') {
            item.style.display = 'block';
        } else if (filterType === 'matched' && action === 'matched') {
            item.style.display = 'block';
        } else if (filterType === 'selecoes' && isSelecao) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function searchPreview(term) {
    term = term.toLowerCase().trim();
    var items = document.querySelectorAll('.db3-club-item');

    items.forEach(function(item) {
        var clubName = item.getAttribute('data-club-name') || '';
        var players = item.querySelectorAll('.player-row');
        var clubMatches = (term === '' || clubName.indexOf(term) !== -1);
        var playerMatchesCount = 0;

        players.forEach(function(pRow) {
            var pName = pRow.getAttribute('data-player-name') || '';
            if (term === '' || pName.indexOf(term) !== -1 || clubMatches) {
                pRow.style.display = '';
                playerMatchesCount++;
            } else {
                pRow.style.display = 'none';
            }
        });

        if (clubMatches || playerMatchesCount > 0) {
            item.style.display = 'block';
            if (term !== '' && playerMatchesCount > 0 && !clubMatches) {
                item.classList.add('open');
            }
        } else {
            item.style.display = 'none';
        }
    });
}

document.getElementById('form-preview-db3')?.addEventListener('submit', function() {
    var btn = document.getElementById('btn-preview-db3');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined db3-spin">refresh</span> Analisando Arquivo DB3...';
    }
});

document.getElementById('form-execute-db3')?.addEventListener('submit', function() {
    var btn = document.getElementById('btn-execute-db3');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined db3-spin">refresh</span> Executando Importação...';
    }
});
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
