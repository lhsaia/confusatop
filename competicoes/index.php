<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Índice de competições";
$css_filename = "home_redesign";
$aux_css = "competicoes_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$pais = new Pais($db);
	$federacao_comp = new Federacao($db);
	$competicao = new Competicao_clube($db);

	// query caixa de seleção países
	$stmtPais = $pais->read(null, null, false);
	$listaPaises = array();
	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		$addArray = array($id, $nome);
		$listaPaises[] = $addArray;
	}
	
	// query caixa de seleção federações
	$stmtFederacao = $federacao_comp->read();
	$listaFederacoes = array();
	while ($row_federacao = $stmtFederacao->fetch(PDO::FETCH_ASSOC)){
		extract($row_federacao);
		$addArray = array($id, $nome);
		$listaFederacoes[] = $addArray;
	}
	
?>

<script>

var localData = [];
var asc = true;
var activeSort = '';

var listaPaises =  <?php echo json_encode($listaPaises); ?>;

var listaFederacoes =  <?php echo json_encode($listaFederacoes); ?>;

 var logged ='<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
		echo "true";
	 } else {
		echo "false";
	 };?>';
	 
  var admin ="<?php if(isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1){
	echo 'true';
 } else {
	echo 'false';
 };?>";
 
   var user_id ='<?php if(isset($_SESSION['user_id']) ){
	echo $_SESSION['user_id'];
 } else {
	echo $_SESSION['user_id'];
 };?>';

$(document).ready(function($){
	
		 <?php
	 if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
		 echo "$('#toolbar').html('<div id=\"criar_competicao\"><span class=\"material-symbols-outlined\">add_circle</span><span>Competição</span></div>')";
	 }
    
	?>
	
	$("#criar_competicao").click(function(){
		window.location.href= '/competicoes/criar_competicao.php';
	});

load_data();

	function delay(fn, ms){
		let timer = 0;
		return function(...args){
			clearTimeout(timer)
			timer = setTimeout(fn.bind(this, ...args), ms || 0)
		}
	}

	//on keyup, start the countdown
	$('#caixa_pesquisa').keyup(delay(function(e){
		load_data();
	},800));

function load_data(){

var searchText = $('#caixa_pesquisa').val();
$('#loading').show();  // show loading indicator

$.ajax({
    url:"search_competicao.php",
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

    var results_per_page = 18;
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
    tbl += "<table id='tabelaPrincipal' class='table'>";
        tbl += "<thead id='headings'>";
			tbl += "<tr>";
				tbl += "<th asc='' class='headings' width='30%' id='nome'><span class='th-content'>Competição<span class='material-symbols-outlined ascending hidden'>expand_less</span><span class='material-symbols-outlined descending hidden'>expand_more</span></span></th>";
				tbl += "<th class='headings' width='12%'>Logo</th>";
				tbl += "<th asc='' class='headings' width='12%' id='ano'><span class='th-content'>Ano<span class='material-symbols-outlined ascending hidden'>expand_less</span><span class='material-symbols-outlined descending hidden'>expand_more</span></span></th>";
				tbl += "<th asc='' class='headings' width='18%' id='federacao'><span class='th-content'>Federação<span class='material-symbols-outlined ascending hidden'>expand_less</span><span class='material-symbols-outlined descending hidden'>expand_more</span></span></th>";
				tbl += "<th asc='' class='headings' width='18%' id='sede'><span class='th-content'>Sede<span class='material-symbols-outlined ascending hidden'>expand_less</span><span class='material-symbols-outlined descending hidden'>expand_more</span></span></th>";
				tbl += "<th class='headings' width='10%'>Opções</th>";
			tbl += "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
			
			// genero
			let genderCode = ""
			let genderClass = ""
			if(val['genero'] == 0){
                genderCode = "M";
                genderClass = "genderMas";
            } else {
                genderCode = "F";
                genderClass = "genderFem";
            }
			
			// geração da tabela
			tbl += "<tr id='"+val['id']+"' data-sexo='"+val['sexo']+"' >";
				tbl +=  "<td data-label='Competição'><span class='nomeEditavel' id='nom"+val['id']+"'><a class='linkNome' href='/competicoes/competitionstatus.php?id="+val['id']+"' >"+val['nome']+"</a></span><span class=' "+genderClass+" genderSign'>"+genderCode+"</span></td>";
				tbl += "<td data-label='Logo'><div class='imageUpload'><img class='thumb' src='/images/competicoes/"+val['logo']+"' /> <input type='file' hidden id='logo"+val['id']+"' class='hiddenInput custom-file-upload' name='logo' accept='.jpg,.png,.jpeg,.webp'/></div></td>";
				tbl += "<td data-label='Ano'><span class='fidelidadeFixo'>"+val['ano']+"</span><input type='number' min='1' max='2100' class=' fidelidade inputHerdeiro' value="+val['ano']+" id='ano"+val['id']+"' hidden></td>";
                
				if(val['federacao'] != null){
                    tbl += "<td class='wide' data-label='Federação'>  <span class='nomePais' id='fed"+val['id']+"'>"+val['federacao']+"</span>";
                } else {
                    tbl += "<td data-label='Federação'>";
                }
                tbl += " <select class='comboLiga editavel ' id='selfed"+val['idFederacao']+"' hidden>'  ";
				tbl += "<option value='0'>-</option>";
					listaFederacoes.forEach(function(value, key){
						tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
					});

                tbl += "</select>";
				tbl += "</td>";
				
				if(val['idSede'] != 0){
                    tbl += "<td class='wide' data-label='Sede'><img src='/images/bandeiras/"+val['bandeiraSede']+"' class='bandeira nomePais' id='ban"+val['id']+"'>  <span class='nomePais' id='pai"+val['id']+"'>"+val['siglaSede']+"</span>";
                } else {
                    tbl += "<td data-label='Sede'>";
                }
                tbl += "<select class='comboPais editavel' id='"+val['idSede']+"' hidden>'  ";
						tbl += "<option value='0'>Sem sede fixa</option>";
					listaPaises.forEach(function(value, key){
						tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
					});

                tbl += "</select>";
                tbl += "</td>";

                let optionsString = "<td class='wide' data-label='Opções'>";

                if(logged == "true"){
                    if(admin == "true" || user_id === val['idDonoPais']){
                        optionsString += "<a id='edi"+val['id']+"' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        optionsString += "<a hidden id='sal"+val['id']+"' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        optionsString += "<a hidden id='can"+val['id']+"' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                    }
                    optionsString += "</td>";
                    tbl += optionsString;
                }

                 tbl += "</tr>";


            }
        });

        tbl += '</tbody>';
    tbl += '</table>';
	
	

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);

    addFilters();

    //$(document).find('#'+highlighted).addClass('highlighted');

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
	
	// inclusão de formulas de edição
	
	 $('.editar').click(function(){
		var tbl_row =  $(this).closest('tr');
		tbl_row.find('span').each(function(index, val){
			$(this).attr('original_entry', $(this).html());
		});
		tbl_row.find('.linkNome').css("cursor","text");
		tbl_row.find('.linkNome').css("pointer-events","none");
		tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
		tbl_row.find('.salvar').show();
		tbl_row.find('.cancelar').show();
		tbl_row.find('.editar').hide();
		tbl_row.find('.deletar').hide();
		tbl_row.find('.nomePais').hide();
		tbl_row.find('.hiddenInput').show();
		tbl_row.find('.fidelidadeFixo').hide();
		tbl_row.find('.fidelidade').show().val(tbl_row.find('.fidelidadeFixo').html());
		
		tbl_row.find('.thumb').addClass('editableThumb');

		var paisId = tbl_row.find('.comboPais').attr('id');
		tbl_row.find('.comboPais').show().val(paisId);

		var ligaId = tbl_row.find('.comboLiga').attr('id').replace(/\D/g,'');;
		tbl_row.find('.comboLiga').show().val(ligaId);

});

    $('.cancelar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();
    tbl_row.find('.comboLiga').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();
    tbl_row.find('.fidelidadeFixo').show();
    tbl_row.find('.fidelidade').hide();

    tbl_row.find('span').each(function(index, val){
        $(this).html($(this).attr('original_entry'));
    });
});

  $(".fidelidade").each(function(){

    $(this).keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });

  });

  $(".fidelidade").each(function(){

    $(this).keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1));
    else
      $(this).val($(this).data("old"));
  });


  });
  
     
