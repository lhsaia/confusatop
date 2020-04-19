<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus climas - ".$_SESSION['nomereal'];
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

            echo "<tr id='".$id."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeClima nomeEditavel' id='nom".$id."'>{$Nome}</span></td>";
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

                        //$optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
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
    echo "<div class='alert alert-info'>Não há climas</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

//     $(document).ready(function() {

//          $('.editar').click(function(){
//         var tbl_row =  $(this).closest('tr');
//         tbl_row.find('span').each(function(index, val){
//             $(this).attr('original_entry', $(this).html());

//         });
//         tbl_row.find('.nomeEditavel').css("cursor","text");
//         tbl_row.find('.nomeLiga').css("cursor","text");
//         tbl_row.find('.nomeLiga').css("pointer-events","none");
//         tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
//         tbl_row.find('.salvar').show();
//         tbl_row.find('.cancelar').show();
//         tbl_row.find('.editar').hide();
//         tbl_row.find('.deletar').hide();
//         tbl_row.find('.nomePais').hide();
//         tbl_row.find('.newlogoedit').show();
//         tbl_row.find('.logoimage').hide();

//         var paisId = tbl_row.find('.comboPais').attr('id');
//         tbl_row.find('.comboPais').show().val(paisId);

//     });

//         $('.cancelar').click(function(){
//         var tbl_row =  $(this).closest('tr');
//         tbl_row.find('.nomeLiga').css("pointer-events","auto");
//         tbl_row.find('.nomeLiga').css("cursor","auto");
//         tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
//         tbl_row.find('.comboPais').hide();
//         tbl_row.find('.nomePais').show();
//         tbl_row.find('.salvar').hide();
//         tbl_row.find('.cancelar').hide();
//         tbl_row.find('.editar').show();
//         tbl_row.find('.deletar').show();
//         tbl_row.find('.newlogoedit').hide();
//         tbl_row.find('.logoimage').show();

//         tbl_row.find('span').each(function(index, val){
//             $(this).html($(this).attr('original_entry'));
//         });
//     });

//     $('.salvar').click(function(){
//         var tbl_row =  $(this).closest('tr');
//         tbl_row.find('.nomeLiga').css("pointer-events","auto");
//         tbl_row.find('.nomeLiga').css("cursor","auto");
//         tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
//         tbl_row.find('.comboPais').hide();
//         tbl_row.find('.nomePais').show();
//         tbl_row.find('.salvar').hide();
//         tbl_row.find('.cancelar').hide();
//         tbl_row.find('.editar').show();
//         tbl_row.find('.deletar').show();
//         tbl_row.find('.newlogoedit').hide();
//         tbl_row.find('.logoimage').show();

//         var id = tbl_row.attr('id');
//         var nomeLiga = tbl_row.find('#nom'+id).html();
//         var tierLiga = tbl_row.find('#tie'+id).html();
//         var pais = tbl_row.find('.comboPais').val();

//         var input = (tbl_row.find('#newlogo'+id))[0];
//         var logo;

//         if (input.files.length > 0) {
//            logo = input.files[0];
//         } else {
//            logo = null;
//         }

//         //var formId = 'form'+id;
//         //var form = document.getElementById(formId);
//          var formData = new FormData();
//          formData.append('id', id);
//          formData.append('nomeLiga', nomeLiga);
//          formData.append('tierLiga', tierLiga);
//          formData.append('pais', pais);
//          if(logo != null){
//             formData.append('logo', logo);
//          }


//     // for (var key of formData.entries()) {
//     //      console.log(key[0] + ', ' + key[1]);
//     //  }

//         //console.log(formData);
//          $.ajax({
//              url: 'alterar_liga.php',
//              processData: false,
//             contentType: false,
//             cache: false,
//             type: "POST",
//             dataType: 'json',
//              data: formData,
//                   success: function(data) {
//                       if(data.error != ''){
//                         alert(data.error)
//                       }
//                       location.reload();
//                   },
//                   error: function(data) {
//                       successmessage = 'Error';
//                       alert("Erro, o procedimento não foi realizado, tente novamente.");
//                       location.reload();
//                   }
//               });
//      });

// });



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
