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

// Função para parsear a entrada de erro do PHP
function parseLogLine(string $entry): array {
    $rawLines = explode("\n", str_replace("\r", "", $entry));
    $lines = [];
    foreach ($rawLines as $l) {
        if (trim($l) !== '') {
            $lines[] = trim($l);
        }
    }
    
    $dateTime = 'N/A';
    $type = 'Unknown';
    $message = '';
    $file = '';
    $lineNum = '';
    $stackTrace = [];
    
    if (!empty($lines)) {
        $firstLine = $lines[0];
        
        // Extrai data/hora da primeira linha: [29-Aug-2026 16:36:22 America/Sao_Paulo] ...
        if (preg_match('/^\[([^\]]+)\]\s*(.*)$/', $firstLine, $matches)) {
            $dateTime = $matches[1];
            $rest = $matches[2];
            
            // Identifica o tipo do erro (ex: PHP Deprecated:, PHP Warning:, PHP Fatal error:, PHP Notice:, etc.)
            if (preg_match('/^(PHP [a-zA-Z0-9_\s]+?):\s*(.*)$/i', $rest, $typeMatches)) {
                $type = $typeMatches[1];
                $message = $typeMatches[2];
            } else {
                $message = $rest;
            }
            
            // Extrai arquivo e linha se houver (ex: "... in /caminho/arquivo.php on line 123" ou "... in D:\path\file.php:123")
            if (preg_match('/\s+in\s+(.+?)(?:\s+on\s+line\s+(\d+)|:(\d+))$/i', $message, $fileMatches)) {
                $file = $fileMatches[1];
                $lineNum = !empty($fileMatches[2]) ? $fileMatches[2] : ($fileMatches[3] ?? '');
                $message = preg_replace('/\s+in\s+.+?(?:\s+on\s+line\s+\d+|:\d+)$/i', '', $message);
            }
        } else {
            $message = $firstLine;
            if (preg_match('/\s+in\s+(.+?)(?:\s+on\s+line\s+(\d+)|:(\d+))$/i', $message, $fileMatches)) {
                $file = $fileMatches[1];
                $lineNum = !empty($fileMatches[2]) ? $fileMatches[2] : ($fileMatches[3] ?? '');
                $message = preg_replace('/\s+in\s+.+?(?:\s+on\s+line\s+\d+|:\d+)$/i', '', $message);
            }
        }
        
        // Trata linhas seguintes como stack trace
        for ($k = 1; $k < count($lines); $k++) {
            $sLine = $lines[$k];
            // Remove timestamp repetido do log em cada linha do stack trace
            $sLineClean = preg_replace('/^\[[^\]]+\]\s*/', '', $sLine);
            $stackTrace[] = $sLineClean;
        }
    }
    
    return [
        'datetime' => $dateTime,
        'type' => trim($type),
        'message' => trim($message),
        'file' => trim($file),
        'line' => trim($lineNum),
        'stack' => implode("\n", $stackTrace)
    ];
}

// Gera hash de identificação única do erro baseando-se estritamente na Mensagem, Arquivo e Linha (sem stack trace)
function computeLogEntryHash(string $entry): string {
    $parsed = parseLogLine($entry);
    
    $type = strtolower(trim($parsed['type']));
    $msg = strtolower(preg_replace('/\s+/', ' ', trim($parsed['message'])));
    $file = strtolower(str_replace('\\', '/', trim($parsed['file'])));
    $line = trim((string)$parsed['line']);
    
    // Se temos a mensagem e ao menos arquivo ou linha, o hash compara apenas erro + arquivo + linha
    if ($msg !== '' && ($file !== '' || $line !== '')) {
        return md5($type . '|' . $msg . '|' . $file . '|' . $line);
    }
    
    // Fallback caso não seja possível extrair arquivo e linha da primeira linha
    $cleanFirstLine = '';
    $clean = str_replace("\r", "", $entry);
    $lines = explode("\n", $clean);
    foreach ($lines as $l) {
        $t = trim($l);
        if ($t !== '') {
            $cleanFirstLine = preg_replace('/^\[[^\]]+\]\s*/', '', $t);
            break;
        }
    }
    return md5(strtolower(trim($cleanFirstLine)));
}

$prodLogDir = '/home/lhsaia/confusa.top/logs';
$prodLogPath = $prodLogDir . '/php_errors.log';

