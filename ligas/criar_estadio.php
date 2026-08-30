<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$estadio = new Estadio($db);
$pais = new Pais($db);
$clima = new Clima($db);
$usuario = new Usuario($db);

if (!function_exists('compress_png')) {
    if (file_exists($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php")) {
        @include_once($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    }
}

if (!function_exists('imageImporterEstadio')) {
    function imageImporterEstadio($file_name, $target_filename) {
        $maxDim = 800;
        list($width, $height, $type, $attr) = getimagesize($file_name);
        if ($width > $maxDim || $height > $maxDim) {
            $ratio = $width / $height;
            if ($ratio > 1) {
                $new_width = (int) $maxDim;
                $new_height = (int) round($maxDim / $ratio);
            } else {
                $new_width = (int) round($maxDim * $ratio);
                $new_height = (int) $maxDim;
            }
        } else {
            $new_width = (int) $width;
            $new_height = (int) $height;
        }

        if ($type == IMAGETYPE_PNG || $type == "image/png") {
            if (function_exists('compress_png')) {
                $compressed_png_content = compress_png($file_name);
                $src = @imagecreatefromstring($compressed_png_content);
            } else {
                $src = @imagecreatefrompng($file_name);
            }
        } else if ($type == IMAGETYPE_WEBP || $type == 18 || $type == "image/webp") {
            $src = @imagecreatefromwebp($file_name);
        } else if ($type == IMAGETYPE_JPEG || $type == "image/jpeg" || $type == "image/jpg") {
            $src = @imagecreatefromjpeg($file_name);
        } else {
            $src = @imagecreatefromstring(file_get_contents($file_name));
        }

        if ($src) {
            $dst = imagecreatetruecolor($new_width, $new_height);
            $background = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagecolortransparent($dst, $background);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, (int)$width, (int)$height);
            imagedestroy($src);
            imagewebp($dst, $target_filename, 85);
            imagedestroy($dst);
            return true;
        }
        return false;
    }
}

$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// se formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
        if(!empty($_POST['nome']) && !empty($_POST['capacidade']) && !empty($_POST['clima']) && !empty($_POST['pais'])){

            $estadio->nome = $_POST['nome'];
            $estadio->capacidade = $_POST['capacidade'];
            $estadio->clima = $_POST['clima'];
            $estadio->pais = $_POST['pais'];
            $estadio->altitude = isset($_POST['altitude']) ? 1 : 0;
            $estadio->caldeirao = isset($_POST['caldeirao']) ? 1 : 0;

            if(isset($_FILES['foto']) && !empty($_FILES['foto']['tmp_name']) && (file_exists($_FILES['foto']['tmp_name']) || is_uploaded_file($_FILES['foto']['tmp_name']))){
                $fileName = $_FILES['foto']['name'];
                $fileSize = $_FILES['foto']['size'];
                $filePath = $_FILES['foto']['tmp_name'];
                $fileType = $_FILES['foto']['type'];
                $tempVar = explode('.',$fileName);
                $fileExt = strtolower(end($tempVar));
                $correct_extensions = array("image/png","image/jpg","image/jpeg","image/webp");
                $upload_dir = "/images/estadios/";

                if($filePath != "" && (in_array($fileType, $correct_extensions) || in_array($fileExt, ['png', 'jpg', 'jpeg', 'webp'])) && $fileSize <= 10485760){
                    $cleanBase = preg_replace("/[^a-zA-Z0-9]/", "", $tempVar[0]);
                    if (empty($cleanBase)) {
                        $cleanBase = "estadio";
                    }
                    $newFileName = $_SESSION['user_id'] . "-" . strtolower($cleanBase) . mt_rand(1000, 9999) . ".webp";
                    $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
                    if(imageImporterEstadio($filePath, $upload_path)){
                        $estadio->foto = $newFileName;
                    }
                } else if ($fileSize > 10485760) {
                    $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>O arquivo de imagem deve ter no máximo 10MB!</div>";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
            }

            if($estadio->create()){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $_SESSION['flash_msg'] = "<div class='alert alert-success'><span class='closebtn'>&times;</span>Estádio inserido com sucesso!</div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Houve um erro ao inserir o estádio!</div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Preencha todos os campos obrigatórios!</div>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

$page_title = "Criar Estádio";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_estadio_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="card-header-flex">
            <h2 class="propostas-title">➕ Criar Estádio</h2>
            <div>
                <a href="/usuario/meusestadios.php" class="btn-voltar">Voltar</a>
            </div>
        </div>

        <?php echo $feedback_html; ?>

        <div id='inscricao'>
            <form method="POST" enctype="multipart/form-data" action='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'>
                
                <label for="nomeEstadio">Nome do Estádio</label>
                <input type='text' name='nome' id='nomeEstadio' class='form-control' required placeholder="Ex: Maracanã" />

                <label for="capacidade">Capacidade</label>
                <input type='number' name='capacidade' id='capacidade' class='form-control' required min="500" step="100" placeholder="Ex: 50000" />

                <label for="clima">Clima</label>
                <select class='form-control' id='clima' name='clima' required>
                    <option value=''>Selecione o clima...</option>
                    <?php
                    $stmtClima = $clima->read($_SESSION['user_id']);
                    while ($row_clima = $stmtClima->fetch(PDO::FETCH_ASSOC)){
                        echo "<option value='{$row_clima['ID']}'>{$row_clima['Nome']}</option>";
                    }
                    ?>
                </select>

                <label for="pais">País</label>
                <select class='form-control' id='pais' name='pais' required>
                    <option value=''>Selecione o país...</option>
                    <?php
                    $stmt = $pais->read();
                    while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                        extract($row_category);
                        echo "<option value='{$id}'>{$nome}</option>";
                    }
                    ?>
                </select>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="altitude" name="altitude" value="1">
                        <label for="altitude">Possui Altitude</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="caldeirao" name="caldeirao" value="1">
                        <label for="caldeirao">Estádio Caldeirão</label>
                    </div>
                </div>

                <label>Foto do Estádio (opcional)</label>
                <label class='custom-file-upload' for='foto'>
                    <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
                    <img id='foto-preview' style="display:none;">
                    <span id='nomeFoto'>Clique para selecionar a foto do estádio</span>
                </label>
                <input type="file" id='foto' name='foto' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">

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

<script>
$(document).ready(function(){
    $(document).on('click', '.closebtn', function(){
        var div = $(this).parent();
        div.fadeOut(300, function(){ $(this).remove(); });
    });

    function readURL(input, target_div) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#' + target_div + '-preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#foto').on('change', function(){
        if (this.files && this.files[0]) {
            $('#nomeFoto').text(this.files[0].name);
            readURL(this, 'foto');
        } else {
            $('#nomeFoto').text('Clique para selecionar a foto do estádio');
            $('#foto-preview').hide().attr('src', '');
        }
    });

    $('button[type="reset"]').on('click', function(){
        $('#nomeFoto').text('Clique para selecionar a foto do estádio');
        $('#foto-preview').hide().attr('src', '');
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão para criar estádios, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
