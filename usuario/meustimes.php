<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus times - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "meustimes_redesign";
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
var activeDirection = true;
var currentPage = 1;

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
    url:"search_team.php",
    method:"POST",
    cache:false,
    data:{searchText:searchText},
    success:function(data){
        $('#loading').hide();  // hide loading indicator
        localData = JSON.parse(data);
        updateTable(localData, currentPage, activeSort, activeDirection ? 1 : 0);
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
        treated_page = parseInt(current_page) || 1;
    }

    currentPage = treated_page;

    var from_result_num = (results_per_page * treated_page) - results_per_page;

    var pgn = pagination(treated_page,total_pages);

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='tabelaPrincipal' class='table'>";
        tbl += "<thead id='headings'>";
			tbl += "<tr>";
				tbl += "<th id='Nome' asc='' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Time</th>";
				tbl += "<th id='TresLetras' asc='' class='headings' width='4%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Sigla</th>";
				tbl += "<th asc='' class='headings' width='3%'>Escudo</th>";
				tbl += "<th asc='' class='headings' width='3%'>Mascote</th>";
				tbl += "<th asc='' class='headings' width='3%'>Uniforme 1</th>";
				tbl += "<th asc='' class='headings' width='4%'>Cores 1</th>";
				tbl += "<th asc='' class='headings' width='3%'>Uniforme 2</th>";
				tbl += "<th asc='' class='headings' width='4%'>Cores 2</th>";
				tbl += "<th id='nomeEstadio' asc='' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Estádio</th>";
				tbl += "<th id='MaxTorcedores' asc='' class='headings' width='7%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Max Torcida</th>";
				tbl += "<th id='Fidelidade' asc='' class='headings' width='4%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Fidelidade</th>";
				tbl += "<th id='nomeLiga' asc='' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Liga</th>";
				tbl += "<th id='siglaPais' asc='' class='headings' width='14%' class=''><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;País</th>";
				tbl += "<th asc='' class='headings' width='9%' class=''>Opções</th>";
			tbl += "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index >= (from_result_num) && index < (from_result_num + results_per_page)){
			
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
				tbl += "<td><span class='siglaEditavel' id='sig"+val['id']+"'>"+(val['TresLetras'] ? val['TresLetras'] : '')+"</span></td>";
				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/escudos/"+val['Escudo']+"' loading='lazy' decoding='async'/> <input type='file' hidden id='escudo"+val['id']+"' class='hiddenInput custom-file-upload' name='escudo' accept='.jpg,.png,.jpeg'/></div></td>";

				let mascoteThumb = val['mascote'] && val['mascote'] != 'null' && val['mascote'] != '0.png' && val['mascote'] != '0' ? '/images/mascotes/' + val['mascote'] : '/images/mascotes/placeholder.png';
				tbl += "<td><div class='imageUpload'><img class='thumb' src='"+mascoteThumb+"' loading='lazy' decoding='async'/> <input type='file' hidden id='mascote"+val['id']+"' class='hiddenInput custom-file-upload' name='mascote' accept='.jpg,.png,.jpeg,.webp'/></div></td>";

				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/"+val['Uniforme1']+"' loading='lazy' decoding='async'/> <input type='file' hidden id='uni1"+val['id']+"' class='hiddenInput custom-file-upload' name='uni1' accept='.jpg,.png,.jpeg'/></div></td>";
				tbl += "<td class='celula-uniforme'><div class='quadrado-uniforme' id='"+val['Uni1Cor1']+"'><input type='color' name='u1c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni1Cor2']+"'><input type='color' name='u1c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='"+val['Uni1Cor3']+"'><input type='color' name='u1c3' hidden class='hiddenInput' /></div></td>";
				tbl += "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/"+val['Uniforme2']+"' loading='lazy' decoding='async'/> <input type='file' hidden id='uni2"+val['id']+"' class='hiddenInput custom-file-upload' name='uni2' accept='.jpg,.png,.jpeg'/></div></td>";
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
                    tbl += "<td class='wide'><img src='/images/ligas/"+val['logo']+"' class='bandeira nomePais' id='log"+val['id']+"' loading='lazy' decoding='async'>  <span class='nomePais' id='lig"+val['id']+"'>"+val['nomeLiga']+"</span>";
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
                    tbl += "<td class='wide'><img src='/images/bandeiras/"+val['bandeiraPais']+"' class='bandeira nomePais' id='ban"+val['id']+"' loading='lazy' decoding='async'>  <span class='nomePais' id='pai"+val['id']+"'>"+val['siglaPais']+"</span>";
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
                    if(admin == "true" || user_id == val['idDonoPais']){
                        optionsString += "<a id='edi"+val['id']+"' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        optionsString += "<a hidden id='sal"+val['id']+"' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        optionsString += "<a hidden id='can"+val['id']+"' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                        optionsString += "<a id='pro"+val['id']+"' title='Promover 1 jogador da base' class='clickable promover'><span class='material-symbols-outlined inlineButton'>pan_tool_alt</span></a>";
                        optionsString += "<a id='com"+val['id']+"' title='Completar elenco com a base' class='clickable completar-base'><span class='material-symbols-outlined inlineButton'>diversity_3</span></a>";
                        optionsString += "<a id='niv"+val['id']+"' title='Ajustar níveis do elenco' class='clickable ajustar-niveis'><span class='material-symbols-outlined inlineButton'>trending_up</span></a>";
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
    if (cores) {
        cores = cores.match(/.{1,3}/g);
        if (cores && cores.length >= 3) {
            var fundo = "rgb(" + cores[0] + "," + cores[1] + "," + cores[2] + ")";
            $(this).css({ 'background-color' : fundo });
        }
    }
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
    updateBatchFloatingBar();
}

