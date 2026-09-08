<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
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

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true){
    header("Location: /index.php");
    exit;
}

if($_SESSION['emTestes'] ?? false){
    $_SESSION['flash_msg'] = "<div class='alert alert-warning' style='background: rgba(251, 191, 36, 0.15); color: #d97706; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;'>Usuários em período de testes não possuem permissão para criar competições.</div>";
    header("Location: /competicoes/index.php");
    exit;
}

$error_msg = '';
$alert_html = '';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && !($_SESSION['emTestes'] ?? false)){
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
        if(isset($_POST['nome']) && isset($_POST['ano']) ){
            $competicao->nome = $_POST['nome'];
            $competicao->ano = $_POST['ano'];
            $tipo_comp = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;
            $competicao->tipo = $tipo_comp;
            $competicao->genero = $_POST['genero'];
            $competicao->dono = $_SESSION['user_id'];
            
            if($tipo_comp == 1){
                // Competição Nacional: vinculada a um país obrigatório, que também é a sede
                $pais_nacional_id = isset($_POST['pais_nacional']) ? intval($_POST['pais_nacional']) : 0;
                if($pais_nacional_id <= 0){
                    $error_msg .= "Para competições nacionais, selecione o país da competição.<br>";
                }
                $competicao->sede = $pais_nacional_id;
                
                // Buscar federação do país no banco de dados
                $stPaisFed = $db->prepare("SELECT federacao FROM paises WHERE id = ? LIMIT 1");
                $stPaisFed->execute([$pais_nacional_id]);
                $rPaisFed = $stPaisFed->fetch(PDO::FETCH_ASSOC);
                $competicao->federacao = ($rPaisFed && !empty($rPaisFed['federacao'])) ? intval($rPaisFed['federacao']) : 0;
            } else {
                // Competição Internacional
                $competicao->federacao = isset($_POST['federacao']) ? intval($_POST['federacao']) : 0;
                $competicao->sede = isset($_POST['sede']) ? intval($_POST['sede']) : 0;
            }
            
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
            
            if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
                $fileName = $_FILES['logo']['name'];
                $fileExplode = explode(".",$fileName);
                $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "competicao";
                $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
                $fileSize = $_FILES['logo']['size'];
                $filePath = $_FILES['logo']['tmp_name'];
                $upload_dir = "/images/competicoes/";

                if($fileSize <= 5000000){
                    $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
                    if(processAndSaveWebPImage($filePath, $upload_path, 300, 90)){
                        $localizacao_foto = $_SESSION['user_id'] ."-" .$fileName;
                        $competicao->logo = $localizacao_foto;
                    } else {
                        $error_msg .= "Não foi possível processar o logo da competição em WebP.";
                    }
                } else {
                    $error_msg .= "Arquivo de imagem deve ser menor que 5MB.";
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
$aux_css = 'home_redesign';
$extra_css = 'criar_pais_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    echo $alert_html;
?>

<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>

<main class="propostas-container">
    <div class="propostas-card">
        <h2 class="propostas-title">➕ Criar Competição</h2>
        <div id='inscricao'>
            <form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

                <label for='nome'>Nome</label>
                <input type='text' name='nome' id='nome' class='form-control' required />

                <label for='ano'>Ano</label>
                <input type='number' id='ano' name='ano' value='<?php echo date("Y")?>' min='1900' max='2100' class='form-control' required />

                <label for='tipo'>Abrangência / Tipo</label>
                <select class='form-control' id='tipo' name='tipo'>
                    <option value='0' selected>Internacional</option>
                    <option value='1'>Nacional</option>
                </select>

                <div id='bloco-federacao'>
                    <label for='federacao'>Federação</label>
                    <?php
                        echo "<select class='form-control' id='federacao' name='federacao'>";
                        echo "<option selected value='0'>Sem federação</option>";
                        echo "<option value='1'>FEASCO</option>";
                        echo "<option value='2'>FEMIFUS</option>";
                        echo "<option value='3'>COMPACTA</option>";
                        echo "</select>";
                    ?>
                </div>

                <div id='bloco-sede'>
                    <label for='sede'>País Sede</label>
                    <?php
                        $stmt = $pais->read(null, null, false);
                        $paisesArray = array();
                        echo "<select class='form-control' id='sede' name='sede'>";
                        echo "<option value='0'>Sem sede fixa</option>";
                        while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                            extract($row_category);
                            $paisesArray[] = array('id' => $id, 'nome' => $nome);
                            echo "<option value='{$id}'>{$nome}</option>";
                        }
                        echo "</select>";
                    ?>
                </div>

                <div id='bloco-pais-nacional' style='display: none;'>
                    <label for='pais_nacional'>País da Competição</label>
                    <select class='form-control' id='pais_nacional' name='pais_nacional'>
                        <option value='' disabled selected>Selecione o país...</option>
                        <?php
                            foreach($paisesArray as $pItem){
                                echo "<option value='{$pItem['id']}'>{$pItem['nome']}</option>";
                            }
                        ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">A sede e federação serão associadas automaticamente ao país selecionado.</small>
                </div>

                <label>Logo</label>
                <label class='custom-file-upload' for='logo'>
                    <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
                    <img id='logo-preview' style="display:none; max-height:40px; max-width:60px; object-fit:contain; border-radius:4px;">
                    <span id='nomeLogo'>Clique para selecionar a Logo</span>
                </label>
                <input type="file" id='logo' class='form-control' name='logo' data-max-size="2048" multiple='false' accept='image/*' style="display: none !important;">

                <label for='genero'>Masculina/Feminina</label>
                <?php
                    echo "<select class='form-control' id='genero' name='genero'>";
                    echo "<option value='0'>Masculina</option>";
                    echo "<option value='1'>Feminina</option>";
                    echo "</select>";
                ?>

                <div class="form-actions">
                    <button type="submit" name="criar" id="salvar" class="btn">
                        <span class="material-symbols-outlined">add_circle</span> Inserir Competição
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

    // Alternar campos entre Internacional e Nacional
    function atualizarCamposTipo() {
        var tipo = $('#tipo').val();
        if (tipo === '1') { // Nacional
            $('#bloco-federacao').hide();
            $('#bloco-sede').hide();
            $('#bloco-pais-nacional').show();
            $('#pais_nacional').prop('required', true);
        } else { // Internacional
            $('#bloco-federacao').show();
            $('#bloco-sede').show();
            $('#bloco-pais-nacional').hide();
            $('#pais_nacional').prop('required', false);
        }
    }

    $('#tipo').on('change', atualizarCamposTipo);
    atualizarCamposTipo();
    
    function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();
            
           reader.onload = function (e) {
               $('#'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden").show();
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
