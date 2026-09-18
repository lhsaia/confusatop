<?php
declare(strict_types=1);

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['admin_status'] ?? 0) !== 1) {
    header('Location: /index.php');
    exit;
}

// Obter nome do banco de dados atual
$dbName = 'confusa';
try {
    $stmtDb = $db->query("SELECT DATABASE()");
    $currentDb = $stmtDb->fetchColumn();
    if (!empty($currentDb)) {
        $dbName = (string)$currentDb;
    }
} catch (Exception $e) {
    // Mantém fallback
}

$errorMessage = '';

// =========================================================================
// PROCESSAMENTO DO DOWNLOAD DO BACKUP SQL
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $exportType = $_POST['export_type'] ?? 'full'; // 'full' ou 'schema'
    $compression = $_POST['compression'] ?? 'gz';  // 'gz' ou 'sql'
    $selectedTablesInput = $_POST['tables'] ?? [];

    $tmpFile = tempnam(sys_get_temp_dir(), 'confusa_backup_');
    $useGzip = ($compression === 'gz' && function_exists('gzopen'));

    try {
        if ($useGzip) {
            $fp = gzopen($tmpFile, 'wb9');
            if (!$fp) {
                throw new Exception('Não foi possível abrir arquivo temporário compactado para escrita.');
            }
            $write = function(string $str) use ($fp) { gzwrite($fp, $str); };
        } else {
            $fp = fopen($tmpFile, 'wb');
            if (!$fp) {
                throw new Exception('Não foi possível abrir arquivo temporário para escrita.');
            }
            $write = function(string $str) use ($fp) { fwrite($fp, $str); };
        }

        // Buscar lista real de tabelas existentes no banco
        $stmtAllTables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $availableTables = [];
        while ($row = $stmtAllTables->fetch(PDO::FETCH_NUM)) {
            $availableTables[] = $row[0];
        }

        // Filtrar tabelas selecionadas
        $tablesToExport = [];
        if (empty($selectedTablesInput) || in_array('__all__', (array)$selectedTablesInput, true)) {
            $tablesToExport = $availableTables;
        } else {
            foreach ((array)$selectedTablesInput as $tbl) {
                if (in_array($tbl, $availableTables, true)) {
                    $tablesToExport[] = $tbl;
                }
            }
        }

        if (empty($tablesToExport)) {
            $tablesToExport = $availableTables;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $downloadFilename = "backup_{$dbName}_{$timestamp}." . ($useGzip ? 'sql.gz' : 'sql');

        // Cabeçalho do arquivo SQL
        $write("-- ========================================================\n");
        $write("-- CONFUSA.top - Database Backup SQL\n");
        $write("-- Gerado por: " . addslashes($_SESSION['nomereal'] ?? $_SESSION['username'] ?? 'Administrador') . " (@" . addslashes($_SESSION['username'] ?? '') . ")\n");
        $write("-- Data de Geração: " . date('Y-m-d H:i:s') . "\n");
        $write("-- Banco de Dados: `{$dbName}`\n");
        $write("-- Tipo de Exportação: " . ($exportType === 'schema' ? 'Apenas Estrutura (Schema)' : 'Estrutura e Dados (Completo)') . "\n");
        $write("-- Tabelas Exportadas: " . count($tablesToExport) . "\n");
        $write("-- ========================================================\n\n");

        $write("SET NAMES utf8mb4;\n");
        $write("SET FOREIGN_KEY_CHECKS = 0;\n");
        $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        $write("SET AUTOCOMMIT = 0;\n");
        $write("START TRANSACTION;\n\n");

        foreach ($tablesToExport as $table) {
            $write("\n-- --------------------------------------------------------\n");
            $write("-- Estrutura da tabela `{$table}`\n");
            $write("-- --------------------------------------------------------\n");
            $write("DROP TABLE IF EXISTS `{$table}`;\n");

            // Obter CREATE TABLE
            $stmtCreate = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $stmtCreate->fetch(PDO::FETCH_NUM);
            if ($createRow && isset($createRow[1])) {
                $write($createRow[1] . ";\n\n");
            }

            // Exportar dados se solicitado usando query desbufferizada (baixo consumo de RAM)
            if ($exportType === 'full') {
                $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $stmtRows = $db->query("SELECT * FROM `{$table}`");

                $columnNames = [];
                for ($i = 0; $i < $stmtRows->columnCount(); $i++) {
                    $meta = $stmtRows->getColumnMeta($i);
                    if ($meta && isset($meta['name'])) {
                        $columnNames[] = '`' . str_replace('`', '``', $meta['name']) . '`';
                    }
                }
                $colsSql = !empty($columnNames) ? ' (' . implode(', ', $columnNames) . ')' : '';

                $batchSize = 250;
                $batch = [];
                $hasRows = false;

                while ($row = $stmtRows->fetch(PDO::FETCH_NUM)) {
                    $hasRows = true;
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif (is_int($val) || is_float($val)) {
                            $values[] = (string)$val;
                        } else {
                            $values[] = $db->quote((string)$val);
                        }
                    }
                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= $batchSize) {
                        $write("INSERT INTO `{$table}`{$colsSql} VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                $stmtRows->closeCursor();
                $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

                if (!empty($batch)) {
                    $write("INSERT INTO `{$table}`{$colsSql} VALUES\n" . implode(",\n", $batch) . ";\n");
                }
                if ($hasRows) {
                    $write("\n");
                }
            }
        }

        $write("COMMIT;\n");
        $write("SET FOREIGN_KEY_CHECKS = 1;\n");
        $write("-- Fim do backup\n");

        if ($useGzip) {
            gzclose($fp);
        } else {
            fclose($fp);
        }

        $fileSize = filesize($tmpFile);
        if ($fileSize === false || $fileSize === 0) {
            throw new Exception('O arquivo de backup gerado está vazio (0 bytes).');
        }

        // Limpar qualquer buffer de saída pendente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Headers de download com tamanho exato
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($useGzip ? 'application/x-gzip' : 'application/sql; charset=utf-8'));
        header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);

        // Enviar arquivo em blocos
        $readFp = fopen($tmpFile, 'rb');
        if ($readFp) {
            while (!feof($readFp)) {
                echo fread($readFp, 1024 * 64);
                flush();
            }
            fclose($readFp);
        } else {
            readfile($tmpFile);
        }

        @unlink($tmpFile);
        exit;

    } catch (Throwable $t) {
        if (isset($tmpFile) && file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
        $errorMessage = 'Erro ao gerar backup SQL: ' . $t->getMessage();
    }
}

