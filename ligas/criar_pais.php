<?php

ini_set('memory_limit', '512M');

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$usuario = new Usuario($db);

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true){
    header("Location: /index.php");
    exit;
}

if($_SESSION['emTestes'] ?? false){
    $_SESSION['flash_msg'] = "<div class='alert alert-warning' style='background: rgba(251, 191, 36, 0.15); color: #d97706; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;'>Usuários em período de testes não possuem permissão para criar novos países.</div>";
    header("Location: /usuario/meuspaises.php");
    exit;
}

$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && !($_SESSION['emTestes'] ?? false)){
if(isset($_POST['nome']) && !empty($_POST['sigla']) && !empty($_POST['nome']) ){
	
    $error_msg = '';
    $logo_path = isset($_FILES['bandeira']['name']) ? $_FILES['bandeira']['name'] : '';
    $fileSize = isset($_FILES['bandeira']['size']) ? $_FILES['bandeira']['size'] : 0;
    $filePath = isset($_FILES['bandeira']['tmp_name']) ? $_FILES['bandeira']['tmp_name'] : '';
    $fileError = isset($_FILES['bandeira']['error']) ? $_FILES['bandeira']['error'] : UPLOAD_ERR_NO_FILE;
    $extension = explode(".",$logo_path);
    $file_ext = isset($extension[1]) ? end($extension) : '';
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

        // Processar upload da bandeira apenas se um arquivo foi enviado
        if (!empty($logo_path) && $fileError != UPLOAD_ERR_NO_FILE) {
            $file_ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));

            if ($fileError == UPLOAD_ERR_OK && !empty($filePath) && (is_uploaded_file($filePath) || file_exists($filePath))) {
                if (!in_array($file_ext, $correct_extensions)) {
                    $error_msg .= " Mas a extensão '." . htmlspecialchars($file_ext) . "' não é permitida (use PNG, JPG, JPEG ou WEBP).";
                } else if ($fileSize > 3000000) {
                    $error_msg .= " Mas o arquivo da bandeira é maior que 3MB.";
                } else {
                    $new_logo_path = $pais->sigla . ".webp";
                    $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $new_logo_path;
                    
                    if(file_exists($upload_path)){
                        @unlink($upload_path);
                    }

                    if(imageImporterWebP($filePath, $upload_path)){
                        $bandeiraAtualizada = $new_logo_path;
                        if(!$pais->atualizarBandeira($idPais, $bandeiraAtualizada)){
                            $error_msg .= " Mas não foi possível vincular a bandeira.";
                        }
                    } else {
                        $error_msg .= " Mas não foi possível processar a imagem da bandeira.";
                    }
                }
            } else {
                if ($fileError == UPLOAD_ERR_INI_SIZE || $fileError == UPLOAD_ERR_FORM_SIZE) {
                    $error_msg .= " Mas o arquivo da bandeira ultrapassou o tamanho máximo permitido.";
                } else if ($fileError == UPLOAD_ERR_PARTIAL) {
                    $error_msg .= " Mas o upload da bandeira foi interrompido.";
                } else {
                    $error_msg .= " Mas não foi possível processar o upload da bandeira.";
                }
            }
        }

        $usuario->atualizarAlteracao($_SESSION['user_id']);
        $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>País inserido com sucesso.".$error_msg."</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // if unable to create the product, tell the user
    else{
        $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir o país. ".$error_msg."</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}  else {

    $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Campos em branco</div>";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
}
}

$page_title = "Inserir país";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_pais_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    $emTestes = $usuario->emTestes($_SESSION['user_id']);
?>

<main class="propostas-container">
    <div class="propostas-card">
        <h2 class="propostas-title">Inserir País</h2>
        <?php echo $feedback_html; ?>
        <div id='inscricao'>
            <form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>
                
                <label for="nome">Nome</label>
                <input type='text' name='nome' id="nome" class='form-control' required />

                <label for="sigla">Sigla</label>
                <input type='text' maxlength='3' id='sigla' name='sigla' class='form-control' required />

                <label>Bandeira</label>
                <label class='custom-file-upload' for='bandeira'>
                    <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
                    <img id='bandeira-preview' style="display:none; max-height:40px; max-width:60px; object-fit:contain; border-radius:4px;">
                    <span id='nomeBandeira'>Clique para selecionar a bandeira</span>
                </label>
                <input type="file" id='bandeira' class='form-control' name='bandeira' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">
                
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

                <div class="form-actions">
                    <button type="submit" name="criar" id="salvar" class="btn">
                        <span class="material-symbols-outlined">add_circle</span> Inserir
                    </button>
                    <button type="reset" name="reset" class="btn">
                        <span class="material-symbols-outlined">restart_alt</span> Limpar
                    </button>
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
   
    $('#bandeira').on('change', function(){
        if (this.files && this.files[0]) {
            $('#nomeBandeira').text(this.files[0].name);
            readURL(this, 'bandeira');
        } else {
            $('#nomeBandeira').text('Clique para selecionar a bandeira');
            $('#bandeira-preview').hide().attr('src', '');
        }
    });

    $('button[type="reset"]').on('click', function(){
        $('#nomeBandeira').text('Clique para selecionar a bandeira');
        $('#bandeira-preview').hide().attr('src', '');
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
