<?php

session_start();

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
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

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


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <table class='table table-below float-table'>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome</td>
            <td class="td_inv input_nome_time"><input type='text' name='nome' class='form-control' /></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Capacidade</td>
            <td class="td_inv input_nome_time"><input type='number' name='capacidade' class='form-control' min='100'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Clima</td>
            <td class="td_inv input_nome_time">
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
            </td>


        </tr>
        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Altitude</td>
            <td class="td_inv input_nome_time checkbox_container">

            <input type="checkbox" class='custom-file-upload' name='altitude'>


            </td>
        </tr>
        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Caldeirão</td>
            <td class="td_inv input_nome_time checkbox_container">

            <input type="checkbox" class='custom-file-upload' name='caldeirao'>


            </td>
        </tr>


        <tr class="tr_inv">
            <td class="td_inv input_nome_time">País</td>
            <td class="td_inv input_nome_time">
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
            </td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="criar" class="btn">Inserir</button>
            </td>
        </tr>

    </table>
</form>

<?php

    } else {

    echo "Usuário sem permissão para criar estádios, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
