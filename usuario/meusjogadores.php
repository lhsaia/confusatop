<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus jogadores - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");

	$database = new Database();
	$db = $database->getConnection();
	
	$usuario = new Usuario($db);
	$time = new Time($db);
	$jogador = new Jogador($db);
	$pais = new Pais($db);
	
	// query caixa de seleção países desse dono
	$stmtPais = $pais->read();
	$listaPaises = array();
	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		$addArray = array($id, $sigla);
		$listaPaises[] = $addArray;
	}
	
	// query caixa de seleção de posições
	$stmtPos = $jogador->selectPosicoes();
	$listaPosicoes = array();
	while ($row_pos = $stmtPos->fetch(PDO::FETCH_ASSOC)){
		extract($row_pos);
		$addArray = array($ID, $Sigla);
		$listaPosicoes[] = $addArray;
	}
	
	// query caixa de seleção de posições
	$stmtMen = $jogador->listaMentalidade();
	$listaMentalidades = array();
	while ($row_men = $stmtMen->fetch(PDO::FETCH_ASSOC)){
		extract($row_men);
		$addArray = array($ID, $Nome);
		$listaMentalidades[] = $addArray;
	}
	
		// query caixa de seleção de posições
	$stmtCob = $jogador->listaCobradorFalta();
	$listaCobradores = array();
	while ($row_cob = $stmtCob->fetch(PDO::FETCH_ASSOC)){
		extract($row_cob);
		$addArray = array($ID, $Nome);
		$listaCobradores[] = $addArray;
	}
?>

<script>

var localData = [];
var asc = true;
var activeSort = '';

var listaPaises =  <?php echo json_encode($listaPaises); ?>;

var listaPosicoes =  <?php echo json_encode($listaPosicoes); ?>;

var listaMentalidades =  <?php echo json_encode($listaMentalidades); ?>;

