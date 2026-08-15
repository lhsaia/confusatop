<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

// Basic login check/header inclusion from the main system
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogos de Clubes";
// Reusing ranking CSS as base since it has the table styles
$css_filename = "indexRanking"; 
$css_login = 'login';
$aux_css = 'jogoserecordes'; 
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<style>
    /* Minimal overrides for the manager context */
    #ranking-container {
        padding: 20px;
        background: #f9f9f9;
        min-height: 800px;
    }

    .btn {
        width:20% !important;
    }
    #ranking {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        display: block; /* Override flex/grid if any */
        max-width: 1200px;
        margin: 0 auto;
    }
    #caixa_pesquisa {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
        width: 300px;
    }
    .headings {
        cursor: pointer;
        background: #eee;
    }
    .headings:hover {
        background: #e0e0e0;
    }
    .highlighted {
        background-color: #f0f0f0;
    }
    
    /* Hide some ranking specific icons if they don't make sense here */
    .penaltybox {
        color: #666;
    }
    .btn-action {
        width: 30px !important;
        height: 30px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        font-size: 14px !important;
        margin: 0 auto;
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
        border-radius: 4px;
    }
    .btn-action:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
    }
    /* Fix specific override that might be breaking buttons */
    #ranking .btn:not(.btn-action) {
        width: auto !important;
    }

</style>

<script>
var currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
var isAdmin = <?php echo (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1') ? 'true' : 'false'; ?>;
var localData = [];
var asc = true;
var activeSort = '';

$(document).ready(function($){

    load_data();

    $('#caixa_pesquisa').keyup(function(){load_data()});

    function load_data(){

        var searchText = $('#caixa_pesquisa').val();
        $('#loading').show();

        $.ajax({
            url:"get_matches.php",
            method:"POST",
            cache:false,
            data:{searchText:searchText},
            success:function(data){
                $('#loading').hide();
                // Ensure data is valid JSON
                try {
                    var parsedData = JSON.parse(data);
                    localData = parsedData;
                    updateTable(parsedData,1,0,0);
                } catch(e) {
                    console.error("Error parsing JSON", e);
                }
            }
        });
    }

    function updateTable(ajax_data, current_page, highlighted, direction){

        var results_per_page = 20; // Increased page size a bit
        var total_results = ajax_data.length;
        var total_pages = Math.ceil(total_results/results_per_page);

        var treated_page;
        if(current_page == 'final'){
            treated_page = total_pages;
        } else if(current_page == 'inicio'){
            treated_page = 1;
        } else {
            treated_page = current_page;
        }

        var from_result_num = (results_per_page * treated_page) - results_per_page;

        var pgn = pagination(treated_page,total_pages);

        var tbl = '';
        tbl += "<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;'>";
        tbl += "<span>Total: " + total_results + " jogos</span>";
        tbl += pgn;
        tbl += "</div>";
        tbl += "<hr>";
        tbl += "<table id='tabelajogos' class='table table-striped table-hover'>"; // Added boostrap classes for better look if available
            tbl += "<thead id='headings'>";
                tbl += "<tr>";
                    tbl += "<th asc='' id='nomeA' class='headings' width='24%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspMandante</th>";
                    tbl +=  "<th asc='' id='timeAgols' class='headings' width='5%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspPlacar</th>";
                    tbl +=  "<th asc='' id='timeApenaltis' class='headings' width='5%' class='penaltybox'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp</th>";
                    tbl +=  "<th asc='' id='timeBgols' class='headings' width='5%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspPlacar</th>";
                    tbl +=  "<th asc='' id='nomeB' class='headings' width='24%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspVisitante</th>";
                    tbl +=  "<th asc='' id='data' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspData</th>";
                    tbl +=  "<th asc='' id='campeonato' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspCampeonato</th>";
                    tbl +=  "<th style='text-align:center;'>Ações</th>";
                tbl +=  "</tr>";
            tbl +=  "</thead>";
            tbl +=  "<tbody>";

            if(total_results == 0) {
                 tbl += "<tr><td colspan='8' style='text-align:center; padding:20px;'>Nenhum jogo encontrado.</td></tr>";
            }

            $.each(ajax_data, function(index, val){

                var pen = '';
                if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

                // Only show penalty score if values are present (not null/empty)
                if(val['timeApenaltis'] !== null && val['timeApenaltis'] !== "" && val['timeBpenaltis'] !== null && val['timeBpenaltis'] !== ""){
                    pen = "("+val['timeApenaltis']+") pen. ("+val['timeBpenaltis']+")";
                }

                // Point to local view.php
                var viewLink = "view.php?match_id="+val['id'];

                tbl += "<tr id='"+val['id']+"' style='cursor:pointer;' onclick='window.location=\""+viewLink+"\"'>";
                    // Use escudoA/escudoB as provided by the new query
                    var escudoA = val['escudoA'] ? val['escudoA'] : '0.png'; // Fallback if needed, though PHP query handles generic logic usually
                    var escudoB = val['escudoB'] ? val['escudoB'] : '0.png';

                    tbl += "<td class='esquerdo nopadding'><img src='/images/escudos/"+escudoA+"' class='bandeira' style='width:20px; vertical-align:middle; margin-right:5px;'> "+val['nomeA']+"</td>";
                    tbl +=  "<td class='nopadding' style='text-align:center; font-weight:bold;'>"+val['timeAgols']+"</td>";
                    tbl +=  "<td class='penaltybox nopadding' style='font-size:0.8em; text-align:center;'>"+pen+"</td>";
                    tbl +=  "<td class='nopadding' style='text-align:center; font-weight:bold;'>"+val['timeBgols']+"</td>";
                    tbl +=  "<td class='direito nopadding'>"+val['nomeB']+" <img src='/images/escudos/"+escudoB+"' class='bandeira' style='width:20px; vertical-align:middle; margin-left:5px;'></td>";
                    
                    // Use already formatted date from PHP
                    tbl +=  "<td>"+val['data_formatada']+"</td>";
                    tbl +=  "<td>"+val['campeonato']+"</td>";
                    
                    
                    var actions = "";
                    if (currentUserId > 0 && (val['dono'] == currentUserId || isAdmin)) {
                        actions += "<button class='btn btn-action' onclick='event.stopPropagation(); deleteMatch("+val['id']+")' title='Apagar Jogo'><span class='material-symbols-outlined'>delete</span></button>";
                    }
                    tbl +=  "<td style='text-align:center;'>"+actions+"</td>";
                tbl +=  "</tr>";
                }
            });

            tbl += '</tbody>';
        tbl += '</table>';

        $(document).find('.tbl_user_data').html(tbl);
        addFilters();
        
        // Highlight sort column
        $(document).find('#'+highlighted).addClass('highlighted');
        
        if(direction == 1){
            asc = activeDirection;
        }
        if(asc){
            $(document).find('#'+highlighted).find('.descending').addClass('hidden');
            $(document).find('#'+highlighted).find('.ascending').removeClass('hidden');
        } else {
            $(document).find('#'+highlighted).find('.ascending').addClass('hidden');
            $(document).find('#'+highlighted).find('.descending').removeClass('hidden');
        }

        activeSort = highlighted;
        activeDirection = asc;
    }

    $(document).on('click', '.pagination_link', function(){
        var page = $(this).attr('id');
        updateTable(localData, page,activeSort, 1);
    });

    function pagination(current_page, total_pages){
        var pgn = '';
        pgn += "<ul class='pagination' style='margin:0;'>";

        if(current_page>1){
            pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>&laquo;</button></li>";
        }

        const range = 2;
        var initial_num = current_page - range;
        var condition_limit_num = (+current_page + +range)  + +1;
        var x;
        if(initial_num > 0){ x = initial_num; } else { x = 1; }

        while(x <= total_pages && x < condition_limit_num){
            if (x == current_page) {
                pgn += "<li><button class='pagination_link active' id='"+x+"' disabled style='background:#007bff; color:white;'>"+x+"</button></li>";
            } else {
                pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
            }
            x = x+1;
        }

        if(current_page<total_pages){
            pgn += "<li><button class='pagination_link' id='final' title='Última página'>&raquo;</button></li>";
        }
        pgn += "</ul>";
        return pgn;
    }

    function addFilters(){
        $(document).find('.headings').click(function(){
           treatResults(this);
        });
    }

    function treatResults(item){
        var id = $(item).attr('id');
        sortResults(id, asc);
        asc = !asc;
    }

    function sortResults(prop, asc) {
         localData = localData.sort(function(a, b) {
             // Handle numeric sort for goals/penalties
             var valA = a[prop];
             var valB = b[prop];
             
             if(prop.includes('gols') || prop.includes('pontos') || prop.includes('penaltis')) {
                 valA = parseInt(valA) || 0;
                 valB = parseInt(valB) || 0;
             }

             if (((valA < valB) && (!asc)) || ((valA > valB) && (asc))) return 1;
             else if (((valA > valB) && (!asc)) || ((valA < valB) && (asc))) return -1;
             else return 0;
         });
         updateTable(localData, 1, prop, 0);
    }

});