// =========================================================================
// LEITURA DE ESTATÍSTICAS DO BANCO PARA A INTERFACE
// =========================================================================
$tablesStats = [];
$totalDatabaseSizeMb = 0.0;
$totalEstimatedRows = 0;

try {
    $stmtStats = $db->prepare("
        SELECT 
            TABLE_NAME, 
            TABLE_ROWS, 
            DATA_LENGTH, 
            INDEX_LENGTH,
            (DATA_LENGTH + INDEX_LENGTH) AS TOTAL_SIZE,
            ENGINE,
            TABLE_COLLATION
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
        ORDER BY TABLE_NAME ASC
    ");
    $stmtStats->execute([$dbName]);
    $rawStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawStats as $st) {
        $sizeBytes = (int)($st['TOTAL_SIZE'] ?? 0);
        $sizeMb = $sizeBytes / (1024 * 1024);
        $totalDatabaseSizeMb += $sizeMb;
        $rows = (int)($st['TABLE_ROWS'] ?? 0);
        $totalEstimatedRows += $rows;

        $tablesStats[] = [
            'name' => (string)$st['TABLE_NAME'],
            'rows' => $rows,
            'size_formatted' => $sizeMb < 0.1 ? number_format($sizeBytes / 1024, 1, ',', '.') . ' KB' : number_format($sizeMb, 2, ',', '.') . ' MB',
            'engine' => (string)($st['ENGINE'] ?? 'InnoDB'),
        ];
    }
} catch (Exception $e) {
    // Fallback básico se information_schema não tiver permissão
    $stmtBasic = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($r = $stmtBasic->fetch(PDO::FETCH_NUM)) {
        $tablesStats[] = [
            'name' => (string)$r[0],
            'rows' => 0,
            'size_formatted' => '-',
            'engine' => '-',
        ];
    }
}

