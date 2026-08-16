<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['admin_status'] !== '1') {
    die("Acesso negado.");
}

echo "<h3>Listagem de Arquivos na pasta Partidas/</h3>";

$dirPartidas = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/";

if (!is_dir($dirPartidas)) {
    echo "A pasta Partidas/ não existe.";
} else {
    $files = [];
    $it = new RecursiveDirectoryIterator($dirPartidas);
    foreach(new RecursiveIteratorIterator($it) as $file) {
        if ($file->isFile()) {
            $files[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file->getPathname()) . " (" . $file->getSize() . " bytes) - Modificado em: " . date("Y-m-d H:i:s", $file->getMTime());
        }
    }
    
    if (empty($files)) {
        echo "Nenhum arquivo encontrado dentro de Partidas/.";
    } else {
        echo "<ul>";
        foreach ($files as $f) {
            echo "<li>" . htmlspecialchars($f) . "</li>";
        }
        echo "</ul>";
    }
}
?>
