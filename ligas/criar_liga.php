<?php

session_start();

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
if(isset($_POST['nome']) && !empty($_POST['pais']) && !empty($_POST['nome']) && !empty($_POST['tier'])){

    if((file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
    $logo_path = $_FILES['logo']['name'];
    $fileSize = $_FILES['logo']['size'];
    $filePath = $_FILES['logo']['tmp_name'];
    $extension = explode(".",$logo_path);
    $correct_extensions = array("png","jpg","jpeg");
    $upload_dir = "/images/ligas/";

    if($logo_path != "" && substr_count($logo_path,".")==1 && in_array($extension[1],$correct_extensions) && $fileSize <= 100000){


        $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $logo_path;
        $result = move_uploaded_file($filePath, $upload_path);
            if (!$result) {
                $error_msg .= "Não foi possível inserir o logo, erro na inserção.";
                $liga->logo = $liga->logoPadrao();
            } else {
                $liga->logo = $_SESSION['user_id'] ."-" .$logo_path;
            }

    } else {
        $error_msg .= "Mas não foi possível inserir o logo. ";
        if($fileSize > 100000){
            $error_msg .= "Arquivo deve ser menor que 100kb.";
        }
        if($logo_path == ''){
            $error_msg .= "Falha no nome do arquivo.";
        }
        if(substr_count($logo_path,".") > 1){
            $error_msg .= "Nome do arquivo não pode conter pontos além da extensão.";
        }
        if(in_array($extension[1],$correct_extensions) == false){
            $error_msg .= "Extensão ".$extension[1]." não é permitida.";
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


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <table class='table table-below float-table'>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome</td>
            <td class="td_inv input_nome_time"><input type='text' name='nome' class='form-control' /></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Masculina/Feminina</td>
            <td class="td_inv input_nome_time">
            <?php
                // put them in a select drop-down
                echo "<select class='form-control' name='sexo'>";
                echo "<option value='0'>Masculina</option>";
                echo "<option value='1'>Feminina</option>";


                echo "</select>";

                ?>

            </td>


        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Tier</td>
            <td class="td_inv input_nome_time">
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

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Logo</td>
            <td class="td_inv input_nome_time">

            <input type="file" class='form-control custom-file-upload' name='logo' accept=".jpg,.png,.jpeg">


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

    echo "Usuário sem permissão para editar ligas, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
