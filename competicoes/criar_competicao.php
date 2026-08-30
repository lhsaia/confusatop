<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$usuario = new Usuario($db);
$competicao = new Competicao_clube($db);
$arbitro_read = new TrioArbitragem($db);
$estadio_read = new Estadio($db);
$clima_read = new Clima($db);

$error_msg = '';
$alert_html = '';

function imageImporter($file_name, $target_filename){
  $maxDim = 180;
  list($width, $height, $type, $attr) = getimagesize( $file_name );
  if ( $width > $maxDim || $height > $maxDim ) {
    $ratio = $width/$height;
    if( $ratio > 1) {
      $new_width = (int) $maxDim;
      $new_height = (int) round($maxDim/$ratio);
    } else {
      $new_width = (int) round($maxDim*$ratio);
      $new_height = (int) $maxDim;
    }
  } else {
    $new_width = (int) $width;
    $new_height = (int) $height;
  }
  if($type == "image/png"){
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
  imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, (int)$width, (int)$height );
  imagedestroy( $src );
  imagewebp($dst, $target_filename);
  imagedestroy( $dst );
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
        if(isset($_POST['nome']) && isset($_POST['ano']) ){
            $competicao->nome = $_POST['nome'];
            $competicao->ano = $_POST['ano'];
            $competicao->federacao = $_POST['federacao'];
            $competicao->sede = $_POST['sede'];
            $competicao->genero = $_POST['genero'];
            $competicao->dono = $_SESSION['user_id'];
            
            if($competicao->federacao != 0){
                $nivelCompeticao = 1;
            } else {
                $nivelCompeticao = 2;
            }
            
            $stmt = $arbitro_read->read($nivelCompeticao, $competicao->federacao);
            $listaArbitros = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $addArray = array($id, $nomeArbitro, $nomeAuxiliarUm, $nomeAuxiliarDois, $estilo, $siglaPais);
                $listaArbitros[] = $addArray;
            }
            
            if($competicao->sede != 0){
                $stmt = $clima_read->exportacao($competicao->sede);
                $listaClimas = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $addArray = array($idClima, $nomeClima, $TempVerao, $EstiloVerao, $TempOutono, $EstiloOutono, $TempInverno, $EstiloInverno, $TempPrimavera, $EstiloPrimavera, $Hemisferio);
                    $listaClimas[] = $addArray;
                }
                
                $stmt = $estadio_read->exportacao($competicao->sede);
                $listaEstadios = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $addArray = array($ID, $Nome, $Capacidade, $Clima, $Altitude, $Caldeirao);
                    $listaEstadios[] = $addArray;
                }
            }
            
            if(isset($_FILES['logo']) && !empty($_FILES['logo']['name'])){
                $fileName = $_FILES['logo']['name'];
                $fileExplode = explode(".",$fileName);
                $fileName = $fileExplode[0] . mt_rand(1,10000).".webp";
                $fileSize = $_FILES['logo']['size'];
                $filePath = $_FILES['logo']['tmp_name'];
                $fileType = $_FILES['logo']['type'];
                $fileExt = strtolower( end($fileExplode));
                $correct_extensions = array("image/png","image/jpg","image/jpeg", "image/webp");
                $upload_dir = "/images/competicoes/";

                if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){
                    $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
                    imageImporter($filePath, $upload_path);
                    $localizacao_foto = $_SESSION['user_id'] ."-" .$fileName;
                    $competicao->logo = $localizacao_foto;
                } else {
                    $error_msg .= "Não foi possível inserir o logo. ";
                    if($fileSize > 2000000){
                        $error_msg .= "Arquivo deve ser menor que 2Mb.";
                    }
                    if($filePath == ''){
                        $error_msg .= "Falha no nome do arquivo.";
                    }
                    if(in_array($fileType,$correct_extensions) == false){
                        $error_msg .= "Extensão não é permitida.";
                    }
                }
            } 

            if($error_msg == ''){
                if($competicao->inserir(true)){
                    $sqlite = new SQLiteDatabase();
                    $comp_id = $db->lastInsertId();
                    $sqlite->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/".$comp_id."-database.db3";
                    $ldb= $sqlite->getConnection();
                    $sqlite->prepareTables();
                    $sqlite->initialMainValues();
                    $sqlite->competitionParameters();
                    
                    $competicao->inserirOpcoes($comp_id);

                    $arbitro = new TrioArbitragem($ldb);
                    foreach($listaArbitros as $novoArbitro){
                        $arbitro->id = $novoArbitro[0];
                        $arbitro->nomeArbitro = $novoArbitro[1] . " [" .$novoArbitro[5] ."]";
                        $arbitro->nomeAuxiliarUm = $novoArbitro[2]. " [" .$novoArbitro[5] ."]";
                        $arbitro->nomeAuxiliarDois = $novoArbitro[3]. " [" .$novoArbitro[5] ."]";
                        $arbitro->estilo = $novoArbitro[4];
                        $arbitro->createSqlite();
                    }
                    
                    if($competicao->sede != 0){
                        $clima = new Clima($ldb);
                        foreach($listaClimas as $novoClima){
                            $clima->id = $novoClima[0];
                            $clima->nome = $novoClima[1];
                            $clima->tempVerao = $novoClima[2];
                            $clima->estiloVerao = $novoClima[3];
                            $clima->tempOutono=$novoClima[4];
                            $clima->estiloOutono=$novoClima[5];
                            $clima->tempInverno=$novoClima[6];
                            $clima->estiloInverno=$novoClima[7];
                            $clima->tempPrimavera=$novoClima[8];
                            $clima->estiloPrimavera=$novoClima[9];
                            $clima->hemisferio=$novoClima[10];
                            $clima->createSqlite();
                        }
                        
                        $estadio = new Estadio($ldb);
                        foreach($listaEstadios as $novoEstadio){
                            $estadio->id = $novoEstadio[0];
                            $estadio->nome = $novoEstadio[1];
                            $estadio->capacidade = $novoEstadio[2];
                            $estadio->clima = $novoEstadio[3];
                            $estadio->altitude = $novoEstadio[4];
                            $estadio->caldeirao = $novoEstadio[5];
                            $estadio->createSqlite();
                        }
                    }
                    
                    $_SESSION['success_message'] = "Competição inserida com sucesso!";
                    header("Location: /competicoes/index.php");
                    exit();
                } else{
                    $alert_html = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir a competição!</div>";
                }
            } else {
                $alert_html = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>" . $error_msg . "</div>";
            }
        }  else {
            $alert_html = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir a competição, campos em branco!</div>";
        }
    }
}
?>
<!DOCTYPE HTML>

