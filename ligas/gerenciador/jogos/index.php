<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

// Basic login check/header inclusion from the main system
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogos de Clubes";
$css_filename = "home_redesign"; 
$css_login = 'login';
$aux_css = 'home_redesign'; 
$extra_css = 'jogos_clubes_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<script>
var currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; ?>;
var isAdmin = <?php echo (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1') ? 'true' : 'false'; ?>;
var localData = [];
var asc = true;
var activeSort = '';
var activeDirection = true;

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

        var results_per_page = 20;
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
        tbl += "<div class='pagination-wrapper'>";
        tbl += "<span class='pagination-info'>Total: <strong>" + total_results + "</strong> jogos registrados</span>";
        tbl += pgn;
        tbl += "</div>";
        tbl += "<table id='tabelajogos' class='clubes-table'>";
            tbl += "<thead id='headings'>";
                tbl += "<tr>";
                    tbl += "<th asc='' id='nomeA' class='headings' style='width: 25%; text-align: left;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Mandante</th>";
                    tbl +=  "<th asc='' id='timeAgols' class='headings' style='width: 7%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Placar</th>";
                    tbl +=  "<th asc='' id='timeApenaltis' class='headings penaltybox' style='width: 7%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Pên.</th>";
                    tbl +=  "<th asc='' id='timeBgols' class='headings' style='width: 7%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Placar</th>";
                    tbl +=  "<th asc='' id='nomeB' class='headings' style='width: 25%; text-align: left;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Visitante</th>";
                    tbl +=  "<th asc='' id='data' class='headings' style='width: 12%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Data</th>";
                    tbl +=  "<th asc='' id='campeonato' class='headings' style='width: 13%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Campeonato</th>";
                    tbl +=  "<th style='text-align:center; width: 4%;'>Ações</th>";
                tbl +=  "</tr>";
            tbl +=  "</thead>";
            tbl +=  "<tbody>";

            if(total_results == 0) {
                 tbl += "<tr><td colspan='8' style='text-align:center; padding:35px; color:#64748b;'>Nenhum jogo encontrado para os critérios pesquisados.</td></tr>";
            }

            $.each(ajax_data, function(index, val){

                var pen = '-';
                if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

                if(val['timeApenaltis'] !== null && val['timeApenaltis'] !== "" && val['timeBpenaltis'] !== null && val['timeBpenaltis'] !== ""){
                    pen = "<span class='penalty-tag'>(" + val['timeApenaltis'] + " - " + val['timeBpenaltis'] + ")</span>";
                }

                var viewLink = "view.php?match_id="+val['id'];

                tbl += "<tr id='"+val['id']+"' onclick='window.location=\""+viewLink+"\"'>";
                    var escudoA = val['escudoA'] ? val['escudoA'] : '0.png';
                    var escudoB = val['escudoB'] ? val['escudoB'] : '0.png';

                    tbl += "<td style='text-align: left;'><div style='display:flex; align-items:center;'><img src='/images/escudos/"+escudoA+"' class='team-crest' alt='"+val['nomeA']+"'> <a href='/times/team_presentation_magazine.php?team="+val['idA']+"' class='team-name-link' onclick='event.stopPropagation();'>"+val['nomeA']+"</a></div></td>";
                    tbl +=  "<td class='score-num'>"+val['timeAgols']+"</td>";
                    tbl +=  "<td class='penaltybox'>"+pen+"</td>";
                    tbl +=  "<td class='score-num'>"+val['timeBgols']+"</td>";
                    tbl +=  "<td style='text-align: left;'><div style='display:flex; align-items:center;'><img src='/images/escudos/"+escudoB+"' class='team-crest' alt='"+val['nomeB']+"'> <a href='/times/team_presentation_magazine.php?team="+val['idB']+"' class='team-name-link' onclick='event.stopPropagation();'>"+val['nomeB']+"</a></div></td>";
                    
                    tbl +=  "<td style='color:#475569; font-size:0.85rem;'>"+val['data_formatada']+"</td>";
                    tbl +=  "<td style='font-weight:600; font-size:0.85rem;'>"+val['campeonato']+"</td>";
                    
                    var actions = "";
                    if (currentUserId > 0 && (val['dono'] == currentUserId || isAdmin)) {
                        actions += "<button class='btn-delete-match' onclick='event.stopPropagation(); deleteMatch("+val['id']+")' title='Apagar Jogo'><span class='material-symbols-outlined'>delete</span></button>";
                    }
                    tbl +=  "<td style='text-align:center;'>"+actions+"</td>";
                tbl +=  "</tr>";
                }
            });

            tbl += '</tbody>';
        tbl += '</table>';

        $(document).find('.tbl_user_data').html(tbl);
        addFilters();
        
        if(highlighted && highlighted !== 0 && highlighted !== '0'){
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
        }

        activeSort = highlighted;
        activeDirection = asc;
    }

    $(document).on('click', '.pagination_link', function(){
        var page = $(this).attr('id');
        updateTable(localData, page,activeSort, 1);
    });

    function pagination(current_page, total_pages){
        var pgn = '<ul class="pagination">';
        if(total_pages > 1){
            var prev_page = parseInt(current_page) - 1;
            var next_page = parseInt(current_page) + 1;

            if(current_page > 1){
                pgn += '<li><button class="pagination_link" id="inicio" title="Primeira Página">&laquo;</button></li>';
                pgn += '<li><button class="pagination_link" id="' + prev_page + '" title="Página Anterior">&lsaquo;</button></li>';
            }

            var range = 2;
            var initial_num = parseInt(current_page) - range;
            var condition_limit_num = parseInt(current_page) + range + 1;

            for (var x = initial_num; x < condition_limit_num; x++) {
                if ((x > 0) && (x <= total_pages)) {
                    if (x == current_page) {
                        pgn += '<li><button class="pagination_link active" id="' + x + '" disabled>' + x + '<span class="sr-only">(current)</span></button></li>';
                    } else {
                        pgn += '<li><button class="pagination_link" id="' + x + '">' + x + '</button></li>';
                    }
                }
            }

            if(current_page < total_pages){
                pgn += '<li><button class="pagination_link" id="' + next_page + '" title="Próxima Página">&rsaquo;</button></li>';
                pgn += '<li><button class="pagination_link" id="final" title="Última Página">&raquo;</button></li>';
            }
        }
        pgn += '</ul>';
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
            $('#caixa_pesquisa').trigger('keyup');
        } else {
            alert('Erro ao apagar: ' + data.message);
        }
    }, 'json').fail(function() {
        alert('Erro de comunicação com o servidor.');
    });
}
</script>

