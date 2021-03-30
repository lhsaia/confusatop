<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus estádios - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button  id='importar_time' onclick="window.location='/ligas/criar_estadio.php';">Criar estádio</button>
<button id='importar_time' onclick="window.location='/import/importar_estadio.php';">Importar estádio</button>
<h2>Quadro de estádios - <?php echo $_SESSION['nomereal']?></h2>
<div id='error_box'></div>

<hr>

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$estadio = new Estadio($db);
$clima = new Clima($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

// query caixa de seleção países desse dono
$stmtClima = $clima->read($_SESSION['user_id']);
$listaClimas = array();
while ($row_clima = $stmtClima->fetch(PDO::FETCH_ASSOC)){
    extract($row_clima);
    $addArray = array($ID, $Nome);
    $listaClimas[] = $addArray;
}

//queries de ligas e estadios

//query de estadios
$stmt = $estadio->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meusestadios.php?";

    // count all products in the database to calculate total pages
    $total_rows = $estadio->countAll($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){


    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
		   echo "<th asc='' class='headings' width='2%'>Foto</th>";
            echo "<th width='30%'>Estádio</th>";
            echo "<th width='20%'>Capacidade</th>";
            echo "<th width='10%'>Clima</th>";
            echo "<th width='10%'>Altitude</th>";
            echo "<th width='10%'>Caldeirão</th>";
            echo "<th width='20%'class='wide'>País</th>";
            echo "<th width='20%' class='wide'>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            echo "<tr id='".$ID."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
				echo "<td><div class='imageUpload'><img class='stadiumThumb' src='/images/estadios/{$foto}' /> <input type='file' hidden id='foto".$ID."' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td><span class='nomeEstadio nomeEditavel' id='nom".$ID."'>{$Nome}</span></td>";
                echo "<td><span class='capacidadeFixo' id='cap".$ID."'>{$Capacidade}</span><input id='capedit".$ID."' type='number' min='0' step='100' class='capacidade inputHerdeiro' value={$Capacidade} hidden></td>";

                echo "<td class='wide'><span class='nomeClima' id='cli".$ID."'>{$nomeClima}</span>";
                echo " <select class='comboClima editavel ' id='{$Clima}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaClimas);$i++){
                        echo "<option value='{$listaClimas[$i][0]}'>{$listaClimas[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                echo "<td><input type='checkbox' class='altitude' id='alt".$ID."' ". ($Altitude == 1? 'checked' : '')." disabled></td>";
                echo "<td><input type='checkbox' class='caldeirao' id='cal".$ID."' ". ($Caldeirao == 1? 'checked' : '')." disabled></td>";

                echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                    $optionsString = "<td class='wide'>";

                        $optionsString .= "<a id='edi".$ID."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$ID."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$ID."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        //$optionsString .= "<a id='del".$id."' title='Deletar' class='clickable deletar'><i class='far fa-trash-alt inlineButton negative'></i></a>";
                    $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há estádios</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

$(document).ready(function() {

	$('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());
        });
		let id = tbl_row.attr("id")
        tbl_row.find('.nomeEditavel').css("cursor","text");
        // tbl_row.find('.nomeLiga').css("cursor","text");
        // tbl_row.find('.nomeLiga').css("pointer-events","none");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        // tbl_row.find('.deletar').hide();
        tbl_row.find('.nomePais').hide();
		tbl_row.find('.nomeClima').hide();
        tbl_row.find('.newlogoedit').show();
        tbl_row.find('.logoimage').hide();
		tbl_row.find('.capacidadeFixo').hide();
		tbl_row.find('.capacidade').show();
		tbl_row.find('.capacidade').val(tbl_row.find('.capacidadeFixo').html());
		document.getElementById("alt" + id).disabled= false;
		document.getElementById("cal" + id).disabled= false;
		let altitude = document.getElementById("alt" + id).checked;
		let caldeirao = document.getElementById("cal" + id).checked;
		document.getElementById("alt" + id).setAttribute("original_entry", altitude)
		document.getElementById("cal" + id).setAttribute("original_entry", caldeirao)
		tbl_row.find('.hiddenInput').show();

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId); 
		
		var climaId = tbl_row.find('.comboClima').attr('id');
        tbl_row.find('.comboClima').show().val(climaId); 
		
		

     });

    $('.cancelar').click(function(){
        let tbl_row =  $(this).closest('tr');
		let id = tbl_row.attr("id")
        tbl_row.find('.nomeEstadio').css("pointer-events","auto");
        tbl_row.find('.nomeEstadio').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
		tbl_row.find('.nomeClima').show();
		tbl_row.find('.comboClima').hide();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
		tbl_row.find('.capacidadeFixo').show();
		tbl_row.find('.capacidade').hide();
        // tbl_row.find('.deletar').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();
		tbl_row.find('.hiddenInput').hide();
		document.getElementById("alt" + id).disabled= true;
		document.getElementById("cal" + id).disabled= true;
		let altitude = (document.getElementById("alt" + id).getAttribute("original_entry") === 'true')
		let caldeirao = (document.getElementById("cal" + id).getAttribute("original_entry") === 'true')
		document.getElementById("alt" + id).checked = altitude
		document.getElementById("cal" + id).checked = caldeirao
		//document.getElementById("capedit" + id).value(document.getElementById("capedit" + id).getAttribute("original_entry"))



        tbl_row.find('span, input').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

     $('.salvar').click(function(){
		let tbl_row =  $(this).closest('tr');
		let id = tbl_row.attr("id")
        tbl_row.find('.nomeEstadio').css("pointer-events","auto");
        tbl_row.find('.nomeEstadio').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
		tbl_row.find('.nomeClima').show();
		tbl_row.find('.comboClima').hide();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
		tbl_row.find('.capacidadeFixo').show();
		tbl_row.find('.capacidade').hide();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();
		tbl_row.find('.hiddenInput').hide();
		document.getElementById("alt" + id).disabled= true;
		document.getElementById("cal" + id).disabled= true;
		
         let nomeEstadio = tbl_row.find('#nom'+id).html();
         let capacidade = tbl_row.find('#capedit'+id).val();
		 let clima = tbl_row.find('.comboClima').val();
         let pais = tbl_row.find('.comboPais').val();
		 let altitude = document.getElementById("alt" + id).checked
		 let caldeirao = document.getElementById("cal" + id).checked
		 
		 		//foto
		var inputFoto = (tbl_row.find('#foto'+id))[0];
		var foto;

		if (inputFoto.files.length > 0) {
		   foto = inputFoto.files[0];
		} else {
		   foto = null;
		}


//         //var formId = 'form'+id;
//         //var form = document.getElementById(formId);
          var formData = new FormData();
          formData.append('id', id);
          formData.append('nomeEstadio', nomeEstadio);
          formData.append('capacidade', capacidade);
          formData.append('clima', clima);
          formData.append('altitude', altitude);
          formData.append('caldeirao', caldeirao);
          formData.append('pais', pais);
		  
		if(foto != null){
			formData.append('foto',foto);
		}


 //for (var key of formData.entries()) {
  //    console.log(key[0] + ', ' + key[1]);
  //}

//         //console.log(formData);
          $.ajax({
              url: 'alterar_estadio.php',
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

 });



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
