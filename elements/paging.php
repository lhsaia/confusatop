<?php
// calculate total pages
$total_pages = ceil($total_rows / $records_per_page);

if ($total_pages > 1) {
    echo "<ul class='pagination'>";
    
    $prev_page = (int)$page - 1;
    $next_page = (int)$page + 1;

    // button for first and previous page
    if ($page > 1) {
        echo "<li><a href='{$page_url}page=1' title='Primeira Página'>&laquo;</a></li>";
        echo "<li><a href='{$page_url}page={$prev_page}' title='Página Anterior'>&lsaquo;</a></li>";
    }

    // range of links to show
    $range = 2;
    $initial_num = $page - $range;
    $condition_limit_num = ($page + $range) + 1;

    for ($x = $initial_num; $x < $condition_limit_num; $x++) {
        if (($x > 0) && ($x <= $total_pages)) {
            if ($x == $page) {
                echo "<li class='active'><a href=\"#\">{$x} <span class=\"sr-only\">(current)</span></a></li>";
            } else {
                echo "<li><a href='{$page_url}page={$x}'>{$x}</a></li>";
            }
        }
    }

    // button for next and last page
    if ($page < $total_pages) {
        echo "<li><a href='{$page_url}page={$next_page}' title='Próxima Página'>&rsaquo;</a></li>";
        echo "<li><a href='{$page_url}page={$total_pages}' title='Última Página'>&raquo;</a></li>";
    }

    echo "</ul>";
}
?>