$host = $_SERVER['HTTP_HOST'] ?? '';
$isWindows = (PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\');

$isProduction = !$isWindows 
    && is_dir($prodLogDir) 
    && (strpos($host, 'confusa.top') !== false)
    && (strpos($host, 'local') === false);

$logPath = $isProduction 
    ? $prodLogPath 
    : dirname(__DIR__) . '/php_errors.log';

$message = '';
$messageType = '';

// Verifica se uma linha inicia um novo erro (ex: [data] PHP Warning/Notice/Deprecated/Fatal...)
function isLogEntryStart(string $line): bool {
    $trimmed = trim($line);
    // Início de erro padrão do PHP (ex: [29-Aug-2026 16:36:22 America/Sao_Paulo] PHP Deprecated: ...)
    // Ignora linhas que são continuação de Stack trace: "PHP Stack trace:", "PHP   1. {main}()"
    if (preg_match('/^\[[^\]]+\]\s+PHP\s+(?:Stack\s+trace|\d+\.)/i', $trimmed)) {
        return false;
    }
    // Caso padrão de início de log com data
    if (preg_match('/^\[[^\]]+\]/', $trimmed)) {
        return true;
    }
    return false;
}

// Ação de Limpar ou Excluir Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear') {
        if (file_exists($logPath)) {
            if (is_writable($logPath)) {
                file_put_contents($logPath, '');
                $message = 'Arquivo de logs limpo com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro: O arquivo de logs não tem permissão de escrita.';
                $messageType = 'error';
            }
        } else {
            // Se não existir, tenta criar vazio
            @file_put_contents($logPath, '');
            $message = 'Arquivo de logs criado e limpo com sucesso!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'delete_single' && !empty($_POST['entry_hash'])) {
        $hashToDelete = trim((string)$_POST['entry_hash']);
        
        if (file_exists($logPath) && is_writable($logPath)) {
            $tempPath = $logPath . '.tmp.' . uniqid();
            $in = fopen($logPath, 'r');
            $out = fopen($tempPath, 'w');
            
            if ($in && $out) {
                $currentEntry = '';
                $countDeleted = 0;
                
                $writeGroup = function($entry) use ($out, $hashToDelete, &$countDeleted) {
                    if (trim($entry) === '') return;
                    if (computeLogEntryHash($entry) === $hashToDelete) {
                        $countDeleted++;
                    } else {
                        fwrite($out, $entry);
                    }
                };
                
                while (($line = fgets($in)) !== false) {
                    if (isLogEntryStart($line)) {
                        $writeGroup($currentEntry);
                        $currentEntry = $line;
                    } else {
                        $currentEntry .= $line;
                    }
                }
                $writeGroup($currentEntry);
                
                fclose($in);
                fclose($out);
                
                if ($countDeleted > 0) {
                    rename($tempPath, $logPath);
                    $message = $countDeleted > 1 
                        ? "$countDeleted registros de erros idênticos foram removidos com sucesso!" 
                        : "Registro de erro removido com sucesso!";
                    $messageType = 'success';
                } else {
                    @unlink($tempPath);
                    $message = "Erro: Nenhum erro correspondente foi encontrado para exclusão.";
                    $messageType = 'error';
                }
            } else {
                if ($in) fclose($in);
                if ($out) {
                    fclose($out);
                    @unlink($tempPath);
                }
                $message = "Erro ao processar a exclusão do log.";
                $messageType = 'error';
            }
        }
    }
}

// Função para ler as últimas N entradas do log do final para o início agrupando blocos de erro e somando ocorrências idênticas
function getLatestLogEntries(string $filePath, int $limit): array {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return [];
    }

    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return [];
    }

    fseek($handle, 0, SEEK_END);
    $pos = ftell($handle);
    
    $buffer = '';
    $chunkSize = 4096;
    $groupedMap = []; // hash => ['hash' => ..., 'raw' => ..., 'count' => N, 'last_seen' => timestamp]
    $currentEntryLines = [];
    $totalRead = 0;
    
    while ($pos > 0 && $totalRead < $limit) {
        $readSize = min($pos, $chunkSize);
        $pos -= $readSize;
        fseek($handle, $pos);
        $chunk = fread($handle, $readSize);
        
        $buffer = $chunk . $buffer;
        $lines = explode("\n", $buffer);
        
        if ($pos > 0) {
            $buffer = array_shift($lines);
        } else {
            $buffer = '';
        }
        
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = $lines[$i];
            $trimmed = trim($line);
            if ($trimmed === '' && empty($currentEntryLines)) {
                continue;
            }
            
            array_unshift($currentEntryLines, $line);
            
            if (isLogEntryStart($line)) {
                $entryText = implode("\n", $currentEntryLines);
                $hash = computeLogEntryHash($entryText);
                
                if (isset($groupedMap[$hash])) {
                    $groupedMap[$hash]['count']++;
                } else {
                    $groupedMap[$hash] = [
                        'hash' => $hash,
                        'raw' => $entryText,
                        'count' => 1
                    ];
                }
                
                $totalRead++;
                $currentEntryLines = [];
            }
        }
    }
    
    if (!empty($currentEntryLines)) {
        $entryText = implode("\n", $currentEntryLines);
        if (trim($entryText) !== '') {
            $hash = computeLogEntryHash($entryText);
            if (isset($groupedMap[$hash])) {
                $groupedMap[$hash]['count']++;
            } else {
                $groupedMap[$hash] = [
                    'hash' => $hash,
                    'raw' => $entryText,
                    'count' => 1
                ];
            }
        }
    }
    
    fclose($handle);
    return array_values($groupedMap);
}

