<?php

//definição do rodapé
$isRedesigned = false;
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Check if the current page URI belongs to a redesigned module
$redesignPaths = ['/usuario', '/competicoes', '/mercado', '/ligas', '/ranking', '/sugestoes'];
foreach ($redesignPaths as $path) {
    if (strpos($uri, $path) !== false) {
        $isRedesigned = true;
        break;
    }
}
// Also check if it is the root home page
if ($uri === '/' || $uri === '' || strpos($uri, '/index.php') !== false) {
    $isRedesigned = true;
}

if ($isRedesigned) {
    echo "<style>html, body { margin-bottom: 0 !important; }</style>";
    echo "<div id='bottom-bar' style='position: relative !important; margin-top: auto !important; bottom: auto !important;' class=''>";
} else {
    echo "<div id='bottom-bar' class=''>";
}
echo '<a id="botaoVoltar" href="javascript:history.go(-1)" title="Voltar para página anterior"><span class="material-symbols-outlined">arrow_circle_left</span></a>';
echo "<div id='copyright-text'>";
echo "&copy;2018-2026 website by <a href='https://lhsaia.github.io/'><span class='material-symbols-outlined' style='font-size: 1em; vertical-align: middle;'>link</span> Luis Cereda</a>";
echo "</div>";
echo "</div>";

echo "</body>";
echo "</html>";

    ?>
