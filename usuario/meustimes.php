<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus times - ".$_SESSION['nomereal'];
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
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$time = new Time($db);
	$estadio = new Estadio($db);
	$pais = new Pais($db);
	$liga = new Liga($db);

	// query caixa de seleção países desse dono
	$stmtPais = $pais->read($_SESSION['user_id']);
	$listaPaises = array();
	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		$addArray = array($id, $nome);
		$listaPaises[] = $addArray;
	}


	// query caixa de seleção ligas desse dono
	$stmtLiga = $liga->read($_SESSION['user_id']);
	$listaLigas = array();
	while ($row_liga = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
		extract($row_liga);
		$addArray = array($id, $nome);
		$listaLigas[] = $addArray;
	}

	// query caixa de seleção estadios desse dono
	$stmtEstadio = $estadio->read($_SESSION['user_id']);
	$listaEstadios = array();
	while ($row_estadio = $stmtEstadio->fetch(PDO::FETCH_ASSOC)){
		extract($row_estadio);
		$addArray = array($id, $nome, $capacidade);
		$listaEstadios[] = $addArray;
	}
	
	
?>

<script>

var localData = [];
var asc = true;
var activeSort = '';

// obtenção dos dados de estádios, países e ligas
var listaEstadios =  <?php echo json_encode($listaEstadios); ?>;

var listaPaises =  <?php echo json_encode($listaPaises); ?>;

var listaLigas =  <?php echo json_encode($listaLigas); ?>;

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
	



load_data();

//typing timer ajax improvement
//setup before functions
//var typingTimer;                //timer identifier
//var doneTypingInterval = 800;  //time in ms (5 seconds)

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
		//clearTimeout(typingTimer);
		//if ($('#caixa_pesquisa').val()) {
		//	typingTimer = setTimeout(doneTyping, doneTypingInterval);
		//}
		//typingTimer = setTimeout(doneTyping, doneTypingInterval);
	},800));

// user is "finished typing," do something
// function doneTyping () {
    // load_data();
// }

//$('#caixa_pesquisa').keyup(function(){load_data()});

