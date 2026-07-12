<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$usuario = new Usuario($db);



$page_title = "Inserir país";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");



if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';
	//ver se é período de testes ou não
$emTestes = $usuario->emTestes($_SESSION['user_id']);

// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && !empty($_POST['sigla']) && !empty($_POST['nome']) ){
	


    $logo_path = $_FILES['bandeira']['name'];
    $fileSize = $_FILES['bandeira']['size'];
    $filePath = $_FILES['bandeira']['tmp_name'];
    $extension = explode(".",$logo_path);
    $file_ext = isset($extension[1]) ? $extension[1] : '';
    $correct_extensions = array("png","jpg","jpeg");
    $upload_dir = "/images/bandeiras/";
    $pais->bandeira = "flag.png";
    // set product property values
    $pais->nome = $_POST['nome'];
    $pais->sigla = $_POST['sigla'];
	
	if(isset($_POST['federacao'])) {
        $pais->federacao = $_POST['federacao'];
    } else {
        $pais->federacao = 0;
    }
	
    $pais->dono = $_SESSION['user_id'];
    if(isset($_POST['ranking'])) {
        $pais->ranqueado = 0;
    } else {
        $pais->ranqueado = 1;
    }

    // create the product
    if($pais->inserir()){
        $idPais = $db->lastInsertId();
        if($logo_path != "" && substr_count($logo_path,".")==1 && in_array($file_ext,$correct_extensions) && $fileSize <= 12000){


            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir . $pais->sigla . "." . $file_ext;
            $result = move_uploaded_file($filePath, $upload_path);
                if (!$result) {
                    $error_msg .= "Não foi possível inserir a bandeira, erro na inserção.";
                } else {
                    $bandeiraAtualizada = $pais->sigla . "." . $file_ext;

                    if($pais->atualizarBandeira($idPais, $bandeiraAtualizada)){

                    } else {
                        $error_msg .= "Não foi possível inserir a bandeira, erro na vinculação.";
                    }
                }

        } else {
            $error_msg .= "Mas não foi possível inserir a bandeira. ";
            if($fileSize > 8000){
                $error_msg .= "Arquivo deve ser menor que 12kb.";
            }
            if($logo_path == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(substr_count($logo_path,".") > 1){
                $error_msg .= "Nome do arquivo não pode conter pontos além da extensão.";
            }
            if(in_array($file_ext,$correct_extensions) == false){
                $error_msg .= "Extensão ".$file_ext." não é permitida.";
            }
        }


        $usuario->atualizarAlteracao($_SESSION['user_id']);
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Pais inserido com sucesso. ".$error_msg."</div>";
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


<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>
<div id='errorbox'></div>
<div>
<div id='inscricao'>

<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    

        <label>Nome</label>
<input type='text' name='nome' class='form-control' />

        <label for="sigla">Sigla</label>
<input type='text' maxlength='3' id='sigla' name='sigla' class='form-control' />

        <label>Bandeira</label>
<input type="file" class='form-control custom-file-upload' name='bandeira' accept=".jpg,.png,.jpeg">
		
		<?php
		if(!$emTestes){
		?>

        <label>É membro da CONFUSA?</label>
<input type="checkbox" class='custom-file-upload' name='ranking'>
		
		
        <label>Federação</label>
<?php
                // put them in a select drop-down
                echo "<select class='form-control' name='federacao'>";
                echo "<option selected value='0'>Sem federação</option>";

                echo "<option value='1'>FEASCO</option>";
                echo "<option value='2'>FEMIFUS</option>";
                echo "<option value='3'>COMPACTA</option>";

                echo "</select>";

                ?>
		
		<?php
		}
		?>

        <div style="margin-top: 15px;">
<button type="submit" name="criar" class="btn">Inserir</button>
</div>

    </form>
</div>
</div>

<?php

    } else {

    echo "Usuário sem permissão para inserir países, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
