<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$liga = new Liga($db);
$pais = new Pais($db);

$page_title = "Inserir liga";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");



if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && !empty($_POST['pais']) && !empty($_POST['nome']) && !empty($_POST['tier'])){

    if((file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
    $logo_path = $_FILES['logo']['name'];
    $fileSize = $_FILES['logo']['size'];
    $filePath = $_FILES['logo']['tmp_name'];
    $tempVar = explode(".",$logo_path);
    $fileExt = strtolower(end($tempVar));
    $correct_extensions = array("png","jpg","jpeg");
    $upload_dir = "/images/ligas/";

    if($logo_path != "" && in_array($fileExt,$correct_extensions) && $fileSize <= 2000000){


        $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $logo_path;
        $result = move_uploaded_file($filePath, $upload_path);
            if (!$result) {
                $error_msg .= " Não foi possível salvar o logo no servidor.";
                $liga->logo = $liga->logoPadrao();
            } else {
                $liga->logo = $_SESSION['user_id'] ."-" .$logo_path;
            }

    } else {
        $liga->logo = $liga->logoPadrao();
        $error_msg .= " Mas ocorreu um aviso: o escudo não pôde ser enviado. ";
        if($fileSize > 2000000){
            $error_msg .= "A imagem enviada excede 2MB. ";
        }
        if($logo_path == ''){
            $error_msg .= "O nome do arquivo estava em branco. ";
        }
        if(in_array($fileExt,$correct_extensions) == false){
            $error_msg .= "A extensão (.".$fileExt.") não é permitida. Use JPG ou PNG.";
        }
    }

} else {
    $liga->logo = $liga->logoPadrao();
}
    // set product property values
    $liga->nome = $_POST['nome'];
    $liga->pais = $_POST['pais'];
    $liga->tier = $_POST['tier'];

    $liga->sexo = $_POST['sexo'];


    // create the product
    if($liga->inserir()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Liga inserida com sucesso. ".$error_msg."</div>";
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>".$error_msg."</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>".$error_msg.", campos em branco</div>";
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

        <label>Masculina/Feminina</label>
<?php
                // put them in a select drop-down
                echo "<select class='form-control' name='sexo'>";
                echo "<option value='0'>Masculina</option>";
                echo "<option value='1'>Feminina</option>";


                echo "</select>";

                ?>

        <label>Tier</label>
<?php
                // put them in a select drop-down
                echo "<select class='form-control' name='tier'>";
                echo "<option>Selecione tier...</option>";

                echo "<option value='1'>1 (primeira divisão)</option>";
                echo "<option value='2'>2 (segunda divisão)</option>";
                echo "<option value='3'>3 (terceira divisão)</option>";
                echo "<option value='4'>4 (quarta divisão)</option>";
                echo "<option value='5'>5 (quinta divisão)</option>";
                echo "<option value='6'>6 (sexta divisão)</option>";

                echo "</select>";

                ?>


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

        <label>Logo</label>
<input type="file" class='form-control custom-file-upload' name='logo' accept=".jpg,.png,.jpeg">

        <div style="margin-top: 15px;">
<button type="submit" name="criar" class="btn">Inserir</button>
</div>

    </form>
</div>
</div>

<?php

    } else {

    echo "Usuário sem permissão para editar ligas, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
