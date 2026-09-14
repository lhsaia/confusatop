<?php
date_default_timezone_set('America/Sao_Paulo');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

// CLI/Cron runner for next-day matches simulation
$cronLogFile = __DIR__ . '/cron_exec.log';
function cron_log($msg) {
    global $cronLogFile;
    $line = (strpos($msg, '[') === 0 ? "" : "[" . date('Y-m-d H:i:s') . "] ") . $msg . "\n";
    echo $line;
    @file_put_contents($cronLogFile, $line, FILE_APPEND);
}

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        cron_log("[FATAL ERROR SHUTDOWN] " . $error['message'] . " em " . $error['file'] . ":" . $error['line']);
    }
});

$docRoot = dirname(__DIR__);
$isCommandLine = (php_sapi_name() === 'cli') || (php_sapi_name() === 'cgi') || (php_sapi_name() === 'cgi-fcgi') || !isset($_SERVER['HTTP_HOST']);

cron_log("=== DISPARO DO CRON INICIADO ===");
cron_log("PHP: " . PHP_VERSION . " | SAPI: " . php_sapi_name() . " | BIN: " . (defined('PHP_BINARY') ? PHP_BINARY : 'N/A') . " | CLI: " . ($isCommandLine ? 'SIM' : 'NAO'));

if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
    @session_start();
}
$isAdmin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && (int)($_SESSION['admin_status'] ?? 0) === 1;

if (!$isCommandLine && !isset($_GET['cron_key']) && !$isAdmin) {
    // Permitir execução via CLI ou via Web se cron_key estiver presente ou admin autenticado
    cron_log("[BLOQUEIO 403] Acesso negado: não é CLI, falta cron_key e não é Admin autenticado.");
    header('HTTP/1.0 403 Forbidden');
    die("Acesso restrito ao agendador (Cron CLI).");
}

if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sqliteDatabase.php';
require_once __DIR__ . '/../objetos/competicao_clube.php';
require_once __DIR__ . '/../objetos/time.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

// Selecionar jogos pendentes (status = 0) agendados até as próximas 24 horas (incluindo partidas atrasadas no passado)
$fimBusca = date('Y-m-d H:i:s', strtotime('+24 hours')); // Até 24 horas à frente

cron_log("Iniciando Cron de Simulação para partidas pendentes até {$fimBusca}...");

// Buscar jogos pendentes com data até o limite estipulado
$query = "SELECT id, competicao_id AS competicao, timeA_id AS timeA, timeB_id AS timeB, estadio_id AS estadio, neutro, fase, data, subir_live 
          FROM jogos_clube 
          WHERE status = 0 
            AND simulador_interno = 1
            AND data <= :fim 
          ORDER BY data ASC, fase ASC, id ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':fim', $fimBusca);
$stmt->execute();