<?php
$page_title = "Criar Competição";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'competicoes_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    echo $alert_html;
?>

<script type="application/javascript">
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.classList.add('fade-out');
        setTimeout(function(){ div.style.display = "none"; }, 400);
    }
}

function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();

           reader.onload = function (e) {
               $('#driver-'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              $('#driver-' + target_div + '-text').addClass("hidden");
              $('label[for="driver-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }
</script>

<style>
main.redesign-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}

#inscricao {
    background: rgba(255, 255, 255, 0.85) !important;
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
    border-radius: 18px !important;
    padding: 30px !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04) !important;
    color: #1e293b !important;
}

#inscricao label {
    font-family: 'Outfit', sans-serif !important;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
    color: #0284c7 !important;
    display: block !important;
    margin-top: 15px !important;
    margin-bottom: 5px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

#inscricao input[type='text'],
#inscricao input[type='number'],
#inscricao select {
    width: 100% !important;
    background: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.15) !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
    color: #334155 !important;
    font-family: 'Montserrat', sans-serif !important;
    font-size: 0.95rem !important;
    outline: none !important;
    transition: all 0.25s ease !important;
    box-sizing: border-box !important;
}

#inscricao input[type='text']:focus,
#inscricao input[type='number']:focus,
#inscricao select:focus {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
}

