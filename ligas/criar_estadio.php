<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$estadio = new Estadio($db);
$clima = new Clima($db);
$pais = new Pais($db);
$usuario = new Usuario($db);

$page_title = "Inserir Estádio";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");



if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && !empty($_POST['pais']) && !empty($_POST['clima']) && !empty($_POST['nome']) && !empty($_POST['capacidade'])){

    // set product property values
    $estadio->nome = $_POST['nome'];
    $estadio->capacidade = $_POST['capacidade'];
    $estadio->clima = $_POST['clima'];
    $estadio->pais = $_POST['pais'];

    if(isset($_POST['altitude'])) {
        $estadio->altitude = 1;
    } else {
        $estadio->altitude = 0;
    }

    if(isset($_POST['caldeirao'])) {
        $estadio->caldeirao = 1;
    } else {
        $estadio->caldeirao = 0;
    }

    // create the product
    if($estadio->create()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Estádio inserido com sucesso. ".$error_msg."</div>";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o estádio. ".$error_msg."</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o estádio, campos em branco</div>";
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

        <label>Capacidade</label>
<input type='number' name='capacidade' class='form-control' min='100'/>

        <label>Clima</label>
<?php
                // ler times do banco de dados
                $stmt = $clima->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' name='clima'>";
                echo "<option>Selecione clima...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$ID}'>{$Nome}</option>";
                }

                echo "</select>";
                ?>
        <label>Altitude</label>
<input type="checkbox" class='custom-file-upload' name='altitude'>
        <label>Caldeirão</label>
<input type="checkbox" class='custom-file-upload' name='caldeirao'>


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

<?php

    } else {

    echo "Usuário sem permissão para criar estádios, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
