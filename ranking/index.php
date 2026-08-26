<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if(isset($_GET['fed'])){
    $federacao = $_GET['fed'];
} else {
    $federacao = null;
}

$nomeFederacao = '';
switch ($federacao) {
    case '1':
        $nomeFederacao = ' da FEASCO';
        break;
    case '2':
        $nomeFederacao = ' da FEMIFUS';
        break;
    case '3':
        $nomeFederacao = ' da COMPACTA';
        break;
    default:
        break;
}

if (!$is_ajax) {
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

    $page_title = "Ranking de Seleções - Masculino";
    $css_filename = "indexRanking";
    $css_login = 'login';
    $aux_css = 'home_redesign';
    $css_versao = date('h:i:s');
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    include_once 'ranking_header.php';
?>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="ranking-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">trophy</span>
                    Ranking de Seleções Masculino <span id="nomeFederacao"><?php echo $nomeFederacao; ?></span>
                </h2>
                <h3 class="ranking-card-date">Atualizado em 02 de junho de 2026</h3>
            </div>
            
            <div class="federation-filters">
                <a href="/ranking/index.php" class="fed-filter-btn <?php echo ($federacao === null || $federacao === '') ? 'active' : ''; ?>">Geral</a>
                <a href="/ranking/index.php?fed=1" class="fed-filter-btn <?php echo ($federacao === '1') ? 'active' : ''; ?>">FEASCO</a>
                <a href="/ranking/index.php?fed=2" class="fed-filter-btn <?php echo ($federacao === '2') ? 'active' : ''; ?>">FEMIFUS</a>
                <a href="/ranking/index.php?fed=3" class="fed-filter-btn <?php echo ($federacao === '3') ? 'active' : ''; ?>">COMPACTA</a>
            </div>
        </div>

        <div id="ranking-table-container">
<?php
} // end if (!$is_ajax)

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// set number of records per page
$records_per_page = 16;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$usuario = new Usuario($db);

// query paises
if($federacao == null){
    $stmt = $pais->readAll($from_record_num, $records_per_page);
} else {
    $stmt = $pais->readFromFederation($from_record_num, $records_per_page, $federacao);
}

$num = $stmt->rowCount();

// the page where this paging is used
if($federacao != null){
    $page_url = "index.php?fed=" .$federacao . "&";
} else {
    $page_url = "index.php?";
}

// count all products in the database to calculate total pages
$total_rows = $pais->countAll($federacao);

// paging buttons here (top)
include($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

// display the products if there are any
if($num > 0){
    echo "<div class='tbl_user_data'>";
    echo "<table class='table'>";
    echo "<thead>";
        echo "<tr>";
            echo "<th style='width: 80px;'>Posição</th>";
            echo "<th style='text-align: left;'>País</th>";
            echo "<th style='width: 140px;'>Pontos Totais</th>";
        echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    if($page == 1){
        $pos = 0;
    } else {
        $pos = ($page - 1) * $records_per_page;
    }
    $comparapontos = 0;
    $pular_posicao = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        extract($row);

        if($pontos <> $comparapontos){
            $pos = $pos + 1 + $pular_posicao;
            $pular_posicao = 0;
        } else {
            $pular_posicao++;
        }

        $cor = ($ativo == 0) ? "cinza" : "preto";
        
        $badge_class = "rank-badge";
        if ($pos == 1) $badge_class .= " top-1";
        else if ($pos == 2) $badge_class .= " top-2";
        else if ($pos == 3) $badge_class .= " top-3";

        echo "<tr class='{$cor}'>";
            echo "<td><span class='{$badge_class}'>{$pos}</span></td>";
            echo "<td style='text-align: left;'>";
                echo "<div class='team-cell'>";
                    echo "<img src='/images/bandeiras/{$bandeira}' class='bandeira' alt='{$nome}'>";
                    echo "<a href='./teamstatus.php?team={$id}' class='team-link'>{$nome}</a>";
                    if($ativo == 0){
                        echo "<span class='inativo' style='font-size: 0.7rem; padding: 2px 6px;'>Inativo</span>";
                    }
                echo "</div>";
            echo "</td>";
            echo "<td class='points-cell'>{$pontos}</td>";
        echo "</tr>";

        $comparapontos = $pontos;
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";

    // Bottom pagination
    include($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

} else {
    echo "<div class='alert alert-info' style='margin: 1.5rem 0;'>Não há países cadastrados para esta seleção.</div>";
}

if ($is_ajax) {
    exit;
}
?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function loadRankingPage(url, pushState) {
        if (pushState === undefined) pushState = true;
        var $container = $('#ranking-table-container');
        $container.css({ 'opacity': '0.4', 'pointer-events': 'none', 'transition': 'opacity 0.2s ease' });

        var ajaxUrl = url + (url.indexOf('?') > -1 ? '&' : '?') + 'ajax=1';

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            success: function(response) {
                $container.html(response);
                $container.css({ 'opacity': '1', 'pointer-events': 'auto' });

                // Update federation filter active state and title
                var search = url.indexOf('?') > -1 ? url.substring(url.indexOf('?')) : '';
                var urlParams = new URLSearchParams(search);
                var fed = urlParams.get('fed');
                
                $('.federation-filters .fed-filter-btn').removeClass('active');
                if (!fed) {
                    $('.federation-filters a:not([href*="fed="])').addClass('active');
                    $('#nomeFederacao').text('');
                } else {
                    $('.federation-filters a[href*="fed=' + fed + '"]').addClass('active');
                    var fedTitles = { '1': ' da FEASCO', '2': ' da FEMIFUS', '3': ' da COMPACTA' };
                    $('#nomeFederacao').text(fedTitles[fed] || '');
                }

                if (pushState && window.history && window.history.pushState) {
                    window.history.pushState({ url: url }, '', url);
                }

                // Scroll smoothly to top of table if user scrolled down
                var card = $('.ranking-card');
                if (card.length) {
                    var cardTop = card.offset().top - 80;
                    if ($(window).scrollTop() > cardTop) {
                        $('html, body').animate({ scrollTop: cardTop }, 200);
                    }
                }
            },
            error: function() {
                $container.css({ 'opacity': '1', 'pointer-events': 'auto' });
                window.location.href = url;
            }
        });
    }

    // Intercept pagination clicks
    $(document).on('click', '#ranking-table-container .pagination a', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (href && href !== '#' && !$(this).parent().hasClass('active')) {
            loadRankingPage(href);
        }
    });

    // Intercept federation filter clicks
    $(document).on('click', '.federation-filters .fed-filter-btn', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (href && !$(this).hasClass('active')) {
            loadRankingPage(href);
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        loadRankingPage(window.location.href, false);
    });
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