// Eventos delegados
$(document).on('click', '.editar', function(){
    var tbl_row = $(this).closest('tr');
    tbl_row.addClass('in-edition');
    tbl_row.find('span').each(function(index, val){
        $(this).attr('original_entry', $(this).html());
    });
    tbl_row.find('.linkNome').css("cursor","text");
    tbl_row.find('.linkNome').css("pointer-events","none");
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.siglaEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.salvar').show();
    tbl_row.find('.cancelar').show();
    tbl_row.find('.editar').hide();
    tbl_row.find('.deletar').hide();
    tbl_row.find('.promover').hide();
    tbl_row.find('.completar-base').hide();
    tbl_row.find('.ajustar-niveis').hide();
    tbl_row.find('.nomePais').hide();
    tbl_row.find('.hiddenInput').show();
    tbl_row.find('.fidelidadeFixo').hide();
    tbl_row.find('.fidelidade').show();
    tbl_row.find('.maxTorcida').show();
    tbl_row.find('.maxTorcedores').hide();

    // acertar questão cores
    tbl_row.find(".celula-uniforme :input").each(function(){
        var rgb = $(this).closest('.quadrado-uniforme').attr('id');
        if (rgb) {
            var rgbp = rgb.match(/.{1,3}/g);
            if (rgbp && rgbp.length >= 3) {
                var hex = rgbToHex(rgbp);
                $(this).val(hex);
            }
        }
    });

    tbl_row.find('.thumb').addClass('editableThumb');

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);

    var torcidaVal = tbl_row.find('.comboTorcedores').attr('id');
    tbl_row.find('.comboTorcedores').show().val(torcidaVal);

    var ligaId = tbl_row.find('.comboLiga').attr('id').replace(/\D/g,'');
    tbl_row.find('.comboLiga').show().val(ligaId);

    var estadioId = tbl_row.find('.comboEstadio').attr('id').replace(/\D/g,'');
    tbl_row.find('.comboEstadio').show().val(estadioId);

    updateBatchFloatingBar();
});

$(document).on('click', '.cancelar', function(){
    var tbl_row = $(this).closest('tr');
    tbl_row.removeClass('in-edition');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.siglaEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();
    tbl_row.find('.comboLiga').hide();
    tbl_row.find('.comboEstadio').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.promover').show();
    tbl_row.find('.completar-base').show();
    tbl_row.find('.ajustar-niveis').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();
    tbl_row.find('.comboTorcedores').hide();
    tbl_row.find('.maxTorcedores').show();

    tbl_row.find('.fidelidadeFixo').show();
    tbl_row.find('.fidelidade').hide();

    tbl_row.find('span').each(function(index, val){
        $(this).html($(this).attr('original_entry'));
    });
    updateBatchFloatingBar();
});

$(document).on('keydown', '.fidelidade', function () {
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
        $(this).data("old", $(this).val());
});

$(document).on('keyup', '.fidelidade', function () {
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1));
    else
        $(this).val($(this).data("old"));
});

$(document).on('keypress', '.siglaEditavel.editavel', function(e){
    if ($(this).text().length >= 3 && window.getSelection().toString().length === 0) {
        e.preventDefault();
    }
});

$(document).on('paste', '.siglaEditavel.editavel', function(e){
    e.preventDefault();
    var text = (e.originalEvent || e).clipboardData.getData('text/plain').toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 3);
    document.execCommand('insertText', false, text);
});

