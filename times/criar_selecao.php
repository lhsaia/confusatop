<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$time = new Time($db);
$estadio = new Estadio($db);
$liga = new Liga($db);
$tecnico = new Tecnico($db);
$codigoPais = $_GET['idPais'];
$donoLogado = $pais->checarDono($codigoPais, $_SESSION['user_id']);

$page_title = "Criar Seleção";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && $donoLogado == true){

    $error_msg = '';


// se jogador foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['categoria']) && isset($_POST['sigla']) && $_POST['pais'] != 0){

    $codigoPais = $_POST['pais'];
    $stmtPais = $pais->readInfo($codigoPais);
    $resultPais = $stmtPais->fetch(PDO::FETCH_ASSOC);
    $nomePais = $resultPais['nome'];



    // atributos basicos dos jogadores
    $time->nome = $nomePais;
    $time->sigla = $_POST['sigla'];
    $time->estadio = $_POST['estadio'];
    $time->status = $_POST['categoria'];

    //cores

    function hexToRgb($hex){
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return str_pad($r, 3, "0", STR_PAD_LEFT) . str_pad($g, 3, "0", STR_PAD_LEFT) . str_pad($b, 3, "0", STR_PAD_LEFT);
    }

    $time->uniforme1cor1 = hexToRgb($_POST['cor1uni1']);
    $time->uniforme1cor2 = hexToRgb($_POST['cor2uni1']);
    $time->uniforme1cor3 = hexToRgb($_POST['cor3uni1']);
    $time->uniforme2cor1 = hexToRgb($_POST['cor1uni2']);
    $time->uniforme2cor2 = hexToRgb($_POST['cor2uni2']);
    $time->uniforme2cor3 = hexToRgb($_POST['cor3uni2']);
    $time->maxTorcedores = $_POST['maxTorcida'];
    $time->fidelidade = $_POST['fidelidade'];
    $time->pais = $_POST['pais'];
    $time->liga = -1;
    $sexo = $_POST['sexo'];
    $time->sexo = $sexo;

    if($time->status == 2){
        $time->nome = $time->nome . " [U21]";
    } else if($time->status == 3){
        $time->nome = $time->nome . " [U20]";
    } else if($time->status == 4){
        $time->nome = $time->nome . " [U18]";
    }

    if($sexo == 1){
        $time->nome = $time->nome . " (F)";
    }

    //recebimento arquivos
    $new_logo_path = null;

    if(file_exists($_FILES['escudo']['tmp_name']) || is_uploaded_file($_FILES['escudo']['tmp_name'])){
        $fileName = $_FILES['escudo']['name'];
        $fileSize = $_FILES['escudo']['size'];
        $filePath = $_FILES['escudo']['tmp_name'];
        $fileType = $_FILES['escudo']['type'];
        $tempVar = explode('.',$fileName);
        $fileExt = strtolower( end($tempVar));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/escudos/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 100000){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          $result = move_uploaded_file($filePath, $upload_path);
              if (!$result) {
                  $error_msg .= "Não foi possível inserir o escudo, erro na inserção.";
                  $time->escudo = $time->escudoPadrao();
              } else {
                  $time->escudo = $_SESSION['user_id'] ."-" .$fileName;
              }

        } else {
            $time->escudo = $time->escudoPadrao();
            $error_msg .= "Não foi possível inserir o escudo. ";
            if($fileSize > 100000){
                $error_msg .= "Arquivo deve ser menor que 100kb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    } else {
        $time->escudo = $time->escudoPadrao();
    }

    if(file_exists($_FILES['uni1']['tmp_name']) || is_uploaded_file($_FILES['uni1']['tmp_name'])){
        $fileName = $_FILES['uni1']['name'];
        $fileSize = $_FILES['uni1']['size'];
        $filePath = $_FILES['uni1']['tmp_name'];
        $fileType = $_FILES['uni1']['type'];
        $tempVar = explode('.',$fileName);
        $fileExt = strtolower( end($tempVar));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/uniformes/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 100000){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          $result = move_uploaded_file($filePath, $upload_path);
              if (!$result) {
                  $error_msg .= "Não foi possível inserir o uniforme, erro na inserção.";
                  $time->uniforme1 = $time->uniforme1Padrao();
              } else {
                  $time->uniforme1 = $_SESSION['user_id'] ."-" .$fileName;
              }

        } else {
            $time->uniforme1 = $time->uniforme1Padrao();
            $error_msg .= "Não foi possível inserir o uniforme 1. ";
            if($fileSize > 100000){
                $error_msg .= "Arquivo deve ser menor que 100kb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    } else {
        $time->uniforme1 = $time->uniforme1Padrao();
    }

    if(file_exists($_FILES['uni2']['tmp_name']) || is_uploaded_file($_FILES['uni2']['tmp_name'])){
        $fileName = $_FILES['uni2']['name'];
        $fileSize = $_FILES['uni2']['size'];
        $filePath = $_FILES['uni2']['tmp_name'];
        $fileType = $_FILES['uni2']['type'];
        $tempVar = explode('.',$fileName);
        $fileExt = strtolower( end($tempVar));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/uniformes/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 100000){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          $result = move_uploaded_file($filePath, $upload_path);
              if (!$result) {
                  $error_msg .= "Não foi possível inserir o uniforme, erro na inserção.";
                  $time->uniforme2 = $time->uniforme2Padrao();
              } else {
                  $time->uniforme2 = $_SESSION['user_id'] ."-" .$fileName;
              }


        } else {
          $time->uniforme2 = $time->uniforme2Padrao();
            $error_msg .= "Não foi possível inserir o uniforme 2. ";
            if($fileSize > 100000){
                $error_msg .= "Arquivo deve ser menor que 100kb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    } else {
        $time->uniforme2 = $time->uniforme2Padrao();
    }


    //echo $error_msg;


    //create the product
   if($time->create()){

    echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Seleção inserida com sucesso!</div>";

    } else {
     echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Houve erro na inserção da seleção!</div>";
    }

}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir o time, campos em branco!</div>";
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


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['REQUEST_URI']; ?>'>

    <table class='table table-below float-table'>

    <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Categoria</td>
            <td class="td_inv input_nome_time"><select class='form-control' name='categoria' id='categoria'>
                <option selected value='1'>Principal</option>
                <option value='2'>Olímpica</option>
                <option value='3'>Sub-20</option>
                <option value='4'>Sub-18</option>
            </select></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Sigla</td>
            <td class="td_inv input_nome_time"><input type='text' maxlength="3" name='sigla' id='sigla' class='form-control inputHerdeiro' /></td>
        </tr>


        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Escudo</td>
            <td class="td_inv input_nome_time">

            <input type="file" class='form-control custom-file-upload' name='escudo' accept=".jpg,.png,.jpeg">


            </td>
        </tr>

        <tr class="tr_inv  spec_height">
            <td class="td_inv input_nome_time">Uniforme titular</td>
            <td class="td_inv input_nome_time">

            <input type="file" class='form-control custom-file-upload' name='uni1' accept=".jpg,.png,.jpeg">


            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Cores uniforme titular</td>
            <td class="td_inv input_nome_time">

            <input type="color" name='cor1uni1'>
            <input type="color" name='cor2uni1' value='#ffffff'>
            <input type="color" name='cor3uni1'>

            </td>
        </tr>

        <tr class="tr_inv  spec_height">
            <td class="td_inv input_nome_time">Uniforme reserva</td>
            <td class="td_inv input_nome_time">

            <input type="file" class='form-control custom-file-upload' name='uni2' accept=".jpg,.png,.jpeg">


            </td>
        </tr>

        <tr class="tr_inv  spec_height">
            <td class="td_inv input_nome_time">Cores uniforme reserva</td>
            <td class="td_inv input_nome_time">

            <input type="color" name='cor1uni2' value='#ffffff'>
            <input type="color" name='cor2uni2'>
            <input type="color" name='cor3uni2' value='#ffffff'>

            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Máx. Torcida</td>
            <td class="td_inv input_nome_time"><select class='form-control' name='maxTorcida' id='maxTorcida'>
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
            </select></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Fidelidade</td>
            <td class="td_inv input_nome_time"><input type='number' id='fidelidade' value='5' max='10' min='1' name='fidelidade' class='form-control inputHerdeiro' /></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Masc/Fem</td>
            <td class="td_inv input_nome_time">

                <select class='form-control' id='sexo' name='sexo'>
                <option selected value='0'>Masculino</option>
                <option value='1'>Feminino</option>
                </select>

            </td>
        </tr>


        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Estádio</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $estadio->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' id='estadio'  name='estadio'>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}' data-pais='{$Pais}'>{$nome} ({$capacidade})</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                  <input type='hidden' name='pais' value='<?php echo $_GET['idPais'] ?>'>
                <button type="submit" name="criar" class="btn" value="0">Inserir</button>
                <button type="reset" name="reset" class="btn">Limpar</button>
            </td>
        </tr>

    </table>
</form>

  <script>

  $(function () {
  $("#fidelidade").keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });
  $("#fidelidade").keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1));
    else
      $(this).val($(this).data("old"));
  });
});

  </script>

<?php

    } else {

    echo "Usuário sem permissão para criar seleções, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
