<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

if(isset($_SESSION['nomereal'])){
	$page_title = "Meus paises - ".$_SESSION['nomereal'];
} else {
	$page_title = "Meus paises";
}

$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<div style="clear:both;"></div>
<iframe id="results_sheet" hidden></iframe>
<div style="clear:both;"></div>
<div id="quadro-container">
<div align="center" id="quadroTimes">

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 30;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$liga = new Liga($db);

//queries de ligas e estadios

//query de ligas
$stmt = $pais->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();


if(!$_SESSION['emTestes'] || $num < 1){
	$onclick = "window.location='/ligas/criar_pais.php";
	echo "<button id='importar_time' onclick=".$onclick."'>Criar país</button>";
}


?>
<h2>Quadro de países - <?php echo $_SESSION['nomereal']?></h2>

<hr>

<?php



// the page where this paging is used
$page_url = "meuspaises.php?";

    // count all products in the database to calculate total pages
    $total_rows = $pais->countAll(null,$_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){

  echo "<div id='errorbox'></div>";
    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th width='15%'>País</th>";
            echo "<th width='20%'>Bandeira</th>";
            echo "<th width='15%'>Sigla</th>";
			echo "<th width='10%'>Latitude | Longitude</th>";
			if(!$_SESSION['emTestes']){
				echo "<th width='10%' class='wide'>CONFUSA?</th>";
				echo "<th width='20%' class='wide'>Federação</th>";
			}

           // echo "<th width='10%'>Demografia</th>";
            echo "<th width='15%' class='wide'>Opções</th>";


        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $moreInfo = $pais->readMoreInfo($id);

            echo "<tr id='".$id."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><a class='nomeLiga' href='../ligas/paisstatus.php?country=".$id."'><span class='nomeEditavel' id='nom".$id."'>{$nome}</span></a></td>";
                echo "<td><img class='logoimage' id='log".$id."' src='../images/bandeiras/".$bandeira."?" . time() . "' height='30px'/><div class='newlogoedit' hidden> <input type='file' id='newlogo".$id."' class='form-control custom-file-upload' name='file' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td><span class='nomeEditavel' id='sig".$id."'>{$sigla}</span></td>";
				echo "<td><span class='coordenadas' id='coo".$id."'>{$latitude},{$longitude}</span><input class='example editavel' type='text' data-id='{$id}' value='{$latitude},{$longitude}' style='display:none' /><div id='mapContainer{$id}' style='display:none'></div></td>";
				
				if(!$_SESSION['emTestes']){
				echo "<td><input type='checkbox' class='inputranking' id='chk".$id."' ". ($ranqueavel == 0? 'checked disabled' : 'disabled')."></span></td>";
                echo "<td><span class='nomeEditavel fedpais' id='fed".$id."'>{$federacao}</span>";
                echo " <select class='comboPais editavel ' id='{$idFederacao}' hidden>'  ";

                    echo "<option value='1'>FEASCO</option>";
                    echo "<option value='2'>FEMIFUS</option>";
                    echo "<option value='3'>COMPACTA</option>";

                    echo "</select>";
                    echo "</td>";
				}
                   // echo "<td><canvas width='150px' height='35px' class='chartContainer' id='chartContainer".$id."'></canvas></td>";
                    $optionsString = "<td class='wide'>";

                        $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        $optionsString .= "<a id='dem".$id."' title='Alterar demografia' class='clickable demografia'><i class='fas fa-language inlineButton'></i></a>";
                        $optionsString .= "<a id='sel".$id."' title='Tela de seleções' class='clickable selecoes'><i class='fas fa-globe inlineButton'></i></a>";
                        $optionsString .= "<a id='exp".$id."' title='Exportar planilha base' class='clickable exportarplanilha'><i class='fas fa-file-export inlineButton'></i></a>";
                        $optionsString .= "<a id='imp".$id."' title='Importar planilha base' class='clickable importarplanilha'><i class='fas fa-file-import inlineButton'></i></a>";
                        $optionsString .= "<input type='file' hidden id='inputplanilha".$id."' class='inputfile' name='inputplanilha' accept='.xlsx'/>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Importar' class='clickable confirmarimport'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelarimport'><i class='fas fa-times inlineButton negative'></i></a>";
                        $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há países</div>";
}