/* Custom file upload styling */
.custom-file-upload {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    background: rgba(2, 132, 199, 0.04) !important;
    border: 1px dashed rgba(2, 132, 199, 0.3) !important;
    border-radius: 8px !important;
    padding: 15px !important;
    cursor: pointer !important;
    transition: all 0.25s ease !important;
    margin-top: 15px !important;
    box-sizing: border-box !important;
    width: 100% !important;
    color: #0369a1 !important;
}

.custom-file-upload:hover {
    background: rgba(2, 132, 199, 0.08) !important;
    border-color: #0284c7 !important;
}

#logo {
    display: none !important;
}

#logo-preview {
    max-height: 40px !important;
    max-width: 40px !important;
    object-fit: contain !important;
    border-radius: 4px !important;
}

#inscricao input[type='submit'] {
    width: 100% !important;
    background: linear-gradient(135deg, #0284c7, #0369a1) !important;
    border: none !important;
    color: #fff !important;
    padding: 12px 20px !important;
    border-radius: 8px !important;
    font-weight: 700 !important;
    font-family: 'Outfit', sans-serif !important;
    font-size: 1.05rem !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3) !important;
    transition: all 0.25s ease !important;
    margin-top: 25px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

#inscricao input[type='submit']:hover {
    background: linear-gradient(135deg, #0369a1, #075985) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.45) !important;
}

.hub-section-title {
    font-family: 'Outfit', sans-serif !important;
    font-weight: 600 !important;
    color: #1e293b !important;
    text-align: center !important;
    margin-bottom: 25px !important;
    font-size: 2rem !important;
}
</style>

<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>

<main class="redesign-container">
    <h2 class="hub-section-title">Criar Competição</h2>
    <div id='inscricao'>
        <form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

            <label for='nome'>Nome</label>
            <input type='text' name='nome' id='nome' class='form-control inputHerdeiro' required />

            <label for='ano'>Ano</label>
            <input type='number' id='ano' name='ano' value='<?php echo date("Y")?>' min='1900' max='2100' class='form-control inputHerdeiro' required />

            <label for='federacao'>Federação</label>
            <?php
                echo "<select class='form-control' id='federacao' name='federacao'>";
                echo "<option selected value='0'>Sem federação</option>";
                echo "<option value='1'>FEASCO</option>";
                echo "<option value='2'>FEMIFUS</option>";
                echo "<option value='3'>COMPACTA</option>";
                echo "</select>";
            ?>

            <label class='custom-file-upload' for='logo'>
                <img id='logo-preview' style="display:none;">
                <span id='nomeLogo'>Clique para selecionar a Logo</span>
            </label>
            <input type="file" id='logo' class='form-control custom-file-upload' name='logo' data-max-size="2048" multiple='false' accept='image/*' placeholder=''>
			
            <label for='sede'>País Sede</label>
            <?php
                $stmt = $pais->read(null, null, false);
                echo "<select class='form-control' id='sede' name='sede'>";
                echo "<option value='0'>Sem sede fixa</option>";
                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }
                echo "</select>";
            ?>

            <label for='genero'>Masculina/Feminina</label>
            <?php
                echo "<select class='form-control' id='genero' name='genero'>";
                echo "<option value='0'>Masculina</option>";
                echo "<option value='1'>Feminina</option>";
                echo "</select>";
            ?>

            <input type="submit" name="criar" value='Inserir Competição' class="btn"/>
        </form>
    </div>
</main>
  <script>

$(document).ready(function(){
	$(function () {
  $("#ano").keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 2100 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });
  $("#ano").keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 2100 && parseInt($(this).val()) >= 1))
      ;
    else
      $(this).val($(this).data("old"));
  });
});



    
    function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();
            
           reader.onload = function (e) {
               $('#'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              //$('#' + target_div + '-text').addClass("hidden");
              //$('label[for="car-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }
   
    $('#logo').change(function(){
        $("#nomeLogo").text("");
        readURL(this, 'logo');
        
    });
	
});
  </script>

<?php

    } else {

    echo "Usuário sem permissão para criar competições, por favor faça o login.";
}

echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
