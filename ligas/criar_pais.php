<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$usuario = new Usuario($db);

function imageImporterWebP($file_name, $target_filename){
    $maxDim = 100;
    list($width, $height, $type, $attr) = getimagesize( $file_name );
    if ( $width > $maxDim || $height > $maxDim ) {
        $ratio = $width/$height;
        if( $ratio > 1) {
            $new_width = $maxDim;
            $new_height = $maxDim/$ratio;
        } else {
            $new_width = $maxDim*$ratio;
            $new_height = $maxDim;
        }
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    if($type == IMAGETYPE_PNG){
        $compressed_png_content = compress_png($file_name);
        $src = imagecreatefromstring($compressed_png_content);
    } else if ($type == 18 || $type == "") {
        $src = imagecreatefromwebp($file_name);
    } else {
        try {
            $src = imagecreatefromstring( file_get_contents( $file_name ) );
        } catch (Exception $e) {
            $src = imagecreatefromwebp($file_name);
        }
    }
    $dst = imagecreatetruecolor( $new_width, $new_height );
    $background = imagecolorallocate($dst , 0, 0, 0);
    imagecolortransparent($dst, $background);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
    imagedestroy( $src );
    imagewebp($dst, $target_filename);
    imagedestroy( $dst );
}



$page_title = "Inserir país";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_pais_redesign';
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
    $correct_extensions = array("png","jpg","jpeg","webp");
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
        if($logo_path != "" && substr_count($logo_path,".")==1 && in_array(strtolower($file_ext),$correct_extensions) && $fileSize <= 3000000){

            $new_logo_path = $pais->sigla . ".webp";
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $new_logo_path;
            
            if(file_exists($upload_path)){
                unlink($upload_path);
            }

            imageImporterWebP($filePath, $upload_path);
            $bandeiraAtualizada = $new_logo_path;

            if($pais->atualizarBandeira($idPais, $bandeiraAtualizada)){

            } else {
                $error_msg .= "Não foi possível inserir a bandeira, erro na vinculação.";
            }

        } else {
            $error_msg .= "Mas não foi possível inserir a bandeira. ";
            if($fileSize > 3000000){
                $error_msg .= "Arquivo deve ser menor que 3MB.";
            }
            if($logo_path == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(substr_count($logo_path,".") > 1){
                $error_msg .= "Nome do arquivo não pode conter pontos além da extensão.";
            }
            if(in_array(strtolower($file_ext),$correct_extensions) == false){
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

<main class="propostas-container">
    <div class="propostas-card">
        <h2 class="propostas-title">Inserir País</h2>
        <div id='inscricao'>
            <form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>
                
                <label for="nome">Nome</label>
                <input type='text' name='nome' id="nome" class='form-control' required />

                <label for="sigla">Sigla</label>
                <input type='text' maxlength='3' id='sigla' name='sigla' class='form-control' required />

                <label>Bandeira</label>
                <label class='custom-file-upload' for='bandeira'>
                    <img id='bandeira-preview' style="display:none;">
                    <span id='nomeBandeira'>Clique para selecionar a Bandeira (Max 3MB)</span>
                </label>
                <input type="file" id='bandeira' class='form-control' name='bandeira' accept=".jpg,.png,.jpeg" style="display: none !important;">
                
                <?php
                if(!$emTestes){
                ?>
                <div class="checkbox-container">
                    <input type="checkbox" id='ranking' name='ranking'>
                    <label for="ranking">Membro da CONFUSA?</label>
                </div>
                
                <label for="federacao">Federação</label>
                <select class='form-control' id="federacao" name='federacao' disabled>
                    <option selected value='0'>Sem federação</option>
                    <option value='1'>FEASCO</option>
                    <option value='2'>FEMIFUS</option>
                    <option value='3'>COMPACTA</option>
                </select>
                <?php
                }
                ?>

                <div style="margin-top: 15px;">
                    <button type="submit" name="criar" class="btn">Inserir</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script type="application/javascript">
$(document).ready(function(){
    $(document).on('click', '.closebtn', function(){
        var div = $(this).parent();
        div.addClass('fade-out');
        setTimeout(function(){ div.hide(); }, 400);
    });
    
    function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();
           reader.onload = function (e) {
               $('#'+target_div + "-preview")
                   .attr('src', e.target.result).show();
           };
           reader.readAsDataURL(input.files[0]);
       }
    }
   
    $('#bandeira').change(function(){
        $("#nomeBandeira").text("");
        readURL(this, 'bandeira');
    });

    function toggleFederacao() {
        if ($('#ranking').is(':checked')) {
            $('#federacao').prop('disabled', false);
        } else {
            $('#federacao').prop('disabled', true).val('0');
        }
    }
    
    toggleFederacao();
    $('#ranking').change(function(){
        toggleFederacao();
    });
});
</script>

<?php
    } else {
        echo "<main class='propostas-container'><div class='propostas-card'><h2 class='propostas-title'>Inserir País</h2><p>Usuário sem permissão para inserir países, por favor faça o login.</p></div></main>";
    }

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
