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

            echo "<tr id='".$id."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeEstadio nomeEditavel' id='nom".$id."'>{$Nome}</span></td>";
                echo "<td><span class='capacidade nomeEditavel' id='cap".$id."'>{$Capacidade}</span></td>";

                echo "<td class='wide'><span class='nomeClima' id='cli".$ID."'>{$nomeClima}</span>";
                echo " <select class='comboClima editavel ' id='{$Clima}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaClimas);$i++){
                        echo "<option value='{$listaClimas[$i][0]}'>{$listaClimas[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                echo "<td><input type='checkbox' class='altitude' id='alt".$id."' ". ($Altitude == 1? 'checked' : '')." disabled></td>";
                echo "<td><input type='checkbox' class='caldeirao' id='cal".$id."' ". ($Caldeirao == 1? 'checked' : '')." disabled></td>";

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
    echo "<div class='alert alert-info'>Não há estádios</div>";
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