function load_data(){

var searchText = $('#caixa_pesquisa').val();
$('#loading').show();  // show loading indicator

$.ajax({
    url:"search_team.php",
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
        tbl += "<thead id='headings'>";
			tbl += "<tr>";
				tbl += "<th asc='' class='headings' width='10%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTime</th>";
				tbl += "<th asc='' class='headings' width='2%'>Escudo</th>";
				tbl += "<th asc='' class='headings' width='2%'>Uniforme 1</th>";
				tbl += "<th asc='' class='headings' width='2%'>Cores 1</th>";
				tbl += "<th asc='' class='headings' width='2%'>Uniforme 2</th>";
				tbl += "<th asc='' class='headings' width='2%'>Cores 2</th>";
				tbl += "<th asc='' class='headings' width='15%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspEstadio</th>";
				tbl += "<th asc='' class='headings' width='2%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspMax Torcida</th>";
				tbl += "<th asc='' class='headings' width='2%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspFidelidade</th>";
				tbl += "<th asc='' class='headings' width='20%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspLiga</th>";
				tbl += "<th asc='' class='headings' width='20%' class=''><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspPaís</th>";
				tbl += "<th asc='' class='headings' width='5%' class=''>Opções</th>";
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
			
			// geração da tabela
			tbl += "<tr id='"+val['id']+"' data-sexo='"+val['sexo']+"' >";
				tbl +=  "<td><span class='nomeEditavel' id='nom"+val['id']+"'><a class='linkNome' href='/ligas/teamstatus.php?team="+val['id']+"' >"+val['Nome']+"</a></span><span class=' "+genderClass+" genderSign'>"+genderCode+"</span></td>";
				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/escudos/"+val['Escudo']+"' /> <input type='file' hidden id='escudo"+val['id']+"' class='hiddenInput custom-file-upload' name='escudo' accept='.jpg,.png,.jpeg'/></div></td>";
				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/"+val['Uniforme1']+"' /> <input type='file' hidden id='uni1"+val['id']+"' class='hiddenInput custom-file-upload' name='uni1' accept='.jpg,.png,.jpeg'/></div></td>";
				tbl += "<td class='celula-uniforme'><div class='quadrado-uniforme' id='"+val['Uni1Cor1']+"'><input type='color' name='u1c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni1Cor2']+"'><input type='color' name='u1c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni1Cor3']+"'><input type='color' name='u1c3' hidden class='hiddenInput' /></div></td>";
				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/"+val['Uniforme2']+"' /> <input type='file' hidden id='uni2"+val['id']+"' class='hiddenInput custom-file-upload' name='uni2' accept='.jpg,.png,.jpeg'/></div></td>";
				tbl += "<td class='celula-uniforme'><div class='quadrado-uniforme' id='"+val['Uni2Cor1']+"'><input type='color' name='u2c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni2Cor2']+"'><input type='color' name='u2c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni2Cor3']+"'><input type='color' name='u2c3' hidden class='hiddenInput' /></div></td>";
				tbl += "<td class='wide'><span class='nomePais' id='est"+val['id']+"'>"+val['nomeEstadio']+" ("+val['capacidade']+")</span>";
					tbl += "<select class='comboEstadio editavel' id='selest"+val['estadioId']+"' hidden>  ";
						listaEstadios.forEach(function(value, key){
							tbl += "<option value='"+value[0]+"'>"+value[1]+" ("+value[2]+")</option>";
						});
                    tbl += "</select>";
                tbl += "</td>";
				let maximoTorcedores = (val['MaxTorcedores'] == 0? ">100000" : "<" + val['MaxTorcedores']);
				tbl += "<td><span class='maxTorcedores' id='max"+val['id']+"'>"+val['MaxTorcedores']+"</span><select class='editavel inputHerdeiro comboTorcedores' name='maxTorcida' id='"+val['MaxTorcedores']+"' hidden>" +
				"<option value='1000'>&lt;1000</option>" +
                "<option value='2000'>&lt;2000</option>" +
                "<option value='3000'>&lt;3000</option>" +
                "<option value='4000'>&lt;4000</option>" +
                "<option value='5000'>&lt;5000</option>" +
                "<option value='6000'>&lt;6000</option>" +
                "<option value='7000'>&lt;7000</option>" +
                "<option value='8000'>&lt;8000</option>" +
                "<option value='9000'>&lt;9000</option>" +
                "<option value='10000'>&lt;10000</option>" +
                "<option value='20000'>&lt;20000</option>" +
                "<option value='30000'>&lt;30000</option>" +
                "<option value='40000'>&lt;40000</option>" +
                "<option value='50000'>&lt;50000</option>" +
                "<option value='60000'>&lt;60000</option>" +
                "<option value='70000'>&lt;70000</option>" +
                "<option value='80000'>&lt;80000</option>" +
                "<option value='90000'>&lt;90000</option>" +
                "<option value='100000'>&lt;100000</option>" +
                "<option selected value='0'>&gt;100000</option>" +
				"</select></td>";
				tbl += "<td><span class='fidelidadeFixo'>"+val['Fidelidade']+"</span><input type='number' min='1' max='10' class=' fidelidade inputHerdeiro' value="+val['Fidelidade']+" id='fid"+val['id']+"' hidden></td>";
                if(val['liga'] != 0){
                    tbl += "<td class='wide'><img src='/images/ligas/"+val['logo']+"' class='bandeira nomePais' id='log"+val['id']+"'>  <span class='nomePais' id='lig"+val['id']+"'>"+val['nomeLiga']+"</span>";
                } else {
                    tbl += "<td>";
                }
                tbl += " <select class='comboLiga editavel ' id='sellig"+val['liga']+"' hidden>'  ";
					listaLigas.forEach(function(value, key){
						tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
					});

                tbl += "</select>";
				tbl += "</td>";
                if(val['idPais'] != 0){
                    tbl += "<td class='wide'><img src='/images/bandeiras/"+val['bandeiraPais']+"' class='bandeira nomePais' id='ban"+val['id']+"'>  <span class='nomePais' id='pai"+val['id']+"'>"+val['siglaPais']+"</span>";
                } else {
                    tbl += "<td>";
                }
                tbl += "<select class='comboPais editavel' id='"+val['idPais']+"' hidden>'  ";
					listaPaises.forEach(function(value, key){
						tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
					});

                tbl += "</select>";
                tbl += "</td>";

                let optionsString = "<td class='wide'>";

                if(logged == "true"){
                    if(admin == "true" || user_id === val['idDonoPais']){
                        optionsString += "<a id='edi"+val['id']+"' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        optionsString += "<a hidden id='sal"+val['id']+"' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        optionsString += "<a hidden id='can"+val['id']+"' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        optionsString += "<a id='pro"+val['id']+"' title='Promover 1 jogador da base' class='clickable promover'><i class='fas fa-hand-point-up inlineButton'></i></a>";
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
	
$('.quadrado-uniforme').each(function(i, obj) {
    var cores = $(this).attr('id');
    cores = cores.match(/.{1,3}/g);
    var fundo = "rgb(";
    fundo += cores[0];
    fundo += ",";
    fundo += cores[1];
    fundo += ",";
    fundo += cores[2];
    fundo += ")";
    $(this).css({ 'background-color' : fundo, });

});

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
    tbl_row.find('.fidelidade').show();
    tbl_row.find('.maxTorcida').show();
    tbl_row.find('.maxTorcedores').hide();

        //acertar questão cores
        tbl_row.find(".celula-uniforme :input").each(function(){
            var rgb = $(this).closest('.quadrado-uniforme').attr('id');
            var rgbp = rgb.match(/.{1,3}/g);
            var hex = rgbToHex(rgbp);
            $(this).val(hex);
        });

        //console.log(tbl_row.find(".celula-uniforme :input"));

    tbl_row.find('.thumb').addClass('editableThumb');

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);

    var paisId = tbl_row.find('.comboTorcedores').attr('id');
    tbl_row.find('.comboTorcedores').show().val(paisId);

    var ligaId = tbl_row.find('.comboLiga').attr('id').replace(/\D/g,'');;
    tbl_row.find('.comboLiga').show().val(ligaId);

        var estadioId = tbl_row.find('.comboEstadio').attr('id').replace(/\D/g,'');;
    tbl_row.find('.comboEstadio').show().val(estadioId);

});

    $('.cancelar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();
    tbl_row.find('.comboLiga').hide();
    tbl_row.find('.comboEstadio').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();
    tbl_row.find('.comboTorcedores').hide();
    tbl_row.find('.maxTorcedores').show();

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
    tbl_row.find('.comboEstadio').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();
    tbl_row.find('.comboTorcedores').hide();
    tbl_row.find('.maxTorcedores').show();


    tbl_row.find('.fidelidadeFixo').show();
    tbl_row.find('.fidelidade').hide();

    var id = tbl_row.attr('id');
    var nomeTime = tbl_row.find('#nom'+id).html();
    var maxTorcedores = tbl_row.find('.comboTorcedores').val();
    var fidelidade = tbl_row.find('#fid'+id).val();
    var estadio = tbl_row.find('.comboEstadio').val();
    var liga = tbl_row.find('.comboLiga').val();
    var pais = tbl_row.find('.comboPais').val();

    //cores1
    var uni1cor1hex = tbl_row.find('[name=u1c1]').val();
    var uni1cor2hex = tbl_row.find('[name=u1c2]').val();
    var uni1cor3hex = tbl_row.find('[name=u1c3]').val();

    var uni1cor1 = hexToRgb(uni1cor1hex);
    var uni1cor2 = hexToRgb(uni1cor2hex);
    var uni1cor3 = hexToRgb(uni1cor3hex);

    //cores2
    var uni2cor1hex = tbl_row.find('[name=u2c1]').val();
    var uni2cor2hex = tbl_row.find('[name=u2c2]').val();
    var uni2cor3hex = tbl_row.find('[name=u2c3]').val();

    var uni2cor1 = hexToRgb(uni2cor1hex);
    var uni2cor2 = hexToRgb(uni2cor2hex);
    var uni2cor3 = hexToRgb(uni2cor3hex);

    //escudo
    var inputEscudo = (tbl_row.find('#escudo'+id))[0];
    var escudo;

    if (inputEscudo.files.length > 0) {
       escudo = inputEscudo.files[0];
    } else {
       escudo = null;
    }

    //uniforme 1
    var inputUni1 = (tbl_row.find('#uni1'+id))[0];
    var uni1;

    if (inputUni1.files.length > 0) {
        uni1 = inputUni1.files[0];
    } else {
        uni1 = null;
    }

    //uniforme 2
    var inputUni2 = (tbl_row.find('#uni2'+id))[0];
    var uni2;

    if (inputUni2.files.length > 0) {
        uni2 = inputUni2.files[0];
    } else {
        uni2 = null;
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append('nomeTime', nomeTime);
    formData.append('maxTorcedores', maxTorcedores);
    formData.append('fidelidade', fidelidade);
    formData.append('pais', pais);
    formData.append('estadio', estadio);
    formData.append('liga', liga);
     if(escudo != null){
        formData.append('escudo', escudo);
     }
     if(uni1 != null){
        formData.append('uni1', uni1);
     }
     if(uni2 != null){
        formData.append('uni2', uni2);
     }
    formData.append('uni1cor1', uni1cor1);
    formData.append('uni1cor2', uni1cor2);
    formData.append('uni1cor3', uni1cor3);
    formData.append('uni2cor1', uni2cor1);
    formData.append('uni2cor2', uni2cor2);
    formData.append('uni2cor3', uni2cor3);


for (var key of formData.entries()) {
     console.log(key[0] + ', ' + key[1]);
 }

     $.ajax({
         url: 'alterar_time.php',
         processData: false,
        contentType: false,
        cache: false,
        type: "POST",
        dataType: 'json',
         data: formData,
              success: function(data) {
                  if(data.error != ''){
                    alert(data.error)
                  }
                  location.reload();
              },
              error: function(data) {
                  successmessage = 'Error';
                  alert("Erro, o procedimento não foi realizado, tente novamente.");
                  //location.reload();
              }
          });
});


$('.promover').click(function(){
    var clube = $(this).closest('tr').attr("id");
    var nacionalidade = $(this).closest('tr').find('.comboPais').attr("id");
    var sexo = $(this).closest('tr').attr("data-sexo");

    var formData = {
        'nacionalidade' : nacionalidade,
        'codigoPosicao' : 0,
        'inserir' : true,
        'clube' : clube,
        'base' : true,
        'sexo' : sexo
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/hexagen.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                $('#error_box').html('<div class="alert alert-success">O jogador '+data.player_info.nomeJogador+' foi promovido com sucesso!</div>');
            } else {
                $('#error_box').html('<div class="alert alert-danger">Não foi possível realizar a inserção, '+data.error+'</div>');
            }

}).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });

});

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

	
	var hexDigits = new Array
        ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");

function rgbToHex(rgb) {
    return "#" + hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
}

function hex(x) {
  return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
 }
 
 
function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ?
        parseInt(result[1], 16).toString().padStart(3,'0').concat(parseInt(result[2], 16).toString().padStart(3,'0'),parseInt(result[3], 16).toString().padStart(3,'0'))
     : null;
}

 
});

</script>

<div id="quadro-container">
<div align="center" id="quadroTimes">
<div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><i class='fas fa-search'></i></div>
<button id='importar_time' onclick="window.location='/times/criar_time.php';">Criar time</button>
<button id='importar_time' onclick="window.location='/times/importar_time.php';">Importar time</button>
<h2>Quadro de times - <?php echo $_SESSION['nomereal']?></h2>

<hr>
<div id='error_box'></div>

<?php

echo "<div style='clear:both;'></div>";
echo "<hr>";
echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

// echo "<div class='alert alert-info'>Não há times</div>";

echo('</div>');
echo('</div>');

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
