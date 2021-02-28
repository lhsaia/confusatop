<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();

$team_id = $_GET['id'];
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transaction.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$transaction = new Transaction($db);

$nome_time = $time->getName($team_id);
$dono_time = $time->getDono($team_id);

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Resumo Financeiro - " . $nome_time["Nome"];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

//if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	//estabelecer conexão com banco de dados
	
	



	
?>

<script>

var teamId = '<?php echo $team_id;?>';
var donoTime = '<?php echo $dono_time["dono"];?>';
//console.log(donoTime);
var localData = [];
var asc = true;
var activeSort = '';
var receitas = 0;
var despesas = 0;
var totais = 0;

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
	echo "-1";
 };?>';

$(document).ready(function($){
	
	$("#select_transaction_type").change(function(){
		let icon = $("#select_transaction_type option:selected").attr("data-icon");
		$("#transaction_type_icon").removeClass().addClass(icon);
		load_data();
	});
	
	load_data();
	
	$("#input_inicio").change(function(){
		load_data();
	});
	
	$("#input_fim").change(function(){
		load_data();
	});
	
	function calculo_financeiro(){
		$("#Receitas .informacao").html("F$ " + receitas.toLocaleString('pt-BR'));
		$("#Gastos .informacao").html("F$" + despesas.toLocaleString('pt-BR'));
		$("#Balanco .informacao").html("F$" + totais.toLocaleString('pt-BR'));
	}
	
	

//typing timer ajax improvement
//setup before functions
// var typingTimer;                //timer identifier
// var doneTypingInterval = 800;  //time in ms (5 seconds)

//on keyup, start the countdown
// $('#caixa_pesquisa').keyup(function(){
    // clearTimeout(typingTimer);
    // if ($('#caixa_pesquisa').val()) {
        // typingTimer = setTimeout(doneTyping, doneTypingInterval);
    // }
	// typingTimer = setTimeout(doneTyping, doneTypingInterval);
// });

//user is "finished typing," do something
// function doneTyping () {
    // load_data();
// }

//$('#caixa_pesquisa').keyup(function(){load_data()});

function load_data(){

// var searchText = $('#caixa_pesquisa').val();
var transactionType = $("#select_transaction_type").val();
var startDate = $("#input_inicio").val();
var endDate = $("#input_fim").val();
$('#loading').show();  // show loading indicator

// console.log(transactionType);
console.log(startDate);
console.log(endDate);

$.ajax({
    url:"/finance/filter_financial_records.php",
    method:"POST",
    cache:false,
    data:{	teamId:teamId,
			transactionType:transactionType,
			startDate:startDate,
			endDate:endDate
			},
    success:function(data){
        $('#loading').hide();  // hide loading indicator
		
		json_data = JSON.parse(data);
		//console.log(typeof json_data);
        updateTable(JSON.parse(data),1,0,0);
        localData = JSON.parse(data);
		
		// let valores = json_data.map(item => item.valor);
		
		// let receitas = valores.filter(function (item) {
			// return item > 0;
		// });
		
		// let despesas = valores.filter(function (item) {
			// return item < 0;
		// });
		
		receitas = json_data.reduce(function (acumulador, item) {
			return (item.valor > 0) ? acumulador + parseInt(item.valor) : acumulador;
		}, 0);
		
		despesas = json_data.reduce(function (acumulador, item) {
			return (item.valor < 0) ? acumulador + parseInt(item.valor) : acumulador;
		}, 0);
		
		totais = json_data.reduce(function (acumulador, item) {
			return acumulador + parseInt(item.valor);
		}, 0);
		
		calculo_financeiro();
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
				tbl += "<th asc='' class='headings' width='15%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspData</th>";
				tbl += "<th asc='' class='headings' width='5%'>Tipo</th>";
				tbl += "<th asc='' class='headings' width='2%'>Fluxo de Caixa</th>";
				tbl += "<th asc='' class='headings' width='10%'>Valor</th>";
				tbl += "<th asc='' class='headings' width='20%'>Comentário</th>";
				tbl += "<th asc='' class='headings' width='10%%' class=''>Opções</th>";
			tbl += "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
				
			fluxo_caixa = (val['fluxo_caixa'] == 1? "receita" : "despesa");
			
			//console.log(val);
			
			// geração da tabela
			tbl += "<tr id='"+val['trans_id']+"' >";
				tbl +=  "<td><span class='dataEditavel' id='dat"+val['trans_id']+"'>"+val['data']+"</span></td>";
				tbl +=  "<td><span class='tipoTransacao' id='tip"+val['trans_id']+"'>"+val['nome']+"</span></td>";
				tbl +=  "<td><span class='fluxoCaixa' id='flx"+val['trans_id']+"'>"+fluxo_caixa.charAt(0).toUpperCase() + fluxo_caixa.slice(1);+"</span></td>";
				tbl +=  "<td><span class='valor "+fluxo_caixa+"' id='val"+val['trans_id']+"'> F$ "+parseInt(val['valor']).toLocaleString('pt-BR')+"</span></td>";
				tbl +=  "<td><span class='comentario' id='com"+val['trans_id']+"'>"+val['comentario']+"</span></td>";
                let optionsString = "<td class='wide'>";

                if(logged == "true"){
                    if( (admin == "true" ||user_id === donoTime) && val['tableFrom'] == "transactions"){
                        // optionsString += "<a id='edi"+val['id']+"' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        // optionsString += "<a hidden id='sal"+val['id']+"' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        // optionsString += "<a hidden id='can"+val['id']+"' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        optionsString += "<a id='apa"+val['trans_id']+"' title='Apagar' class='clickable apagar'><i class='fas fa-trash-alt negative inlineButton'></i></a>";
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
	
	$('.apagar').click(function(){
        var transactionId = $(this).closest('tr').attr('id');
		//console.log(transactionId);
        var r = confirm("Você tem certeza que deseja apagar essa transação?");
        if (r) {
            $.ajax({
                type: "POST",
                url: '/finance/delete_transaction.php',
                data: {transactionId:transactionId},
                success: function(data) {
                    successmessage = 'Sucesso'; // modificar depois
                    //$("label#successmessage").text(successmessage);
                    location.reload();
                },
                error: function(data) {
                    successmessage = 'Erro';
                    alert("Erro, o procedimento não foi realizado, tente novamente.");
                }
            });
        }

    });
	
	// inclusão de formulas de edição
	
	 // $('.editar').click(function(){
    // var tbl_row =  $(this).closest('tr');
    // tbl_row.find('span').each(function(index, val){
        // $(this).attr('original_entry', $(this).html());

    // });
    // tbl_row.find('.linkNome').css("cursor","text");
    // tbl_row.find('.linkNome').css("pointer-events","none");
    // tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    // tbl_row.find('.salvar').show();
    // tbl_row.find('.cancelar').show();
    // tbl_row.find('.editar').hide();
    // tbl_row.find('.deletar').hide();
    // tbl_row.find('.nomePais').hide();
    // tbl_row.find('.hiddenInput').show();
    // tbl_row.find('.fidelidadeFixo').hide();
    // tbl_row.find('.fidelidade').show();
    // tbl_row.find('.maxTorcida').show();
    // tbl_row.find('.maxTorcedores').hide();

        // //acertar questão cores
        // tbl_row.find(".celula-uniforme :input").each(function(){
            // var rgb = $(this).closest('.quadrado-uniforme').attr('id');
            // var rgbp = rgb.match(/.{1,3}/g);
            // var hex = rgbToHex(rgbp);
            // $(this).val(hex);
        // });

        // //console.log(tbl_row.find(".celula-uniforme :input"));

    // tbl_row.find('.thumb').addClass('editableThumb');

    // var paisId = tbl_row.find('.comboPais').attr('id');
    // tbl_row.find('.comboPais').show().val(paisId);

    // var paisId = tbl_row.find('.comboTorcedores').attr('id');
    // tbl_row.find('.comboTorcedores').show().val(paisId);

    // var ligaId = tbl_row.find('.comboLiga').attr('id').replace(/\D/g,'');;
    // tbl_row.find('.comboLiga').show().val(ligaId);

        // var estadioId = tbl_row.find('.comboEstadio').attr('id').replace(/\D/g,'');;
    // tbl_row.find('.comboEstadio').show().val(estadioId);

// });

    // $('.cancelar').click(function(){
    // var tbl_row =  $(this).closest('tr');
    // tbl_row.find('.linkNome').css("cursor","pointer");
    // tbl_row.find('.linkNome').css("pointer-events","auto");

    // tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    // tbl_row.find('.comboPais').hide();
    // tbl_row.find('.comboLiga').hide();
    // tbl_row.find('.comboEstadio').hide();
    // tbl_row.find('.nomePais').show();
    // tbl_row.find('.salvar').hide();
    // tbl_row.find('.cancelar').hide();
    // tbl_row.find('.editar').show();
    // tbl_row.find('.deletar').show();
    // tbl_row.find('.thumb').removeClass('editableThumb');
    // tbl_row.find('.hiddenInput').hide();
    // tbl_row.find('.comboTorcedores').hide();
    // tbl_row.find('.maxTorcedores').show();

    // tbl_row.find('.fidelidadeFixo').show();
    // tbl_row.find('.fidelidade').hide();

    // tbl_row.find('span').each(function(index, val){
        // $(this).html($(this).attr('original_entry'));
    // });
// });

  // $(".fidelidade").each(function(){

    // $(this).keydown(function () {
    // // Save old value.
    // if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
    // $(this).data("old", $(this).val());
  // });

  // });

  // $(".fidelidade").each(function(){

    // $(this).keyup(function () {
    // // Check correct, else revert back to old value.
    // if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1));
    // else
      // $(this).val($(this).data("old"));
  // });


  // });
  
     
// $('.salvar').click(function(){
    // var tbl_row =  $(this).closest('tr');
    // tbl_row.find('.linkNome').css("cursor","pointer");
    // tbl_row.find('.linkNome').css("pointer-events","auto");

    // tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    // tbl_row.find('.comboPais').hide();
    // tbl_row.find('.comboLiga').hide();
    // tbl_row.find('.comboEstadio').hide();
    // tbl_row.find('.nomePais').show();
    // tbl_row.find('.salvar').hide();
    // tbl_row.find('.cancelar').hide();
    // tbl_row.find('.editar').show();
    // tbl_row.find('.deletar').show();
    // tbl_row.find('.thumb').removeClass('editableThumb');
    // tbl_row.find('.hiddenInput').hide();
    // tbl_row.find('.comboTorcedores').hide();
    // tbl_row.find('.maxTorcedores').show();


    // tbl_row.find('.fidelidadeFixo').show();
    // tbl_row.find('.fidelidade').hide();

    // var id = tbl_row.attr('id');
    // var nomeTime = tbl_row.find('#nom'+id).html();
    // var maxTorcedores = tbl_row.find('.comboTorcedores').val();
    // var fidelidade = tbl_row.find('#fid'+id).val();
    // var estadio = tbl_row.find('.comboEstadio').val();
    // var liga = tbl_row.find('.comboLiga').val();
    // var pais = tbl_row.find('.comboPais').val();

    // //cores1
    // var uni1cor1hex = tbl_row.find('[name=u1c1]').val();
    // var uni1cor2hex = tbl_row.find('[name=u1c2]').val();
    // var uni1cor3hex = tbl_row.find('[name=u1c3]').val();

    // var uni1cor1 = hexToRgb(uni1cor1hex);
    // var uni1cor2 = hexToRgb(uni1cor2hex);
    // var uni1cor3 = hexToRgb(uni1cor3hex);

    // //cores2
    // var uni2cor1hex = tbl_row.find('[name=u2c1]').val();
    // var uni2cor2hex = tbl_row.find('[name=u2c2]').val();
    // var uni2cor3hex = tbl_row.find('[name=u2c3]').val();

    // var uni2cor1 = hexToRgb(uni2cor1hex);
    // var uni2cor2 = hexToRgb(uni2cor2hex);
    // var uni2cor3 = hexToRgb(uni2cor3hex);

    // //escudo
    // var inputEscudo = (tbl_row.find('#escudo'+id))[0];
    // var escudo;

    // if (inputEscudo.files.length > 0) {
       // escudo = inputEscudo.files[0];
    // } else {
       // escudo = null;
    // }

    // //uniforme 1
    // var inputUni1 = (tbl_row.find('#uni1'+id))[0];
    // var uni1;

    // if (inputUni1.files.length > 0) {
        // uni1 = inputUni1.files[0];
    // } else {
        // uni1 = null;
    // }

    // //uniforme 2
    // var inputUni2 = (tbl_row.find('#uni2'+id))[0];
    // var uni2;

    // if (inputUni2.files.length > 0) {
        // uni2 = inputUni2.files[0];
    // } else {
        // uni2 = null;
    // }

    // var formData = new FormData();
    // formData.append('id', id);
    // formData.append('nomeTime', nomeTime);
    // formData.append('maxTorcedores', maxTorcedores);
    // formData.append('fidelidade', fidelidade);
    // formData.append('pais', pais);
    // formData.append('estadio', estadio);
    // formData.append('liga', liga);
     // if(escudo != null){
        // formData.append('escudo', escudo);
     // }
     // if(uni1 != null){
        // formData.append('uni1', uni1);
     // }
     // if(uni2 != null){
        // formData.append('uni2', uni2);
     // }
    // formData.append('uni1cor1', uni1cor1);
    // formData.append('uni1cor2', uni1cor2);
    // formData.append('uni1cor3', uni1cor3);
    // formData.append('uni2cor1', uni2cor1);
    // formData.append('uni2cor2', uni2cor2);
    // formData.append('uni2cor3', uni2cor3);


// for (var key of formData.entries()) {
     // console.log(key[0] + ', ' + key[1]);
 // }

     // $.ajax({
         // url: 'alterar_time.php',
         // processData: false,
        // contentType: false,
        // cache: false,
        // type: "POST",
        // dataType: 'json',
         // data: formData,
              // success: function(data) {
                  // if(data.error != ''){
                    // alert(data.error)
                  // }
                  // location.reload();
              // },
              // error: function(data) {
                  // successmessage = 'Error';
                  // alert("Erro, o procedimento não foi realizado, tente novamente.");
                  // //location.reload();
              // }
          // });
// });


// $('.promover').click(function(){
    // var clube = $(this).closest('tr').attr("id");
    // var nacionalidade = $(this).closest('tr').find('.comboPais').attr("id");
    // var sexo = $(this).closest('tr').attr("data-sexo");

    // var formData = {
        // 'nacionalidade' : nacionalidade,
        // 'codigoPosicao' : 0,
        // 'inserir' : true,
        // 'clube' : clube,
        // 'base' : true,
        // 'sexo' : sexo
    // }

     // $.ajax({
            // type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            // url         : '/jogadores/hexagen.php', // the url where we want to POST
            // data        : formData, // our data object
            // dataType    : 'json', // what type of data do we expect back from the server
                        // encode          : true
            // })

                    // .done(function(data) {

            // // log data to the console so we can see
            // console.log(data);


            // if (data.success) {
                // $('#error_box').html('<div class="alert alert-success">O jogador '+data.player_info.nomeJogador+' foi promovido com sucesso!</div>');
            // } else {
                // $('#error_box').html('<div class="alert alert-danger">Não foi possível realizar a inserção, '+data.error+'</div>');
            // }

// }).fail(function(jqXHR, textStatus, errorThrown ){
            // console.log("Erro");
            // console.log(jqXHR);
            // console.log(textStatus);
            // console.log(errorThrown);
            // });

// });

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
<div id='datas'>
<div class='date_wrapper'><input type="date" id="input_fim" name="fim"></div>
<div class='date_wrapper'><input type="date" id="input_inicio" name="inicio"></div>

</div>
<div id='select_wrapper'><select id='select_transaction_type'>
<option value='0'>Ver tudo</option>
<?php
	$opcoes_transacao = $transaction->getOptions();
	while ($row = $opcoes_transacao->fetch(PDO::FETCH_ASSOC)){
		extract($row);
		echo "<option value='{$id}' data-icon='{$icone}'>{$nome}</option>";
	}

?>
</select><i id='transaction_type_icon' class=''></i></div>
<!--<div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><i class='fas fa-search'></i></div> -->
<button id='importar_time' onclick="window.location='/finance/create_transaction.php?team=<?php echo $team_id ?>';">Criar transação</button>
<h2>Resumo Financeiro - <?php echo $nome_time["Nome"]?></h2>
<hr>
<?php
echo "<div style='clear:both; float:center'></div>";
echo "<div id='info-financeira'>";
echo "<div id='Receitas' class='infoblock vitoria larger' title='Receitas'><i class='fas fa-sign-in-alt'></i><span class='informacao'></span></div>";
echo "<div id='Gastos' class='infoblock derrota larger' title='Gastos'><i class='fas fa-sign-out-alt'></i><span class='informacao'></span></div>";
echo "<div id='Balanco' class='infoblock larger' title='Balanço'><i class='fas fa-balance-scale'></i><span class='informacao'></span></div>";
echo "</div>";
echo "<br>";
echo "<div style='clear:both;'></div>";
?>
<hr>
<div id='error_box'></div>

<?php

echo "<div style='clear:both;'></div>";
echo "<hr>";
echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";

// echo "<div class='alert alert-info'>Não há times</div>";

echo('</div>');
echo('</div>');

//} else {
//    echo "Usuário, por favor refaça o login.";
//}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
