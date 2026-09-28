<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$isAdmin = (isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1);
$page_title = "CONFUSA.top - Sugestões / Bugs";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "sugestoes_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
<script>

var localData = [];
var asc = true;
var activeSort = '';
var show_all = false;

$(document).ready(function($){

 var logged ='<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
		echo "true";
	 } else {
		echo "false";
	 };?>';

 var isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

load_data();

var pendingStatusUpdate = null;

$(document).on('focusin', '.admin_status_select', function(){
    $(this).data('prev-val', $(this).val());
});

$(document).on('change', '.admin_status_select', function(){
	let id = $(this).closest("tr").attr("id");
	let newStatus = $(this).val();
	let prevStatus = $(this).data('prev-val') !== undefined ? $(this).data('prev-val') : '0';
	let selectElem = $(this);
	
    let item = null;
    if (typeof localData !== 'undefined' && localData && localData.length) {
        item = localData.find(function(s){ return String(s.id) === String(id); });
    }
    
    let sugTitle = item ? item.title : 'Sugestão #' + id;
    let authorName = item && item.autor_nome ? item.autor_nome : 'Anônimo';
    let authorEmail = item && item.autor_email ? item.autor_email : '';
    
    let statusNames = {
        '0': '⏳ Pendente',
        '1': '🔄 Em processo',
        '2': '✅ Concluído / Resolvido',
        '3': '❌ Cancelado'
    };
    
    pendingStatusUpdate = {
        id: id,
        newStatus: newStatus,
        prevStatus: prevStatus,
        selectElem: selectElem
    };
    
    $('#modalSugTitle').text(sugTitle);
    $('#modalSugNewStatus').html(statusNames[newStatus] || 'Atualizado');
    
    if (authorEmail && authorEmail.indexOf('@') !== -1) {
        $('#modalSugAuthor').text(authorName + ' (' + authorEmail + ')');
        $('#modalNotifyEmail').prop('disabled', false).prop('checked', true);
        $('#modalEmailWarning').hide();
        $('#modalCustomMessageField').show();
        $('#modalNotifyContainer').css('opacity', '1');
    } else {
        $('#modalSugAuthor').text(authorName + ' (Sem e-mail cadastrado)');
        $('#modalNotifyEmail').prop('disabled', true).prop('checked', false);
        $('#modalEmailWarning').show();
        $('#modalCustomMessageField').hide();
        $('#modalNotifyContainer').css('opacity', '0.6');
    }
    
    $('#modalCustomMessage').val('');
    $('#statusModalOverlay').css('display', 'flex').hide().fadeIn(180);
});

function closeStatusModal(revert){
    if(revert && pendingStatusUpdate && pendingStatusUpdate.selectElem){
        pendingStatusUpdate.selectElem.val(pendingStatusUpdate.prevStatus);
    }
    $('#statusModalOverlay').fadeOut(150, function(){
        pendingStatusUpdate = null;
    });
}

$(document).on('click', '#modalCancelBtn, #modalCloseXBtn', function(e){
    e.preventDefault();
    closeStatusModal(true);
});

$(document).on('click', '#statusModalOverlay', function(e){
    if (e.target === this) {
        closeStatusModal(true);
    }
});

$(document).on('change', '#modalNotifyEmail', function(){
    if($(this).is(':checked')){
        $('#modalCustomMessageField').slideDown(150);
    } else {
        $('#modalCustomMessageField').slideUp(150);
    }
});

$(document).on('click', '#modalConfirmBtn', function(e){
    e.preventDefault();
    if(!pendingStatusUpdate) return;
    
    let id = pendingStatusUpdate.id;
    let newStatus = pendingStatusUpdate.newStatus;
    let selectElem = pendingStatusUpdate.selectElem;
    let notifyEmail = $('#modalNotifyEmail').is(':checked');
    let customMessage = $('#modalCustomMessage').val();
    
    selectElem.prop('disabled', true);
    $('#modalConfirmBtn').prop('disabled', true).text('Salvando...');
    
    $.ajax({
        url: "update_status.php",
        method: "POST",
        dataType: "json",
        data: { 
            id: id, 
            status: newStatus,
            notify_email: notifyEmail,
            custom_message: customMessage
        },
        success: function(res){
            $('#modalConfirmBtn').prop('disabled', false).text('Confirmar Alteração');
            closeStatusModal(false);
            if(res && res.success){
                if(res.email_sent){
                    // Notificação enviada
                } else if(notifyEmail && res.email_message){
                    alert(res.email_message);
                }
                load_data();
            } else {
                alert(res && res.message ? res.message : "Erro ao atualizar status.");
                selectElem.prop('disabled', false);
                selectElem.val(pendingStatusUpdate ? pendingStatusUpdate.prevStatus : '0');
            }
        },
        error: function(){
            $('#modalConfirmBtn').prop('disabled', false).text('Confirmar Alteração');
            alert("Erro de comunicação com o servidor.");
            selectElem.prop('disabled', false);
            if(pendingStatusUpdate) {
                selectElem.val(pendingStatusUpdate.prevStatus);
            }
            closeStatusModal(false);
        }
    });
});



function clear_values(){
	$('#newSuggestionTitle').val("");
	$('#newSuggestionDescription').val("");
	$('#newSuggestionType').val(0);
}

$('#add-new-suggestion').click(function(){
	$("#newSuggestion").addClass("open");
	$("#newSuggestionWrapper").addClass("open");
	$(".newSuggestionItem").addClass("open");
	$(".pagination").addClass("closed");
});

    $('#filter-pending').click(function (e) {
		e.preventDefault();
		show_all = !show_all;
		let new_text = (show_all ? 'Mostrar pendentes e em progresso' : 'Mostrar todos');

		$('#filter-pending span').text(new_text);

		updateTable(localData, 1,0,0);
		
    });

$('#cancel-new-suggestion').click(function(){
	$("#newSuggestion").removeClass("open");
	$("#newSuggestionWrapper").removeClass("open");
	$(".newSuggestionItem").removeClass("open");
	$(".pagination").removeClass("closed");
	clear_values();
});

$('#confirm-new-suggestion').click(function(){
	let title = $('#newSuggestionTitle').val();
	let description = $('#newSuggestionDescription').val();
	let type = $('#newSuggestionType').val();
	
	if(title.length > 0 && description.length > 0){
		
	$("#newSuggestion").removeClass("open");
	$("#newSuggestionWrapper").removeClass("open");
	$(".newSuggestionItem").removeClass("open");
	$(".pagination").removeClass("closed");
	
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
	}
});


$(document).on('click', '.toggle_like', function(){
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
    }
});
}

