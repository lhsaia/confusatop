<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$parametro = new Parametro($db);
$pais = new Pais($db);

$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
        if(!empty($_POST['nome'])){
            $parametro->nome = $_POST['nome'];
            $parametro->dono = $_SESSION['user_id'];
            $parametro->gols = $_POST['gols'];
            $parametro->faltas = $_POST['faltas'];
            $parametro->impedimentos = $_POST['impedimentos'];
            $parametro->cartoes = $_POST['cartoes'];
            $parametro->estilo = $_POST['estilo'];
            $parametro->selecionado = isset($_POST['selecionado']) ? 1 : 0;
            $parametro->paisPadrao = $_POST['paisPadrao'];
            $parametro->exibirBandeiras = isset($_POST['exibirBandeiras']) ? 1 : 0;

            if($parametro->inserir()){
                $_SESSION['flash_msg'] = "<div class='alert alert-success'><span class='closebtn'>&times;</span>Parâmetros inseridos com sucesso!</div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Houve um erro ao inserir os parâmetros!</div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Preencha o nome dos parâmetros!</div>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

$page_title = "Criar Parâmetros HYMT";
$css_filename = "home_redesign";
$css_login = "login";
$aux_css = 'home_redesign';
$extra_css = 'criar_parametros_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="propostas-header">
            <h2 class="propostas-title">
                <span>⚙️ Criar Parâmetros HYMT</span>
            </h2>
            <div class="header-actions-container">
                <a href="/usuario/meusparametros.php" class="btn-voltar">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Voltar
                </a>
            </div>
        </div>

        <?php if(!empty($feedback_html)) echo $feedback_html; ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="dono" value="<?php echo $_SESSION['user_id']; ?>"/>

            <div class="form-group">
                <label for="nome">Nome do Perfil</label>
                <input type="text" id="nome" name="nome" required placeholder="Ex: Padrão Equilibrado, Super Ofensivo..." />
            </div>

            <div class="form-group">
                <div class="form-group-header">
                    <label>Frequência de Gols (1 a 20)</label>
                    <span class="slider-badge" id="valGols">10</span>
                </div>
                <div class="slider-wrapper">
                    <div id="sliderGols">
                        <input type="hidden" name="gols" id="inputGols" value="10"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group-header">
                    <label>Frequência de Faltas (1 a 20)</label>
                    <span class="slider-badge" id="valFaltas">10</span>
                </div>
                <div class="slider-wrapper">
                    <div id="sliderFaltas">
                        <input type="hidden" name="faltas" id="inputFaltas" value="10"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group-header">
                    <label>Frequência de Impedimentos (1 a 10)</label>
                    <span class="slider-badge" id="valImpedimentos">5</span>
                </div>
                <div class="slider-wrapper">
                    <div id="sliderImpedimentos">
                        <input type="hidden" name="impedimentos" id="inputImpedimentos" value="5"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group-header">
                    <label>Frequência de Cartões (1 a 10)</label>
                    <span class="slider-badge" id="valCartoes">5</span>
                </div>
                <div class="slider-wrapper">
                    <div id="sliderCartoes">
                        <input type="hidden" name="cartoes" id="inputCartoes" value="5"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group-header">
                    <label>Estilo de Jogo Predominante</label>
                    <span class="slider-badge" id="valEstilo">Intermediário</span>
                </div>
                <div class="slider-wrapper">
                    <div id="sliderEstilo">
                        <input type="hidden" name="estilo" id="inputEstilo" value="3"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="paisPadrao">País Padrão</label>
                <select name="paisPadrao" id="paisPadrao">
                    <option value="0">Sem país padrão</option>
                    <?php
                    $stmt = $pais->read($_SESSION['user_id']);
                    while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                        extract($row_category);
                        echo "<option value='{$id}'>{$nome}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="checkbox-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="selecionado" value="1" />
                    <span>Definir como perfil padrão</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="exibirBandeiras" value="1" />
                    <span>Exibir bandeiras no simulador</span>
                </label>
            </div>

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
</main>

<script>
$(function() {
    $("#sliderGols").slider({
        slide: function(event, ui) {
            $("#valGols").text(ui.value);
            $("#inputGols").val(ui.value);
        },
        min: 1,
        max: 20,
        step: 1,
        value: 10
    });

    $("#sliderFaltas").slider({
        slide: function(event, ui) {
            $("#valFaltas").text(ui.value);
            $("#inputFaltas").val(ui.value);
        },
        min: 1,
        max: 20,
        step: 1,
        value: 10
    });

    $("#sliderImpedimentos").slider({
        slide: function(event, ui) {
            $("#valImpedimentos").text(ui.value);
            $("#inputImpedimentos").val(ui.value);
        },
        min: 1,
        max: 10,
        step: 1,
        value: 5
    });

    $("#sliderCartoes").slider({
        slide: function(event, ui) {
            $("#valCartoes").text(ui.value);
            $("#inputCartoes").val(ui.value);
        },
        min: 1,
        max: 10,
        step: 1,
        value: 5
    });

    $("#sliderEstilo").slider({
        slide: function(event, ui) {
            var text = "Intermediário";
            switch(ui.value){
                case 1: text = "Pelo chão"; break;
                case 2: text = "Mais pelo chão"; break;
                case 3: text = "Intermediário"; break;
                case 4: text = "Mais pelo alto"; break;
                case 5: text = "Pelo alto"; break;
            }
            $("#valEstilo").text(text);
            $("#inputEstilo").val(ui.value);
        },
        min: 1,
        max: 5,
        step: 1,
        value: 3
    });

    $(document).on('click', '.closebtn', function(){
        $(this).parent().fadeOut(300, function(){ $(this).remove(); });
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
