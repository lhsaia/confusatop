<?php
/**
 * Utilitário CLI para Diagnóstico e Validação de Integridade dos Bancos SQLite de Competições.
 * Uso no Terminal: php competicoes/checar_bancos_sqlite.php
 */

$databasesDir = __DIR__ . '/databases';

if (!is_dir($databasesDir)) {
    echo "Diretório competicoes/databases não encontrado.\n";
    exit(1);
}

echo "=== DIAGNÓSTICO DE BANCOS SQLITE DE COMPETIÇÕES ===\n";
echo "Varrendo pasta: {$databasesDir}\n\n";

$files = glob($databasesDir . '/*-database.db3');
if (empty($files)) {
    echo "Nenhum arquivo *-database.db3 encontrado na pasta.\n";
    exit(0);
}

$total = count($files);
$ok = 0;
$corrompidos = 0;
$zerados = 0;

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
            try {
                $clubes = (int)$pdo->query("SELECT COUNT(*) FROM clube")->fetchColumn();
            } catch (\Throwable $e) {}
            try {
                $jogadores = (int)$pdo->query("SELECT COUNT(*) FROM jogador")->fetchColumn();
            } catch (\Throwable $e) {}
            
            $sizeKb = round($tamanho / 1024, 1);
            echo "✅ [OK] {$nome} ({$sizeKb} KB) | Clubes: {$clubes} | Jogadores: {$jogadores}\n";
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
echo "===================================================\n";