$maxLines = 1000;
$logEntries = getLatestLogEntries($logPath, $maxLines);

// Títulos e estilos
$page_title = "Agregador de Logs de Erros";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="logs-container">
    <div class="logs-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px;">
        <div>
            <h1 class="admin-gradient-title">Central de Logs de Erros</h1>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 14px;">Monitoramento e depuração centralizada do site Confusa.top</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" onsubmit="return confirm('Tem certeza que deseja apagar todos os registros do arquivo de log?');" style="margin: 0;">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="admin-btn admin-btn-danger">Limpar Logs</button>
            </form>
            <a href="/admin/index.php" class="admin-btn admin-btn-secondary">Voltar ao Painel</a>
        </div>
    </div>

    <!-- Status e Informações -->
    <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 15px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: #94a3b8;">Caminho do Arquivo:</span> 
            <code style="background: #0f172a; padding: 3px 8px; border-radius: 4px; color: #38bdf8; font-size: 13px; font-family: monospace; border: 1px solid rgba(255,255,255,0.05);"><?= htmlspecialchars($logPath) ?></code>
            <span style="margin: 0 10px; color: rgba(255,255,255,0.1);">|</span>
            <span style="color: #94a3b8;">Status:</span> 
            <?php if (file_exists($logPath)): ?>
                <span style="color: #10b981; font-weight: bold;">● Ativo</span> (<?= round(filesize($logPath) / 1024, 2) ?> KB)
            <?php else: ?>
                <span style="color: #f59e0b; font-weight: bold;">○ Inexistente / Vazio</span>
            <?php endif; ?>
        </div>
        <div>
            <span style="color: #94a3b8;">Registros exibidos:</span> <strong><?= count($logEntries) ?></strong> (Limite: <?= $maxLines ?>)
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div style="background: <?= $messageType === 'success' ? '#064e3b' : '#7f1d1d' ?>; border: 1px solid <?= $messageType === 'success' ? '#059669' : '#dc2626' ?>; color: <?= $messageType === 'success' ? '#a7f3d0' : '#fca5a5' ?>; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Barra de Filtros e Busca -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" id="logSearch" placeholder="Pesquisar por erro, arquivo ou código..." class="admin-input" onkeyup="filterLogs()">
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="filterType('ALL')" class="btn-filter active" style="padding: 8px 16px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: #334155; color: white; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;">Todos</button>
            <button onclick="filterType('FATAL')" class="btn-filter" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #ef4444; background: transparent; color: #fca5a5; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;">Fatais</button>
            <button onclick="filterType('WARNING')" class="btn-filter" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #f97316; background: transparent; color: #ffedd5; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;">Warnings</button>
            <button onclick="filterType('NOTICE')" class="btn-filter" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #eab308; background: transparent; color: #fef9c3; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;">Notices</button>
        </div>
    </div>

    <!-- Tabela de Logs -->
    <div class="admin-table-container">
        <table id="logTable" class="admin-table">
            <thead>
                <tr>
                    <th style="width: 180px;">Data/Hora (UTC)</th>
                    <th style="width: 140px;">Tipo de Erro</th>
                    <th>Detalhes e Mensagem</th>
                    <th style="width: 220px;">Arquivo / Linha</th>
                    <th style="width: 80px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logEntries)): ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #64748b;">Nenhum erro registrado no arquivo de log.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logEntries as $entry): 
                        $rawEntry = $entry['raw'];
                        $originalIdx = $entry['hash'];
                        $parsed = parseLogLine($rawEntry);
                        
                        // Determinar a gravidade para cores e filtros
                        $severity = 'other';
                        $badgeClass = 'admin-badge-secondary';
                        $badgeBg = 'rgba(255, 255, 255, 0.1)';
                        $badgeColor = '#cbd5e1';
                        
                        $typeLower = strtolower($parsed['type']);
                        if (strpos($typeLower, 'fatal') !== false || strpos($typeLower, 'error') !== false || strpos($typeLower, 'parse') !== false || strpos($typeLower, 'exception') !== false) {
                            $severity = 'fatal';
                            $badgeClass = 'admin-badge-danger';
                        } elseif (strpos($typeLower, 'warning') !== false) {
                            $severity = 'warning';
                            $badgeClass = 'admin-badge-warning';
                        } elseif (strpos($typeLower, 'notice') !== false || strpos($typeLower, 'deprecated') !== false) {
                            $severity = 'notice';
                            $badgeClass = 'admin-badge-warning';
                        }
                    ?>
                        <tr class="log-row" data-severity="<?= $severity ?>">
                            <!-- Data/Hora -->
                            <td style="font-family: monospace; white-space: nowrap;">
                                <?= htmlspecialchars($parsed['datetime']) ?>
                            </td>
                            <!-- Badge de Tipo e Ocorrências -->
                            <td style="white-space: nowrap;">
                                <span class="admin-badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($parsed['type']) ?>
                                </span>
                                <?php if (($entry['count'] ?? 1) > 1): ?>
                                    <span style="display: inline-block; margin-left: 6px; padding: 2px 7px; background: rgba(56, 189, 248, 0.2); border: 1px solid rgba(56, 189, 248, 0.4); color: #38bdf8; font-size: 11px; font-weight: 700; border-radius: 12px;" title="<?= $entry['count'] ?> ocorrências idênticas">
                                        <?= $entry['count'] ?>x
                                    </span>
                                <?php endif; ?>
                            </td>
                            <!-- Mensagem -->
                            <td style="word-break: break-word; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px;">
                                <?= nl2br(htmlspecialchars($parsed['message'])) ?>
                                <?php if ($parsed['stack'] !== ''): ?>
                                    <details style="margin-top: 8px; color: #94a3b8; font-size: 12px; cursor: pointer;">
                                        <summary style="font-weight: 600; outline: none; user-select: none; color: #38bdf8;">Ver Stack Trace</summary>
                                        <pre style="margin-top: 5px; background: #0f172a; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); line-height: 1.4; font-family: monospace; white-space: pre-wrap;"><?= htmlspecialchars($parsed['stack']) ?></pre>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <!-- Arquivo e Linha -->
                            <td style="word-break: break-all; font-size: 12px;">
                                <?php if ($parsed['file'] !== ''): ?>
                                    <span style="color: #38bdf8; font-weight: 600;"><?= htmlspecialchars(basename($parsed['file'])) ?></span>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px; font-family: monospace;"><?= htmlspecialchars($parsed['file']) ?></div>
                                    <div style="font-size: 11px; color: #f59e0b; margin-top: 2px;">Linha: <strong><?= htmlspecialchars($parsed['line']) ?></strong></div>
                                <?php else: ?>
                                    <span style="color: #475569;">-</span>
                                <?php endif; ?>
                            </td>
                            <!-- Ações -->
                            <td style="text-align: center; vertical-align: middle;">
                                <form method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('Deseja realmente excluir <?= (($entry['count'] ?? 1) > 1) ? "todas as " . $entry['count'] . " ocorrências deste erro" : "este registro de erro" ?>?');">
                                    <input type="hidden" name="action" value="delete_single">
                                    <input type="hidden" name="entry_hash" value="<?= $originalIdx ?>">
                                    <button type="submit" style="background: transparent; color: #ef4444; border: none; cursor: pointer; padding: 4px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.15)'" onmouseout="this.style.background='transparent'" title="Excluir este erro (<?= ($entry['count'] ?? 1) ?> ocorrência(s))">
                                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let currentFilter = 'ALL';