function deleteMatch(id) {
    if(!confirm('Tem certeza que deseja apagar esta partida? Isso apagará também todos os eventos e escalações vinculados a ela.')) return;

    $.post('apagar_jogo.php', {match_id: id}, function(data) {
        if(data.success) {
            alert('Jogo apagado com sucesso!');
            // Reload data
            $('#caixa_pesquisa').trigger('keyup');
        } else {
            alert('Erro ao apagar: ' + data.message);
        }
    }, 'json').fail(function() {
        alert('Erro de comunicação com o servidor.');
    });
}


</script>

<div id='ranking-container'>
    <div id='ranking'>
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
            <h2 style="margin:0;">Jogos de Clubes</h2>
                <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <a href="editar.php" class="btn btn-success me-2" style="margin-right:15px;"><span class='material-symbols-outlined'>add</span> Novo Jogo</a>
                    <a href="../importar_jogo.php" class="btn btn-primary" style="margin-right:15px;"><span class='material-symbols-outlined'>upload</span> Importar</a>
                <?php endif; ?>
            <div id='direita' style="display:flex; align-items:center;">
                <div style="position:relative;">
                    <input type=text id='caixa_pesquisa' placeholder='Pesquisar times, campeonato, data...' style="width:300px; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <span class='material-symbols-outlined' style='position:absolute; right:10px; top:0px; color:#999;font-size:24px'>search</span>
                </div>
            </div>
        </div>

        <div class='tbl_user_data'>
            <div style="text-align:center; padding:50px;">
                <img id='loading' src='/images/icons/ajax-loader.gif'>
                <p>Carregando jogos...</p>
            </div>
        </div>

    </div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
