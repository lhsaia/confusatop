<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/formacao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$jogador = new Jogador($db);
$pais = new Pais($db);
$clima = new Clima($db);
$time = new Time($db);
$estadio = new Estadio($db);
$liga = new Liga($db);
$formacao = new Formacao($db);
$tecnico = new Tecnico($db);

if (!function_exists('hexToRgb')) {
    function hexToRgb($hex){
        $hex = ltrim(trim((string)$hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            $hex = '000000';
        }
        list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
        return str_pad($r, 3, "0", STR_PAD_LEFT) . str_pad($g, 3, "0", STR_PAD_LEFT) . str_pad($b, 3, "0", STR_PAD_LEFT);
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
if(isset($_POST['nome']) && isset($_POST['sigla']) && $_POST['pais'] != 0){

    $error_msg = '';


    // atributos basicos dos jogadores
    $time->nome = $_POST['nome'];
    $time->sigla = $_POST['sigla'];
    $time->estadio = $_POST['estadio'];

    //cores
    $time->uniforme1cor1 = hexToRgb($_POST['cor1uni1']);
    $time->uniforme1cor2 = hexToRgb($_POST['cor2uni1']);
    $time->uniforme1cor3 = hexToRgb($_POST['cor3uni1']);
    $time->uniforme2cor1 = hexToRgb($_POST['cor1uni2']);
    $time->uniforme2cor2 = hexToRgb($_POST['cor2uni2']);
    $time->uniforme2cor3 = hexToRgb($_POST['cor3uni2']);
    $time->maxTorcedores = $_POST['maxTorcida'];
    $time->fidelidade = $_POST['fidelidade'];
    $time->pais = $_POST['pais'];
    $time->liga = $_POST['liga'];
    $sexo = $_POST['sexo'];
    $time->sexo = $sexo;

    //recebimento arquivos
    $new_logo_path = null;

    if(!empty($_FILES['escudo']['tmp_name']) && (file_exists($_FILES['escudo']['tmp_name']) || is_uploaded_file($_FILES['escudo']['tmp_name']))){
        $fileName = $_FILES['escudo']['name'];
        $fileSize = $_FILES['escudo']['size'];
        $filePath = $_FILES['escudo']['tmp_name'];
        $fileBase = pathinfo($fileName, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'escudo';
        $newFileName = $_SESSION['user_id'] . "-" . $cleanBase . "-" . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/escudos/";

        $correct_extensions = array("image/png", "image/jpg", "image/jpeg", "image/webp");
        $mime = function_exists('mime_content_type') ? @mime_content_type($filePath) : $_FILES['escudo']['type'];

        if($filePath != "" && (in_array($mime, $correct_extensions) || in_array($_FILES['escudo']['type'], $correct_extensions)) && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 512, 90);
            if (!$result) {
                $error_msg .= "Não foi possível processar o escudo em WebP. ";
                $time->escudo = $time->escudoPadrao();
            } else {
                $time->escudo = $newFileName;
            }
        } else {
            $time->escudo = $time->escudoPadrao();
            $error_msg .= "Não foi possível inserir o escudo. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5MB. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo. ";
            }
        }
    } else {
        $time->escudo = $time->escudoPadrao();
    }

    if(!empty($_FILES['uni1']['tmp_name']) && (file_exists($_FILES['uni1']['tmp_name']) || is_uploaded_file($_FILES['uni1']['tmp_name']))){
        $fileName = $_FILES['uni1']['name'];
        $fileSize = $_FILES['uni1']['size'];
        $filePath = $_FILES['uni1']['tmp_name'];
        $fileBase = pathinfo($fileName, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'uni1';
        $newFileName = $_SESSION['user_id'] . "-" . $cleanBase . "-" . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/uniformes/";

        $correct_extensions = array("image/png", "image/jpg", "image/jpeg", "image/webp");
        $mime = function_exists('mime_content_type') ? @mime_content_type($filePath) : $_FILES['uni1']['type'];

        if($filePath != "" && (in_array($mime, $correct_extensions) || in_array($_FILES['uni1']['type'], $correct_extensions)) && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 600, 90);
            if (!$result) {
                $error_msg .= "Não foi possível processar o uniforme 1 em WebP. ";
                $time->uniforme1 = $time->uniforme1Padrao();
            } else {
                $time->uniforme1 = $newFileName;
            }
        } else {
            $time->uniforme1 = $time->uniforme1Padrao();
            $error_msg .= "Não foi possível inserir o uniforme 1. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5MB. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo. ";
            }
        }
    } else {
        $time->uniforme1 = $time->uniforme1Padrao();
    }

    if(!empty($_FILES['uni2']['tmp_name']) && (file_exists($_FILES['uni2']['tmp_name']) || is_uploaded_file($_FILES['uni2']['tmp_name']))){
        $fileName = $_FILES['uni2']['name'];
        $fileSize = $_FILES['uni2']['size'];
        $filePath = $_FILES['uni2']['tmp_name'];
        $fileBase = pathinfo($fileName, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'uni2';
        $newFileName = $_SESSION['user_id'] . "-" . $cleanBase . "-" . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/uniformes/";

        $correct_extensions = array("image/png", "image/jpg", "image/jpeg", "image/webp");
        $mime = function_exists('mime_content_type') ? @mime_content_type($filePath) : $_FILES['uni2']['type'];

        if($filePath != "" && (in_array($mime, $correct_extensions) || in_array($_FILES['uni2']['type'], $correct_extensions)) && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 600, 90);
            if (!$result) {
                $error_msg .= "Não foi possível processar o uniforme 2 em WebP. ";
                $time->uniforme2 = $time->uniforme2Padrao();
            } else {
                $time->uniforme2 = $newFileName;
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
        $_POST['clube'] = $db->lastInsertId();
      if($_POST['criar'] == 1){
          //entrar com Hexagen

          // 1. Loop para criar posições base dos jogadores, conforme esquema tático e número de jogadores definidos
          $totalJogadores = $_POST['numeroJogadores'];
          $formacaoSelecionada = $_POST['formacao'];
          $arrayPosicoes = $formacao->arrayPosicoes($formacaoSelecionada);
            $novaArray = array_slice($arrayPosicoes,0,$totalJogadores);

          foreach($novaArray as &$value){
              $value = $jogador->posicaoPorSigla($value);
          }
          unset($value);


          // 2. Loop para criar os jogadores em si + vincular jogadores ao clube

          $array_hexagen = array();
            include_once($_SERVER['DOCUMENT_ROOT']."/jogadores/hexagen.php");

           // echo '<pre>' . var_export($array_hexagen, true) . '</pre>';

          // 3. Loop para definição dos titulares, reservas e suplentes baseando-se na formação, nas posições e no nível. Garantir que os titulares têm posição base definida. + cobradores e capitão
//organizando em ordem decrescente de nível para seleção dos jogadores
usort($array_hexagen, function($a, $b) {
    return $b['nivel'] <=> $a['nivel'];
});

//titulares
$array_titulares = array_slice($novaArray,0,11);
$capitao = array();
$cobradores = array();


foreach($array_titulares as &$value){
    $posicaoAtual = $value;
    $lookupValue = $posicaoAtual - 1;
    foreach($array_hexagen as $key => $potencialJogador){
        if($potencialJogador['stringPosicoes'][$lookupValue] == 1){
            $value = ['id' => $potencialJogador['id'], 'nivel' => $potencialJogador['nivel'], 'mentalidade' => $potencialJogador['mentalidade'], 'posicaoBase' => $value];
            $capitao[] = ['id' => $potencialJogador['id'], 'nivel' => $potencialJogador['nivel'], 'mentalidade' => $potencialJogador['mentalidade']];
            $cobradores[] = ['id' => $potencialJogador['id'], 'nivel' => $potencialJogador['nivel'], 'mentalidade' => $potencialJogador['mentalidade']];

            unset($array_hexagen[$key]);
            break;
        }
    }
}
unset($value);

if($time->escalarHexagen($array_titulares, $_POST['clube'])){

} else {
    $error_msg .= "Houve erro na escalação dos titulares";
}

//organizar capitao e cobradores da melhor forma.
foreach($capitao as &$value){
    if($value['mentalidade'] == 5){
        $value['nivel'] = $value['nivel']/10 + 3;
    } else if($value['mentalidade'] == 2 || $value['mentalidade'] == 3){
        $value['nivel'] = $value['nivel']/10 + 2;
    } else {
        $value['nivel'] = $value['nivel']/10;
    }
}

unset($value);

//organizando em ordem decrescente de nível para seleção dos jogadores
usort($capitao, function($a, $b) {
    return $b['nivel'] <=> $a['nivel'];
});

$capitao = array_slice($capitao,0,1);

foreach($cobradores as &$value){
    if($value['mentalidade'] == 5){
        $value['nivel'] = $value['nivel'] + 5;
    } else if($value['mentalidade'] == 2){
        $value['nivel'] = $value['nivel'] + 4;
    } else if($value['mentalidade'] == 3 || $value['mentalidade'] == 4 || $value['mentalidade'] == 6 ){
        $value['nivel'] = $value['nivel'] + 3;
    }
}

unset($value);

//organizando em ordem decrescente de nível para seleção dos jogadores
usort($cobradores, function($a, $b) {
    return $b['nivel'] <=> $a['nivel'];
});

$cobradores = array_slice($cobradores,0,3);

if($time->alterarCapitaoCobrador($capitao[0]['id'], $cobradores[0]['id'],$cobradores[1]['id'],$cobradores[2]['id'],$_POST['clube'] )){

} else {
    $error_msg .= "Houve erro no ajuste de capitão e cobradores";
}

//criar técnico


    //verificar se houve override de origem
    if(isset($_POST['nomenclatura']) && $_POST['nomenclatura'] == 1 && (!empty($_POST['origemNomes']) || !empty($_POST['origemSobrenomes']))){

        if(!empty($_POST['origemNomes'])){
            $listaNomes = $_POST['origemNomes'];
            if(!is_array($listaNomes)){
                $listaNomes = array($listaNomes);
            }
            $origemNomes = $listaNomes[array_rand($listaNomes)];
        } else {
            $origemNomes = $pais->sorteioDemografico($nacionalidade, 0, $sexo);
        }

        if(!empty($_POST['origemSobrenomes'])){
            $listaSobrenomes = $_POST['origemSobrenomes'];
            if(!is_array($listaSobrenomes)){
                $listaSobrenomes = array($listaSobrenomes);
            }
            $origemSobrenomes = $listaSobrenomes[array_rand($listaSobrenomes)];
        } else {
            $origemSobrenomes = $pais->sorteioDemografico($nacionalidade, 1, $sexo);
        }

        $indiceMiscigenacao = 100;
        $ocorrenciaNomeDuplo = 0;

    } else {
        $origemNomes = $pais->sorteioDemografico($nacionalidade, 0, $sexo);
        $origemSobrenomes = $pais->sorteioDemografico($nacionalidade,1, $sexo);
        $indiceMiscigenacao = $pais->verificarMiscigenacao($nacionalidade,$origemNomes);
        $ocorrenciaNomeDuplo = $pais->verificarNomeDuplo($nacionalidade,$origemNomes);
    }



    $tecnico->randomTecnico($_POST['pais'], $origemNomes, $origemSobrenomes,18,80,ceil($_POST['nivelMin']/10),ceil($_POST['nivelMax']/10),ceil($_POST['nivelMed']/10),55,$ocorrenciaNomeDuplo, $indiceMiscigenacao, $sexo);

    $tecnico->sexo = $sexo;

    if($tecnico->create(true)){
        $idTecnico = $db->lastInsertId();
        if($tecnico->transferir($idTecnico, $_POST['clube'])){

        } else {
            $error_msg .= "Houve erro na transferência do técnico";
        }
    } else {
        $error_msg .= "Houve erro na criação do técnico";
    }

          // 5. Alerta das mensagens (sobre criação do time e dos jogadores)
          if($error_msg == ''){
            $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Time inserido com sucesso!</div>";
          } else {
            $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Time inserido com sucesso, mas com os seguintes avisos: </br> {$error_msg}</div>";
          }
          header("Location: " . $_SERVER['PHP_SELF']);
          exit;

      } else {
        $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Time inserido com sucesso!</div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
      }
   }

    // if unable to create the product, tell the user
   else{
       $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve erros ao inserir o time! ". $error_msg. "</div>";
       header("Location: " . $_SERVER['PHP_SELF']);
       exit;
   }
}  else {

    $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir o time, campos em branco!</div>";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
}
}

$page_title = "Criar Time";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_time_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<div class="propostas-container">
<div class="propostas-card">
<h2 class="propostas-title">➕ Criar Time</h2>
<?php echo $feedback_html; ?>
<div id='errorbox'></div>

<div id='inscricao'>

<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

        <label for="nomeTime">Nome</label>
        <input type='text' name='nome' id='nomeTime' class='form-control inputHerdeiro' />

        <label for="sigla">Sigla</label>
        <input type='text' maxlength="3" name='sigla' id='sigla' class='form-control inputHerdeiro' />

        <label>Escudo</label>
        <label class='custom-file-upload' for='escudo'>
            <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
            <img id='escudo-preview' style="display:none;">
            <span id='nomeEscudo'>Clique para selecionar o escudo</span>
        </label>
        <input type="file" id='escudo' name='escudo' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">

        <label>Uniforme titular</label>
        <label class='custom-file-upload' for='uni1'>
            <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
            <img id='uni1-preview' style="display:none;">
            <span id='nomeUni1'>Clique para selecionar o uniforme titular</span>
        </label>
        <input type="file" id='uni1' name='uni1' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">

        <label>Cores uniforme titular</label>
        <div class="cores-grid">
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 1</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor1uni1" name="cor1uni1" value="#000000">
                    <input type="text" class="color-hex-input" data-color-target="cor1uni1" value="#000000" maxlength="7" placeholder="#000000" spellcheck="false">
                </div>
            </div>
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 2</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor2uni1" name="cor2uni1" value="#ffffff">
                    <input type="text" class="color-hex-input" data-color-target="cor2uni1" value="#ffffff" maxlength="7" placeholder="#ffffff" spellcheck="false">
                </div>
            </div>
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 3</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor3uni1" name="cor3uni1" value="#000000">
                    <input type="text" class="color-hex-input" data-color-target="cor3uni1" value="#000000" maxlength="7" placeholder="#000000" spellcheck="false">
                </div>
            </div>
        </div>

        <label>Uniforme reserva</label>
        <label class='custom-file-upload' for='uni2'>
            <span class="material-symbols-outlined" style="font-size: 24px; color: #0284c7;">cloud_upload</span>
            <img id='uni2-preview' style="display:none;">
            <span id='nomeUni2'>Clique para selecionar o uniforme reserva</span>
        </label>
        <input type="file" id='uni2' name='uni2' accept=".jpg,.png,.jpeg,.webp" style="display: none !important;">

        <label>Cores uniforme reserva</label>
        <div class="cores-grid">
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 1</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor1uni2" name="cor1uni2" value="#ffffff">
                    <input type="text" class="color-hex-input" data-color-target="cor1uni2" value="#ffffff" maxlength="7" placeholder="#ffffff" spellcheck="false">
                </div>
            </div>
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 2</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor2uni2" name="cor2uni2" value="#000000">
                    <input type="text" class="color-hex-input" data-color-target="cor2uni2" value="#000000" maxlength="7" placeholder="#000000" spellcheck="false">
                </div>
            </div>
            <div class="color-picker-box">
                <span class="color-picker-label">Cor 3</span>
                <div class="color-picker-input-group">
                    <input type="color" id="cor3uni2" name="cor3uni2" value="#ffffff">
                    <input type="text" class="color-hex-input" data-color-target="cor3uni2" value="#ffffff" maxlength="7" placeholder="#ffffff" spellcheck="false">
                </div>
            </div>
        </div>

        <label for="maxTorcida">Máx. Torcida</label>
        <select class='form-control' name='maxTorcida' id='maxTorcida'>
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
                <option value='0' selected>&gt;100000</option>
            </select>

        <label for="fidelidade">Fidelidade</label>
        <input type='number' id='fidelidade' value='5' max='10' min='1' name='fidelidade' class='form-control inputHerdeiro' />

        <label for="pais">País</label>
        <?php
                // ler times do banco de dados
                $stmt = $pais->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' id='pais' name='pais'>";
                echo "<option value='0'>-</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>

        <label for="sexo">Masc/Fem</label>
        <select class='form-control' id='sexo' name='sexo'>
                <option selected value='0'>Masculino</option>
                <option value='1'>Feminino</option>
                </select>

        <label for="liga">Liga</label>
        <?php
                // ler times do banco de dados
                $stmt = $liga->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' id='liga' name='liga'>";
                echo "<option value='0'>-</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}' data-sexo='{$Sexo}' data-pais='{$Pais}'>{$nome}</option>";
                }

                echo "</select>";
                ?>


        <div class="field-label-row">
            <label for="estadio">Estádio</label>
            <button type="button" class="btn-action-inline" id="btn-abrir-modal-estadio">
                <span class="material-symbols-outlined" style="font-size: 16px;">add_circle</span> Novo Estádio
            </button>
        </div>
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

        <label for="sliderNiveis"><span class="material-symbols-outlined">casino</span>&nbsp Níveis</label>
        <div class="slider-group">
            <div class="slider_itself" id="sliderNiveis"></div>
            <div class="slider-values-row">
                <div><label for='inputNivelMin'>Min</label><input type="number" name="nivelMin" id="inputNivelMin" class='inputHerdeiro smallInput' value="30" min='1' max='99'/></div>
                <div><label for='inputNivelMax'>Max</label><input type="number" name="nivelMax" id="inputNivelMax" class='inputHerdeiro smallInput' value="90" min='1' max='99'/></div>
                <div><label for='inputNivelMed'>Med</label><input type="number" name="nivelMed" id="inputNivelMed" class='inputHerdeiro smallInput' value="60" min='1' max='99'/></div>
            </div>
        </div>

        <label for="sliderIdades"><span class="material-symbols-outlined">casino</span>&nbsp Idades</label>
        <div class="slider-group">
            <div class="slider_itself" id="sliderIdades"></div>
            <div class="slider-values-row">
                <div><label for='inputIdadeMin'>Min</label><input type="number" name="idadeMin" id="inputIdadeMin" class='inputHerdeiro smallInput' value="18" min='13' max='44'/></div>
                <div><label for='inputIdadeMax'>Max</label><input type="number" name="idadeMax" id="inputIdadeMax" class='inputHerdeiro smallInput' value="36" min='13' max='44'/></div>
                <div><label for='inputIdadeMed'>Med</label><input type="number" name="idadeMed" id="inputIdadeMed" class='inputHerdeiro smallInput' value="25" min='13' max='44'/></div>
            </div>
        </div>

        <label for="numeroJogadores"><span class="material-symbols-outlined">casino</span>&nbsp Número de Jogadores</label>
        <select class='form-control' id='numeroJogadores' name='numeroJogadores'>
                <option value='23'>23</option>
                <option value='22'>22</option>
                <option value='21'>21</option>
                <option value='20'>20</option>
                <option value='19'>19</option>
                <option value='18'>18</option>
                <option value='17'>17</option>
              </select>

        <label for="nomenclatura"><span class="material-symbols-outlined">casino</span>&nbsp Nomenclatura</label>
        <select class='form-control' id='nomenclatura' name='nomenclatura'>
                <option value='0'>Automática</option>
                <option value='1'>Manual</option>
              </select>

        <div class="origemNomes row_atributo">
            <label for="origemNomes"><span class="material-symbols-outlined">casino</span>&nbsp Origem dos Nomes</label>
            <select multiple class='form-control' id='origemNomes' name='origemNomes[]'>
                <?php
                $stmt = $pais->listaOrigens();

                  while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($result);
                    echo "<option value='{$ID}' data-nomeMasc='{$nomeM}' data-nomeFem='{$nomeF}'>{$Origem}</option>";
                  }
                ?>
              </select>
        </div>

        <div class="origemSobrenomes row_atributo">
            <label for="origemSobrenomes"><span class="material-symbols-outlined">casino</span>&nbsp Origem dos Sobrenomes</label>
            <select multiple class='form-control' id='origemSobrenomes' name='origemSobrenomes[]'>
                <?php

                $stmt = $pais->listaOrigens();

                  while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($result);
                    echo "<option value='{$ID}' data-sobrenomeMasc='{$sobrenomeM}' data-sobrenomeFem='{$sobrenomeF}'>{$Origem}</option>";
                  }
                ?>
              </select>
        </div>

        <label for="formacao"><span class="material-symbols-outlined">casino</span>&nbsp Formação Base</label>
        <select class='form-control' id='formacao' name='formacao'>
                <?php

                $stmt = $formacao->read();

                  while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($result);
                    echo "<option value='{$id}'>{$nome}</option>";
                  }
                ?>
              </select>

        <input type='hidden' name='inserir'>
        <input type='hidden' name='clube' value=''>
        
        <div class="form-actions">
            <button type="submit" name="criar" class="btn" value="0">Inserir sem jogadores</button>
            <button type="reset" name="reset" class="btn">Limpar</button>
            <button type='submit' id='hexagen' name="criar" class="btn" value="1"><span class="material-symbols-outlined">casino</span>&nbsp Inserir com jogadores</button>
        </div>

    </form>
