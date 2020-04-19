<?php

session_start();

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);

$pais->id = $_GET['idTime'];

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Inserir fatia demográfica";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && $pais->checarDono($pais->id,$_SESSION['user_id'])){

    $error_msg = '';


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    
    print_r($POST);
if(isset($_POST['origem']) && isset($_POST['fatorPercentual']) ){

    // set product property values
    $origem = $_POST['origem'];
    $fatorPercentual = (int)$_POST['fatorPercentual'];
    $ocorrenciaNomeDuplo = (int)$_POST['ocorrenciaNomeDuplo'];
    $indiceMiscigenacao = (int)$_POST['indiceMiscigenacao'];
    if($fatorPercentual > 100){
        $fatorPercentual = 100;
    }

    if($fatorPercentual < 1){
        $fatorPercentual = 1;
    }
    if($ocorrenciaNomeDuplo > 100){
        $ocorrenciaNomeDuplo = 100;
    }

    if($ocorrenciaNomeDuplo < 0){
        $ocorrenciaNomeDuplo = 0;
    }
    if($indiceMiscigenacao > 100){
        $indiceMiscigenacao = 100;
    }

    if($indiceMiscigenacao < 0){
        $indiceMiscigenacao = 0;
    }

    if($_POST['nomeOuSobrenome'] > 10){
        $nome = 1;
        $sobrenome = 1;
    } else if ($_POST['nomeOuSobrenome'] == 10){
        $sobrenome = 0;
        $nome = 1;
    } else {
        $nome = 0;
        $sobrenome = 1;
    }

    // create the product
    if($pais->novaDemografia($pais->id, $nome, $sobrenome, $origem, $fatorPercentual, $ocorrenciaNomeDuplo, $indiceMiscigenacao)){

        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Fatia demográfica inserida com sucesso. ".$error_msg."</div>";
    }
    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>".$error_msg."</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>".$error_msg." Campos em branco</div>";
}
}
?>

<script type="application/javascript">
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 600);
    }
}
</script>


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF'] . "?idTime=" . $pais->id ?>'>

    <table class='table table-below float-table'>

    <tr class="tr_inv">
            <td class="td_inv input_nome_time">Origem</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $pais->listaOrigens();

                // put them in a select drop-down
                echo "<select class='form-control' id='origem' name='origem'>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$ID}' data-nomeMasc='{$nomeM}' data-sobrenomeMasc='{$sobrenomeM}'>{$Origem}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Fator percentual (%)</td>
            <td class="td_inv input_nome_time fatorPercentual">

            <input type="number" class='form-control inputHerdeiro' min='1' max='100' value='10' name='fatorPercentual'>

            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Ocorrência de nome duplo (%)</td>
            <td class="td_inv input_nome_time ocorrenciaNomeDuplo">

            <input type="number" class='form-control inputHerdeiro' min='0' max='100' value='0' name='ocorrenciaNomeDuplo'>

            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Índice de miscigenação (%)</td>
            <td class="td_inv input_nome_time indiceMiscigenacao">

            <input type="number" class='form-control inputHerdeiro' min='0' max='100' value='0' id='indiceMiscigenacao'  name='indiceMiscigenacao'>

            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome ou Sobrenome</td>
            <td class="td_inv input_nome_time">
                <?php
                // put them in a select drop-down
                echo "<select class='form-control' id='nomeOuSobrenome' name='nomeOuSobrenome'>";
                echo "<option id='opcaoNome' value='10'>Nome apenas</option>";
                echo "<option id='opcaoSobrenome'  value='1'>Sobrenome apenas</option>";
                echo "<option id='opcaoAmbos' value='11'>Ambos</option>";
                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
            <input type='hidden' name='pais' value='<?php echo $pais->id ?>'/>
                <button type="submit" name="criar" class="btn">Inserir</button>
            </td>
        </tr>

    </table>
</form>

<script>

$("#origem").on("change",function(){
    //console.log("Inicio");
    var temNomeMasc = $('option:selected', this).attr("data-nomeMasc");
    var temSobrenomeMasc = $('option:selected', this).attr("data-sobrenomeMasc");
    //console.log(temNomeMasc);
    //console.log(temSobrenomeMasc);
    if(temNomeMasc < 1){
        $("#opcaoNome").hide();
    } else {
        $("#opcaoNome").show();
        $("#nomeOuSobrenome").val(10).change();
    }
    if(temSobrenomeMasc < 1){
        $("#opcaoSobrenome").hide();
    } else {
        $("#opcaoSobrenome").show();
        $("#nomeOuSobrenome").val(1).change();
    }

    if(temNomeMasc >= 1 && temSobrenomeMasc >= 1){
        $("#opcaoAmbos").show();
        $("#nomeOuSobrenome").val(11).change();
    } else {
        $("#opcaoAmbos").hide();
    }


});

$("#nomeOuSobrenome").val(11);

$("#nomeOuSobrenome").on("change", function(){
    if($(this).val() != 11){
      console.log(10);
        $("#indiceMiscigenacao").val(100);
        $("#indiceMiscigenacao").prop("readonly", true).change();
    } else {
        $("#indiceMiscigenacao").val(0);
        $("#indiceMiscigenacao").prop("readonly", false).change();
    }
});


</script>

<?php

    } else {

    echo "Usuário sem permissão para inserir demografia nesse país, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