echo('</div>');
echo('</div>');

?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
  integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
  integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
  crossorigin=""></script>

<link rel="stylesheet" href="/lib/leaflet-locationpicker/dist/leaflet-locationpicker.src.css" />

<script src="/lib/leaflet-locationpicker/dist/leaflet-locationpicker.min.js"></script>

<script>

    $(document).ready(function() {
		
		$(".example").each(function(){
			let picker_id = $(this).attr("data-id");
			let container_name = "#mapContainer" + picker_id;
			console.log(container_name);
			
			$(this).leafletLocationPicker({
				alwaysOpen:true,
				mapContainer:container_name
			});    
		});
		
        $('.exportarplanilha').click(function(){
            var idPais = $(this).attr("id").replace(/\D/g,'');
            //window.location.href = "exportar_planilha.php?idPais="+ idPais;

            var formData = new FormData();
            formData.append('idPais', idPais);

            $.ajax({
                url: 'exportar_planilha.php',
                processData: false,
               contentType: false,
               cache: false,
               type: "POST",
               dataType: 'json',
                data: formData,
                     success: function(data) {
                         document.getElementById("results_sheet").src = data.filename;
                         //location.reload();
                     },
                     error: function(data) {
                         successmessage = 'Error';
                         alert("Erro na execução da solicitação");
                         //location.reload();
                     }
                 }).fail(function(jqXHR, textStatus, errorThrown ){
                     console.log("Erro");
                     console.log(jqXHR);
                     console.log(textStatus);
                     console.log(errorThrown);
                 });;
        });

        $('.importarplanilha').click(function(){
            var idPais = $(this).attr("id").replace(/\D/g,'');
            var tbl_row =  $(this).closest('tr');
            tbl_row.find('.confirmarimport').show();
            tbl_row.find('.cancelarimport').show();
            tbl_row.find('.inputfile').show();
            tbl_row.find('.editar').hide();
            tbl_row.find('.demografia').hide();
            tbl_row.find('.selecoes').hide();
            tbl_row.find('.importarplanilha').hide();
            tbl_row.find('.exportarplanilha').hide();

        });

        $('.cancelarimport').click(function(){
            var tbl_row =  $(this).closest('tr');
            tbl_row.find('.confirmarimport').hide();
            tbl_row.find('.cancelarimport').hide();
            tbl_row.find('.inputfile').hide();
            tbl_row.find('.editar').show();
            tbl_row.find('.demografia').show();
            tbl_row.find('.selecoes').show();
            tbl_row.find('.importarplanilha').show();
            tbl_row.find('.exportarplanilha').show();
            tbl_row.find('.inputfile').val('');


        });

        $('.confirmarImport').click(function(){
            var tbl_row =  $(this).closest('tr');

            //escudo
            var inputArquivo = (tbl_row.find('.inputfile'))[0];
            var escudo;

            console.log(inputArquivo.files);

            if (inputArquivo.files.length > 0) {
               planilha_importada = inputArquivo.files[0];
            } else {
               planilha_importada = null;
            }

            var formData = new FormData();
            if(planilha_importada != null){
               formData.append('planilha_importada', planilha_importada);
            }

            $.ajax({
                url: 'importar_planilha.php',
                processData: false,
               contentType: false,
               cache: false,
               type: "POST",
               method: "POST",
               dataType: 'json',
                data: formData,
                     success: function(data) {
                       if(data.success == true){
                         $('#errorbox').append("<div class='alert alert-success'>Atualização concluída com sucesso!</div>");
                       } else {
                         $('#errorbox').append("<div class='alert alert-danger'>Houve os seguintes erros na importação: "+data.error_msg+"</div>");
                       }
                        //console.log(data.player_list);
                         //location.reload();
                     },
                     error: function(data) {
                         successmessage = 'Error';
                         alert("Erro na execução da solicitação");
                         //location.reload();
                     }
                 }).fail(function(jqXHR, textStatus, errorThrown ){
                     console.log("Erro");
                     console.log(jqXHR);
                     console.log(textStatus);
                     console.log(errorThrown);
                 });

                 tbl_row.find('.confirmarimport').hide();
                 tbl_row.find('.cancelarimport').hide();
                 tbl_row.find('.inputfile').hide();
                 tbl_row.find('.editar').show();
                 tbl_row.find('.demografia').show();
                 tbl_row.find('.selecoes').show();
                 tbl_row.find('.importarplanilha').show();
                 tbl_row.find('.exportarplanilha').show();
                 tbl_row.find('.inputfile').val('');

        });

         $('.demografia').click(function(){
             var id = $(this).attr("id").replace(/\D/g,'');
             window.location.href = "alterar_demografia.php?idPais="+ id;
         });

         $('.selecoes').click(function(){
             var id = $(this).attr("id").replace(/\D/g,'');
             window.location.href = "selecoesdopais.php?idPais="+ id;
         });

         $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.nomeLiga').css("cursor","text");
        tbl_row.find('.nomeLiga').css("pointer-events","none");
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.fedPais').hide();
        tbl_row.find('.newlogoedit').show();
        tbl_row.find('.logoimage').hide();
		tbl_row.find("[id^='mapContainer']").show();
		tbl_row.find(".example").show();
		tbl_row.find(".coordenadas").hide();
        var inputranking = tbl_row.find('.inputranking');
        if(inputranking.prop("checked") == false){
            inputranking.prop("disabled", false);
            inputranking.prop("data-original", false);
        } else {
            inputranking.prop("data-original", true);
        }

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);

    });

        $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nomeLiga').css("pointer-events","auto");
        tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.fedPais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();
		tbl_row.find("[id^='mapContainer']").hide();
		tbl_row.find(".example").hide();
		tbl_row.find(".coordenadas").show();
        var inputranking = tbl_row.find('.inputranking');
        if(inputranking.prop("data-original") == false){
            inputranking.prop("checked", false);
            inputranking.prop("disabled", true);
        }

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nomeLiga').css("pointer-events","auto");
        tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.fedPais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();
		tbl_row.find("[id^='mapContainer']").hide();
		tbl_row.find(".example").hide();
		tbl_row.find(".coordenadas").show();

        var id = tbl_row.attr('id');
        var nomePais = tbl_row.find('#nom'+id).html();
        var siglaPais = tbl_row.find('#sig'+id).html();
        var federacaoPais = tbl_row.find('.comboPais').val();
        var rank = tbl_row.find('#chk'+id).prop("checked");
        var inputranking = tbl_row.find('.inputranking');

        if(inputranking.prop("data-original") == false){
            inputranking.prop("checked", false);
            inputranking.prop("disabled", true);
        }

        if (rank == true){
            ranqueavel = 0;
        } else {
            ranqueavel = 1;
        }

        var input = (tbl_row.find('#newlogo'+id))[0];
        var logo;

        if (input.files.length > 0) {
           logo = input.files[0];
        } else {
           logo = null;
        }
		
		var coordenadas = tbl_row.find(".example").val().split(",");
		var latitude = coordenadas[0];
		var preLongitude = parseFloat(coordenadas[1]);
		
		let longitude = preLongitude + Math.round(preLongitude/-360) * 360;
		
		// console.log(preLongitude);
		// console.log(longitude);

         var formData = new FormData();
         formData.append('id', id);
         formData.append('nomePais', nomePais);
         formData.append('siglaPais', siglaPais);
         formData.append('federacaoPais', federacaoPais);
         formData.append('ranqueavel', ranqueavel);
		 formData.append('latitude', latitude);
		 formData.append('longitude', longitude);

         if(logo != null){
            formData.append('logo', logo);
         }


    // for (var key of formData.entries()) {
         // console.log(key[0] + ', ' + key[1]);
     // }

        //console.log(formData);
         $.ajax({
             url: 'alterar_pais.php',
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
                      alert("Erro na execução da solicitação");
                      //location.reload();
                  }
              });
     });

});


</script>



<script>

$(".chartContainer").each(function(){
    var id = $(this).attr("id");
    var canvas = $("#"+id)[0];
    var pais = id.replace(/\D/g,'');
    var largura = 150;
    var altura = 35;
    var ctx = canvas.getContext("2d");

    ctx.beginPath();
    ctx.rect(0, 0, pais, altura);
    ctx.fillStyle = "red";
    ctx.fill();

});



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