</div>
</div>
</div>

<!-- Modal Criar Estádio -->
<div id="modal-estadio" class="custom-modal-backdrop" style="display: none;">
    <div class="custom-modal-card">
        <div class="custom-modal-header">
            <h3><span class="material-symbols-outlined">stadium</span> Novo Estádio</h3>
            <button type="button" class="custom-modal-close" id="btn-fechar-modal-estadio">&times;</button>
        </div>
        <div class="custom-modal-body">
            <div id="modal-estadio-feedback"></div>
            
            <label for="modal-estadio-nome">Nome do Estádio *</label>
            <input type="text" id="modal-estadio-nome" class="form-control" placeholder="Ex: Estádio Monumental" required />

            <div class="modal-form-grid">
                <div>
                    <label for="modal-estadio-capacidade">Capacidade *</label>
                    <input type="number" id="modal-estadio-capacidade" class="form-control" placeholder="Ex: 50000" min="500" step="100" value="30000" required />
                </div>
                <div>
                    <label for="modal-estadio-pais">País *</label>
                    <select id="modal-estadio-pais" class="form-control" required>
                        <option value="">Selecione o país...</option>
                        <?php
                        $stmtPaisesModal = $pais->read($_SESSION['user_id']);
                        while ($rowPais = $stmtPaisesModal->fetch(PDO::FETCH_ASSOC)){
                            echo "<option value='{$rowPais['id']}'>{$rowPais['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <label for="modal-estadio-clima">Clima *</label>
            <select id="modal-estadio-clima" class="form-control" required>
                <option value="">Selecione o clima...</option>
                <?php
                $stmtClimaModal = $clima->read($_SESSION['user_id']);
                while ($rowClima = $stmtClimaModal->fetch(PDO::FETCH_ASSOC)){
                    echo "<option value='{$rowClima['ID']}'>{$rowClima['Nome']}</option>";
                }
                ?>
            </select>

            <div class="modal-checkbox-row">
                <label class="custom-checkbox-label">
                    <input type="checkbox" id="modal-estadio-altitude" value="1">
                    <span>Possui Altitude</span>
                </label>
                <label class="custom-checkbox-label">
                    <input type="checkbox" id="modal-estadio-caldeirao" value="1">
                    <span>Estádio Caldeirão</span>
                </label>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn-modal-secondary" id="btn-cancelar-modal-estadio">Cancelar</button>
            <button type="button" class="btn-modal-primary" id="btn-salvar-modal-estadio">
                <span class="material-symbols-outlined">save</span> Salvar Estádio
            </button>
        </div>
    </div>
</div>

  <script>
  $( function() {
    $( "#sliderNiveis" ).slider({
      slide: function(event, ui) {
        $("#inputNivelMin").val($( this ).slider( "values" , 0 ));
        $("#inputNivelMax").val($( this ).slider( "values" , 1 ));
        $("#inputNivelMed").val($( this ).slider( "values" , 2 ));
      },
      stop: function( event, ui ) {
        $("#inputNivelMin").val($( this ).slider( "values" , 0 ));
        $("#inputNivelMax").val($( this ).slider( "values" , 1 ));
        $("#inputNivelMed").val($( this ).slider( "values" , 2 ));
      },
        min: 1, // min value
        max: 99, // max value
        step: 1,
        values: [30,90,60] // default value of slider
    });
  } );

  $("#inputNivelMin").on("change", function(){
    $("#sliderNiveis").slider("values", 0 , $(this).val());
  });
  $("#inputNivelMax").on("change", function(){
    $("#sliderNiveis").slider("values", 1 , $(this).val());
  });
  $("#inputNivelMed").on("change", function(){
    $("#sliderNiveis").slider("values", 2 , $(this).val());
  });

    $( function() {
    $( "#sliderIdades" ).slider({
      slide: function(event, ui) {
        $("#inputIdadeMin").val($( this ).slider( "values" , 0 ));
        $("#inputIdadeMax").val($( this ).slider( "values" , 1 ));
        $("#inputIdadeMed").val($( this ).slider( "values" , 2 ));
      },
      stop: function( event, ui ) {
        $("#inputIdadeMin").val($( this ).slider( "values" , 0 ));
        $("#inputIdadeMax").val($( this ).slider( "values" , 1 ));
        $("#inputIdadeMed").val($( this ).slider( "values" , 2 ));
      },
        min: 13, // min value
        max: 44, // max value
        step: 1,
        values: [18,36,25] // default value of slider
    });
  } );

  $("#inputIdadeMin").on("change", function(){
    $("#sliderIdades").slider("values", 0 , $(this).val());
  });
  $("#inputIdadeMax").on("change", function(){
    $("#sliderIdades").slider("values", 1 , $(this).val());
  });
  $("#inputIdadeMed").on("change", function(){
    $("#sliderIdades").slider("values", 2 , $(this).val());
  });

  $("#nomenclatura").on("change", function(){
      if($(this).val() == 1){
          $(".origemNomes").show();
          $(".origemSobrenomes").show();
      } else {
          $(".origemNomes").hide();
          $(".origemSobrenomes").hide();
      }
  });

    $("#pais").on("change", function(){
        updateLeagues();
        updateNames();
    });

$("#sexo").on("change", function(){
    updateLeagues();
    updateNames();
});

function updateLeagues(){
    var paisSelecionado = $("#pais").val();
    var sexoUsado = $('#sexo').val();

    $("#liga option").each(function(){
        var sexoLiga = $(this).attr("data-sexo");
        if (sexoLiga != sexoUsado || $(this).attr("data-pais") != paisSelecionado ){
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}

function updateNames(){
    var sexo = $("#sexo").val();

    $("#origemNomes option").each(function(){
        if (sexo == 0){
            var temNome = $(this).attr("data-nomeMasc");
        } else {
            var temNome = $(this).attr("data-nomeFem");
        }

        if (temNome < 2){
            $(this).hide();
        } else {
            $(this).show();
        }
    });

    $("#origemSobrenomes option").each(function(){
        if (sexo == 0){
            var temSobrenome = $(this).attr("data-sobrenomeMasc");
        } else {
            var temSobrenome = $(this).attr("data-sobrenomeFem");
        }

        if (temSobrenome < 2){
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}

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

  function readURL(input, target_div) {
      if (input.files && input.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
              $('#' + target_div + '-preview').attr('src', e.target.result).show();
          };
          reader.readAsDataURL(input.files[0]);
      }
  }

  $('#escudo').on('change', function(){
      if (this.files && this.files[0]) {
          $('#nomeEscudo').text(this.files[0].name);
          readURL(this, 'escudo');
      } else {
          $('#nomeEscudo').text('Clique para selecionar o escudo');
          $('#escudo-preview').hide().attr('src', '');
      }
  });

  $('#uni1').on('change', function(){
      if (this.files && this.files[0]) {
          $('#nomeUni1').text(this.files[0].name);
          readURL(this, 'uni1');
      } else {
          $('#nomeUni1').text('Clique para selecionar o uniforme titular');
          $('#uni1-preview').hide().attr('src', '');
      }
  });

  $('#uni2').on('change', function(){
      if (this.files && this.files[0]) {
          $('#nomeUni2').text(this.files[0].name);
          readURL(this, 'uni2');
      } else {
          $('#nomeUni2').text('Clique para selecionar o uniforme reserva');
          $('#uni2-preview').hide().attr('src', '');
      }
  });

  // Sincronização Cores <-> Hexadecimal
  $('input[type="color"]').on('input change', function() {
      var colorVal = $(this).val().toUpperCase();
      var targetInput = $('.color-hex-input[data-color-target="' + $(this).attr('id') + '"]');
      targetInput.val(colorVal);
  });

  $('.color-hex-input').on('input change blur', function(e) {
      var rawVal = $(this).val().trim();
      var colorTargetId = $(this).data('color-target');
      var colorPicker = $('#' + colorTargetId);
      
      if (rawVal.length > 0) {
          if (!rawVal.startsWith('#')) {
              rawVal = '#' + rawVal;
          }
          if (e.type === 'blur' || e.type === 'change') {
              if (/^#[0-9A-Fa-f]{3}$/.test(rawVal)) {
                  rawVal = '#' + rawVal[1] + rawVal[1] + rawVal[2] + rawVal[2] + rawVal[3] + rawVal[3];
              }
              $(this).val(rawVal.toUpperCase());
          }
          if (/^#[0-9A-Fa-f]{6}$/.test(rawVal)) {
              colorPicker.val(rawVal);
          }
      }
  });

  $('button[type="reset"]').on('click', function(){
      $('#nomeEscudo').text('Clique para selecionar o escudo');
      $('#escudo-preview').hide().attr('src', '');
      $('#nomeUni1').text('Clique para selecionar o uniforme titular');
      $('#uni1-preview').hide().attr('src', '');
      $('#nomeUni2').text('Clique para selecionar o uniforme reserva');
      $('#uni2-preview').hide().attr('src', '');

      setTimeout(function() {
          $('.color-hex-input').each(function() {
              var target = $('#' + $(this).data('color-target'));
              if (target.length) {
                  $(this).val(target.val().toUpperCase());
              }
          });
      }, 50);
  });

  // Modal Novo Estádio
  $('#btn-abrir-modal-estadio').on('click', function() {
      $('#modal-estadio-feedback').empty();
      var paisAtual = $('#pais').val();
      if (paisAtual && paisAtual != '0') {
          $('#modal-estadio-pais').val(paisAtual);
      }
      $('#modal-estadio').fadeIn(200);
      $('#modal-estadio-nome').focus();
  });

  function fecharModalEstadio() {
      $('#modal-estadio').fadeOut(200);
      $('#modal-estadio-feedback').empty();
  }

  $('#btn-fechar-modal-estadio, #btn-cancelar-modal-estadio').on('click', fecharModalEstadio);

  $('#modal-estadio').on('click', function(e) {
      if ($(e.target).is('#modal-estadio')) {
          fecharModalEstadio();
      }
  });

  $('#btn-salvar-modal-estadio').on('click', function() {
      var btn = $(this);
      var nome = $('#modal-estadio-nome').val().trim();
      var capacidade = $('#modal-estadio-capacidade').val();
      var pais = $('#modal-estadio-pais').val();
      var clima = $('#modal-estadio-clima').val();
      var altitude = $('#modal-estadio-altitude').is(':checked') ? 1 : 0;
      var caldeirao = $('#modal-estadio-caldeirao').is(':checked') ? 1 : 0;

      if (!nome) {
          $('#modal-estadio-feedback').html('<div class="modal-alert-error">Preencha o nome do estádio.</div>');
          $('#modal-estadio-nome').focus();
          return;
      }
      if (!capacidade || capacidade < 1) {
          $('#modal-estadio-feedback').html('<div class="modal-alert-error">Informe uma capacidade válida.</div>');
          $('#modal-estadio-capacidade').focus();
          return;
      }
      if (!pais || pais == '0') {
          $('#modal-estadio-feedback').html('<div class="modal-alert-error">Selecione o país do estádio.</div>');
          $('#modal-estadio-pais').focus();
          return;
      }
      if (!clima || clima == '0') {
          $('#modal-estadio-feedback').html('<div class="modal-alert-error">Selecione o clima do estádio.</div>');
          $('#modal-estadio-clima').focus();
          return;
      }

      btn.prop('disabled', true).text('Salvando...');

      $.ajax({
          url: '/api/estadio/criar.php',
          type: 'POST',
          dataType: 'json',
          data: {
              nome: nome,
              capacidade: capacidade,
              pais: pais,
              clima: clima,
              altitude: altitude,
              caldeirao: caldeirao
          },
          success: function(response) {
              btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> Salvar Estádio');
              if (response && response.success) {
                  // Adiciona a nova opção ao select de estádios e seleciona
                  var novaOpcao = $('<option>', {
                      value: response.id,
                      text: response.nome + ' (' + response.capacidade + ')',
                      'data-pais': response.pais
                  });
                  $('#estadio').append(novaOpcao);
                  $('#estadio').val(response.id);

                  // Limpa inputs do modal
                  $('#modal-estadio-nome').val('');
                  $('#modal-estadio-capacidade').val('30000');
                  $('#modal-estadio-altitude').prop('checked', false);
                  $('#modal-estadio-caldeirao').prop('checked', false);

                  fecharModalEstadio();

                  // Mostra feedback no formulário principal
                  var toast = $('<div class="alert alert-success alert-btn"><span class="closebtn">&times;</span>Estádio "' + response.nome + '" criado e selecionado com sucesso!</div>');
                  $('#errorbox').html(toast);
                  setTimeout(function() { toast.fadeOut(400); }, 5000);
              } else {
                  var erroMsg = (response && response.message) ? response.message : 'Erro ao criar o estádio.';
                  $('#modal-estadio-feedback').html('<div class="modal-alert-error">' + erroMsg + '</div>');
              }
          },
          error: function() {
              btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> Salvar Estádio');
              $('#modal-estadio-feedback').html('<div class="modal-alert-error">Erro de comunicação ao criar estádio.</div>');
          }
      });
  });
});


  </script>

<?php

    } else {

    echo "Usuário sem permissão para criar times, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