function updateTable(ajax_data, current_page, highlighted, direction){

    var filtered_data = [];
    $.each(ajax_data, function(index, val){
        if(show_all || (!show_all && (val['status'] == 0 || val['status'] == 1))){
            filtered_data.push(val);
        }
    });

    var results_per_page = 17;
    var total_results = filtered_data.length;
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

    function formatarDataSugestao(dataStr) {
        if (!dataStr) return "-";
        var parts = dataStr.split(' ');
        var dateParts = parts[0].split('-');
        if (dateParts.length === 3) {
            return dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];
        }
        return dataStr;
    }

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='suggestionTable' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
                if(logged == "true"){
                    tbl += "<th asc='' id='suggestionTitle' class='headings' width='22%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTítulo</th>";
                    tbl += "<th asc='' id='suggestionDescription' class='headings' width='22%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspDescrição</th>";
                    tbl += "<th asc='' id='suggestionAuthor' class='headings' width='13%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspUsuário</th>";
                    tbl += "<th asc='' id='suggestionDate' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspData</th>";
                    tbl += "<th asc='' id='suggestionType' class='headings' width='9%' class='penaltybox'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTipo</th>";
                    tbl += "<th asc='' id='suggestionStatus' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspStatus</th>";
                    tbl += "<th asc='' id='suggestionVote' class='headings' width='7%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspVotar</th>";
                    tbl += "<th asc='' id='suggestionVoteNumber' class='headings' width='7%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspVotos</th>";
                } else {
                    tbl += "<th asc='' id='suggestionTitle' class='headings' width='25%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTítulo</th>";
                    tbl += "<th asc='' id='suggestionDescription' class='headings' width='25%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspDescrição</th>";
                    tbl += "<th asc='' id='suggestionAuthor' class='headings' width='14%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspUsuário</th>";
                    tbl += "<th asc='' id='suggestionDate' class='headings' width='11%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspData</th>";
                    tbl += "<th asc='' id='suggestionType' class='headings' width='9%' class='penaltybox'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTipo</th>";
                    tbl += "<th asc='' id='suggestionStatus' class='headings' width='9%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspStatus</th>";
                    tbl += "<th asc='' id='suggestionVoteNumber' class='headings' width='7%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspVotos</th>";
                }
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(filtered_data, function(index, val){
			
            if(index>=(from_result_num) && index<=(from_result_num+results_per_page-1)){
				
			//status
			let status = "";
			if(isAdmin){
				status = "<select class='admin_status_select status_val_" + val['status'] + "'>";
				status += "<option value='0' " + (val['status'] == 0 ? "selected" : "") + ">⏳ Pendente</option>";
				status += "<option value='1' " + (val['status'] == 1 ? "selected" : "") + ">🔄 Em processo</option>";
				status += "<option value='2' " + (val['status'] == 2 ? "selected" : "") + ">✅ Completo</option>";
				status += "<option value='3' " + (val['status'] == 3 ? "selected" : "") + ">❌ Cancelado</option>";
				status += "</select>";
			} else {
				if(val['status'] == 0){
					// pendente
					status = "<p class='pending icon_box'><span class='material-symbols-outlined' style='font-size: 1.1rem; vertical-align: middle;'>hourglass_empty</span> Pendente</p>";
				} else if(val['status'] == 1){
					// em processo
					status = "<p class='processing icon_box'><span class='material-symbols-outlined animate-spin' style='font-size: 1.1rem; vertical-align: middle;'>sync</span> Em processo</p>";
				} else if(val['status'] == 2){
					// concluido
					status = "<p class='complete icon_box'><span class='material-symbols-outlined' style='font-size: 1.1rem; vertical-align: middle;'>check_circle</span> Completo</p>";
				} else {
					// cancelado
					status = "<p class='cancelled icon_box'><span class='material-symbols-outlined' style='font-size: 1.1rem; vertical-align: middle;'>cancel</span> Cancelado</p>";
				}
			}
			
			//tipo
			let type = "";
			if(val['type'] == 1){
				// sugestão
				type = "<p class='suggestion icon_box'><span class='material-symbols-outlined' style='font-size: 1.1rem; vertical-align: middle;'>lightbulb</span> Sugestão</p>";
			} else {
				// bug
				type = "<p class='bug icon_box'><span class='material-symbols-outlined' style='font-size: 1.1rem; vertical-align: middle;'>bug_report</span> Bug</p>";
			} 
			
			// votado pelo usuário
			let voted_by_user = val['voted_by_user'];
			let button_class = "";
			if(voted_by_user == 1){
				button_class = "<button class='toggle_like toggled'><span class='material-symbols-outlined' style='font-size: 1.1rem;'>check</span></button>";
			} else {
				button_class = "<button class='toggle_like'><span class='material-symbols-outlined' style='font-size: 1.1rem;'>thumb_up</span></button>";
			}

            let author = val['autor_nome'] ? val['autor_nome'] : 'Anônimo';
            let author_html = "<span class='author_box' style='display: inline-flex; align-items: center; gap: 4px; font-weight: 500;'><span class='material-symbols-outlined' style='font-size: 1.1rem; color: #64748b; vertical-align: middle;'>person</span> " + $('<div>').text(author).html() + "</span>";

            let date_formatted = formatarDataSugestao(val['created_at']);
            let date_html = "<span class='date_box' style='display: inline-flex; align-items: center; gap: 4px; color: #64748b; font-size: 0.88rem; white-space: nowrap;'><span class='material-symbols-outlined' style='font-size: 1rem; color: #94a3b8; vertical-align: middle;'>calendar_today</span> " + date_formatted + "</span>";

            tbl += "<tr id='"+val['id']+"' >";
				tbl +=  "<td>"+val['title']+"</td>";
				tbl +=  "<td>"+val['description']+"</td>";
				tbl +=  "<td>"+author_html+"</td>";
				tbl +=  "<td>"+date_html+"</td>";
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
                    pgn += '<li><button class="pagination_link" id="' + x + '" disabled>' + x + '<span class="sr-only">(current)</span></button></li>';
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

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {
    var field = prop;
    if(prop === 'suggestionTitle') field = 'title';
    else if(prop === 'suggestionDescription') field = 'description';
    else if(prop === 'suggestionAuthor') field = 'autor_nome';
    else if(prop === 'suggestionDate') field = 'created_at';
    else if(prop === 'suggestionType') field = 'type';
    else if(prop === 'suggestionStatus') field = 'status';
    else if(prop === 'suggestionVoteNumber') field = 'vote_count';

    localData = localData.sort(function(a, b) {
        let aActive = (a.status == 0 || a.status == 1);
        let bActive = (b.status == 0 || b.status == 1);
        if (aActive && !bActive) return -1;
        if (!aActive && bActive) return 1;
        
        let valA = a[field] !== undefined && a[field] !== null ? a[field] : '';
        let valB = b[field] !== undefined && b[field] !== null ? b[field] : '';
        
        if (field === 'created_at') {
            let dateA = valA ? new Date(String(valA).replace(/-/g, '/')).getTime() || 0 : 0;
            let dateB = valB ? new Date(String(valB).replace(/-/g, '/')).getTime() || 0 : 0;
            if (asc) return dateA - dateB;
            else return dateB - dateA;
        } else if (field === 'vote_count' || field === 'type' || field === 'status') {
            valA = Number(valA) || 0;
            valB = Number(valB) || 0;
            if (asc) return valA - valB;
            else return valB - valA;
        } else {
            valA = String(valA).toLowerCase();
            valB = String(valB).toLowerCase();
            if (asc) return valA.localeCompare(valB, 'pt-BR');
            else return valB.localeCompare(valA, 'pt-BR');
        }
    });

    updateTable(localData, 1, prop, 0);
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
	echo "<button id='filter-pending'><span>Mostrar todos</span></button>";
	echo "</div>";

//query informacoes
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/suggestion.php");
$database = new Database();
$db = $database->getConnection();

$suggestion = new Suggestion($db);


// paging buttons here
//echo "<div style='clear:both; float:center'></div>";
echo "<hr>";

echo "<div id='newSuggestionWrapper'><div id='newSuggestion'>
<input required  id='newSuggestionTitle' class='newSuggestionItem' type='text' maxlength='40' placeholder='Título...'></input>
<textarea id='newSuggestionDescription' class='newSuggestionItem' placeholder='Descrição...' required ></textarea>
<select id='newSuggestionType' class='newSuggestionItem'>
<option value='0' selected disabled hidden>Tipo...</option>
<option value='1'>Sugestão</option>
<option value='2'>Bug</option>
</select>
<div class='suggestion-actions'>
<button class='newSuggestionItem' id='confirm-new-suggestion'>Inserir</button>
<button class='newSuggestionItem' id='cancel-new-suggestion'>Cancelar</button>
</div>
</div></div>";

//echo "<div style='clear:both; float:center'></div>";

// display the products if there are any

echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

if ($isAdmin) {
    echo "
    <div id='statusModalOverlay'>
        <div id='statusChangeModal'>
            <div class='modal-header'>
                <h3><span class='material-symbols-outlined' style='font-size: 1.3rem; vertical-align: middle;'>forward_to_inbox</span> Atualizar Status da Sugestão</h3>
                <button class='modal-close-btn' id='modalCloseXBtn'>&times;</button>
            </div>
            <div class='modal-body'>
                <div class='modal-info-card'>
                    <div class='modal-info-row'>
                        <strong style='min-width: 90px;'>Sugestão:</strong>
                        <span id='modalSugTitle' style='color: #0f172a; font-weight: 600;'></span>
                    </div>
                    <div class='modal-info-row'>
                        <strong style='min-width: 90px;'>Novo Status:</strong>
                        <span id='modalSugNewStatus'></span>
                    </div>
                    <div class='modal-info-row'>
                        <strong style='min-width: 90px;'>Autor:</strong>
                        <span id='modalSugAuthor'></span>
                    </div>
                </div>

                <div class='modal-field'>
                    <label class='modal-checkbox-label' id='modalNotifyContainer'>
                        <input type='checkbox' id='modalNotifyEmail' checked>
                        <span>Notificar o originador por e-mail sobre esta atualização</span>
                    </label>
                    <div id='modalEmailWarning' style='display: none; font-size: 0.82rem; color: #dc2626; margin-top: 4px; padding-left: 4px;'>
                        <span class='material-symbols-outlined' style='font-size: 1rem; vertical-align: middle;'>warning</span> O autor não possui um e-mail válido cadastrado.
                    </div>
                </div>

                <div class='modal-field' id='modalCustomMessageField'>
                    <label for='modalCustomMessage'>Mensagem personalizada para o originador (opcional):</label>
                    <textarea id='modalCustomMessage' placeholder='Ex: A funcionalidade foi implementada na versão recente e já está disponível para uso.'></textarea>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='modal-btn-cancel' id='modalCancelBtn'>Cancelar</button>
                <button class='modal-btn-confirm' id='modalConfirmBtn'>Confirmar Alteração</button>
            </div>
        </div>
    </div>";
}

echo('</div>');
echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