function getRowFormData(tbl_row) {
    var id = tbl_row.attr('id');
    var nomeTime = tbl_row.find('#nom'+id).text().trim();
    var sigla = tbl_row.find('#sig'+id).text().trim().toUpperCase().substring(0, 3);
    var maxTorcedores = tbl_row.find('.comboTorcedores').val();
    var fidelidade = tbl_row.find('#fid'+id).val();
    var estadio = tbl_row.find('.comboEstadio').val();
    var liga = tbl_row.find('.comboLiga').val();
    var pais = tbl_row.find('.comboPais').val();

    // cores1
    var uni1cor1hex = tbl_row.find('[name=u1c1]').val();
    var uni1cor2hex = tbl_row.find('[name=u1c2]').val();
    var uni1cor3hex = tbl_row.find('[name=u1c3]').val();

    var uni1cor1 = hexToRgb(uni1cor1hex);
    var uni1cor2 = hexToRgb(uni1cor2hex);
    var uni1cor3 = hexToRgb(uni1cor3hex);

    // cores2
    var uni2cor1hex = tbl_row.find('[name=u2c1]').val();
    var uni2cor2hex = tbl_row.find('[name=u2c2]').val();
    var uni2cor3hex = tbl_row.find('[name=u2c3]').val();

    var uni2cor1 = hexToRgb(uni2cor1hex);
    var uni2cor2 = hexToRgb(uni2cor2hex);
    var uni2cor3 = hexToRgb(uni2cor3hex);

    // escudo
    var inputEscudo = (tbl_row.find('#escudo'+id))[0];
    var escudo = (inputEscudo && inputEscudo.files.length > 0) ? inputEscudo.files[0] : null;

    // mascote
    var inputMascote = (tbl_row.find('#mascote'+id))[0];
    var mascote = (inputMascote && inputMascote.files.length > 0) ? inputMascote.files[0] : null;

    // uniforme 1
    var inputUni1 = (tbl_row.find('#uni1'+id))[0];
    var uni1 = (inputUni1 && inputUni1.files.length > 0) ? inputUni1.files[0] : null;

    // uniforme 2
    var inputUni2 = (tbl_row.find('#uni2'+id))[0];
    var uni2 = (inputUni2 && inputUni2.files.length > 0) ? inputUni2.files[0] : null;

    var formData = new FormData();
    formData.append('id', id);
    formData.append('nomeTime', nomeTime);
    formData.append('sigla', sigla);
    formData.append('maxTorcedores', maxTorcedores);
    formData.append('fidelidade', fidelidade);
    formData.append('pais', pais);
    formData.append('estadio', estadio);
    formData.append('liga', liga);
    if(escudo != null){
        formData.append('escudo', escudo);
    }
    if(mascote != null){
        formData.append('mascote', mascote);
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

    return formData;
}

function saveRowAjax(tbl_row) {
    return new Promise(function(resolve, reject){
        var formData = getRowFormData(tbl_row);
        $.ajax({
            url: 'alterar_time.php',
            processData: false,
            contentType: false,
            cache: false,
            type: "POST",
            dataType: 'json',
            data: formData,
            success: function(data) {
                if(data && data.error && data.error !== ''){
                    reject(data.error);
                } else {
                    tbl_row.removeClass('in-edition');
                    resolve(data);
                }
            },
            error: function() {
                reject("Erro ao conectar com o servidor.");
            }
        });
    });
}
     
$(document).on('click', '.salvar', function(){
    var tbl_row = $(this).closest('tr');
    saveRowAjax(tbl_row).then(function(){
        load_data();
    }).catch(function(err){
        alert("Erro: " + err);
        load_data();
    });
});

function updateBatchFloatingBar() {
    var editingRows = $('tr.in-edition');
    var count = editingRows.length;
    var bar = $('#floating-batch-bar');

    if (count > 1) {
        if (bar.length === 0) {
            var barHtml = '<div id="floating-batch-bar" class="floating-batch-actions">' +
                '<div class="floating-batch-info">' +
                '<span class="material-symbols-outlined">edit_note</span>' +
                '<span id="batch-count-badge" class="floating-batch-badge">' + count + '</span>' +
                '<span>linhas em edição</span>' +
                '</div>' +
                '<button type="button" id="btn-batch-save-all" class="btn-batch-save">' +
                '<span class="material-symbols-outlined">done_all</span> Aceitar todos' +
                '</button>' +
                '<button type="button" id="btn-batch-cancel-all" class="btn-batch-cancel">' +
                '<span class="material-symbols-outlined">close</span> Cancelar' +
                '</button>' +
                '</div>';
            $('body').append(barHtml);

            $('#btn-batch-save-all').off('click').on('click', function(){
                var rowsToSave = $('tr.in-edition');
                if (rowsToSave.length === 0) return;
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="material-symbols-outlined">hourglass_top</span> Salvando...');

                var promises = [];
                rowsToSave.each(function(){
                    promises.push(saveRowAjax($(this)));
                });

                Promise.allSettled(promises).then(function(results){
                    var errors = results.filter(function(r){ return r.status === 'rejected'; });
                    if(errors.length > 0) {
                        alert("Houve erro ao salvar " + errors.length + " registro(s).");
                    }
                    load_data();
                });
            });

            $('#btn-batch-cancel-all').off('click').on('click', function(){
                $('tr.in-edition').find('.cancelar').trigger('click');
            });
        } else {
            $('#batch-count-badge').text(count);
            bar.fadeIn(200);
        }
    } else {
        if (bar.length > 0) {
            bar.fadeOut(200);
        }
    }
}

$(document).on('click', '.promover', function(){
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
    };

    $.ajax({
        type        : 'POST',
        url         : '/jogadores/hexagen.php',
        data        : formData,
        dataType    : 'json',
        encode      : true
    }).done(function(data) {
        if (data.success) {
            $('#error_box').html('<div class="alert alert-success">O jogador '+data.player_info.nomeJogador+' foi promovido com sucesso!</div>');
        } else {
            $('#error_box').html('<div class="alert alert-danger">Não foi possível realizar a inserção, '+data.error+'</div>');
        }
    });

// Completar elenco com a base
var clubeCompletarAtual = 0;
$(document).on('click', '.completar-base', function(){
    var tr = $(this).closest('tr');
    var clubeId = tr.attr("id");
    var nomeClube = tr.find('.linkNome').text().trim();
    clubeCompletarAtual = clubeId;

    $('#modalCompletarBaseConteudo').html('<div style="text-align:center; padding:20px; color:#64748b;"><span class="material-symbols-outlined" style="font-size:32px; animation:spin 1s linear infinite;">sync</span><p style="margin-top:8px;">Analisando carências do elenco de <strong>'+nomeClube+'</strong>...</p></div>');
    $('#btnConfirmarCompletarBase').hide();
    $('#modalCompletarBase').css('display', 'flex').hide().fadeIn(200);

    $.ajax({
        type: 'POST',
        url: '/times/completar_base.php',
        data: { clube: clubeId, acao: 'diagnostico' },
        dataType: 'json'
    }).done(function(data){
        if(data.success){
            if(data.jaCompleto){
                var htmlAviso = '<div style="background:rgba(2,132,199,0.08); border:1px solid rgba(2,132,199,0.2); border-radius:10px; padding:16px; color:#0f172a;">' +
                    '<div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-weight:700; color:#0284c7;">' +
                    '<span class="material-symbols-outlined">check_circle</span> Elenco Completo' +
                    '</div>' +
                    '<p style="margin:0; font-size:0.9rem; color:#475569;">O elenco do <strong>' + data.clubeNome + '</strong> já possui ' + data.totalAtual + ' jogadores e todas as posições essenciais estão cobertas pelo esquema.</p>' +
                    '</div>';
                $('#modalCompletarBaseConteudo').html(htmlAviso);
                $('#btnConfirmarCompletarBase').hide();
            } else {
                var htmlDiagnostico = '<p style="margin:0 0 12px 0; font-size:0.95rem; color:#334155; line-height:1.5;">' +
                    'Foram identificadas <strong>' + data.carenciasTotal + ' carência(s)</strong> no elenco do <strong>' + data.clubeNome + '</strong> (total atual: ' + data.totalAtual + ' atletas):' +
                    '</p>' +
                    '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-bottom:14px; max-height:160px; overflow-y:auto;">' +
                    '<div style="font-weight:600; font-size:0.85rem; color:#0284c7; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Posições em falta:</div>' +
                    '<div style="font-size:0.9rem; color:#0f172a; line-height:1.6;">' + data.resumoFormatado + '</div>' +
                    '</div>' +
                    '<p style="margin:0; font-size:0.85rem; color:#64748b;">' +
                    'Deseja gerar <strong>' + data.carenciasTotal + ' jogador(es)</strong> jovens da base (17 a 20 anos) para suprir essas carências? Eles entrarão direto como suplentes no clube.' +
                    '</p>';
                $('#modalCompletarBaseConteudo').html(htmlDiagnostico);
                $('#btnConfirmarCompletarBase').show();
            }
        } else {
            $('#modalCompletarBaseConteudo').html('<div class="alert alert-danger" style="margin:0;">' + (data.error || 'Erro ao consultar elenco.') + '</div>');
            $('#btnConfirmarCompletarBase').hide();
        }
    }).fail(function(){
        $('#modalCompletarBaseConteudo').html('<div class="alert alert-danger" style="margin:0;">Erro de conexão com o servidor.</div>');
        $('#btnConfirmarCompletarBase').hide();
    });
});

$(document).on('click', '#btnFecharModalCompletarBase, #btnCancelarCompletarBase', function(){
    $('#modalCompletarBase').fadeOut(200);
});

$(document).on('click', '#modalCompletarBase', function(e){
    if ($(e.target).is('#modalCompletarBase')) {
        $('#modalCompletarBase').fadeOut(200);
    }
});

$(document).on('click', '#btnConfirmarCompletarBase', function(){
    if(!clubeCompletarAtual) return;
    var btn = $(this);
    btn.prop('disabled', true).html('<span class="material-symbols-outlined" style="font-size:18px; animation:spin 1s linear infinite;">sync</span> Gerando...');

    $.ajax({
        type: 'POST',
        url: '/times/completar_base.php',
        data: { clube: clubeCompletarAtual, acao: 'executar' },
        dataType: 'json'
    }).done(function(data){
        $('#modalCompletarBase').fadeOut(200);
        btn.prop('disabled', false).html('<span class="material-symbols-outlined" style="font-size:18px;">diversity_3</span> Confirmar e Gerar');
        if(data.success){
            $('#error_box').html('<div class="alert alert-success">' + data.mensagem + '</div>');
            load_data();
        } else {
            $('#error_box').html('<div class="alert alert-danger">' + (data.error || 'Erro ao gerar jogadores.') + '</div>');
        }
    }).fail(function(){
        $('#modalCompletarBase').fadeOut(200);
        btn.prop('disabled', false).html('<span class="material-symbols-outlined" style="font-size:18px;">diversity_3</span> Confirmar e Gerar');
        $('#error_box').html('<div class="alert alert-danger">Erro de comunicação com o servidor ao gerar jogadores.</div>');
    });
});
});

