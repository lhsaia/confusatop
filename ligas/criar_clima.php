<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$clima = new Clima($db);
$pais = new Pais($db);
$usuario = new Usuario($db);

$page_title = "Inserir Clima";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");



if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && !empty($_POST['nome'])){

    // set product property values
    $clima->nome = $_POST['nome'];
    $clima->tempVerao = $_POST['tempVerao'];
    $clima->estiloVerao = $_POST['estVerao'];
    $clima->tempOutono = $_POST['tempOutono'];
    $clima->estiloOutono = $_POST['estOutono'];
    $clima->tempInverno = $_POST['tempInverno'];
    $clima->estiloInverno = $_POST['estInverno'];
    $clima->tempPrimavera = $_POST['tempPrimavera'];
    $clima->estiloPrimavera = $_POST['estPrimavera'];
    $clima->hemisferio = $_POST['hemisferio'];
    $clima->pais = $_POST['pais'];

    // create the product
    if($clima->create()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Clima inserido com sucesso. ".$error_msg."</div>";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o clima. ".$error_msg."</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o clima, campos em branco</div>";
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


<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>
<div id='errorbox'></div>
<div>
<div id='inscricao'>

<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    

        <label>Nome</label>
<input type='text' name='nome' class='form-control' />

        <label for="tempVerao">Temp. Verão</label>
<select class='form-control' name='tempVerao' id='tempVerao'>
                    <option value='Muito Frio' data-season='1'>Muito Frio</option>
                    <option value='Frio'  data-season='2'>Frio</option>
                    <option selected value='Normal'  data-season='3'>Normal</option>
                    <option value='Quente'  data-season='4'>Quente</option>
                    <option value='Muito Quente'  data-season='5'>Muito Quente</option>
                </select>

        <label for="estVerao">Estilo Verão</label>
<select class='form-control' name='estVerao' id='estVerao'>
                    <option value='Neve Forte' data-season='1'>Neve Forte</option>
                    <option value='Neve' data-season='1'>Neve</option>
                    <option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>
                    <option value='Neblina' data-season='2'>Neblina</option>
                    <option value='Chuvoso' data-season='234'>Chuvoso</option>
                    <option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>
                    <option selected value='Equilibrado' data-season='3'>Equilibrado</option>
                    <option value='Seco' data-season='45'>Seco</option>
                    <option value='Árido' data-season='5'>Árido</option>
                </select>

        <label for="tempOutono">Temp. Outono</label>
<select class='form-control' name='tempOutono' id='tempOutono'>
                <option value='Muito Frio' data-season='1'>Muito Frio</option>
                    <option value='Frio'  data-season='2'>Frio</option>
                    <option selected value='Normal'  data-season='3'>Normal</option>
                    <option value='Quente'  data-season='4'>Quente</option>
                    <option value='Muito Quente'  data-season='5'>Muito Quente</option>
                </select>

        <label for="estOutono">Estilo Outono</label>
<select class='form-control' name='estOutono' id='estOutono'>
                    <option value='Neve Forte' data-season='1'>Neve Forte</option>
                    <option value='Neve' data-season='1'>Neve</option>
                    <option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>
                    <option value='Neblina' data-season='2'>Neblina</option>
                    <option value='Chuvoso' data-season='234'>Chuvoso</option>
                    <option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>
                    <option selected value='Equilibrado' data-season='3'>Equilibrado</option>
                    <option value='Seco' data-season='45'>Seco</option>
                    <option value='Árido' data-season='5'>Árido</option>
                </select>
        <label for="tempInverno">Temp. Inverno</label>
<select class='form-control' name='tempInverno' id='tempInverno'>
                    <option value='Muito Frio' data-season='1'>Muito Frio</option>
                    <option value='Frio'  data-season='2'>Frio</option>
                    <option selected value='Normal'  data-season='3'>Normal</option>
                    <option value='Quente'  data-season='4'>Quente</option>
                    <option value='Muito Quente'  data-season='5'>Muito Quente</option>
                </select>

        <label for="estInverno">Estilo Inverno</label>
<select class='form-control' name='estInverno' id='estInverno'>
                    <option value='Neve Forte' data-season='1'>Neve Forte</option>
                    <option value='Neve' data-season='1'>Neve</option>
                    <option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>
                    <option value='Neblina' data-season='2'>Neblina</option>
                    <option value='Chuvoso' data-season='234'>Chuvoso</option>
                    <option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>
                    <option selected value='Equilibrado' data-season='3'>Equilibrado</option>
                    <option value='Seco' data-season='45'>Seco</option>
                    <option value='Árido' data-season='5'>Árido</option>
                </select>
        <label for="tempPrimavera">Temp. Primavera</label>
<select class='form-control' name='tempPrimavera' id='tempPrimavera'>
                    <option value='Muito Frio' data-season='1'>Muito Frio</option>
                    <option value='Frio'  data-season='2'>Frio</option>
                    <option selected value='Normal'  data-season='3'>Normal</option>
                    <option value='Quente'  data-season='4'>Quente</option>
                    <option value='Muito Quente'  data-season='5'>Muito Quente</option>
                </select>

        <label for="estPrimavera">Estilo Primavera</label>
<select class='form-control' name='estPrimavera' id='estPrimavera'>
                    <option value='Neve Forte' data-season='1'>Neve Forte</option>
                    <option value='Neve' data-season='1'>Neve</option>
                    <option value='Neve Ocasional' data-season='1'>Neve Ocasional</option>
                    <option value='Neblina' data-season='2'>Neblina</option>
                    <option value='Chuvoso' data-season='234'>Chuvoso</option>
                    <option value='Ventos Fortes' data-season='2345'>Ventos Fortes</option>
                    <option selected value='Equilibrado' data-season='3'>Equilibrado</option>
                    <option value='Seco' data-season='45'>Seco</option>
                    <option value='Árido' data-season='5'>Árido</option>
                </select>

        <label>Hemisfério</label>
<select class='form-control' name='hemisferio'>
                    <option selected value='0'>Sul</option>
                    <option value='1'>Norte</option>
                </select>

        <label>País</label>
<?php
                // ler times do banco de dados
                $stmt = $pais->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' name='pais'>";
                echo "<option>Selecione país...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>

        <div style="margin-top: 15px;">
<button type="submit" name="criar" class="btn">Inserir</button>
</div>

    </form>
</div>
</div>

<script>

$(document).ready(function(){
    $("select[id^=temp]").each(function(){
        updateDropdown(this);
    });

});


$("select[id^=temp]").on("change", function(){

    updateDropdown(this);
});

function updateDropdown(target){
    var seasonName = $(target).attr("id").slice(4);
    var tempSeasonCode = $("option:selected", target).attr("data-season").toString();
    $("#est"+seasonName+" option").each(function(){
        var estSeasonCode = $(this).attr("data-season").toString();
        if(estSeasonCode.includes(tempSeasonCode)){
            $(this).show().attr("selected", "selected");
        } else {
            $(this).hide();
        }
    });
}

</script>

<?php

    } else {

    echo "Usuário sem permissão para criar climas, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
