<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus climas - ".($_SESSION['nomereal'] ?? '');
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/ligas/criar_clima.php';">Criar clima</button>
<h2>Quadro de climas - <?php echo $_SESSION['nomereal']?></h2>
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
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$pais = new Pais($db);
$clima = new Clima($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

//queries de ligas e estadios

//query de estadios
$stmt = $clima->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meusclimas.php?";

    // count all products in the database to calculate total pages
    $total_rows = $clima->countAll($_SESSION['user_id']);


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
            echo "<th width='30%'>Clima</th>";
            echo "<th width='20%'>Temp. Verão</th>";
            echo "<th width='10%'>Estilo Verão</th>";
            echo "<th width='10%'>Temp. Outono</th>";
            echo "<th width='10%'>Estilo Outono</th>";
            echo "<th width='20%'>Temp. Inverno</th>";
            echo "<th width='10%'>Estilo Inverno</th>";
            echo "<th width='10%'>Temp. Primavera</th>";
            echo "<th width='10%'>Estilo Primavera</th>";
            echo "<th width='10%'>Hemisfério</th>";
            echo "<th width='20%'class='wide'>País</th>";
            echo "<th width='20%' class='wide'>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            echo "<tr id='".$ID."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeClima nomeEditavel' id='nom".$ID."'>{$Nome}</span></td>";
                echo "<td class='wide'><span class='nomeTempVer' id='tempver".$ID."'>{$TempVerao}</span>";
                echo "<select class='comboTempVer editavel ' id='{$TempVerao}' hidden>'  ";

                       echo "<option value='Muito Frio'>Muito Frio</option>";
                       echo "<option value='Frio'>Frio</option>";
                       echo "<option value='Normal'>Normal</option>";
                       echo "<option value='Quente'>Quente</option>";
                       echo "<option value='Muito Quente'>Muito Quente</option>";

                    echo "</select>";
                    echo "</td>";

                echo "<td class='wide'><span class='nomeEstOut' id='estver".$ID."'>{$EstiloVerao}</span>";
                echo "<select class='comboEstVer editavel ' id='{$EstiloVerao}' hidden>'  ";

                        echo "<option value='Neve Forte' data-season='1'>Neve Forte</option>";
                        echo "<option value='Neve' data-season='1'>Neve</option>";
                        echo "<option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>";
                        echo "<option value='Neblina' data-season='2'>Neblina</option>";
                        echo "<option value='Chuvoso' data-season='234'>Chuvoso</option>";
                        echo "<option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>";
                        echo "<option value='Equilibrado' data-season='3'>Equilibrado</option>";
                        echo "<option value='Seco' data-season='45'>Seco</option>";
                        echo "<option value='Árido' data-season='5'>Árido</option>";

                    echo "</select>";
                    echo "</td>";

                    echo "<td class='wide'><span class='nomeTempOut' id='tempout".$ID."'>{$TempOutono}</span>";
                    echo "<select class='comboTempOut editavel ' id='{$TempOutono}' hidden>'  ";

                           echo "<option value='Muito Frio'>Muito Frio</option>";
                           echo "<option value='Frio'>Frio</option>";
                           echo "<option value='Normal'>Normal</option>";
                           echo "<option value='Quente'>Quente</option>";
                           echo "<option value='Muito Quente'>Muito Quente</option>";

                        echo "</select>";
                        echo "</td>";

                    echo "<td class='wide'><span class='nomeEstOut' id='estout".$ID."'>{$EstiloOutono}</span>";
                    echo "<select class='comboEstOut editavel ' id='{$EstiloOutono}' hidden>'  ";

                            echo "<option value='Neve Forte' data-season='1'>Neve Forte</option>";
                            echo "<option value='Neve' data-season='1'>Neve</option>";
                            echo "<option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>";
                            echo "<option value='Neblina' data-season='2'>Neblina</option>";
                            echo "<option value='Chuvoso' data-season='234'>Chuvoso</option>";
                            echo "<option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>";
                            echo "<option value='Equilibrado' data-season='3'>Equilibrado</option>";
                            echo "<option value='Seco' data-season='45'>Seco</option>";
                            echo "<option value='Árido' data-season='5'>Árido</option>";

                        echo "</select>";
                        echo "</td>";

                        echo "<td class='wide'><span class='nomeTempInv' id='tempinv".$ID."'>{$TempInverno}</span>";
                        echo "<select class='comboTempInv editavel ' id='{$TempInverno}' hidden>'  ";

                               echo "<option value='Muito Frio'>Muito Frio</option>";
                               echo "<option value='Frio'>Frio</option>";
                               echo "<option value='Normal'>Normal</option>";
                               echo "<option value='Quente'>Quente</option>";
                               echo "<option value='Muito Quente'>Muito Quente</option>";

                            echo "</select>";
                            echo "</td>";

                        echo "<td class='wide'><span class='nomeEstInv' id='estinv".$ID."'>{$EstiloInverno}</span>";
                        echo "<select class='comboEstInv editavel ' id='{$EstiloInverno}' hidden>'  ";

                                echo "<option value='Neve Forte' data-season='1'>Neve Forte</option>";
                                echo "<option value='Neve' data-season='1'>Neve</option>";
                                echo "<option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>";
                                echo "<option value='Neblina' data-season='2'>Neblina</option>";
                                echo "<option value='Chuvoso' data-season='234'>Chuvoso</option>";
                                echo "<option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>";
                                echo "<option value='Equilibrado' data-season='3'>Equilibrado</option>";
                                echo "<option value='Seco' data-season='45'>Seco</option>";
                                echo "<option value='Árido' data-season='5'>Árido</option>";

                            echo "</select>";
                            echo "</td>";

                            echo "<td class='wide'><span class='nomeTempPri' id='temppri".$ID."'>{$TempPrimavera}</span>";
                            echo "<select class='comboTempPri editavel ' id='{$TempPrimavera}' hidden>'  ";

                                   echo "<option value='Muito Frio'>Muito Frio</option>";
                                   echo "<option value='Frio'>Frio</option>";
                                   echo "<option value='Normal'>Normal</option>";
                                   echo "<option value='Quente'>Quente</option>";
                                   echo "<option value='Muito Quente'>Muito Quente</option>";

                                echo "</select>";
                                echo "</td>";

                            echo "<td class='wide'><span class='nomeEstPri' id='estpri".$ID."'>{$EstiloPrimavera}</span>";
                            echo "<select class='comboEstPri editavel ' id='{$EstiloPrimavera}' hidden>'  ";

                                    echo "<option value='Neve Forte' data-season='1'>Neve Forte</option>";
                                    echo "<option value='Neve' data-season='1'>Neve</option>";
                                    echo "<option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>";
                                    echo "<option value='Neblina' data-season='2'>Neblina</option>";
                                    echo "<option value='Chuvoso' data-season='234'>Chuvoso</option>";
                                    echo "<option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>";
                                    echo "<option value='Equilibrado' data-season='3'>Equilibrado</option>";
                                    echo "<option value='Seco' data-season='45'>Seco</option>";
                                    echo "<option value='Árido' data-season='5'>Árido</option>";

                                echo "</select>";
                                echo "</td>";

                                if($Hemisferio == 1){
                                    $nomeHemisferio = "Norte";
                                } else {
                                    $nomeHemisferio = "Sul";
                                }
                                echo "<td class='wide'><span class='hemisferio' id='hem".$ID."'>{$nomeHemisferio}</span>";
                                echo "<select class='comboHem editavel ' id='{$Hemisferio}' hidden>'  ";

                                        echo "<option value='0'>Sul</option>";
                                        echo "<option value='1'>Norte</option>";

                                    echo "</select>";
                                    echo "</td>";

                echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                    $optionsString = "<td class='wide'>";

                        $optionsString .= "<a id='edi".$ID."' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        $optionsString .= "<a hidden id='sal".$ID."' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        $optionsString .= "<a hidden id='can".$ID."' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                        //$optionsString .= "<a id='del".$ID."' title='Deletar' class='clickable deletar'><i class='far fa-trash-alt inlineButton negative'></i></a>";
                    $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há climas</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

    $(document).ready(function() {

        const styleConstraints = {
            'Muito Frio': ['Neve', 'Neve Forte', 'Neve Ocasional'],
            'Frio': ['Chuvoso', 'Neblina', 'Ventos Fortes'],
            'Normal': ['Chuvoso', 'Equilibrado', 'Ventos Fortes'],
            'Quente': ['Chuvoso', 'Ventos Fortes', 'Seco'],
            'Muito Quente': ['Ventos Fortes', 'Seco', 'Árido']
        };

        function updateStyleOptions(tempSelect, styleSelect) {
            const selectedTemp = tempSelect.val();
            const validStyles = styleConstraints[selectedTemp] || [];
            const currentStyle = styleSelect.val();

            // Store the original options if not already stored
            if (!styleSelect.data('original-options')) {
                styleSelect.data('original-options', styleSelect.find('option').clone());
            }

            // Clear current options
            styleSelect.empty();

            // Add valid options
            const originalOptions = styleSelect.data('original-options');
            
            let foundCheck = false;

            originalOptions.each(function() {
                const optionVal = $(this).val();
                if (validStyles.includes(optionVal)) {
                    styleSelect.append($(this).clone());
                    if(optionVal == currentStyle){
                        foundCheck = true;
                    }
                }
            });

            // If the current style is no longer valid, select the first valid option
            if (foundCheck) {
                styleSelect.val(currentStyle);
            } else {
                 if(styleSelect.find('option').length > 0){
                     styleSelect.prop('selectedIndex', 0);
                 }
            }
        }

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
        tbl_row.find('.nomeTempVer').hide();
        tbl_row.find('.nomeEstVer').hide();
        tbl_row.find('.nomeTempOut').hide();
        tbl_row.find('.nomeEstOut').hide();
        tbl_row.find('.nomeTempInv').hide();
        tbl_row.find('.nomeEstInv').hide();
        tbl_row.find('.nomeTempPri').hide();
        tbl_row.find('.nomeEstPri').hide();
        tbl_row.find('.hemisferio').hide();
        
        // tbl_row.find('.newlogoedit').show();
        // tbl_row.find('.logoimage').hide();

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);
        
        var tempVerId = tbl_row.find('.comboTempVer').attr('id');
        var comboTempVer = tbl_row.find('.comboTempVer');
        var comboEstVer = tbl_row.find('.comboEstVer');
        comboTempVer.show().val(tempVerId);
         var estVerId = tbl_row.find('.comboEstVer').attr('id');
        comboEstVer.show().val(estVerId);
        updateStyleOptions(comboTempVer, comboEstVer);
       

        var tempOutId = tbl_row.find('.comboTempOut').attr('id');
        var comboTempOut = tbl_row.find('.comboTempOut');
        var comboEstOut = tbl_row.find('.comboEstOut');
        comboTempOut.show().val(tempOutId);
        var estOutId = tbl_row.find('.comboEstOut').attr('id');
        comboEstOut.show().val(estOutId);
        updateStyleOptions(comboTempOut, comboEstOut);
        
        
        var tempInvId = tbl_row.find('.comboTempInv').attr('id');
        var comboTempInv = tbl_row.find('.comboTempInv');
        var comboEstInv = tbl_row.find('.comboEstInv');
        comboTempInv.show().val(tempInvId);
        var estInvId = tbl_row.find('.comboEstInv').attr('id');
        comboEstInv.show().val(estInvId);
        updateStyleOptions(comboTempInv, comboEstInv);
        
        var tempPriId = tbl_row.find('.comboTempPri').attr('id');
        var comboTempPri = tbl_row.find('.comboTempPri');
        var comboEstPri = tbl_row.find('.comboEstPri');
        comboTempPri.show().val(tempPriId);
        var estPriId = tbl_row.find('.comboEstPri').attr('id');
        comboEstPri.show().val(estPriId);
        updateStyleOptions(comboTempPri, comboEstPri);

        var hemId = tbl_row.find('.comboHem').attr('id');
        tbl_row.find('.comboHem').show().val(hemId);

    });

    // Event listeners for Temp dropdown changes
    $(document).on('change', '.comboTempVer', function() {
        var tbl_row = $(this).closest('tr');
        updateStyleOptions($(this), tbl_row.find('.comboEstVer'));
    });
    $(document).on('change', '.comboTempOut', function() {
        var tbl_row = $(this).closest('tr');
        updateStyleOptions($(this), tbl_row.find('.comboEstOut'));
    });
    $(document).on('change', '.comboTempInv', function() {
        var tbl_row = $(this).closest('tr');
        updateStyleOptions($(this), tbl_row.find('.comboEstInv'));
    });
    $(document).on('change', '.comboTempPri', function() {
        var tbl_row = $(this).closest('tr');
        updateStyleOptions($(this), tbl_row.find('.comboEstPri'));
    });


        $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        // tbl_row.find('.nomeLiga').css("pointer-events","auto");
        // tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        
        tbl_row.find('.comboTempVer').hide();
        tbl_row.find('.nomeTempVer').show();
        tbl_row.find('.comboEstVer').hide();
        tbl_row.find('.nomeEstVer').show();
        
        tbl_row.find('.comboTempOut').hide();
        tbl_row.find('.nomeTempOut').show();
        tbl_row.find('.comboEstOut').hide();
        tbl_row.find('.nomeEstOut').show();
        
        tbl_row.find('.comboTempInv').hide();
        tbl_row.find('.nomeTempInv').show();
        tbl_row.find('.comboEstInv').hide();
        tbl_row.find('.nomeEstInv').show();
        
        tbl_row.find('.comboTempPri').hide();
        tbl_row.find('.nomeTempPri').show();
        tbl_row.find('.comboEstPri').hide();
        tbl_row.find('.nomeEstPri').show();
        
        tbl_row.find('.comboHem').hide();
        tbl_row.find('.hemisferio').show();
        
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        // tbl_row.find('.deletar').show();
        // tbl_row.find('.newlogoedit').hide();
        // tbl_row.find('.logoimage').show();

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        // tbl_row.find('.nomeLiga').css("pointer-events","auto");
        // tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        
        tbl_row.find('.comboTempVer').hide();
        tbl_row.find('.nomeTempVer').show();
        tbl_row.find('.comboEstVer').hide();
        tbl_row.find('.nomeEstVer').show();
        
        tbl_row.find('.comboTempOut').hide();
        tbl_row.find('.nomeTempOut').show();
        tbl_row.find('.comboEstOut').hide();
        tbl_row.find('.nomeEstOut').show();
        
        tbl_row.find('.comboTempInv').hide();
        tbl_row.find('.nomeTempInv').show();
        tbl_row.find('.comboEstInv').hide();
        tbl_row.find('.nomeEstInv').show();
        
        tbl_row.find('.comboTempPri').hide();
        tbl_row.find('.nomeTempPri').show();
        tbl_row.find('.comboEstPri').hide();
        tbl_row.find('.nomeEstPri').show();
        
        tbl_row.find('.comboHem').hide();
        tbl_row.find('.hemisferio').show();
        
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        // tbl_row.find('.deletar').show();
        // tbl_row.find('.newlogoedit').hide();
        // tbl_row.find('.logoimage').show();

        var id = tbl_row.attr('id');
        var nomeClima = tbl_row.find('#nom'+id).html();
        var tempVerao = tbl_row.find('.comboTempVer').val();
        var estiloVerao = tbl_row.find('.comboEstVer').val();
        
        var tempOutono = tbl_row.find('.comboTempOut').val();
        var estiloOutono = tbl_row.find('.comboEstOut').val();
        
        var tempInverno = tbl_row.find('.comboTempInv').val();
        var estiloInverno = tbl_row.find('.comboEstInv').val();
        
        var tempPrimavera = tbl_row.find('.comboTempPri').val();
        var estiloPrimavera = tbl_row.find('.comboEstPri').val();
        
        var hemisferio = tbl_row.find('.comboHem').val();
        
        var pais = tbl_row.find('.comboPais').val();

      
         var formData = new FormData();
         formData.append('id', id);
         formData.append('nomeClima', nomeClima);
         formData.append('tempVerao', tempVerao);
         formData.append('estiloVerao', estiloVerao);
         formData.append('tempOutono', tempOutono);
         formData.append('estiloOutono', estiloOutono);
         formData.append('tempInverno', tempInverno);
         formData.append('estiloInverno', estiloInverno);
         formData.append('tempPrimavera', tempPrimavera);
         formData.append('estiloPrimavera', estiloPrimavera);
         formData.append('hemisferio', hemisferio);
         formData.append('pais', pais);

        //console.log(formData);
         $.ajax({
             url: 'alterar_clima.php',
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
                      location.reload();
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
