<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA.top - Sugestões / Bugs";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'melhorias';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
<script>

var localData = [];
var asc = true;
var activeSort = '';

$(document).ready(function($){

 var logged ='<?php if(isset($_SESSION['user_id'])){
		echo "true";
	 } else {
		echo "false";
	 };?>';

load_data();

function clear_values(){
	$('#newSuggestionTitle').val("");
	$('#newSuggestionDescription').val("");
	$('#newSuggestionType').val(0);
}

$('#add-new-suggestion').click(function(){
	$("#newSuggestionHr").addClass("open");
	$("#newSuggestion").addClass("open");
	$(".newSuggestionItem").addClass("open");
	$(".pagination").addClass("closed");
});

$('#cancel-new-suggestion').click(function(){
	$("#newSuggestionHr").removeClass("open");
	$("#newSuggestion").removeClass("open");
	$(".newSuggestionItem").removeClass("open");
	$(".pagination").removeClass("closed");
	clear_values();
});

$('#confirm-new-suggestion').click(function(){
	$("#newSuggestionHr").removeClass("open");
	$("#newSuggestion").removeClass("open");
	$(".newSuggestionItem").removeClass("open");
	$(".pagination").removeClass("closed");
	let title = $('#newSuggestionTitle').val();
	let description = $('#newSuggestionDescription').val();
	let type = $('#newSuggestionType').val();
	clear_values();
	$.ajax({
		url:"include_suggestion.php",
		method:"POST",
		cache:false,
		data:{title:title,
				description:description,
				type:type},
		success:function(data){
			load_data();
    }
	});
});


$('#caixa_pesquisa').keyup(function(){load_data()});

function load_data(){

var searchText = $('#caixa_pesquisa').val();
$('#loading').show();  // show loading indicator

$.ajax({
    url:"search_suggestion.php",
    method:"POST",
    cache:false,
    data:{searchText:searchText},
    success:function(data){
        $('#loading').hide();  // hide loading indicator
        updateTable(JSON.parse(data),1,0,0);
        localData = JSON.parse(data);
		
		$('.toggle_like').click(function(){
			let id = $(this).closest("tr").attr("id");
			$.ajax({
				url:"toggle_like.php",
				method:"POST",
				cache:false,
				data:{id:id},
				success:function(data){
					load_data();
			}
			});
		});
    }
});
}

function updateTable(ajax_data, current_page, highlighted, direction){

    var results_per_page = 17;
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

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='suggestionTable' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
                tbl += "<th asc='' id='suggestionTitle' class='headings' width='30%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTítulo</th>";
                tbl +=  "<th asc='' id='suggestionDescription' class='headings' width='30%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspDescrição</th>";
                tbl +=  "<th asc='' id='suggestionType' class='headings' width='10%' class='penaltybox'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbsp Tipo</th>";
                tbl +=  "<th asc='' id='suggestionStatus' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspStatus</th>";
                if(logged == "true"){
					tbl +=  "<th asc='' id='suggestionVote' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspVotar</th>";
				}
                tbl +=  "<th asc='' id='suggestionVoteNumber' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspVotos</th>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
				
			//status
			let status = "";
			if(val['status'] == 0){
				// pendente
				status = "<p class='pending icon_box'><i class='fas fa-hourglass-start'></i> Pendente</p>";
			} else if(val['status'] == 1){
				// em processo
				status = "<p class='processing icon_box'><i class='fas fa-spinner'></i> Em processo</p>";
			} else if(val['status'] == 2){
				// concluido
				status = "<p class='complete icon_box'><i class='fas fa-check-circle'></i> Completo</p>";
			} else {
				// cancelado
				status = "<p class='cancelled icon_box'><i class='fas fa-times-circle'></i> Cancelado</p>";
			}
			
			//tipo
			let type = "";
			if(val['type'] == 1){
				// sugestão
				type = "<p class='suggestion icon_box'><i class='far fa-lightbulb'></i> Sugestão</p>";
			} else {
				// bug
				type = "<p class='bug icon_box'><i class='fas fa-bug'></i> Bug</p>";
			} 
			
			// votado pelo usuário
			let voted_by_user = val['voted_by_user'];
			let button_class = "";
			console.log(val['title']);
			console.log(voted_by_user);
			if(voted_by_user == 1){
				button_class = "<button class='icon_box toggle_like toggled'><i class='fas fa-check'></i></button>";
			} else {
				button_class = "<button class='icon_box toggle_like'><i class='fas fa-thumbs-up'></i></button>";
			}

            tbl += "<tr id='"+val['id']+"' >";
				tbl +=  "<td>"+val['title']+"</td>";
				tbl +=  "<td>"+val['description']+"</td>";
                tbl +=  "<td>"+type+"</td>";
                tbl +=  "<td>"+status+"</td>";
				if(logged == "true"){
                tbl +=  "<td>"+button_class+"</td>";
				}
                tbl += "<td>"+val['vote_count']+"</td>";
            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

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
pgn += "<ul class='pagination'>";

// button for first page
if(current_page>1){
    pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
    pgn +=  "Inicio";
    pgn +=  "</button></li>";
}

// range of links to show
const range = 2;

// display links to 'range of pages' around 'current page'
var initial_num = current_page - range;
var condition_limit_num = (+current_page + +range)  + +1;

// teste com While
var x;
if(initial_num > 0){
    x = initial_num;
} else {
    x = 1;
}

while(x <= total_pages && x < condition_limit_num){
    if (x == current_page) {
            pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"<span class=\"sr-only\">(current)</span></button></li>";
        }
        else {
            pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
        }
    x = x+1;
}

// button for last page
if(current_page<total_pages){
    pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
    pgn += "Final";
    pgn += "</button></li>";
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

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {

if(prop == 'pontos'){

    localData = localData.sort(
        function(a,b){
            if (asc) return a[prop] - b[prop];
            if (!asc) return b[prop] - a[prop];
            else return 0;
        }
    );
} else {
    localData = localData.sort(
        function(a, b) {
            if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
            else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
            else return 0;
        }
    );
}


    updateTable(localData, 1,prop,0);

    }

});

</script>
<?php

echo "<div id='main-wrapper'>";
echo "<div id='melhorias-header'>
    <h2>Sugestões de melhorias</h2>
    <div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><i class='fas fa-search'></i></div>";
	if(isset($_SESSION['user_id'])){
		echo "<button id='add-new-suggestion'>+ Adicionar sugestão</button>";
	}
	echo "</div>";

//query informacoes
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/suggestion.php");
$database = new Database();
$db = $database->getConnection();

$suggestion = new Suggestion($db);


// paging buttons here
echo "<div style='clear:both; float:center'></div>";
echo "<hr>";

echo "<div id='newSuggestionWrapper'><div id='newSuggestion'>
<input id='newSuggestionTitle' class='newSuggestionItem' type='text' maxlength='40' placeholder='Título...'></input>
<textarea id='newSuggestionDescription' class='newSuggestionItem' placeholder='Descrição...'></textarea>
<select id='newSuggestionType' class='newSuggestionItem'>
<option value='0' selected disabled hidden>Tipo...</option>
<option value='1'>Sugestão</option>
<option value='2'>Bug</option>
</select>
<button class='newSuggestionItem' id='confirm-new-suggestion'>Inserir</button>
<button class='newSuggestionItem' id='cancel-new-suggestion'>Cancelar</button>
</div>";

echo "<hr id='newSuggestionHr'></div>";

//echo "<div style='clear:both; float:center'></div>";

// display the products if there are any

echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

echo('</div>');
echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