function filterLogs() {
    const searchVal = document.getElementById('logSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.log-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const severity = row.getAttribute('data-severity');
        
        const matchesSearch = text.includes(searchVal);
        const matchesSeverity = (currentFilter === 'ALL' || severity.toUpperCase() === currentFilter);
        
        if (matchesSearch && matchesSeverity) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterType(type) {
    currentFilter = type;
    
    // Atualiza botões
    const buttons = document.querySelectorAll('.btn-filter');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        // Reset estilos padrão
        if (btn.innerText === 'Todos') {
            btn.style.background = 'transparent';
            btn.style.color = '#94a3b8';
        } else if (btn.innerText === 'Fatais') {
            btn.style.background = 'transparent';
            btn.style.color = '#fca5a5';
        } else if (btn.innerText === 'Warnings') {
            btn.style.background = 'transparent';
            btn.style.color = '#ffedd5';
        } else if (btn.innerText === 'Notices') {
            btn.style.background = 'transparent';
            btn.style.color = '#fef9c3';
        }
    });

    // Destaca botão selecionado
    const selectedBtn = event.currentTarget;
    selectedBtn.classList.add('active');
    
    if (type === 'ALL') {
        selectedBtn.style.background = '#334155';
        selectedBtn.style.color = 'white';
    } else if (type === 'FATAL') {
        selectedBtn.style.background = '#ef4444';
        selectedBtn.style.color = 'white';
    } else if (type === 'WARNING') {
        selectedBtn.style.background = '#f97316';
        selectedBtn.style.color = 'white';
    } else if (type === 'NOTICE') {
        selectedBtn.style.background = '#eab308';
        selectedBtn.style.color = 'black';
    }
    
    filterLogs();
}
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