// Ajuste de níveis do elenco (1 clube por vez)
var ajusteClubeAtual = 0;
var ajusteClubeNome = '';
var ajusteModoAtual = 'delta';
var ajustePreviewTimer = null;
var ajustePreviewSeq = 0;
var ajusteTotalAlterados = 0;

function escapeHtmlNiveis(str) {
    return String(str === null || str === undefined ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function atualizarBotaoConfirmarNiveis(loading) {
    var $btn = $('#btnConfirmarAjustarNiveis');
    if (loading) {
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined" style="font-size:18px; animation:spin 1s linear infinite;">sync</span> Aplicando...');
        return;
    }
    $btn.html('<span class="material-symbols-outlined" style="font-size:18px;">trending_up</span> Aplicar Alterações');
    $btn.prop('disabled', ajusteTotalAlterados <= 0);
}

function renderPreviaAjusteNiveis(data) {
    ajusteTotalAlterados = parseInt(data.totalAlterados, 10) || 0;
    var mediaAtual = Number(data.mediaAtual || 0);
    var mediaPrevista = Number(data.mediaPrevista || 0);
    var diffMedia = Number((mediaPrevista - mediaAtual).toFixed(1));

    $('#ajusteMediaAtualInline').text(mediaAtual.toFixed(1));
    var $mediaInput = $('#ajusteMediaAlvoInput');
    if ($mediaInput.val() === '') {
        $mediaInput.attr('placeholder', 'Ex: ' + Math.max(1, Math.min(99, Math.round(mediaAtual || 70))));
    }

    var diffBadge = '';
    if (diffMedia > 0) {
        diffBadge = '<span class="ajuste-badge ajuste-badge-pos">+' + diffMedia.toFixed(1) + '</span>';
    } else if (diffMedia < 0) {
        diffBadge = '<span class="ajuste-badge ajuste-badge-neg">' + diffMedia.toFixed(1) + '</span>';
    }

    var detalhesJogadores = 'Nativos: ' + (parseInt(data.totalNativos, 10) || 0) + ' | Modificador: ' + (parseInt(data.totalEstrangeiros, 10) || 0);
    if ((parseInt(data.totalIgnoradosEstrangeiros, 10) || 0) > 0) {
        detalhesJogadores += ' | Ignorados: ' + (parseInt(data.totalIgnoradosEstrangeiros, 10) || 0);
    }
    if ((parseInt(data.totalFalecidosIgnorados, 10) || 0) > 0) {
        detalhesJogadores += ' | Falecidos: ' + (parseInt(data.totalFalecidosIgnorados, 10) || 0);
    }

    var html = '<div class="ajuste-summary-grid">';
    html += '<div class="ajuste-summary-card">' +
        '<div class="ajuste-summary-label">Jogadores afetados</div>' +
        '<div class="ajuste-summary-value">' + ajusteTotalAlterados + ' / ' + (parseInt(data.totalJogadores, 10) || 0) + '</div>' +
        '<div class="ajuste-summary-sub">' + detalhesJogadores + '</div>' +
        '</div>';
    html += '<div class="ajuste-summary-card">' +
        '<div class="ajuste-summary-label">Média do elenco</div>' +
        '<div class="ajuste-summary-value">' + mediaAtual.toFixed(1) + ' &rarr; ' + mediaPrevista.toFixed(1) + ' ' + diffBadge + '</div>' +
        '<div class="ajuste-summary-sub">Elegíveis: ' + Number(data.mediaElegiveisAtual || 0).toFixed(1) + ' &rarr; ' + Number(data.mediaElegiveisPrevista || 0).toFixed(1) + '</div>' +
        '</div>';
    html += '</div>';

    if ((parseInt(data.totalAtingiramLimite, 10) || 0) > 0) {
        html += '<div class="ajuste-banner-info">' +
            '<span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0;">info</span>' +
            '<span>' + (parseInt(data.totalAtingiramLimite, 10) || 0) + ' jogador(es) atingiram o limite mínimo (1) ou máximo (99).</span>' +
            '</div>';
    }

    var lista = Array.isArray(data.jogadores) ? data.jogadores : [];
    html += '<div class="ajuste-preview-table-wrap"><table class="ajuste-preview-table">';
    html += '<thead><tr><th>Jogador</th><th>Atual &rarr; Novo</th><th>Tipo</th></tr></thead><tbody>';

    if (lista.length === 0) {
        html += '<tr><td colspan="3" style="text-align:center; color:#64748b; padding:16px;">Nenhum jogador ativo encontrado neste clube.</td></tr>';
    } else {
        for (var i = 0; i < lista.length; i++) {
            var jog = lista[i];
            var deltaVal = parseInt(jog.delta, 10) || 0;
            var deltaTxt = '';
            var deltaCls = 'ajuste-delta-zero';
            if (deltaVal > 0) {
                deltaTxt = ' (+' + deltaVal + ')';
                deltaCls = 'ajuste-delta-pos';
            } else if (deltaVal < 0) {
                deltaTxt = ' (' + deltaVal + ')';
                deltaCls = 'ajuste-delta-neg';
            }
            var limiteTag = jog.atingiuLimite ? ' <span title="Atingiu limite 1 ou 99" style="color:#0284c7; font-size:0.74rem; font-weight:700;">(lim.)</span>' : '';

            var tipoBadge = '';
            if (jog.tipoAjuste === 'modificador') {
                tipoBadge = '<span class="ajuste-badge ajuste-badge-mod" title="Alteração via ModificadorNivel (' + (parseInt(jog.modAtual, 10) || 0) + ' -> ' + (parseInt(jog.novoMod, 10) || 0) + ')">Modificador</span>';
            } else if (jog.tipoAjuste === 'ignorado') {
                tipoBadge = '<span class="ajuste-badge ajuste-badge-ignorado">Ignorado</span>';
            } else {
                tipoBadge = '<span class="ajuste-badge ajuste-badge-nivel">Nível</span>';
            }

            html += '<tr>' +
                '<td>' + escapeHtmlNiveis(jog.nome) + '</td>' +
                '<td><span>' + (parseInt(jog.nivelAtual, 10) || 0) + '</span> &rarr; <span class="' + deltaCls + '">' + (parseInt(jog.nivelNovo, 10) || 0) + deltaTxt + '</span>' + limiteTag + '</td>' +
                '<td>' + tipoBadge + '</td>' +
                '</tr>';
        }
    }

    html += '</tbody></table></div>';
    $('#ajustePreviaContainer').html(html);
    atualizarBotaoConfirmarNiveis(false);
}

function solicitarPreviaAjusteNiveis(imediato) {
    if (!ajusteClubeAtual) return;
    clearTimeout(ajustePreviewTimer);

    var executarBusca = function() {
        var mySeq = ++ajustePreviewSeq;
        ajusteTotalAlterados = 0;
        atualizarBotaoConfirmarNiveis(false);
        $('#ajusteModalError').hide().empty();
        $('#ajustePreviaContainer').html('<div class="ajuste-loading-box"><span class="material-symbols-outlined" style="font-size:20px; animation:spin 1s linear infinite;">sync</span> Calculando prévia do elenco...</div>');

        $.ajax({
            type: 'POST',
            url: '/times/ajustar_niveis.php',
            data: {
                acao: 'diagnostico',
                clube: ajusteClubeAtual,
                modo: ajusteModoAtual,
                delta: $('#ajusteDeltaInput').val(),
                media_alvo: $('#ajusteMediaAlvoInput').val(),
                incluir_estrangeiros: $('#chkIncluirEstrangeiros').is(':checked') ? '1' : '0',
                recalcular_passe: $('#chkRecalcularPasse').is(':checked') ? '1' : '0'
            },
            dataType: 'json'
        }).done(function(data) {
            if (mySeq !== ajustePreviewSeq) return;
            if (data && data.success) {
                renderPreviaAjusteNiveis(data);
            } else {
                ajusteTotalAlterados = 0;
                atualizarBotaoConfirmarNiveis(false);
                $('#ajustePreviaContainer').html('<div class="ajuste-banner-error"><span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0;">error</span><span>' + escapeHtmlNiveis((data && data.error) ? data.error : 'Não foi possível calcular a prévia.') + '</span></div>');
            }
        }).fail(function() {
            if (mySeq !== ajustePreviewSeq) return;
            ajusteTotalAlterados = 0;
            atualizarBotaoConfirmarNiveis(false);
            $('#ajustePreviaContainer').html('<div class="ajuste-banner-error"><span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0;">error</span><span>Erro de conexão ao calcular prévia.</span></div>');
        });
    };

    if (imediato) {
        executarBusca();
    } else {
        ajustePreviewTimer = setTimeout(executarBusca, 220);
    }
}

$(document).on('click', '.ajustar-niveis', function() {
    var tr = $(this).closest('tr');
    ajusteClubeAtual = parseInt(tr.attr('id'), 10) || 0;
    ajusteClubeNome = tr.find('.linkNome').text().trim();
    if (!ajusteClubeAtual) return;

    ajusteModoAtual = 'delta';
    ajusteTotalAlterados = 0;

    $('#modalAjustarNiveisTitulo').text('Ajustar Níveis — ' + ajusteClubeNome);
    $('.ajuste-mode-btn').removeClass('active');
    $('.ajuste-mode-btn[data-mode="delta"]').addClass('active');
    $('#painelModoDelta').show();
    $('#painelModoMedia').hide();

    $('#ajusteDeltaInput').val('');
    $('.ajuste-preset-btn').removeClass('active');
    $('#ajusteMediaAlvoInput').val('').attr('placeholder', 'Ex: 70');
    $('#ajusteMediaAtualInline').text('—');
    $('#chkIncluirEstrangeiros').prop('checked', true);
    $('#chkRecalcularPasse').prop('checked', false);
    $('#ajusteModalError').hide().empty();
    atualizarBotaoConfirmarNiveis(false);

    $('#modalAjustarNiveis').css('display', 'flex').hide().fadeIn(200);
    solicitarPreviaAjusteNiveis(true);
});

$(document).on('click', '.ajuste-mode-btn', function() {
    var novoModo = $(this).attr('data-mode');
    if (!novoModo || novoModo === ajusteModoAtual) return;
    ajusteModoAtual = novoModo;
    $('.ajuste-mode-btn').removeClass('active');
    $(this).addClass('active');
    if (ajusteModoAtual === 'delta') {
        $('#painelModoDelta').show();
        $('#painelModoMedia').hide();
    } else {
        $('#painelModoDelta').hide();
        $('#painelModoMedia').show();
    }
    solicitarPreviaAjusteNiveis(true);
});

$(document).on('click', '.ajuste-preset-btn', function() {
    var val = $(this).attr('data-delta');
    $('.ajuste-preset-btn').removeClass('active');
    $(this).addClass('active');
    $('#ajusteDeltaInput').val(val);
    solicitarPreviaAjusteNiveis(true);
});

$(document).on('input change', '#ajusteDeltaInput', function() {
    var raw = String($(this).val()).trim();
    var numVal = parseInt(raw, 10);
    $('.ajuste-preset-btn').removeClass('active');
    if (raw !== '' && !isNaN(numVal)) {
        $('.ajuste-preset-btn[data-delta="' + numVal + '"]').addClass('active');
    }
    solicitarPreviaAjusteNiveis(false);
});

$(document).on('input change', '#ajusteMediaAlvoInput', function() {
    solicitarPreviaAjusteNiveis(false);
});

$(document).on('change', '#chkIncluirEstrangeiros', function() {
    solicitarPreviaAjusteNiveis(true);
});

$(document).on('click', '#btnFecharModalAjustarNiveis, #btnCancelarAjustarNiveis', function() {
    clearTimeout(ajustePreviewTimer);
    $('#modalAjustarNiveis').fadeOut(200);
});

$(document).on('click', '#modalAjustarNiveis', function(e) {
    if ($(e.target).is('#modalAjustarNiveis')) {
        clearTimeout(ajustePreviewTimer);
        $('#modalAjustarNiveis').fadeOut(200);
    }
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#modalAjustarNiveis').is(':visible')) {
        clearTimeout(ajustePreviewTimer);
        $('#modalAjustarNiveis').fadeOut(200);
    }
});

$(document).on('click', '#btnConfirmarAjustarNiveis', function() {
    if (!ajusteClubeAtual || ajusteTotalAlterados <= 0) return;
    atualizarBotaoConfirmarNiveis(true);
    $('#ajusteModalError').hide().empty();

    $.ajax({
        type: 'POST',
        url: '/times/ajustar_niveis.php',
        data: {
            acao: 'executar',
            clube: ajusteClubeAtual,
            modo: ajusteModoAtual,
            delta: $('#ajusteDeltaInput').val(),
            media_alvo: $('#ajusteMediaAlvoInput').val(),
            incluir_estrangeiros: $('#chkIncluirEstrangeiros').is(':checked') ? '1' : '0',
            recalcular_passe: $('#chkRecalcularPasse').is(':checked') ? '1' : '0'
        },
        dataType: 'json'
    }).done(function(data) {
        atualizarBotaoConfirmarNiveis(false);
        if (data && data.success) {
            $('#modalAjustarNiveis').fadeOut(200);
            $('#error_box').html('<div class="alert alert-success">' + data.mensagem + '</div>');
            var boxEl = $('#error_box')[0];
            if (boxEl && boxEl.scrollIntoView) {
                var rect = boxEl.getBoundingClientRect();
                if (rect.top < 0 || rect.bottom > window.innerHeight) {
                    boxEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
            load_data();
        } else {
            $('#ajusteModalError').html('<span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0;">error</span><span>' + escapeHtmlNiveis((data && data.error) ? data.error : 'Erro ao atualizar os níveis.') + '</span>').show();
        }
    }).fail(function() {
        atualizarBotaoConfirmarNiveis(false);
        $('#ajusteModalError').html('<span class="material-symbols-outlined" style="font-size:18px; flex-shrink:0;">error</span><span>Erro de comunicação com o servidor ao salvar os níveis.</span>').show();
    });
});

$(document).on('click', '.pagination_link', function(){
    var page = $(this).attr('id');
    updateTable(localData, page, activeSort, 1);
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

<div class="propostas-container">
<div class="propostas-card">

<div class="header-actions-container">
    <h2 class="propostas-title">Quadro de times - <?php echo $_SESSION['nomereal']?></h2>
    <div class="header-buttons-wrapper">
        <div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><span class='material-symbols-outlined'>search</span></div>
        <button class='btn-action-primary' onclick="window.location='/times/criar_time.php';">Criar time</button>
        <button class='btn-action-primary' onclick="window.location='/times/importar_time.php';">Importar time</button>
    </div>
</div>

<div id='error_box'></div>

<?php

echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

echo('</div>');
echo('</div>');

} else {
    echo "Usuário, por favor refaça o login.";
}



?>
<!-- Modal Completar com a Base -->
<div id="modalCompletarBase" style="display:none; position:fixed; inset:0; background:rgba(9, 13, 22, 0.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:9999; justify-content:center; align-items:center; padding:1.5rem;">
    <div style="background:#ffffff; border-radius:16px; border:1px solid rgba(0,0,0,0.08); box-shadow:0 20px 40px rgba(0,0,0,0.25); max-width:520px; width:100%; overflow:hidden; animation:fadeInModal 0.2s ease;">
        <div style="padding:18px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="color:#0284c7; font-size:24px;">diversity_3</span>
                <h3 style="margin:0; font-family:'Outfit', sans-serif; font-size:1.15rem; font-weight:700; color:#0f172a;">Completar Elenco com a Base</h3>
            </div>
            <span id="btnFecharModalCompletarBase" style="cursor:pointer; color:#64748b; font-size:1.4rem; line-height:1;" class="material-symbols-outlined">close</span>
        </div>
        <div id="modalCompletarBaseConteudo" style="padding:22px 24px; max-height:65vh; overflow-y:auto;">
        </div>
        <div style="padding:14px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; background:#f8fafc;">
            <button type="button" id="btnCancelarCompletarBase" style="padding:8px 18px; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; font-weight:600; font-size:0.85rem; color:#475569; cursor:pointer;">Cancelar</button>
            <button type="button" id="btnConfirmarCompletarBase" style="padding:8px 20px; background:#0284c7; border:none; border-radius:8px; font-weight:600; font-size:0.85rem; color:#ffffff; cursor:pointer; display:none; align-items:center; gap:6px;">
                <span class="material-symbols-outlined" style="font-size:18px;">diversity_3</span> Confirmar e Gerar
            </button>
        </div>
    </div>
</div>

<!-- Modal Ajustar Níveis do Elenco -->
<div id="modalAjustarNiveis" class="ajuste-niveis-overlay" role="dialog" aria-modal="true" aria-labelledby="modalAjustarNiveisTitulo">
    <div class="ajuste-niveis-dialog">
        <div class="ajuste-niveis-header">
            <div class="ajuste-niveis-title-wrap">
                <span class="material-symbols-outlined ajuste-niveis-title-icon">trending_up</span>
                <div>
                    <h3 id="modalAjustarNiveisTitulo" class="ajuste-niveis-title">Ajustar Níveis do Elenco</h3>
                    <p id="modalAjustarNiveisSubtitulo" class="ajuste-niveis-subtitle">Atualize os níveis de todo o elenco de uma só vez</p>
                </div>
            </div>
            <span id="btnFecharModalAjustarNiveis" class="material-symbols-outlined ajuste-niveis-close" title="Fechar">close</span>
        </div>

        <div class="ajuste-niveis-body">
            <div>
                <span class="ajuste-section-label">Modo de ajuste</span>
                <div class="ajuste-mode-tabs">
                    <button type="button" class="ajuste-mode-btn active" data-mode="delta">
                        <span class="material-symbols-outlined">exposure</span>
                        <span>Subir / Baixar (&plusmn; X)</span>
                    </button>
                    <button type="button" class="ajuste-mode-btn" data-mode="media_alvo">
                        <span class="material-symbols-outlined">equalizer</span>
                        <span>Definir média alvo</span>
                    </button>
                </div>
            </div>

            <div id="painelModoDelta" class="ajuste-mode-panel">
                <div class="ajuste-presets-row">
                    <button type="button" class="ajuste-preset-btn neg" data-delta="-10">-10</button>
                    <button type="button" class="ajuste-preset-btn neg" data-delta="-5">-5</button>
                    <button type="button" class="ajuste-preset-btn neg" data-delta="-3">-3</button>
                    <button type="button" class="ajuste-preset-btn neg" data-delta="-1">-1</button>
                    <button type="button" class="ajuste-preset-btn pos" data-delta="1">+1</button>
                    <button type="button" class="ajuste-preset-btn pos" data-delta="3">+3</button>
                    <button type="button" class="ajuste-preset-btn pos" data-delta="5">+5</button>
                    <button type="button" class="ajuste-preset-btn pos" data-delta="10">+10</button>
                </div>
                <div class="ajuste-input-row">
                    <input type="number" id="ajusteDeltaInput" min="-98" max="98" step="1" placeholder="Ex: +10 ou -5">
                    <span class="ajuste-input-help">Valores positivos sobem e negativos baixam o nível (limite 1 a 99)</span>
                </div>
            </div>

            <div id="painelModoMedia" class="ajuste-mode-panel" style="display:none;">
                <div class="ajuste-input-row">
                    <span class="ajuste-media-atual-badge">Média atual: <strong id="ajusteMediaAtualInline">&mdash;</strong></span>
                    <input type="number" id="ajusteMediaAlvoInput" min="1" max="99" step="0.5" placeholder="Ex: 70">
                </div>
                <div class="ajuste-input-help">Distribui os pontos uniformemente pelos jogadores do elenco respeitando os limites de 1 a 99.</div>
            </div>

            <div class="ajuste-options-box">
                <label class="ajuste-checkbox-line">
                    <input type="checkbox" id="chkIncluirEstrangeiros" checked>
                    <span>Incluir jogadores de países de outros usuários (ajuste via Modificador de Nível)</span>
                </label>
                <label class="ajuste-checkbox-line">
                    <input type="checkbox" id="chkRecalcularPasse">
                    <span>Recalcular também Valor (passe) e Salário automaticamente</span>
                </label>
            </div>

            <div id="ajusteModalError" class="ajuste-banner-error" style="display:none;"></div>

            <div id="ajustePreviaContainer" class="ajuste-previa-container"></div>
        </div>

        <div class="ajuste-niveis-footer">
            <button type="button" id="btnCancelarAjustarNiveis" class="btn-modal-cancel">Cancelar</button>
            <button type="button" id="btnConfirmarAjustarNiveis" class="btn-modal-confirm" disabled>
                <span class="material-symbols-outlined" style="font-size:18px;">trending_up</span> Aplicar Alterações
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes fadeInModal { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
</style><?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>