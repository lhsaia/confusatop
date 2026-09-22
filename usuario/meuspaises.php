<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

if(isset($_SESSION['nomereal'])){
	$page_title = "Meus paises - ".$_SESSION['nomereal'];
} else {
	$page_title = "Meus paises";
}

$css_filename = "home_redesign";
$aux_css = "meuspaises_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<div style="clear:both;"></div>
<iframe id="results_sheet" hidden></iframe>
<div style="clear:both;"></div>
<div class="propostas-container">
<div class="propostas-card">

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
?>
<div class="header-actions-container">
    <h2 class="propostas-title">Quadro de países - <?php echo $_SESSION['nomereal']?></h2>
    <?php
    if(!($_SESSION['emTestes'] ?? false)){
        $onclick = "window.location='/ligas/criar_pais.php'";
        echo "<button id='importar_time' class='btn-action-primary' onclick=\"".$onclick."\">Criar país</button>";
    }
    ?>
</div>
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
  echo "<div class='tbl_user_data'>";
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

                        $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                        $optionsString .= "<a id='dem".$id."' title='Alterar demografia' class='clickable demografia'><span class='material-symbols-outlined inlineButton'>language</span></a>";
                        $optionsString .= "<a id='sel".$id."' title='Tela de seleções' class='clickable selecoes'><span class='material-symbols-outlined inlineButton'>public</span></a>";
                        $optionsString .= "<a id='exp".$id."' title='Exportar planilha base' class='clickable exportarplanilha'><span class='material-symbols-outlined inlineButton'>file_upload</span></a>";
                        $optionsString .= "<a id='imp".$id."' title='Importar planilha base' class='clickable importarplanilha'><span class='material-symbols-outlined inlineButton'>file_download</span></a>";
                        $optionsString .= "<input type='file' hidden id='inputplanilha".$id."' class='inputfile' name='inputplanilha' accept='.xlsx'/>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Importar' class='clickable confirmarimport'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelarimport'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                        $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";

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
			// console.log(container_name);
			
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
                     // console.log("Erro");
                     // console.log(jqXHR);
                     // console.log(textStatus);
                     // console.log(errorThrown);
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

            // console.log(inputArquivo.files);

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
                     // console.log("Erro");
                     // console.log(jqXHR);
                     // console.log(textStatus);
                     // console.log(errorThrown);
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

    function getCountryRowFormData(tbl_row) {
        var id = tbl_row.attr('id');
        var nomePais = tbl_row.find('#nom'+id).text().trim();
        var siglaPais = tbl_row.find('#sig'+id).text().trim();
        var federacaoPais = tbl_row.find('.comboPais').val();
        var rank = tbl_row.find('#chk'+id).prop("checked");
        var ranqueavel = (rank == true) ? 0 : 1;

        var input = (tbl_row.find('#newlogo'+id))[0];
        var logo = (input && input.files.length > 0) ? input.files[0] : null;

        var coordenadas = tbl_row.find(".example").val().split(",");
        var latitude = coordenadas[0];
        var preLongitude = parseFloat(coordenadas[1]);
        var longitude = preLongitude + Math.round(preLongitude/-360) * 360;

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
        return formData;
    }

    function saveCountryRowAjax(tbl_row) {
        return new Promise(function(resolve, reject){
            var id = tbl_row.attr('id');
            var formData = getCountryRowFormData(tbl_row);

            $.ajax({
                url: 'alterar_pais.php',
                processData: false,
                contentType: false,
                cache: false,
                type: "POST",
                dataType: 'json',
                data: formData,
                success: function(data) {
                    if(data.error && data.error !== ''){
                        reject(data.error);
                    } else {
                        // Apply DOM updates directly
                        var nomePais = tbl_row.find('#nom'+id).text().trim();
                        var siglaPais = tbl_row.find('#sig'+id).text().trim();
                        var fedText = tbl_row.find('.comboPais option:selected').text();
                        var fedVal = tbl_row.find('.comboPais').val();
                        var coordenadas = tbl_row.find(".example").val();

                        tbl_row.find('#nom'+id).text(nomePais);
                        tbl_row.find('#sig'+id).text(siglaPais);
                        tbl_row.find('#fed'+id).text(fedText);
                        tbl_row.find('.comboPais').attr('id', fedVal);
                        tbl_row.find('#coo'+id).text(coordenadas);

                        var inputLogo = (tbl_row.find('#newlogo'+id))[0];
                        if (inputLogo && inputLogo.files.length > 0) {
                            var cacheBuster = new Date().getTime();
                            tbl_row.find('#log'+id).attr('src', '../images/bandeiras/' + siglaPais + '.webp?' + cacheBuster);
                            tbl_row.find('#newlogo'+id).val('');
                        }

                        tbl_row.removeClass('in-edition');
                        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
                        tbl_row.find('.nomeLiga').css("pointer-events","auto").css("cursor","auto");
                        tbl_row.find('.comboPais').hide();
                        tbl_row.find('.fedPais').show();
                        tbl_row.find('.salvar').hide();
                        tbl_row.find('.cancelar').hide();
                        tbl_row.find('.editar').show();
                        tbl_row.find('.demografia').show();
                        tbl_row.find('.selecoes').show();
                        tbl_row.find('.exportarplanilha').show();
                        tbl_row.find('.importarplanilha').show();
                        tbl_row.find('.newlogoedit').hide();
                        tbl_row.find('.logoimage').show();
                        tbl_row.find("[id^='mapContainer']").hide();
                        tbl_row.find(".example").hide();
                        tbl_row.find(".coordenadas").show();
                        tbl_row.find('.inputranking').prop("disabled", true);

                        resolve(data);
                    }
                },
                error: function() {
                    reject("Erro na execução da solicitação");
                }
            });
        });
    }

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
                        promises.push(saveCountryRowAjax($(this)));
                    });

                    Promise.allSettled(promises).then(function(results){
                        var errors = results.filter(function(r){ return r.status === 'rejected'; });
                        if (errors.length > 0) {
                            alert("Alguns países não puderam ser salvos (" + errors.length + " erro(s)).");
                        }
                        updateBatchFloatingBar();
                    });
                });

                $('#btn-batch-cancel-all').off('click').on('click', function(){
                    $('tr.in-edition').find('.cancelar').click();
                });
            } else {
                $('#batch-count-badge').text(count);
                bar.show();
            }
        } else {
            if (bar.length > 0) {
                bar.remove();
            }
        }
    }

    $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.addClass('in-edition');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());
        });
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.nomeLiga').css("cursor","text");
        tbl_row.find('.nomeLiga').css("pointer-events","none");
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.demografia').hide();
        tbl_row.find('.selecoes').hide();
        tbl_row.find('.exportarplanilha').hide();
        tbl_row.find('.importarplanilha').hide();
        tbl_row.find('.fedPais').hide();
        tbl_row.find('.newlogoedit').show();
        tbl_row.find('.logoimage').hide();
        tbl_row.find("[id^='mapContainer']").show();
        tbl_row.find(".example").show();
        tbl_row.find(".coordenadas").hide();
        var inputranking = tbl_row.find('.inputranking');
        inputranking.prop("disabled", false);
        inputranking.prop("data-original", inputranking.prop("checked"));

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);
        updateBatchFloatingBar();
    });

    $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.removeClass('in-edition');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nomeLiga').css("pointer-events","auto");
        tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.fedPais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.demografia').show();
        tbl_row.find('.selecoes').show();
        tbl_row.find('.exportarplanilha').show();
        tbl_row.find('.importarplanilha').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();
        tbl_row.find("[id^='mapContainer']").hide();
        tbl_row.find(".example").hide();
        tbl_row.find(".coordenadas").show();
        var inputranking = tbl_row.find('.inputranking');
        inputranking.prop("checked", inputranking.prop("data-original"));
        inputranking.prop("disabled", true);

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
        updateBatchFloatingBar();
    });

    $('.salvar').click(function(){
        var tbl_row = $(this).closest('tr');
        saveCountryRowAjax(tbl_row).then(function(){
            updateBatchFloatingBar();
        }).catch(function(err){
            alert("Erro: " + err);
            updateBatchFloatingBar();
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
