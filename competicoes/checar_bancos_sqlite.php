<?php
/**
 * Utilitário CLI para Diagnóstico, Validação de Integridade e Reparo de Escudos/Uniformes nos Bancos SQLite de Competições.
 * Uso no Terminal:
 *   php competicoes/checar_bancos_sqlite.php           (apenas diagnóstico)
 *   php competicoes/checar_bancos_sqlite.php --reparar (repara automaticamente escudos e uniformes de clubes do portal nos .db3)
 */

$reparar = in_array('--reparar', $argv ?? []) || in_array('--fix', $argv ?? []);
$docRoot = dirname(__DIR__);
$databasesDir = __DIR__ . '/databases';
$hexacolorDir = __DIR__ . '/hexacolor';

if (!is_dir($databasesDir)) {
    echo "Diretório competicoes/databases não encontrado.\n";
    exit(1);
}

$db = null;
if ($reparar || file_exists($docRoot . '/config/database.php')) {
    try {
        $_SERVER['DOCUMENT_ROOT'] = $docRoot;
        require_once $docRoot . '/config/database.php';
        require_once $hexacolorDir . '/simulador_helper.php';
        $database = new Database();
        $db = $database->getConnection();
    } catch (\Throwable $e) {
        echo "[AVISO] Não foi possível conectar ao MariaDB: " . $e->getMessage() . "\n";
    }
}

echo "=== DIAGNÓSTICO DE BANCOS SQLITE DE COMPETIÇÕES ===\n";
echo "Varrendo pasta: {$databasesDir}\n";
if ($reparar) {
    echo "Modo: REPARO ATIVO (--reparar)\n";
}
echo "\n";

$files = glob($databasesDir . '/*-database.db3');
if (empty($files)) {
    echo "Nenhum arquivo *-database.db3 encontrado na pasta.\n";
    exit(0);
}

$total = count($files);
$ok = 0;
$corrompidos = 0;
$zerados = 0;
$totalClubesAfetados = 0;
$totalClubesCurados = 0;

foreach ($files as $f) {
    $nome = basename($f);
    $tamanho = filesize($f);
    
    if ($tamanho === 0) {
        echo "❌ [ZERADO] {$nome} (0 bytes) - Arquivo vazio!\n";
        $zerados++;
        continue;
    }
    
    try {
        $pdo = new PDO('sqlite:' . $f);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $integrity = $pdo->query("PRAGMA integrity_check")->fetchColumn();
        
        if ($integrity === 'ok') {
            $clubes = 0;
            $jogadores = 0;
            $afetadosNesteBanco = [];
            try {
                $clubes = (int)$pdo->query("SELECT COUNT(*) FROM clube")->fetchColumn();
                $rowsClubes = $pdo->query("SELECT ID, Nome, Escudo, Uniforme1, Uniforme2 FROM clube")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowsClubes as $rc) {
                    $esc = trim((string)($rc['Escudo'] ?? ''));
                    $u1  = trim((string)($rc['Uniforme1'] ?? ''));
                    $u2  = trim((string)($rc['Uniforme2'] ?? ''));
                    if (
                        stripos($esc, 'EscudoPadrao') !== false ||
                        stripos($u1, 'UniformePadrao') !== false ||
                        stripos($u2, 'UniformePadrao') !== false ||
                        stripos($esc, 'cache_images/') === 0 ||
                        stripos($u1, 'cache_images/') === 0 ||
                        stripos($u2, 'cache_images/') === 0
                    ) {
                        $afetadosNesteBanco[] = $rc;
                    }
                }
            } catch (\Throwable $e) {}
            try {
                $jogadores = (int)$pdo->query("SELECT COUNT(*) FROM jogador")->fetchColumn();
            } catch (\Throwable $e) {}
            
            $sizeKb = round($tamanho / 1024, 1);
            $msgExtra = count($afetadosNesteBanco) > 0 ? " | ⚠️ Clubes c/ imagem padrão/cache: " . count($afetadosNesteBanco) : "";
            echo "✅ [OK] {$nome} ({$sizeKb} KB) | Clubes: {$clubes} | Jogadores: {$jogadores}{$msgExtra}\n";

            if (!empty($afetadosNesteBanco)) {
                $totalClubesAfetados += count($afetadosNesteBanco);
                foreach ($afetadosNesteBanco as $af) {
                    echo "   -> [{$af['ID']}] {$af['Nome']} | Escudo: {$af['Escudo']} | Uni1: {$af['Uniforme1']} | Uni2: {$af['Uniforme2']}\n";
                }

                if ($reparar && $db && function_exists('curarImagensClubesSQLiteComPortal')) {
                    curarImagensClubesSQLiteComPortal($pdo, $db, $hexacolorDir, $docRoot);
                    $rowsAfter = $pdo->query("SELECT ID, Nome, Escudo, Uniforme1, Uniforme2 FROM clube")->fetchAll(PDO::FETCH_ASSOC);
                    $afterMap = [];
                    foreach ($rowsAfter as $ra) {
                        $afterMap[(int)$ra['ID']] = $ra;
                    }
                    foreach ($afetadosNesteBanco as $af) {
                        $cid = (int)$af['ID'];
                        if (isset($afterMap[$cid])) {
                            $novo = $afterMap[$cid];
                            if ($novo['Escudo'] !== $af['Escudo'] || $novo['Uniforme1'] !== $af['Uniforme1'] || $novo['Uniforme2'] !== $af['Uniforme2']) {
                                echo "      🔧 [CURADO] [{$cid}] {$af['Nome']} => Escudo: {$novo['Escudo']} | Uni1: {$novo['Uniforme1']} | Uni2: {$novo['Uniforme2']}\n";
                                $totalClubesCurados++;
                            }
                        }
                    }
                }
            }

            $ok++;
        } else {
            echo "❌ [CORROMPIDO] {$nome} - Erro na integridade: {$integrity}\n";
            $corrompidos++;
        }
        $pdo = null;
    } catch (\Throwable $e) {
        echo "❌ [ERRO SQLITE] {$nome} - " . $e->getMessage() . "\n";
        $corrompidos++;
    }
}

echo "\n--- RESUMO ---\n";
echo "Total de bancos: {$total}\n";
echo "Bancos Saudáveis: {$ok}\n";
echo "Bancos Corrompidos: {$corrompidos}\n";
echo "Bancos Vazios/0 bytes: {$zerados}\n";
echo "Clubes com imagem padrão/cache detectados: {$totalClubesAfetados}\n";
if ($reparar) {
    echo "Clubes curados a partir do MariaDB: {$totalClubesCurados}\n";
} elseif ($totalClubesAfetados > 0) {
    echo "Dica: Execute 'php competicoes/checar_bancos_sqlite.php --reparar' para restaurar os escudos e uniformes originais do portal.\n";
}
echo "===================================================\n";
