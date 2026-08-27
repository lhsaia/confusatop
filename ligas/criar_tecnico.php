<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$tecnico = new Tecnico($db);
$pais = new Pais($db);
$usuario = new Usuario($db);

$page_title = "Criar Técnico";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_tecnico_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';
    $feedback_html = '';

    // se formulário foi submetido
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
        if(!empty($_POST['nome']) && !empty($_POST['nascimento']) && !empty($_POST['pais'])){

            $tecnico->nome = $_POST['nome'];
            $tecnico->nascimento = $_POST['nascimento'];
            $tecnico->mentalidade = $_POST['mentalidade'];
            $tecnico->estilo = $_POST['estilo'];
            $tecnico->pais = $_POST['pais'];
            $tecnico->nivel = $_POST['nivel'];
            $tecnico->sexo = $_POST['sexo'];

            if($tecnico->create(true)){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $feedback_html = "<div class='alert alert-success'><span class='closebtn'>&times;</span>Técnico inserido com sucesso!</div>";
            } else {
                $feedback_html = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Houve um erro ao inserir o técnico!</div>";
            }
        } else {
            $feedback_html = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Preencha todos os campos obrigatórios!</div>";
        }
    }
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="card-header-flex">
            <h2 class="propostas-title">➕ Criar Técnico</h2>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" id="hexagen" class="btn-hexagen-top">
                    <span class="material-symbols-outlined">casino</span>
                    <span>Hexagen</span>
                </button>
                <a href="/usuario/meustecnicos.php" class="btn-voltar">Voltar</a>
            </div>
        </div>

        <?php echo $feedback_html; ?>

        <div id='inscricao'>
            <form method="POST" action='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'>
                
                <label for="nomeTecnico">Nome</label>
                <input type='text' name='nome' id='nomeTecnico' class='form-control' required placeholder="Ex: Abel Ferreira" />

                <label for="nascimentoTecnico">Data de Nascimento</label>
                <input type='date' id='nascimentoTecnico' name='nascimento' class='form-control' required />

                <label for="mentalidade">Mentalidade</label>
                <select class='form-control' id='mentalidade' name='mentalidade'>
                    <option value='1'>Retranca</option>
                    <option value='2'>Defensiva</option>
                    <option selected value='3'>Balanceada</option>
                    <option value='4'>Ofensiva</option>
                    <option value='5'>Ataque Total</option>
                </select>

                <label for="estilo">Estilo</label>
                <select class='form-control' id='estilo' name='estilo'>
                    <option value='1'>Explorar contra-ataques</option>
                    <option value='2'>Cadenciar o jogo</option>
                    <option selected value='3'>Neutro</option>
                    <option value='4'>Atacar pelas laterais</option>
                    <option value='5'>Impôr ritmo ofensivo</option>
                </select>

                <label for="nivel">Nível (1 a 10)</label>
                <input type='number' id='nivel' value='6' max='10' min='1' name='nivel' class='form-control' required />

                <label for="sexo">Gênero</label>
                <select class='form-control' id='sexo' name='sexo'>
                    <option value='0'>Masculino</option>
                    <option value='1'>Feminino</option>
                </select>

                <label for="pais">Nacionalidade</label>
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

    $("#nivel").on("keydown keyup", function () {
        var val = parseInt($(this).val());
        if (val > 10) $(this).val(10);
        if (val < 1 && $(this).val() !== '') $(this).val(1);
    });

    $("#hexagen").on("click", function(){
        var nacionalidade = $("#pais").val();
        var sexo = $("#sexo").val();

        var formData = {
            'nacionalidade': nacionalidade,
            'sexo': sexo
        };

        $.ajax({
            type: 'POST',
            url: '/ligas/hexagen_tecnico.php',
            data: formData,
            dataType: 'json',
            encode: true
        }).done(function(data) {
            if (data.success) {
                $("#nomeTecnico").val(data.tec_info.nome);
                $("#nascimentoTecnico").val(data.tec_info.nascimento);
                $("#mentalidade").val(data.tec_info.mentalidade);
                $("#estilo").val(data.tec_info.estilo);
                $("#nivel").val(data.tec_info.nivel);
                $("#pais").val(data.tec_info.pais);
            }
        }).fail(function(){
            alert("Não foi possível gerar dados aleatórios no momento.");
        });
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão para criar técnicos, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
