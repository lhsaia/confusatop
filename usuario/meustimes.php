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
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/times/criar_time.php';">Criar time</button>
<button id='importar_time' onclick="window.location='/times/importar_time.php';">Importar time</button>
<h2>Quadro de times - <?php echo $_SESSION['nomereal']?></h2>

<hr>
<div id='error_box'></div>

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

//query de times
$stmt = $time->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meustimes.php?";

    // count all products in the database to calculate total pages
    $total_rows = $time->countAll($_SESSION['user_id']);


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
            echo "<th width='10%'>Time</th>";
            echo "<th width='2%'>Escudo</th>";
            echo "<th width='2%'>Uniforme 1</th>";
            echo "<th width='2%'>Cores 1</th>";
            echo "<th width='2%'>Uniforme 2</th>";
            echo "<th width='2%'>Cores 2</th>";
            echo "<th width='15%'>Estadio</th>";
            echo "<th width='2%'>Max Torcida</th>";
            echo "<th width='2%'>Fidelidade</th>";
            echo "<th width='20%'>Liga</th>";
            echo "<th width='20%' class=''>País</th>";

            echo "<th width='5%' class=''>Opções</th>";


        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            //$escudo_imagem = explode(".",$Escudo);
            //$uniforme1_imagem = explode(".",$Uniforme1);
            //$uniforme2_imagem = explode(".",$Uniforme2);
            if($sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }



            echo "<tr id='".$ID."' data-sexo='".$sexo."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeEditavel' id='nom".$ID."'><a class='linkNome' href='/ligas/teamstatus.php?team={$ID}' >{$Nome}</a></span><span class=' {$genderClass} genderSign'>{$genderCode}</span></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/escudos/".$Escudo."' /> <input type='file' hidden id='escudo".$ID."' class='hiddenInput custom-file-upload' name='escudo' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/".$Uniforme1 . "' /> <input type='file' hidden id='uni1".$ID."' class='hiddenInput custom-file-upload' name='uni1' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td class='celula-uniforme'><div class='quadrado-uniforme' id='{$Uni1Cor1}'><input type='color' name='u1c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni1Cor2}'><input type='color' name='u1c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni1Cor3}'><input type='color' name='u1c3' hidden class='hiddenInput' /></div></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/".$Uniforme2. "' /> <input type='file' hidden id='uni2".$ID."' class='hiddenInput custom-file-upload' name='uni2' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td class='celula-uniforme'><div class='quadrado-uniforme' id='{$Uni2Cor1}'><input type='color' name='u2c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni2Cor2}'><input type='color' name='u2c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni2Cor3}'><input type='color' name='u2c3' hidden class='hiddenInput' /></div></td>";

                    echo "<td class='wide'><span class='nomePais' id='est".$ID."'>{$nomeEstadio} ({$capacidade})</span>";
                echo " <select class='comboEstadio editavel' id='selest{$estadioId}' hidden>  ";

                    for($i = 0; $i < count($listaEstadios);$i++){
                        echo "<option value='{$listaEstadios[$i][0]}'>{$listaEstadios[$i][1]} ({$listaEstadios[$i][2]})</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                   $maximoTorcedores = ($MaxTorcedores == 0? ">100000" : "<" . $MaxTorcedores);
                echo "<td><span class='maxTorcedores' id='max".$ID."'>{$maximoTorcedores}</span><select class='editavel inputHerdeiro comboTorcedores' name='maxTorcida' id='{$MaxTorcedores}' hidden>
                <option value='1000'>&lt;1000</option>
                <option value='2000'>&lt;2000</option>
                <option value='3000'>&lt;3000</option>
                <option value='4000'>&lt;4000</option>
                <option value='5000'>&lt;5000</option>
                <option value='6000'>&lt;6000</option>
                <option value='7000'>&lt;7000</option>
                <option value='8000'>&lt;8000</option>
                <option value='9000'>&lt;9000</option>
                <option value='10000'>&lt;10000</option>
                <option value='20000'>&lt;20000</option>
                <option value='30000'>&lt;30000</option>
                <option value='40000'>&lt;40000</option>
                <option value='50000'>&lt;50000</option>
                <option value='60000'>&lt;60000</option>
                <option value='70000'>&lt;70000</option>
                <option value='80000'>&lt;80000</option>
                <option value='90000'>&lt;90000</option>
                <option value='100000'>&lt;100000</option>
                <option selected value='0'>&gt;100000</option>
            </select></td>";
                echo "<td><span class='fidelidadeFixo'>{$Fidelidade}</span><input type='number' min='1' max='10' class=' fidelidade inputHerdeiro' value={$Fidelidade} id='fid".$ID."' hidden></td>";
                if($liga != 0){
                    echo "<td class='wide'><img src='/images/ligas/{$logo}' class='bandeira nomePais' id='log".$ID."'>  <span class='nomePais' id='lig".$ID."'>{$nomeLiga}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboLiga editavel ' id='sellig{$liga}' hidden>'  ";

                    for($i = 0; $i < count($listaLigas);$i++){
                        echo "<option value='{$listaLigas[$i][0]}'>{$listaLigas[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";

                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                $optionsString = "<td class='wide'>";

                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                    if($_SESSION['admin_status'] == '1' || $_SESSION['user_id'] === $idDonoPais){
                        $optionsString .= "<a id='edi".$ID."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$ID."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$ID."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        $optionsString .= "<a id='pro".$ID."' title='Promover 1 jogador da base' class='clickable promover'><i class='fas fa-hand-point-up inlineButton'></i></a>";
                    }
                    $optionsString .= "</td>";
                    echo $optionsString;
                }

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há times</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

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


//var cor = $();
//$('.text_box').css({ 'color' : color, });

</script>

<script>

$(document).ready(function() {

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

 });


</script>

<script>

var hexDigits = new Array
        ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");

function rgbToHex(rgb) {
    return "#" + hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
}

function hex(x) {
  return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
 }



//alert( rgbToHex(0, 51, 255) ); // #0033ff

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ?
        parseInt(result[1], 16).toString().padStart(3,'0').concat(parseInt(result[2], 16).toString().padStart(3,'0'),parseInt(result[3], 16).toString().padStart(3,'0'))
     : null;
}

//alert( hexToRgb("#0033ff").g ); // "51";


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

</script>


<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
