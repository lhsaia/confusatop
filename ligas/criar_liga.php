<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$liga = new Liga($db);
$pais = new Pais($db);

$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// se formulário foi submetido
if($_POST){

    $error_msg = '';

    if(!empty($_FILES['logo']['name']) && isset($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
        $logo_path = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $filePath = $_FILES['logo']['tmp_name'];
        $fileBase = pathinfo($logo_path, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'liga';
        $newFileName = $_SESSION['user_id'] . "-" . $cleanBase . "-" . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/ligas/";

        if($fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 512, 90);
            if (!$result) {
                $error_msg .= " Não foi possível processar o logo em WebP.";
                $liga->logo = $liga->logoPadrao();
            } else {
                $liga->logo = $newFileName;
            }
        } else {
            $liga->logo = $liga->logoPadrao();
            $error_msg .= " A imagem enviada excede 5MB.";
        }
    } else {
        $liga->logo = $liga->logoPadrao();
    }
    // set product property values
    $liga->nome = $_POST['nome'];
    $liga->pais = $_POST['pais'];
    $liga->tier = $_POST['tier'];
    $liga->limite_idade = (!empty($_POST['limite_idade'])) ? intval($_POST['limite_idade']) : null;
    $liga->sexo = $_POST['sexo'];


    // create the product
    if($liga->inserir()){
        $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Liga inserida com sucesso. ".$error_msg."</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // if unable to create the product, tell the user
    else{
        $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao criar a liga. ".$error_msg."</div>";
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

$page_title = "Inserir liga";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'ligas_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div class="propostas-card">
        <h2 class="propostas-title">Criar Liga</h2>
        <?php echo $feedback_html; ?>
        <div id='inscricao'>
            <form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>
                
                <label for="nome">Nome</label>
                <input type='text' name='nome' id="nome" class='form-control' required />

                <label for="sexo">Masculina/Feminina</label>
                <select class='form-control' id="sexo" name='sexo'>
                    <option value='0'>Masculina</option>
                    <option value='1'>Feminina</option>
                </select>

                <label for="tier">Tier</label>
                <select class='form-control' id="tier" name='tier' required>
                    <option value="">Selecione tier...</option>
                    <option value='1'>1 (primeira divisão)</option>
                    <option value='2'>2 (segunda divisão)</option>
                    <option value='3'>3 (terceira divisão)</option>
                    <option value='4'>4 (quarta divisão)</option>
                    <option value='5'>5 (quinta divisão / juniores)</option>
                    <option value='6'>6 (sexta divisão / juniores)</option>
                    <option value='7'>7 (sétima divisão / aspirantes)</option>
                </select>

                <label for="limite_idade">Limite de Idade (opcional / ex: 20 para Sub-20)</label>
                <input type='number' name='limite_idade' id="limite_idade" class='form-control' min="14" max="45" placeholder="Sem restrição (padrão)" />

                <label for="pais">País</label>
                <?php
                // ler times do banco de dados
                $stmt = $pais->read($_SESSION['user_id']);
                echo "<select class='form-control' id='pais' name='pais' required>";
                echo "<option value=''>Selecione país...</option>";
                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }
                echo "</select>";
                ?>

                <label>Logo</label>
                <label class='custom-file-upload' for='logo'>
                    <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
                    <img id='logo-preview' style="display:none; max-height:40px; max-width:60px; object-fit:contain; border-radius:4px;">
                    <span id='nomeLogo'>Clique para selecionar o logo</span>
                </label>
                <input type="file" id='logo' class='form-control' name='logo' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">

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
   
    $('#logo').on('change', function(){
        if (this.files && this.files[0]) {
            $('#nomeLogo').text(this.files[0].name);
            readURL(this, 'logo');
        } else {
            $('#nomeLogo').text('Clique para selecionar o logo');
            $('#logo-preview').hide().attr('src', '');
        }
    });

    $('button[type="reset"]').on('click', function(){
        $('#nomeLogo').text('Clique para selecionar o logo');
        $('#logo-preview').hide().attr('src', '');
    });
});
</script>

<?php
    } else {
        echo "<main class='propostas-container'><div class='propostas-card'><h2 class='propostas-title'>Criar Liga</h2><p>Usuário sem permissão para editar ligas, por favor faça o login.</p></div></main>";
    }

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