$('.salvar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();
    tbl_row.find('.comboLiga').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();
    tbl_row.find('.fidelidadeFixo').show();
    tbl_row.find('.fidelidade').hide();

    var id = tbl_row.attr('id');
    var nome = tbl_row.find('#nom'+id).text();
    var ano = tbl_row.find('#ano'+id).val();
    var federacao = tbl_row.find('.comboLiga').val();
    var sede = tbl_row.find('.comboPais').val();

    //escudo
    var inputLogo = (tbl_row.find('#logo'+id))[0];
    var logo;

    if (inputLogo.files.length > 0) {
       logo = inputLogo.files[0];
    } else {
       logo = null;
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append('nome', nome);
    formData.append('ano', ano);
    formData.append('sede', sede);
    formData.append('federacao', federacao);
     if(logo != null){
        formData.append('logo', logo);
     }


for (var key of formData.entries()) {
     console.log(key[0] + ', ' + key[1]);
 }

     $.ajax({
         url: 'alterar_competicao.php',
         processData: false,
		 contentType: false,
         cache: false,
         type: "POST",
         dataType: 'json',
         data: formData,
		 })

                .done(function(data) {

        // log data to the console so we can see
        console.log(data);


        if (! data.success) {
            window.scrollTo(0, 0);
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível editar o jogador, '+data.error+'</div>');


        } else {

        $('#modalProposta').hide();
            //$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

            location.reload();

        }

        // here we will handle errors and validation messages
        }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível editar o jogador, '+errorThrown+'</div>');
        });

});


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
 
}

});

</script>

<main class="propostas-container">
    <div id='errorbox'></div>
    <div id='error_box'></div>

    <div class="propostas-card">
        <div class="header-search-container">
            <h2 class="propostas-title">Índice de Competições</h2>
            <div id='search_wrapper'>
                <input type="text" id='caixa_pesquisa' placeholder='Pesquisar...'>
                <span class='material-symbols-outlined'>search</span>
            </div>
        </div>

        <div style='clear:both;'></div>
        <hr>
        <div class='tbl_user_data'>
            <img id='loading' src='/images/icons/ajax-loader.gif'>
        </div>
    </div>
</main>

<?php
} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