$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar para verificar e avançar fases de mata-mata concluídas
function checarAvancoMataMataAtivos($db, $competicaoObj) {
    try {
        // Ordena pela ordem cronológica do mata-mata (64-avos=11, 32-avos=10, 16-avos=9, Oitavos=3, Quartas=4, Semi=5)
        $stmtFases = $db->query("
            SELECT DISTINCT competicao_id, fase 
            FROM jogos_clube 
            WHERE simulador_interno = 1 AND fase IN (11, 10, 9, 3, 4, 5) 
            ORDER BY competicao_id, FIELD(fase, 11, 10, 9, 3, 4, 5)
        ");
        if ($stmtFases) {
            while ($rFase = $stmtFases->fetch(PDO::FETCH_ASSOC)) {
                $compId = (int)$rFase['competicao_id'];
                $faseId = (int)$rFase['fase'];
                $avancou = $competicaoObj->verificarEAvancarMataMata($compId, $faseId);
                if ($avancou) {
                    cron_log("[MATA-MATA CRON] Competição #{$compId}: Fase {$faseId} avançada com sucesso para a próxima fase.");
                }
            }
        }
    } catch (\Throwable $e) {
        error_log("PHP Simulador: [ERRO AVANÇO MATA-MATA CRON GERAL] " . $e->getMessage());
        cron_log("[ERRO MATA-MATA CRON] " . $e->getMessage());
    }
}

if (empty($partidas)) {
    cron_log("Nenhuma partida pendente encontrada até {$fimBusca}.");
    checarAvancoMataMataAtivos($db, $competicaoObj);
    cron_log("=== DISPARO DO CRON CONCLUÍDO (SEM PARTIDAS) ===\n");
    exit(0);
}

cron_log("Encontrada(s) " . count($partidas) . " partida(s) para simular.");

$hexacolorDir = __DIR__ . '/hexacolor';

// Helper para garantir e resolver imagens dos clubes no SQLite temporário antes da engine Java rodar
function resolverCaminhoImagem($relPath, $hexacolorDir, $docRoot, $fallbackRel) {
    if (empty($relPath)) {
        return $fallbackRel;
    }
    
    // Caminho absoluto no disco
    if (strpos($relPath, '../../') === 0) {
        $sub = substr($relPath, 6);
        $abs = $docRoot . '/' . ltrim($sub, '/\\');
    } else {
        $abs = $hexacolorDir . '/' . ltrim($relPath, '/\\');
    }
    
    $resolvedAbs = null;
    $resolvedRel = null;
    
    if (file_exists($abs) && is_file($abs) && @filesize($abs) > 0) {
        $resolvedAbs = $abs;
        $resolvedRel = $relPath;
    } else {
        // Tenta encontrar ignorando maiúsculas/minúsculas no mesmo diretório
        $dir = dirname($abs);
        $filename = basename($abs);
        if (is_dir($dir)) {
            $files = @scandir($dir);
            if ($files) {
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..' && strcasecmp($f, $filename) === 0 && is_file($dir . '/' . $f)) {
                        $resolvedAbs = $dir . '/' . $f;
                        if (strpos($relPath, '../../') === 0) {
                            $resolvedRel = '../../' . ltrim(substr(dirname($relPath), 6), '/\\') . '/' . $f;
                        } else {
                            $resolvedRel = (dirname($relPath) !== '.' ? dirname($relPath) . '/' : '') . $f;
                        }
                        break;
                    }
                }
            }
        }
        
        // Tenta procurar em images/escudos ou images/uniformes pelo nome do arquivo
        if (!$resolvedAbs) {
            $filenameBase = basename($relPath);
            if (!empty($filenameBase)) {
                $altDirs = [$docRoot . '/images/escudos', $docRoot . '/images/uniformes', $hexacolorDir . '/Imagens', $hexacolorDir . '/Escudos', $hexacolorDir . '/Uniformes'];
                foreach ($altDirs as $altDir) {
                    if (is_dir($altDir)) {
                        $altFiles = @scandir($altDir);
                        if ($altFiles) {
                            foreach ($altFiles as $af) {
                                if ($af !== '.' && $af !== '..' && strcasecmp($af, $filenameBase) === 0 && is_file($altDir . '/' . $af)) {
                                    $resolvedAbs = $altDir . '/' . $af;
                                    if (strpos($altDir, $docRoot) === 0) {
                                        $relFromDoc = substr($altDir, strlen($docRoot));
                                        $resolvedRel = '../../' . ltrim($relFromDoc, '/\\') . '/' . $af;
                                    } elseif (strpos($altDir, $hexacolorDir) === 0) {
                                        $relFromHexa = substr($altDir, strlen($hexacolorDir));
                                        $resolvedRel = ltrim($relFromHexa, '/\\') . '/' . $af;
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Se não encontrou o arquivo em lugar nenhum, usar fallback
    if (!$resolvedAbs || !file_exists($resolvedAbs) || @filesize($resolvedAbs) <= 0) {
        return $fallbackRel;
    }
    
    $ext = strtolower(pathinfo($resolvedAbs, PATHINFO_EXTENSION));
    
    // Tratamento especial para WEBP: Java 8 (ImageIO) não suporta .webp nativamente e dá erro "image == null"
    if ($ext === 'webp') {
        $cacheDir = $hexacolorDir . '/cache_images';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $pngFilename = md5($resolvedAbs) . '.png';
        $pngAbs = $cacheDir . '/' . $pngFilename;
        $pngRel = 'cache_images/' . $pngFilename;
        
        if (file_exists($pngAbs) && @filesize($pngAbs) > 0) {
            return $pngRel;
        }
        
        if (function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($resolvedAbs);
            if ($img) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                if (@imagepng($img, $pngAbs, 6)) {
                    @imagedestroy($img);
                    return $pngRel;
                }
                @imagedestroy($img);
            }
        }
        
        // Se a conversão webp falhou, usar o placeholder PNG padrão
        return $fallbackRel;
    }
    
    return $resolvedRel;
}

function imagemValida($absPath) {
    if (!file_exists($absPath) || !is_file($absPath) || @filesize($absPath) <= 0) {
        return false;
    }
    // Verificar magic bytes: PNG (89 50 4E 47), JPEG (FF D8 FF), GIF (47 49 46)
    $handle = @fopen($absPath, 'rb');
    if (!$handle) return false;
    $header = fread($handle, 4);
    fclose($handle);
    if (strlen($header) < 3) return false;
    $b = unpack('C*', $header);
    // PNG
    if ($b[1] === 0x89 && $b[2] === 0x50 && $b[3] === 0x4E && $b[4] === 0x47) return true;
    // JPEG
    if ($b[1] === 0xFF && $b[2] === 0xD8 && $b[3] === 0xFF) return true;
    // GIF
    if ($b[1] === 0x47 && $b[2] === 0x49 && $b[3] === 0x46) return true;
    return false;
}

function normalizarImagensClubesSQLite($ldb, $hexacolorDir, $docRoot) {
    if (!$ldb) return;
    try {
        $stmtClubs = $ldb->query("SELECT ID, Nome, Escudo, Uniforme1, Uniforme2 FROM clube");
        if (!$stmtClubs) return;
        $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
        $stmtClubs = null;
        
        $fallbackEscudo = 'Imagens/EscudoPadrao.png';
        $fallbackUniforme = 'Imagens/UniformePadrao.PNG';
        
        $stmtUpClube = $ldb->prepare("UPDATE clube SET Escudo = :esc, Uniforme1 = :uni1, Uniforme2 = :uni2 WHERE ID = :id");
        
        foreach ($clubs as $c) {
            $changed = false;
            $escudo = trim($c['Escudo'] ?? '');
            $uni1 = trim($c['Uniforme1'] ?? '');
            $uni2 = trim($c['Uniforme2'] ?? '');
            $nomeClube = $c['Nome'] ?? "ID#{$c['ID']}";
            
            $escudoResolvido = resolverCaminhoImagem($escudo, $hexacolorDir, $docRoot, $fallbackEscudo);
            if ($escudoResolvido !== $escudo) {
                $escudo = $escudoResolvido;
                $changed = true;
            }
            
            $uni1Resolvido = resolverCaminhoImagem($uni1, $hexacolorDir, $docRoot, $fallbackUniforme);
            if ($uni1Resolvido !== $uni1) {
                $uni1 = $uni1Resolvido;
                $changed = true;
            }
            
            $uni2Resolvido = resolverCaminhoImagem($uni2, $hexacolorDir, $docRoot, $fallbackUniforme);
            if ($uni2Resolvido !== $uni2) {
                $uni2 = $uni2Resolvido;
                $changed = true;
            }
            
            if ($changed) {
                $stmtUpClube->bindValue(':esc', $escudo);
                $stmtUpClube->bindValue(':uni1', $uni1);
                $stmtUpClube->bindValue(':uni2', $uni2);
                $stmtUpClube->bindValue(':id', $c['ID'], PDO::PARAM_INT);
                $stmtUpClube->execute();
            }
        }
        $stmtUpClube = null;
    } catch (\Throwable $e) {
        cron_log("   [AVISO IMAGENS] Erro ao normalizar imagens dos clubes: " . $e->getMessage());
    }
}


// Garantir compatibilidade de pastas e imagens com a engine Java no Linux (case-sensitive)
$imageFolders = ['Imagens' => 'imagens', 'Escudos' => 'escudos', 'Uniformes' => 'uniformes'];
foreach ($imageFolders as $orig => $lower) {
    $origDir = $hexacolorDir . '/' . $orig;
    $lowerDir = $hexacolorDir . '/' . $lower;
    if (is_dir($origDir) && !is_dir($lowerDir)) {
        if (!@symlink($origDir, $lowerDir)) {
            @mkdir($lowerDir, 0777, true);
            $files = glob($origDir . '/*');
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) {
                        @copy($f, $lowerDir . '/' . basename($f));
                    }
                }
            }
        }
    }
    // Garantir arquivos padrões em minúsculo e maiúsculo em todas as pastas
    if (is_dir($origDir)) {
        $escudoPadrao = $origDir . '/EscudoPadrao.png';
        if (file_exists($escudoPadrao)) {
            if (!file_exists($origDir . '/escudopadrao.png')) @copy($escudoPadrao, $origDir . '/escudopadrao.png');
            if (is_dir($lowerDir)) {
                if (!file_exists($lowerDir . '/EscudoPadrao.png')) @copy($escudoPadrao, $lowerDir . '/EscudoPadrao.png');
                if (!file_exists($lowerDir . '/escudopadrao.png')) @copy($escudoPadrao, $lowerDir . '/escudopadrao.png');
            }
            // Garantir também dentro de hexacolor/Escudos e images/escudos
            if (is_dir($hexacolorDir . '/Escudos') && !file_exists($hexacolorDir . '/Escudos/EscudoPadrao.png')) @copy($escudoPadrao, $hexacolorDir . '/Escudos/EscudoPadrao.png');
            if (is_dir($hexacolorDir . '/escudos') && !file_exists($hexacolorDir . '/escudos/EscudoPadrao.png')) @copy($escudoPadrao, $hexacolorDir . '/escudos/EscudoPadrao.png');
            if (is_dir($docRoot . '/images/escudos') && !file_exists($docRoot . '/images/escudos/EscudoPadrao.png')) @copy($escudoPadrao, $docRoot . '/images/escudos/EscudoPadrao.png');
        }
        $uniformePadrao = $origDir . '/UniformePadrao.PNG';
        if (file_exists($uniformePadrao)) {
            if (!file_exists($origDir . '/UniformePadrao.png')) @copy($uniformePadrao, $origDir . '/UniformePadrao.png');
            if (!file_exists($origDir . '/uniformepadrao.png')) @copy($uniformePadrao, $origDir . '/uniformepadrao.png');
            if (is_dir($lowerDir)) {
                if (!file_exists($lowerDir . '/UniformePadrao.PNG')) @copy($uniformePadrao, $lowerDir . '/UniformePadrao.PNG');
                if (!file_exists($lowerDir . '/UniformePadrao.png')) @copy($uniformePadrao, $lowerDir . '/UniformePadrao.png');
                if (!file_exists($lowerDir . '/uniformepadrao.png')) @copy($uniformePadrao, $lowerDir . '/uniformepadrao.png');
            }
            // Garantir também dentro de hexacolor/Uniformes e images/uniformes
            if (is_dir($hexacolorDir . '/Uniformes') && !file_exists($hexacolorDir . '/Uniformes/UniformePadrao.PNG')) @copy($uniformePadrao, $hexacolorDir . '/Uniformes/UniformePadrao.PNG');
            if (is_dir($hexacolorDir . '/uniformes') && !file_exists($hexacolorDir . '/uniformes/UniformePadrao.PNG')) @copy($uniformePadrao, $hexacolorDir . '/uniformes/UniformePadrao.PNG');
            if (is_dir($docRoot . '/images/uniformes') && !file_exists($docRoot . '/images/uniformes/UniformePadrao.PNG')) @copy($uniformePadrao, $docRoot . '/images/uniformes/UniformePadrao.PNG');
        }
    }
}

foreach ($partidas as $matchInfo) {
    $idPartida = $matchInfo['id'];
    $idCompeticao = $matchInfo['competicao'];
    
    cron_log("-> Processando Partida ID #{$idPartida} (Competição #{$idCompeticao})...");
    
    $sourceDbPath = __DIR__ . "/databases/{$idCompeticao}-database.db3";
    $targetDbPath = $hexacolorDir . "/data/database.db3";
    
    if (!file_exists($sourceDbPath)) {
        cron_log("   [ERRO] Banco da competição não encontrado: {$sourceDbPath}");
        continue;
    }
    
    if (!is_dir($hexacolorDir . "/data")) {
        mkdir($hexacolorDir . "/data", 0777, true);
    }
    
    // Limpar arquivos residuais de WAL/lock de simulações anteriores para evitar disk I/O error
    @unlink($targetDbPath . '-wal');
    @unlink($targetDbPath . '-shm');
    @unlink($targetDbPath . '-journal');

    // 1. Copiar SQLite da competição para data/database.db3
    copy($sourceDbPath, $targetDbPath);
    
    $idEstadio = isset($matchInfo['estadio']) ? (int)$matchInfo['estadio'] : 0;
    if ($idEstadio <= 0) {
        cron_log("   [ERRO] A Partida #{$idPartida} não possui estádio definido. Pulando...");
        continue;
    }

    $liteDatabase = new SQLiteDatabase();
    $liteDatabase->fileName = $targetDbPath;
    $ldb = $liteDatabase->getConnection();
    $liteCompeticao = new Competicao_clube($ldb);
    $timeObj = new Time($ldb);

    // Validar se o estádio existe na tabela estadio do SQLite
    $stmtEstCheck = $ldb->prepare("SELECT 1 FROM estadio WHERE ID = :idEst LIMIT 1");
    $stmtEstCheck->bindValue(':idEst', $idEstadio, PDO::PARAM_INT);
    $stmtEstCheck->execute();
    if (!$stmtEstCheck->fetch()) {
        $ldb = null;
        cron_log("   [ERRO] O estádio #{$idEstadio} da Partida #{$idPartida} não existe no banco SQLite. Pulando...");
        continue;
    }

    // Garantir tabela de compatibilidade com as versões necessárias e limpeza de pendências
    try {
        $ldb->exec("DROP TABLE IF EXISTS `comp10_temp_table`");
        $ldb->exec("CREATE TABLE IF NOT EXISTS `compatibilidade` (`versao` TEXT)");
        $stmtCheck = $ldb->query("SELECT `versao` FROM `compatibilidade`");
        $existingVersions = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredVersions = ['2.8', '2.9.1', '2.10', '2.13', '2.14'];
        $stmtInsert = $ldb->prepare("INSERT INTO `compatibilidade` (`versao`) VALUES (:versao)");
        foreach ($requiredVersions as $v) {
            if (!in_array($v, $existingVersions)) {
                $stmtInsert->bindValue(':versao', $v, PDO::PARAM_STR);
                $stmtInsert->execute();
            }
        }
        // Evita que a engine Java tente abrir diálogo interativo de sincronização web (HeadlessException)
        $ldb->exec("DELETE FROM `jogadorpendente`");
    } catch (Exception $e) {
        error_log("Erro ao aplicar compatibilidade no SQLite: " . $e->getMessage());
    }

    // 1. Guardar a escalação original (padrão) na memória para restaurar depois da simulação
    $originalEscalacoes = [];
    try {
        $stmtOrig = $ldb->prepare("SELECT * FROM escalacao WHERE Clube IN (:timeA, :timeB)");
        $stmtOrig->bindValue(':timeA', $matchInfo['timeA'], PDO::PARAM_INT);
        $stmtOrig->bindValue(':timeB', $matchInfo['timeB'], PDO::PARAM_INT);
        $stmtOrig->execute();
        while ($rowOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC)) {
            $originalEscalacoes[(int)$rowOrig['Clube']] = $rowOrig;
        }
    } catch (Exception $e) {}

    // 2. Criar a tabela escalacao_jogo se não existir e buscar escalações específicas para a partida
    $ldb->exec("CREATE TABLE IF NOT EXISTS `escalacao_jogo` (
        `Jogo`	int ( 10 ) NOT NULL,
        `Clube`	int ( 5 ) NOT NULL,
        `Jogador1`	int ( 5 ) NOT NULL,
        `Jogador2`	int ( 5 ) NOT NULL,
        `Jogador3`	int ( 5 ) NOT NULL,
        `Jogador4`	int ( 5 ) NOT NULL,
        `Jogador5`	int ( 5 ) NOT NULL,
        `Jogador6`	int ( 5 ) NOT NULL,
        `Jogador7`	int ( 5 ) NOT NULL,
        `Jogador8`	int ( 5 ) NOT NULL,
        `Jogador9`	int ( 5 ) NOT NULL,
        `Jogador10`	int ( 5 ) NOT NULL,
        `Jogador11`	int ( 5 ) NOT NULL,
        `Capitao`	int ( 5 ) NOT NULL,
        `Penalti1`	int ( 5 ) DEFAULT NULL,
        `Penalti2`	int ( 5 ) DEFAULT NULL,
        `Penalti3`	int ( 5 ) DEFAULT NULL,
        `Indisponiveis`	text,
        PRIMARY KEY(`Jogo`,`Clube`)
    );");

    $customLineupA = null;
    $customLineupB = null;
    try {
        $stmtCustomA = $ldb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
        $stmtCustomA->bindValue(':jogo', $matchInfo['id'], PDO::PARAM_INT);
        $stmtCustomA->bindValue(':clube', $matchInfo['timeA'], PDO::PARAM_INT);
        $stmtCustomA->execute();
        $customLineupA = $stmtCustomA->fetch(PDO::FETCH_ASSOC);

        $stmtCustomB = $ldb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
        $stmtCustomB->bindValue(':jogo', $matchInfo['id'], PDO::PARAM_INT);
        $stmtCustomB->bindValue(':clube', $matchInfo['timeB'], PDO::PARAM_INT);
        $stmtCustomB->execute();
        $customLineupB = $stmtCustomB->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    // Garantir que as colunas Suspenso e Lesionado existam na tabela jogador do SQLite temporário
    try {
        $ldb->exec("ALTER TABLE jogador ADD COLUMN Suspenso INTEGER DEFAULT 0");
    } catch (Exception $e) {}
    try {
        $ldb->exec("ALTER TABLE jogador ADD COLUMN Lesionado INTEGER DEFAULT 0");
    } catch (Exception $e) {}

    // Obter desfalques (suspensos e lesionados) de cada time no MariaDB e sincronizar no SQLite
    $outTeam1 = array();
    $outTeam2 = array();

    // 1. Obter os IDs de jogadores no elenco de cada time
    $team1Players = [];
    $stmtT1 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
    $stmtT1->bindValue(':clube', $matchInfo['timeA']);
    $stmtT1->execute();
    $elRow1 = $stmtT1->fetch(PDO::FETCH_ASSOC);
    if ($elRow1) {
        for ($i = 1; $i <= 23; $i++) {
            if (!empty($elRow1['Jogador' . $i])) {
                $team1Players[] = (int)$elRow1['Jogador' . $i];
            }
        }
    }

    $team2Players = [];
    $stmtT2 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
    $stmtT2->bindValue(':clube', $matchInfo['timeB']);
    $stmtT2->execute();
    $elRow2 = $stmtT2->fetch(PDO::FETCH_ASSOC);
    if ($elRow2) {
        for ($i = 1; $i <= 23; $i++) {
            if (!empty($elRow2['Jogador' . $i])) {
                $team2Players[] = (int)$elRow2['Jogador' . $i];
            }
        }
    }

    $allMatchPlayers = array_merge($team1Players, $team2Players);
    $lesionados = [];
    $suspensos = [];

    if (!empty($allMatchPlayers)) {
        $validPlayerIds = array_filter(array_map('intval', $allMatchPlayers), function($id) { return $id > 0; });
        if (!empty($validPlayerIds)) {
            $inClause = implode(',', array_unique($validPlayerIds));
            // Garantir que a coluna lesionado_ate exista no MariaDB competicao_suspensos
            try {
                $db->exec("ALTER TABLE competicao_suspensos ADD COLUMN lesionado_ate DATE DEFAULT NULL");
            } catch (Exception $e) {}

            $dataMatch = !empty($matchInfo['data']) ? substr($matchInfo['data'], 0, 10) : date('Y-m-d');

            // Consultar status dinâmicos no MariaDB principal (inclui checagem para jogadores de times importados .ymt)
            try {
                $querySt = "SELECT val.ID,
                                   IF((cs.lesionado_ate IS NOT NULL AND cs.lesionado_ate >= :dataJogo1) OR (j.lesionado_ate IS NOT NULL AND j.lesionado_ate >= :dataJogo2), 1, 0) as lesionado,
                                   COALESCE(cs.suspenso, 0) as suspenso
                            FROM (
                                SELECT ID FROM jogador WHERE ID IN ($inClause)
                                UNION
                                SELECT id_jogador AS ID FROM competicao_suspensos WHERE id_competicao = :comp AND id_jogador IN ($inClause)
                            ) val
                            LEFT JOIN jogador j ON val.ID = j.ID
                            LEFT JOIN competicao_suspensos cs ON val.ID = cs.id_jogador AND cs.id_competicao = :comp2";
                $stmtSt = $db->prepare($querySt);
                $stmtSt->bindValue(':comp', $idCompeticao, PDO::PARAM_INT);
                $stmtSt->bindValue(':comp2', $idCompeticao, PDO::PARAM_INT);
                $stmtSt->bindValue(':dataJogo1', $dataMatch, PDO::PARAM_STR);
                $stmtSt->bindValue(':dataJogo2', $dataMatch, PDO::PARAM_STR);
                $stmtSt->execute();
                while ($rowSt = $stmtSt->fetch(PDO::FETCH_ASSOC)) {
                    $pId = (int)$rowSt['ID'];
                    if ($rowSt['lesionado'] == 1) {
                        $lesionados[] = $pId;
                        if (in_array($pId, $team1Players)) $outTeam1[] = $pId;
                        if (in_array($pId, $team2Players)) $outTeam2[] = $pId;
                    }
                    if ($rowSt['suspenso'] == 1) {
                        $suspensos[] = $pId;
                        if (in_array($pId, $team1Players)) $outTeam1[] = $pId;
                        if (in_array($pId, $team2Players)) $outTeam2[] = $pId;
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao consultar status de desfalques no MariaDB: " . $e->getMessage());
            }
        }

        // Remover duplicatas nos desfalques
        $outTeam1 = array_values(array_unique($outTeam1));
        $outTeam2 = array_values(array_unique($outTeam2));

        // Resetar status no SQLite temporário
        $ldb->exec("UPDATE jogador SET Suspenso = 0, Lesionado = 0");

        // Atualizar lesionados no SQLite temporário
        if (!empty($lesionados)) {
            $inLes = implode(',', array_map('intval', $lesionados));
            $ldb->exec("UPDATE jogador SET Lesionado = 1 WHERE ID IN ($inLes)");
        }

        // Atualizar suspensos no SQLite temporário
        if (!empty($suspensos)) {
            $inSus = implode(',', array_map('intval', $suspensos));
            $ldb->exec("UPDATE jogador SET Suspenso = 1 WHERE ID IN ($inSus)");
        }
    }

    // 3. Aplicar as escalações temporárias na tabela escalacao do banco temporário
    try {
        $updateFields = [];
        for ($i = 1; $i <= 11; $i++) {
            $updateFields[] = "Jogador{$i} = :jog{$i}";
        }
        $updateFields[] = "Capitao = :capitao";
        $updateFields[] = "Penalti1 = :pen1";
        $updateFields[] = "Penalti2 = :pen2";
        $updateFields[] = "Penalti3 = :pen3";
        $stmtUp = $ldb->prepare("UPDATE escalacao SET " . implode(', ', $updateFields) . " WHERE Clube = :clube");

        if ($customLineupA) {
            $stmtUp->bindValue(':clube', $matchInfo['timeA'], PDO::PARAM_INT);
            for ($i = 1; $i <= 11; $i++) {
                $stmtUp->bindValue(':jog' . $i, $customLineupA['Jogador' . $i], PDO::PARAM_INT);
            }
            $stmtUp->bindValue(':capitao', $customLineupA['Capitao'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen1', $customLineupA['Penalti1'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen2', $customLineupA['Penalti2'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen3', $customLineupA['Penalti3'], PDO::PARAM_INT);
            $stmtUp->execute();

            if (!empty($customLineupA['Indisponiveis'])) {
                $indispA = array_map('intval', explode(',', $customLineupA['Indisponiveis']));
                $outTeam1 = array_merge($outTeam1, $indispA);
            }
        }

        if ($customLineupB) {
            $stmtUp->bindValue(':clube', $matchInfo['timeB'], PDO::PARAM_INT);
            for ($i = 1; $i <= 11; $i++) {
                $stmtUp->bindValue(':jog' . $i, $customLineupB['Jogador' . $i], PDO::PARAM_INT);
            }
            $stmtUp->bindValue(':capitao', $customLineupB['Capitao'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen1', $customLineupB['Penalti1'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen2', $customLineupB['Penalti2'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen3', $customLineupB['Penalti3'], PDO::PARAM_INT);
            $stmtUp->execute();

            if (!empty($customLineupB['Indisponiveis'])) {
                $indispB = array_map('intval', explode(',', $customLineupB['Indisponiveis']));
                $outTeam2 = array_merge($outTeam2, $indispB);
            }
        }

        $outTeam1 = array_values(array_unique($outTeam1));
        $outTeam2 = array_values(array_unique($outTeam2));
    } catch (Exception $e) {}
    
    // 3.1. Sanitizar escalações, preencher desfalques e equilibrar formações táticas para evitar somaProb == 0 na engine
    require_once $hexacolorDir . '/simulador_helper.php';
    sanitizarEscalacaoPreSimulacao($ldb, (int)$matchInfo['timeA'], $outTeam1);
    sanitizarEscalacaoPreSimulacao($ldb, (int)$matchInfo['timeB'], $outTeam2);

    // 4. Normalizar e validar imagens de todos os clubes para evitar exceções do Java (ImageIO.read retornando null)
    normalizarImagensClubesSQLite($ldb, $hexacolorDir, $docRoot);
    
    $competitionInfo = $competicaoObj->readInfo($idCompeticao);
    $nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
    $databasePath = "jdbc:sqlite:data/database.db3";
    $cores = $liteCompeticao->getColors();
    
    $fullDate = strtotime(date("j-n-Y", strtotime($matchInfo['data']))) * 1000 + 5*60*60*1000;
    $matchdayIndex = 1;
    
    // Obter as opções de desempate da competição
    $options = $competicaoObj->getOptions($idCompeticao);
    $faseJogo = isset($matchInfo['fase']) ? intval($matchInfo['fase']) : 0;
    
    $knockoutTiebraker = 0;
    $knockoutAwayGoals = false;
    
    if ($faseJogo > 2) {
        if ($faseJogo == 8) { // Final
            $tieOption = isset($options['criteriodesempatefinal']) ? intval($options['criteriodesempatefinal']) : 0;
            $knockoutTiebraker = ($tieOption === 0) ? 1 : 2;
            $knockoutAwayGoals = false;
        } else { // Outras fases de mata-mata
            $tieOption = isset($options['criteriodesempate']) ? intval($options['criteriodesempate']) : 0;
            $knockoutTiebraker = ($tieOption === 0) ? 1 : 2;
            $knockoutAwayGoals = isset($options['golfora']) && $options['golfora'] == 1;
        }
    }

    $json_array = array(
        'calendarName' => $nomeComposto,
        'color1' => isset($cores['partidaCor1']) ? $cores['partidaCor1'] : '#000000',
        'color2' => isset($cores['partidaCor2']) ? $cores['partidaCor2'] : '#ffffff',
        'color3' => isset($cores['partidaCor3']) ? $cores['partidaCor3'] : '#cccccc',
        'matchdayIndex' => $matchdayIndex,
        'matches' => [array(
            'databasePath' => $databasePath,
            'id' => $matchInfo['id'],
            'date' => $fullDate,
            'idTeam1' => $matchInfo['timeA'],
            'idTeam2' => $matchInfo['timeB'],
            'kitTeam1' => 0,
            'kitTeam2' => 0,
            'idChosenGround' => $matchInfo['estadio'],
            'neutralGround' => boolval($matchInfo['neutro']),
            'outTeam1' => $outTeam1,
            'outTeam2' => $outTeam2,
            'knockoutTiebraker' => $knockoutTiebraker,
            'knockoutAwayGoals' => $knockoutAwayGoals,
        )]
    );
    
    $siglaA = $timeObj->getSigla($matchInfo['timeA']);
    $siglaB = $timeObj->getSigla($matchInfo['timeB']);
    $path = $siglaA . "x" . $siglaB . " - " . date("j-n-Y", strtotime($matchInfo['data']));
    $completePath = "/Partidas/" . $nomeComposto . "/" . $matchdayIndex . "º Rodada/" . $path . ".hyl";

    // Fechar todas as referências ao SQLite temporariamente para evitar locks de arquivo (SQLITE_BUSY) durante a simulação
    if ($ldb) {
        try {
            $ldb->exec("PRAGMA wal_checkpoint(TRUNCATE);");
        } catch (Exception $e) {}
    }
    $stmtEstCheck = null;
    $stmtCheck = null;
    $stmtInsert = null;
    $stmtOrig = null;
    $stmtCustomA = null;
    $stmtCustomB = null;
    $stmtT1 = null;
    $stmtT2 = null;
    $stmtOut1 = null;
    $stmtOut2 = null;
    $stmtUp = null;
    $liteCompeticao = null;
    $timeObj = null;
    $ldb = null;
    $liteDatabase = null;
    gc_collect_cycles();

    $json = json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    file_put_contents($hexacolorDir . "/agenda/json.txt", $json);
    
    // Executar simulador JAR
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $dir = str_replace('\\', '/', $hexacolorDir);
    
    if ($isWindows) {
        $jarPath = $dir . "/HexacolorYMTv2.jar";
        $jsonPath = $dir . "/agenda/json.txt";
        $cmd = "cd /d \"$dir\" && java -Xms32m -Xmx256m -XX:+UseSerialGC -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -jar \"$jarPath\" -m \"$jsonPath\" 2>&1";
    } else {
        $docRoot = dirname(__DIR__); // raiz do site /home/lhsaia/confusa.top
        $javaBin = $docRoot . "/java_station/jdk/jdk1.8.0_231/bin/java";
        $libPath = $hexacolorDir . "/lib";
        $tmpDir = $docRoot . "/java_station/tmp";
        $jarPath = $hexacolorDir . "/HexacolorYMTv2.jar";
        $jsonPath = $hexacolorDir . "/agenda/json.txt";
        $cmd = "cd {$hexacolorDir} && export LANG=en_US.UTF-8; export LC_ALL=en_US.UTF-8; $javaBin -Xms32m -Xmx256m -XX:+UseSerialGC -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPath 2>&1";
    }
    
    $output = shell_exec($cmd . "\n");
    $output = ($output !== null) ? (string)$output : '';
    
    // Caso o motor execute apenas a rotina de compatibilidade/migração no primeiro disparo, reenviar a simulação
    $tentativas = 1;
    while ($tentativas < 3 && stripos($output, 'compatibilidade com o portal web') !== false) {
        $outputRetry = shell_exec($cmd . "\n");
        $outputRetryStr = ($outputRetry !== null) ? (string)$outputRetry : '';
        $output .= "\n[REENVIO AUTOMÁTICO #" . $tentativas . "]:\n" . $outputRetryStr;
        $tentativas++;
    }
    
    // Geração de LOG para debug do motor de simulação
    $logMessage = "[" . date('Y-m-d H:i:s') . "] CMD: " . $cmd . "\nOUTPUT:\n" . $output . "\n----------------------------------------\n";
    file_put_contents($hexacolorDir . "/simulation_debug.log", $logMessage, FILE_APPEND);
    
    // Reabrir conexão SQLite temporária para restaurar a escalação e salvar
    $liteDatabase = new SQLiteDatabase();
    $liteDatabase->fileName = $targetDbPath;
    $ldb = $liteDatabase->getConnection();

    // 4. Restaurar a escalação original/padrão no banco temporário antes de salvar de volta
    try {
        if (!empty($originalEscalacoes)) {
            $stmtRestore = $ldb->prepare("UPDATE escalacao SET 
                Jogador1 = :jog1, Jogador2 = :jog2, Jogador3 = :jog3, Jogador4 = :jog4, Jogador5 = :jog5, 
                Jogador6 = :jog6, Jogador7 = :jog7, Jogador8 = :jog8, Jogador9 = :jog9, Jogador10 = :jog10, Jogador11 = :jog11, 
                Capitao = :capitao, Penalti1 = :pen1, Penalti2 = :pen2, Penalti3 = :pen3
                WHERE Clube = :clube");
            foreach ($originalEscalacoes as $clubeId => $rowOrig) {
                $stmtRestore->bindValue(':clube', $clubeId, PDO::PARAM_INT);
                for ($i = 1; $i <= 11; $i++) {
                    $stmtRestore->bindValue(':jog' . $i, $rowOrig['Jogador' . $i], PDO::PARAM_INT);
                }
                $stmtRestore->bindValue(':capitao', $rowOrig['Capitao'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen1', $rowOrig['Penalti1'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen2', $rowOrig['Penalti2'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen3', $rowOrig['Penalti3'], PDO::PARAM_INT);
                $stmtRestore->execute();
            }
            $stmtRestore = null;
        }
    } catch (Exception $e) {}

    // Flush WAL checkpoint e fechar conexão SQLite
    if ($ldb) {
        try {
            $ldb->exec("PRAGMA wal_checkpoint(TRUNCATE);");
        } catch (Exception $e) {}
        $ldb = null;
    }
    $liteDatabase = null;
    gc_collect_cycles();

    // 2. Copiar banco SQLite atualizado de volta garantindo integridade de arquivos
    if (file_exists($targetDbPath)) {
        @unlink($sourceDbPath . '-wal');
        @unlink($sourceDbPath . '-shm');
        @unlink($sourceDbPath . '-journal');
        copy($targetDbPath, $sourceDbPath);
        @unlink($targetDbPath . '-wal');
        @unlink($targetDbPath . '-shm');
        @unlink($targetDbPath . '-journal');
    }
    
    $hylFile = $hexacolorDir . $completePath;
    
    // Se o arquivo .hyl não foi gerado na primeira tentativa, acionar o fallback de formação clássica 4-4-2 limpa
    if (!file_exists($hylFile)) {
        cron_log("   [RETRY] Tentando simular partida #{$idPartida} com formação padrão de emergência...");
        $liteEmergency = new SQLiteDatabase();
        $liteEmergency->fileName = $targetDbPath;
        $ldbEm = $liteEmergency->getConnection();
        if ($ldbEm) {
            aplicarEscalacaoEmergenciaSQLite($ldbEm, (int)$matchInfo['timeA']);
            aplicarEscalacaoEmergenciaSQLite($ldbEm, (int)$matchInfo['timeB']);
            try {
                $ldbEm->exec("PRAGMA wal_checkpoint(TRUNCATE);");
            } catch (Exception $e) {}
            $ldbEm = null;
        }
        $liteEmergency = null;
        gc_collect_cycles();

        $jsonEmergency = $json_array;
        $jsonEmergency['matches'][0]['outTeam1'] = [];
        $jsonEmergency['matches'][0]['outTeam2'] = [];
        file_put_contents($hexacolorDir . "/agenda/json.txt", json_encode($jsonEmergency, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));

        $outputRetry = shell_exec($cmd . "\n");
        $outputRetryStr = ($outputRetry !== null) ? (string)$outputRetry : '';
        $output .= "\n[FALLBACK DE EMERGÊNCIA]:\n" . $outputRetryStr;

        // Se gerou após o fallback, atualizar o banco de volta
        if (file_exists($hylFile) && file_exists($targetDbPath)) {
            @unlink($sourceDbPath . '-wal');
            @unlink($sourceDbPath . '-shm');
            @unlink($sourceDbPath . '-journal');
            copy($targetDbPath, $sourceDbPath);
        }
    }

    if (!file_exists($hylFile)) {
        error_log("PHP Simulador: [ERRO] Cron falhou na partida #{$idPartida}. Comando: " . $cmd . " | Output: " . trim($output));
        cron_log("   [ERRO] A simulação da Partida #{$idPartida} falhou. O arquivo .hyl não foi gerado.");
        if (!empty($output)) {
            cron_log("   [DETALHE ENGINE]: " . trim($output));
        }
        cron_log("   Pulando...");
        continue;
    }
    
    $golsTimeA = 0;
    $golsTimeB = 0;
    $penA = null;
    $penB = null;
    $xml = json_decode(file_get_contents($hylFile));
    if ($xml) {
        $golsTimeA = (int)$xml->placarTime1;
        $golsTimeB = (int)$xml->placarTime2;
        if (isset($xml->penaltis) && $xml->penaltis) {
            $penA = isset($xml->placarPenaltisTime1) ? (int)$xml->placarPenaltisTime1 : 0;
            $penB = isset($xml->placarPenaltisTime2) ? (int)$xml->placarPenaltisTime2 : 0;
        }
    } else {
        error_log("PHP Simulador: [ERRO] Cron gerou súmula vazia/corrompida na partida #{$idPartida}. Output: " . trim($output));
        cron_log("   [ERRO] O arquivo .hyl para a Partida #{$idPartida} foi gerado mas está corrompido ou vazio. Pulando...");
        continue;
    }
    
    // Fallback para .hyj se penaltis nao vieram no .hyl
    $hyjFile = str_replace('.hyl', '.hyj', $hylFile);
    if ($penA === null && file_exists($hyjFile)) {
        $jsonHyj = json_decode(file_get_contents($hyjFile));
        if ($jsonHyj && isset($jsonHyj->penaltis) && $jsonHyj->penaltis) {
            $penA = isset($jsonHyj->time1->placarPenaltis) ? (int)$jsonHyj->time1->placarPenaltis : 0;
            $penB = isset($jsonHyj->time2->placarPenaltis) ? (int)$jsonHyj->time2->placarPenaltis : 0;
        }
    }

    // Atualizar resultado no MariaDB
    $competicaoObj->uploadMatchResults($idPartida, $golsTimeA, $golsTimeB, $path, $penA, $penB);

    // Processar desfalques pós jogo (cartões, lesões, suspensões) no MariaDB
    require_once __DIR__ . '/hexacolor/processar_desfalques.php';
    processarPosJogo($db, $idCompeticao, $idPartida, $hylFile, $hyjFile, $suspensos);

    // Se for partida de mata-mata, verificar se todos os confrontos da fase terminaram no tempo real e avançar
    if (isset($matchInfo['fase']) && (int)$matchInfo['fase'] > 2) {
        try {
            $competicaoObj->verificarEAvancarMataMata($idCompeticao, (int)$matchInfo['fase']);
        } catch (\Throwable $e) {
            error_log("PHP Simulador: [ERRO AVANÇO MATA-MATA CRON] " . $e->getMessage());
        }
    }

    // Se a opção subir_live estiver ativada na partida (ou na competição se null), enviar partida para o CONFUSA Live
    $deveSubirLive = isset($matchInfo['subir_live']) && $matchInfo['subir_live'] !== null 
        ? (!empty($matchInfo['subir_live'])) 
        : (!empty($options['subir_live']));

    if ($deveSubirLive) {
        try {
            require_once $docRoot . '/lib/ConfusaLiveUploader.php';
            $faseNome = "Rodada " . (isset($matchInfo['fase']) ? $matchInfo['fase'] : '1');
            try {
                $stmtFase = $db->prepare("SELECT nome FROM fase WHERE id = :faseId LIMIT 1");
                $stmtFase->bindValue(':faseId', $matchInfo['fase'], PDO::PARAM_INT);
                $stmtFase->execute();
                if ($rowFase = $stmtFase->fetch(PDO::FETCH_ASSOC)) {
                    $faseNome = $rowFase['nome'];
                }
            } catch (\Throwable $e) {}

            if (!empty($matchInfo['grupo'])) {
                $faseNome .= " - Grupo " . $matchInfo['grupo'];
            }

            $liveResult = ConfusaLiveUploader::enviarPartida($hylFile, $nomeComposto, $faseNome, $matchInfo['data']);
            if ($liveResult['success']) {
                cron_log("   [LIVE] Partida #{$idPartida} enviada com sucesso para o CONFUSA Live.");
            } else {
                cron_log("   [LIVE AVISO] Não foi possível enviar a partida #{$idPartida} para o Live: {$liveResult['message']}");
            }
        } catch (\Throwable $e) {
            cron_log("   [LIVE EXCEÇÃO] Erro ao disparar upload: " . $e->getMessage());
        }
    }

    $penMsg = ($penA !== null) ? " (Pên: {$penA}x{$penB})" : "";
    cron_log("   [SUCESSO] Partida #{$idPartida} simulada! Placar: {$siglaA} {$golsTimeA} x {$golsTimeB} {$siglaB}{$penMsg}");

    gc_collect_cycles();
}

checarAvancoMataMataAtivos($db, $competicaoObj);

// Limpeza automática de arquivos de imagens convertidas em cache com mais de 30 dias
$cacheDir = $hexacolorDir . '/cache_images';
if (is_dir($cacheDir)) {
    $limiteTempo = time() - (30 * 86400); // 30 dias
    $arquivosCache = @glob($cacheDir . '/*.png');
    $removidos = 0;
    if ($arquivosCache) {
        foreach ($arquivosCache as $arq) {
            if (is_file($arq) && @filemtime($arq) < $limiteTempo) {
                if (@unlink($arq)) {
                    $removidos++;
                }
            }
        }
        if ($removidos > 0) {
            cron_log("   [CACHE LIMPEZA] {$removidos} imagem(ns) temporária(s) antiga(s) removida(s) de cache_images/.");
        }
    }
}

cron_log("Processamento do Cron concluído com sucesso.");
cron_log("=== DISPARO DO CRON FINALIZADO ===\n");
?>