$page_title = "Exportar Banco de Dados (SQL)";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 30px; text-align: center;">
        <h1 class="admin-gradient-title">Exportar Banco de Dados (SQL)</h1>
        <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">
            Gere e baixe uma cópia integral do banco MySQL do sistema em arquivo <code>.sql</code> ou compactado <code>.sql.gz</code>.
        </p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-left: 4px solid #ef4444; border-radius: 8px; padding: 16px; margin-bottom: 25px; color: #fca5a5;">
            <strong style="display: block; margin-bottom: 4px;">Erro na Exportação:</strong>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Banner Informativo de Segurança -->
    <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-left: 4px solid #10b981; border-radius: 10px; padding: 18px 22px; margin-bottom: 30px; display: flex; align-items: center; gap: 16px;">
        <span class="material-symbols-outlined" style="font-size: 32px; color: #10b981; flex-shrink: 0;">verified_user</span>
        <div>
            <strong style="color: #34d399; font-size: 15px; display: block; margin-bottom: 4px;">Operação 100% Segura e Apenas de Leitura</strong>
            <p style="margin: 0; color: #cbd5e1; font-size: 13.5px; line-height: 1.5;">
                Esta ferramenta executa apenas consultas <code>SELECT</code> e <code>SHOW</code>. Nenhum dado é alterado, excluído ou bloqueado durante a exportação. O download é gerado de forma desbufferizada com baixo consumo de memória.
            </p>
        </div>
    </div>

    <!-- Estatísticas Rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 18px; text-align: center;">
            <span class="material-symbols-outlined" style="color: #38bdf8; font-size: 28px; margin-bottom: 6px;">database</span>
            <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Banco Atual</div>
            <div style="font-size: 18px; font-weight: 700; color: #f8fafc; margin-top: 4px;"><?php echo htmlspecialchars($dbName); ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 18px; text-align: center;">
            <span class="material-symbols-outlined" style="color: #818cf8; font-size: 28px; margin-bottom: 6px;">table_chart</span>
            <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Total de Tabelas</div>
            <div style="font-size: 18px; font-weight: 700; color: #f8fafc; margin-top: 4px;"><?php echo count($tablesStats); ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 18px; text-align: center;">
            <span class="material-symbols-outlined" style="color: #10b981; font-size: 28px; margin-bottom: 6px;">storage</span>
            <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Tamanho Estimado</div>
            <div style="font-size: 18px; font-weight: 700; color: #f8fafc; margin-top: 4px;"><?php echo number_format($totalDatabaseSizeMb, 2, ',', '.'); ?> MB</div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 18px; text-align: center;">
            <span class="material-symbols-outlined" style="color: #f59e0b; font-size: 28px; margin-bottom: 6px;">reorder</span>
            <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Linhas Estimadas</div>
            <div style="font-size: 18px; font-weight: 700; color: #f8fafc; margin-top: 4px;"><?php echo number_format($totalEstimatedRows, 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Formulário de Exportação -->
    <form method="POST" action="/admin/exportar_sql.php" id="exportForm">
        <input type="hidden" name="download_backup" value="1">

        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 24px; margin-bottom: 25px;">
            <h2 style="font-size: 18px; color: #f8fafc; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-outlined" style="color: #38bdf8;">tune</span>
                Opções de Exportação
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <!-- Conteúdo do Dump -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px;">
                        Conteúdo do Dump
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); cursor: pointer;">
                            <input type="radio" name="export_type" value="full" checked style="accent-color: #38bdf8;">
                            <div>
                                <strong style="color: #f8fafc; font-size: 14px;">Completo (Estrutura + Dados)</strong>
                                <div style="font-size: 12px; color: #94a3b8;">Gera CREATE TABLE e todos os INSERT INTO (Recomendado).</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); cursor: pointer;">
                            <input type="radio" name="export_type" value="schema" style="accent-color: #38bdf8;">
                            <div>
                                <strong style="color: #f8fafc; font-size: 14px;">Apenas Estrutura (Schema)</strong>
                                <div style="font-size: 12px; color: #94a3b8;">Exporta apenas a definição das tabelas, sem registros.</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Formato / Compressão -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px;">
                        Formato do Arquivo
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); cursor: pointer;">
                            <input type="radio" name="compression" value="gz" checked style="accent-color: #38bdf8;">
                            <div>
                                <strong style="color: #f8fafc; font-size: 14px;">Compactado GZIP (.sql.gz)</strong>
                                <div style="font-size: 12px; color: #94a3b8;">Até 80% menor (~12 MB), download muito mais rápido (Recomendado).</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); cursor: pointer;">
                            <input type="radio" name="compression" value="sql" style="accent-color: #38bdf8;">
                            <div>
                                <strong style="color: #f8fafc; font-size: 14px;">Texto Puro (.sql)</strong>
                                <div style="font-size: 12px; color: #94a3b8;">Arquivo de texto SQL convencional sem compressão (~60 MB).</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Seleção de Tabelas -->
            <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                    <div>
                        <strong style="color: #f8fafc; font-size: 15px;">Tabelas para Exportar</strong>
                        <div style="font-size: 12px; color: #94a3b8;">Por padrão todas as tabelas estão marcadas.</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="admin-btn admin-btn-secondary" id="btnSelectAll" style="padding: 6px 12px; font-size: 12px;">
                            Marcar Todas
                        </button>
                        <button type="button" class="admin-btn admin-btn-secondary" id="btnUnselectAll" style="padding: 6px 12px; font-size: 12px;">
                            Desmarcar Todas
                        </button>
                    </div>
                </div>

                <div style="max-height: 280px; overflow-y: auto; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px;">
                        <?php foreach ($tablesStats as $tbl): ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; background: rgba(255,255,255,0.02); font-size: 13px; color: #cbd5e1; cursor: pointer;">
                                <input type="checkbox" name="tables[]" value="<?php echo htmlspecialchars($tbl['name']); ?>" checked class="tbl-checkbox" style="accent-color: #38bdf8;">
                                <span style="font-family: monospace; font-size: 12.5px; color: #f8fafc;"><?php echo htmlspecialchars($tbl['name']); ?></span>
                                <span style="margin-left: auto; font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($tbl['size_formatted']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 30px;">
            <a href="/admin/index.php" class="admin-btn admin-btn-secondary" style="width: auto !important; flex: 0 0 auto !important;">
                <span class="material-symbols-outlined">arrow_back</span>
                Voltar ao Painel
            </a>
            <button type="submit" class="admin-btn admin-btn-primary" id="btnSubmitExport" style="width: auto !important; max-width: max-content !important; flex: 0 0 auto !important; padding: 11px 22px !important; font-size: 14px !important; white-space: nowrap !important; background: linear-gradient(135deg, #0284c7, #0369a1) !important;">
                <span class="material-symbols-outlined" style="font-size: 20px;">download</span>
                Gerar e Baixar Backup SQL
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSelectAll = document.getElementById('btnSelectAll');
    const btnUnselectAll = document.getElementById('btnUnselectAll');
    const checkboxes = document.querySelectorAll('.tbl-checkbox');
    const btnSubmit = document.getElementById('btnSubmitExport');

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
        });
    }

    if (btnUnselectAll) {
        btnUnselectAll.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
        });
    }

    const form = document.getElementById('exportForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (!anyChecked) {
                e.preventDefault();
                alert('Selecione pelo menos uma tabela para exportar.');
                return;
            }

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="material-symbols-outlined" style="font-size: 22px; animation: spin 1s linear infinite;">sync</span> Gerando Backup...';
                setTimeout(() => {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<span class="material-symbols-outlined" style="font-size: 22px;">download</span> Gerar e Baixar Backup SQL';
                }, 8000);
            }
        });
    }
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