var listaCobradores =  <?php echo json_encode($listaCobradores); ?>;

 var logged ='<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
		echo "true";
	 } else {
		echo "false";
	 };?>';
	 
  var admin ='<?php if(isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1){
	echo "true";
 } else {
	echo "false";
 };?>';
 
   var user_id ='<?php if(isset($_SESSION['user_id']) ){
	echo $_SESSION['user_id'];
 } else {
	echo $_SESSION['user_id'];
 };?>';
 
 $(document).ready(function($){
	 
	function selectElement(id, valueToSelect) {    
		let element = document.getElementById(id);
		element.value = valueToSelect;
	}
	 
	 
	function createPositionString(stringPosicoes){
		if (stringPosicoes[0] == '1') return "G";
		
		var predicateArray = stringPosicoes.substring(1);
		predicateArray = predicateArray.split("");
		predicateArray = predicateArray.map(function(e) { 
			e = parseInt(e);
			return !!e;
		});
		var data = listaPosicoes.map(function(x) {
			return x[1];
		});
		var results = data.filter((d, ind) => predicateArray[ind]);
		
		results = results.join("-");
		
		return results;
	}
	 
	load_data();

	//typing timer ajax improvement
	//setup before functions
	var typingTimer;                //timer identifier
	var doneTypingInterval = 800;  //time in ms (5 seconds)

	//on keyup, start the countdown
	$('#caixa_pesquisa').keyup(function(){
		clearTimeout(typingTimer);
		if ($('#caixa_pesquisa').val()) {
			typingTimer = setTimeout(doneTyping, doneTypingInterval);
		}
		typingTimer = setTimeout(doneTyping, doneTypingInterval);
	});

	//user is "finished typing," do something
	function doneTyping () {
		load_data();
	}
	
	function load_data(){

	var searchText = $('#caixa_pesquisa').val();
	$('#loading').show();  // show loading indicator

	$.ajax({
		url:"search_player.php",
		method:"POST",
		cache:false,
		data:{searchText:searchText},
		success:function(data){
			$('#loading').hide();  // hide loading indicator
			updateTable(JSON.parse(data),1,0,0);
			localData = JSON.parse(data);
			
			// $('.toggle_like').click(function(){
				// let id = $(this).closest("tr").attr("id");
				// $.ajax({
					// url:"toggle_like.php",
					// method:"POST",
					// cache:false,
					// data:{id:id},
					// success:function(data){
						// load_data();
				// }
				// });
			// });
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
			tbl += "<thead id='headings"+user_id+"'>";
				tbl += "<tr>";
					tbl += "<th asc='' class='headings' width='15%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNome</th>";
					tbl += "<th asc='' class='headings' width='10%'>Nascimento (idade) </th>";
					tbl += "<th asc='' class='headings' width='10%'>Mentalidade</th>";
					tbl += "<th asc='' class='headings' width='10%'>Cobrança de Falta</th>";
					tbl += "<th asc='' class='headings' width='5%'>Valor</th>";
					tbl += "<th asc='' class='headings' width='10%'>Posições</th>";
					tbl += "<th asc='' class='headings' width='3%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNível</th>";
					tbl += "<th asc='' class='headings' width='3%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspPaís</th>";
					tbl += "<th asc='' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspClube</th>";
					tbl += "<th asc='' class='headings' width='5%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspStatus</th>";
					tbl += "<th asc='' class='headings' width='10%' class=''>Opções</th>";
				tbl += "</tr>";
			tbl +=  "</thead>";
			tbl +=  "<tbody>";

			// criar linhas
			$.each(ajax_data, function(index, val){

				if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
				
				// genero
				let genderCode = ""
				let genderClass = ""
				if(val['sexo'] == 0){
					genderCode = "M";
					genderClass = "genderMas";
				} else {
					genderCode = "F";
					genderClass = "genderFem";
				}
				
				var options = { year: 'numeric', month: '2-digit', day: '2-digit'};
				var dataNascimento = new Date(val['Nascimento'].replace(/-/g, '\/'));
				var nascimentoDisplay = dataNascimento.toLocaleDateString("pt-BR", options);
				
				var valorDisplay = "F$ " +  Math.round((parseInt(val['valor'])/1000), 2) + "k";
				
				// geração da tabela
				tbl += "<tr id='"+val['ID']+"' data-sexo='"+val['sexo']+"' >";
					tbl +=  "<td><span class='nomeEditavel' id='nom"+val['id']+"'><a class='linkNome' href='/ligas/playerstatus.php?player="+val['id']+"' >"+val['Nome']+"</a></span><span class=' "+genderClass+" genderSign'>"+genderCode+"</span></td>";
					tbl += "<td><span class='nomeNascimento' id='nas"+ val['id']+"'>"+ nascimentoDisplay + " (" +val['Idade']+") "+" </span><input id='selnas"+val['id']+"' class='nascimentoEditavel editavel' type='date' value='"+val['Nascimento']+"' hidden/></td>";
					tbl += "<td><span class='nomeMentalidade' id='men"+ val['id']+"'>"+ val['Mentalidade'] +"</span><select id='selmen"+val['id']+"' class='comboMentalidade editavel' value='"+val['Mentalidade']+"' hidden>";
							listaMentalidades.forEach(function(value, key){
								tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
							});
						tbl += "</select>";	
					tbl += "</td>";
					tbl += "<td><span class='nomeCobrador' id='cob"+ val['ID']+"'>"+ val['CobradorFalta'] +"</span><select id='selcob"+val['ID']+"' class='comboCobrador editavel'  hidden>";
							listaCobradores.forEach(function(value, key){
								tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
							});
						tbl += "</select>";	
					tbl += "</td>";
					tbl += "<td><span class='nomeValor id='val" + val['id']+"'>" + valorDisplay + "</span><span class='valorEditavel editavel' contenteditable='true' hidden>" + val['valor'] + "</span></td>";
					

					let splitPositions = createPositionString(val['StringPosicoes']);
					

					tbl += "<td><span class='nomePosicao posicoesAtuais' id='pos"+ val['id']+"'>"+ splitPositions +"</span>";
                 	tbl += " <select multiple class='comboPosicoes editavel' hidden>'  ";
						listaPosicoes.forEach(function(value, key){
							tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
						});
					
                 	tbl +=  "</select>";
					tbl += "</td>";

					tbl += "<td><span class='nivelEditavel' id='niv"+val['ID']+"'>"+val['Nivel']+"</span></td>";
					
					tbl += "</td>";
					if(val['idPais'] != 0){
						tbl += "<td class='wide'><img src='/images/bandeiras/"+val['bandeiraPais']+"' class='bandeira nomePais' id='ban"+val['ID']+"'>  <span class='nomePais' id='pai"+val['ID']+"'>"+val['siglaPais']+"</span>";
					} else {
						tbl += "<td>";
					}
					tbl += "<select class='comboPais editavel' id='"+val['idPais']+"' hidden>'  ";
						listaPaises.forEach(function(value, key){
							tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
						});

					tbl += "</select>";
					tbl += "</td>";
					
					if(val['clubeVinculado'] != null){
						tbl += "<td><a href='/ligas/teamstatus.php?team="+val['idClubeVinculado']+"' id='dis"+val['ID']+"'><img class='minithumb' src='/images/escudos/"+val['escudoClubeVinculado']+"'>"+val['clubeVinculado']+"</a><span class='donoClubeVinculado' hidden>"+val['donoClubeVinculado']+"</span></td>";
					} else {
						tbl += "<td>";
					}
					tbl += "</td>";
					
					
					var nomeDisponibilidade = "";
					switch(val['disponibilidade']){
						case -2:
							nomeDisponibilidade = "Expatriado";
							break;
						case -1:
							nomeDisponibilidade = "Aposentado"; 
							break;
						case 0:
							nomeDisponibilidade = "Ativo";
							break;
						case 1:
							nomeDisponibilidade = "Ativo (disponível)";
							break;
					}

					tbl += "<td><span class='nomeAtividade' id='dis"+val['ID']+"'>"+nomeDisponibilidade+"</span><select data-idTime='"+val['idClubeVinculado']+"' class='comboAtividade editavel' id='seldis"+val['ID']+"' hidden >";
					tbl += "<option value='1' title='Ativo e disponível para negociar'>Ativo (disponível)</option>";
					tbl += "<option value='0' title='Ativo'>Ativo</option>";
					tbl += "<option value='-1' title='Aposentado, não pode ser contratado'>Aposentado</option>";
					tbl += "<option value='-2' title='Jogando em clubes fora do Portal, não pode ser contratado'>Expatriado</option>";
					tbl += "</select></td>";

					
					let optionsString = "<td class='wide'>";

					if(logged == "true"){
						if(admin == "true" || user_id === val['idDonoPais']){
							optionsString += "<a id='edi"+val['ID']+"' title='Editar jogador' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
							optionsString += "<a id='apa"+val['ID']+"' title='Apagar jogador' class='clickable apagar'><i class='fas fa-trash-alt inlineButton negativo'></i></a>";
							optionsString += "<a hidden id='sal"+val['ID']+"' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
							optionsString += "<a hidden id='can"+val['ID']+"' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
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
		
		// inclusão de formulas de edição
		

	$(".editar").on("click", function(){
	var tbl_row = $(this).closest("tr");

	tbl_row.find('a').each(function(index, val){
		$(this).attr('original_entry', $(this).html());
	});

	tbl_row.find('span').each(function(index, val){
		$(this).attr('original_entry', $(this).html());
	});

	tbl_row.find('input').each(function(index, val){
		$(this).attr('data-original-entry', $(this).val());
	});

	tbl_row.find(".salvar").show();
	tbl_row.find(".cancelar").show();
	tbl_row.find(".editar").hide();
	tbl_row.find(".apagar").hide();

	//garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
	var donoTime = tbl_row.find(".donoClubeVinculado").html();
	var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
	//var donoJogador =9;
	
	console.log(donoTime);
	console.log(donoJogador);

	if (typeof donoTime === 'undefined'){
		donoTime = donoJogador;
	}

if(donoTime.localeCompare(donoJogador) == 0){
    var isDono = true;
} else {
    var isDono = false;
}

if(isDono){
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.linkNome').css("cursor","text");
    tbl_row.find('.linkNome').css("pointer-events","none");
    
    tbl_row.find('.comboMentalidade').show();
    tbl_row.find('.comboAtividade').show();
    tbl_row.find('.nomeCobrador').hide();
    tbl_row.find('.nomeMentalidade').hide();
    tbl_row.find('.nomePais').hide();
    tbl_row.find('.nomeAtividade').hide();
    tbl_row.find('.nomeNascimento').hide();
    tbl_row.find('.nascimentoEditavel').show();
    tbl_row.find('.nomeValor').hide();
    tbl_row.find('.valorEditavel').show();

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);

	tbl_row.find('.comboCobrador').show();

}


tbl_row.find('.nivelEditavel').attr('contenteditable', 'true').addClass('editavel');

tbl_row.find('.comboCobrador option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeCobrador').html();
}).prop("selected", true);

tbl_row.find('.comboMentalidade option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeMentalidade').html();
}).prop("selected", true);

tbl_row.find('.comboAtividade option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeAtividade').html();
}).prop("selected", true);

//verificar se é goleiro
var stringPosicoes = tbl_row.find('.posicoesAtuais').html();
var isGoleiro = stringPosicoes.localeCompare("G");

if(isGoleiro){
    tbl_row.find('.posicoesAtuais').hide();
    tbl_row.find('.comboPosicoes').show();
}

//valor original posicoes
var arrPosicoes = stringPosicoes.split('-');

tbl_row.find('.comboPosicoes option').each(function(){

    if($.inArray($(this).html(), arrPosicoes) !== -1){
        $(this).prop("selected","selected");
    } else {
        $(this).prop("selected", false);
    }
});

});

$('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".apagar").show();
        tbl_row.find('.linkNome').css("cursor","pointer");
        tbl_row.find('.linkNome').css("pointer-events","auto");

        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboCobrador').hide();
        tbl_row.find('.comboMentalidade').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboAtividade').hide();
        tbl_row.find('.nomeCobrador').show();
        tbl_row.find('.nomeMentalidade').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.nomeAtividade').show();
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.nomeValor').show();
        tbl_row.find('.valorEditavel').hide();
        tbl_row.find('.nomePosicao').show();
        tbl_row.find('.comboPosicoes').hide();

        tbl_row.find('a').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('input').each(function(index, val){
            $(this).val($(this).attr('data-original-entry'));
        });
    });

    $('.apagar').click(function(){
        var tbl_row =  $(this).closest('tr');
        var jogadorId = tbl_row.prop('id');
        var r = confirm("Você tem certeza que deseja apagar esse jogador? Essa ação não pode ser desfeita!");
        if (r) {
            $.ajax({
                type: "POST",
                url: '/jogadores/apagar_jogador.php',
                data: {jogadorId:jogadorId},
                dataType: 'json',
                success: function(data) {
                  console.log(data.error);
                  if(!data.success){
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível apagar o jogador. '+ data.error +'</div>');
                  } else {
                    location.reload();
                  }


                },
                error: function(data) {
                    successmessage = 'Error';
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível apagar o jogador. '+data.error+'</div>');
                }
            });
        }


    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".apagar").show();
        tbl_row.find('.linkNome').css("cursor","pointer");
        tbl_row.find('.linkNome').css("pointer-events","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboCobrador').hide();
        tbl_row.find('.comboMentalidade').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboAtividade').hide();
        tbl_row.find('.nomeCobrador').show();
        tbl_row.find('.nomeMentalidade').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.nomeAtividade').show();
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.nomeValor').show();
        tbl_row.find('.valorEditavel').hide();
        tbl_row.find('.nomePosicao').show();
        tbl_row.find('.comboPosicoes').hide();

        //coleta de valores

        //check se é dono do jogador
        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = tbl_row.find(".donoClubeVinculado").html();
        var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
        //var donoJogador =9;

        if (typeof donoTime === 'undefined'){
            donoTime = donoJogador;
        }

        if(donoTime.localeCompare(donoJogador) == 0){

            var isDono = true;
        } else {
            var isDono = false;
        }

        var idJogador = tbl_row.prop('id');

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nacionalidade = tbl_row.find(".comboPais").val();
            var nascimento = tbl_row.find(".nascimentoEditavel").val();
            var valor = parseInt(tbl_row.find(".valorEditavel").html());
            var determinacao = "1";
            var mentalidade = tbl_row.find(".comboMentalidade").val();
            var cobrancaFalta = tbl_row.find(".comboCobrador").val();
            var atividade = tbl_row.find(".comboAtividade").val();
			var timeParaDemissao = tbl_row.find(".comboAtividade").attr("data-idTime");
        }

        var nivel = tbl_row.find(".nivelEditavel").html();
        var posicoes = tbl_row.find(".comboPosicoes").val();
        //var idTime = $('#quadro-container').prop('class');

        var formData = {
            'idJogador' : idJogador,
            'alteracao' : 9,
            'posicoes' : posicoes,
            'nivel' : nivel
        }



if(isDono){
    var moreData = {
            'nome' : nome,
            'nacionalidade' : nacionalidade,
            'nascimento' : nascimento,
            'valor' : valor,
            'determinacao' : determinacao,
            'mentalidade' : mentalidade,
            'cobrancaFalta' : cobrancaFalta,
            'atividade' : atividade,
			'timeParaDemissao' : timeParaDemissao

        }

    $.extend(formData,moreData);
}


console.log(formData);

     ajaxCallJogador(formData);


    });

	
	
function ajaxCallJogador(formData){

$.ajax({
        type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
        url         : '/jogadores/editar_jogador.php', // the url where we want to POST
        data        : formData, // our data object
        // processData : false,
        // contentType : false,
        dataType    : 'json', // what type of data do we expect back from the server
                    encode          : true
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
}
	  
		

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


<div id="quadro-container">
<div align="center" id="quadroTimes">
<div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><i class='fas fa-search'></i></div>
<button id='importar_time' onclick="window.location='/jogadores/criar_jogador.php';">Criar jogador</button>
<button id='importar_time' onclick="window.location='/jogadores/importar_jogador.php';">Importar jogador</button>
<h2>Quadro de jogadores - <?php echo $_SESSION['nomereal']?></h2>
<hr>
<div id='errorbox'></div>

<?php




    // paging buttons here
echo "<div style='clear:both;'></div>";
echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

echo('</div>');
echo('</div>');

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