<div class="clubes-container">
    <div class="clubes-card">
        <div class="clubes-header-bar">
            <div class="clubes-title-group">
                <span class="material-symbols-outlined clubes-title-icon">sports_soccer</span>
                <div>
                    <h2 class="clubes-main-title">Jogos de Clubes</h2>
                    <p class="clubes-subtitle">Histórico oficial de partidas, escalações e estatísticas dos clubes</p>
                </div>
            </div>

            <div class="clubes-actions-group">
                <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <a href="editar.php" class="btn-clubes-primary">
                        <span class="material-symbols-outlined" style="font-size:1.1rem;">add_circle</span>
                        <span>Novo Jogo</span>
                    </a>
                    <a href="../importar_jogo.php" class="btn-clubes-secondary">
                        <span class="material-symbols-outlined" style="font-size:1.1rem;">upload_file</span>
                        <span>Importar</span>
                    </a>
                <?php endif; ?>

                <div class="search-wrapper-clubes">
                    <input type="text" id="caixa_pesquisa" class="search-input-clubes" placeholder="Pesquisar times, campeonato, data...">
                    <span class="material-symbols-outlined search-icon-clubes">search</span>
                </div>
            </div>
        </div>

        <div class="tbl_user_data">
            <div style="text-align:center; padding:50px;">
                <img id="loading" src="/images/icons/ajax-loader.gif" alt="Carregando...">
                <p style="color:#64748b; font-size:0.9rem; margin-top:10px;">Carregando jogos...</p>
            </div>
        </div>

    </div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
