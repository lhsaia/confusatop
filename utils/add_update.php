<?php
/**
 * Utilitário cirúrgico para adicionar novidades no updates.json
 * Preserva estritamente quebras CRLF (\r\n) e indentação de 2 espaços.
 * Uso: php utils/add_update.php --badge="bugfix|new|design|security" --title="Título" --desc="Descrição"
 */

$options = getopt("", ["badge:", "title:", "desc:"]);

$badge = trim($options['badge'] ?? '');
$title = trim($options['title'] ?? '');
$desc = trim($options['desc'] ?? '');

if (empty($badge) || empty($title) || empty($desc)) {
    fwrite(STDERR, "Erro: Parâmetros obrigatórios ausentes. Uso:\n");
    fwrite(STDERR, "php utils/add_update.php --badge=\"bugfix\" --title=\"Título\" --desc=\"Descrição\"\n");
    exit(1);
}

$validBadges = ['new', 'bugfix', 'design', 'security'];
if (!in_array($badge, $validBadges)) {
    fwrite(STDERR, "Erro: Badge inválido '$badge'. Permitidos: " . implode(', ', $validBadges) . "\n");
    exit(1);
}

$file = dirname(__DIR__) . '/updates.json';
if (!file_exists($file)) {
    fwrite(STDERR, "Erro: updates.json não encontrado.\n");
    exit(1);
}

$content = file_get_contents($file);
$eol = (strpos($content, "\r\n") !== false) ? "\r\n" : "\n";

$todayId = "day_" . date("d_m_Y");
$todayDate = date("d/m/Y");

// Sanitizar strings para JSON simples
$jsonTitle = json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsonDesc = json_encode($desc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$targetToday = "\"id\": \"{$todayId}\",{$eol}      \"date\": \"{$todayDate}\",{$eol}      \"items\": [{$eol}";

if (strpos($content, $targetToday) !== false) {
    // Bloco de hoje já existe, injetar no início do array items
    $newItem = "        {{$eol}" .
               "          \"badge\": \"{$badge}\",{$eol}" .
               "          \"title\": {$jsonTitle},{$eol}" .
               "          \"desc\": {$jsonDesc}{$eol}" .
               "        },{$eol}";

    $newContent = str_replace($targetToday, $targetToday . $newItem, $content);
} else {
    // Bloco de hoje não existe, criar no início do array days
    $targetDays = "\"days\": [{$eol}";
    if (strpos($content, $targetDays) === false) {
        fwrite(STDERR, "Erro: Estrutura '\"days\": [' não encontrada no updates.json.\n");
        exit(1);
    }

    $newBlock = "    {{$eol}" .
                "      \"id\": \"{$todayId}\",{$eol}" .
                "      \"date\": \"{$todayDate}\",{$eol}" .
                "      \"items\": [{$eol}" .
                "        {{$eol}" .
                "          \"badge\": \"{$badge}\",{$eol}" .
                "          \"title\": {$jsonTitle},{$eol}" .
                "          \"desc\": {$jsonDesc}{$eol}" .
                "        }{$eol}" .
                "      ]{$eol}" .
                "    },{$eol}";

    $newContent = str_replace($targetDays, $targetDays . $newBlock, $content);
}

// Validar integridade JSON
$decoded = json_decode($newContent);
if ($decoded === null) {
    fwrite(STDERR, "Erro fatal: JSON gerado é inválido: " . json_last_error_msg() . "\n");
    exit(1);
}

// Salvar
file_put_contents($file, $newContent);
echo "Item adicionado com sucesso ao updates.json (badge: $badge).\n";
