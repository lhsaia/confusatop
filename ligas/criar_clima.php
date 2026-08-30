<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$clima = new Clima($db);
$pais = new Pais($db);
$usuario = new Usuario($db);

$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// se formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
        if(!empty($_POST['nome']) && !empty($_POST['pais'])){

            $clima->nome = $_POST['nome'];
            $clima->tempVerao = $_POST['tempVerao'];
            $clima->estiloVerao = $_POST['estiloVerao'];
            $clima->tempOutono = $_POST['tempOutono'];
            $clima->estiloOutono = $_POST['estiloOutono'];
            $clima->tempInverno = $_POST['tempInverno'];
            $clima->estiloInverno = $_POST['estiloInverno'];
            $clima->tempPrimavera = $_POST['tempPrimavera'];
            $clima->estiloPrimavera = $_POST['estiloPrimavera'];
            $clima->hemisferio = $_POST['hemisferio'];
            $clima->pais = $_POST['pais'];

            if($clima->create()){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $_SESSION['flash_msg'] = "<div class='alert alert-success'><span class='closebtn'>&times;</span>Clima inserido com sucesso!</div>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['flash_msg'] = "<div class='alert alert-danger'><span class='closebtn'>&times;</span>Houve um erro ao inserir o clima!</div>";
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

$page_title = "Criar Clima";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'criar_clima_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="card-header-flex">
            <h2 class="propostas-title">➕ Criar Clima</h2>
            <div>
                <a href="/usuario/meusclimas.php" class="btn-voltar">Voltar</a>
            </div>
        </div>

        <?php echo $feedback_html; ?>

        <div id='inscricao'>
            <form method="POST" action='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'>
                
                <label for="nomeClima">Nome do Clima</label>
                <input type='text' name='nome' id='nomeClima' class='form-control' required placeholder="Ex: Tropical Úmido" />

                <label for="hemisferio">Hemisfério</label>
                <select class='form-control' id='hemisferio' name='hemisferio' required>
                    <option value='Sul' selected>Sul</option>
                    <option value='Norte'>Norte</option>
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

                <!-- Verão -->
                <div class="season-block">
                    <div class="season-title"><span class="material-symbols-outlined">wb_sunny</span> Verão</div>
                    <div class="season-grid">
                        <div>
                            <label>Temperatura</label>
                            <select class="form-control select-temp" data-estilo="estiloVerao" name="tempVerao" id="tempVerao">
                                <option value="Muito Frio">Muito Frio</option>
                                <option value="Frio">Frio</option>
                                <option value="Normal">Normal</option>
                                <option value="Quente" selected>Quente</option>
                                <option value="Muito Quente">Muito Quente</option>
                            </select>
                        </div>
                        <div>
                            <label>Estilo / Condição</label>
                            <select class="form-control" name="estiloVerao" id="estiloVerao">
                                <option value="Neve Forte">Neve Forte</option>
                                <option value="Neve">Neve</option>
                                <option value="Neve Ocasional">Neve Ocasional</option>
                                <option value="Neblina">Neblina</option>
                                <option value="Chuvoso" selected>Chuvoso</option>
                                <option value="Ventos Fortes">Ventos Fortes</option>
                                <option value="Equilibrado">Equilibrado</option>
                                <option value="Seco">Seco</option>
                                <option value="Árido">Árido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Outono -->
                <div class="season-block">
                    <div class="season-title"><span class="material-symbols-outlined">eco</span> Outono</div>
                    <div class="season-grid">
                        <div>
                            <label>Temperatura</label>
                            <select class="form-control select-temp" data-estilo="estiloOutono" name="tempOutono" id="tempOutono">
                                <option value="Muito Frio">Muito Frio</option>
                                <option value="Frio">Frio</option>
                                <option value="Normal" selected>Normal</option>
                                <option value="Quente">Quente</option>
                                <option value="Muito Quente">Muito Quente</option>
                            </select>
                        </div>
                        <div>
                            <label>Estilo / Condição</label>
                            <select class="form-control" name="estiloOutono" id="estiloOutono">
                                <option value="Neve Forte">Neve Forte</option>
                                <option value="Neve">Neve</option>
                                <option value="Neve Ocasional">Neve Ocasional</option>
                                <option value="Neblina">Neblina</option>
                                <option value="Chuvoso">Chuvoso</option>
                                <option value="Ventos Fortes">Ventos Fortes</option>
                                <option value="Equilibrado" selected>Equilibrado</option>
                                <option value="Seco">Seco</option>
                                <option value="Árido">Árido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Inverno -->
                <div class="season-block">
                    <div class="season-title"><span class="material-symbols-outlined">ac_unit</span> Inverno</div>
                    <div class="season-grid">
                        <div>
                            <label>Temperatura</label>
                            <select class="form-control select-temp" data-estilo="estiloInverno" name="tempInverno" id="tempInverno">
                                <option value="Muito Frio">Muito Frio</option>
                                <option value="Frio" selected>Frio</option>
                                <option value="Normal">Normal</option>
                                <option value="Quente">Quente</option>
                                <option value="Muito Quente">Muito Quente</option>
                            </select>
                        </div>
                        <div>
                            <label>Estilo / Condição</label>
                            <select class="form-control" name="estiloInverno" id="estiloInverno">
                                <option value="Neve Forte">Neve Forte</option>
                                <option value="Neve">Neve</option>
                                <option value="Neve Ocasional">Neve Ocasional</option>
                                <option value="Neblina">Neblina</option>
                                <option value="Chuvoso">Chuvoso</option>
                                <option value="Ventos Fortes">Ventos Fortes</option>
                                <option value="Equilibrado" selected>Equilibrado</option>
                                <option value="Seco">Seco</option>
                                <option value="Árido">Árido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Primavera -->
                <div class="season-block">
                    <div class="season-title"><span class="material-symbols-outlined">local_florist</span> Primavera</div>
                    <div class="season-grid">
                        <div>
                            <label>Temperatura</label>
                            <select class="form-control select-temp" data-estilo="estiloPrimavera" name="tempPrimavera" id="tempPrimavera">
                                <option value="Muito Frio">Muito Frio</option>
                                <option value="Frio">Frio</option>
                                <option value="Normal" selected>Normal</option>
                                <option value="Quente">Quente</option>
                                <option value="Muito Quente">Muito Quente</option>
                            </select>
                        </div>
                        <div>
                            <label>Estilo / Condição</label>
                            <select class="form-control" name="estiloPrimavera" id="estiloPrimavera">
                                <option value="Neve Forte">Neve Forte</option>
                                <option value="Neve">Neve</option>
                                <option value="Neve Ocasional">Neve Ocasional</option>
                                <option value="Neblina">Neblina</option>
                                <option value="Chuvoso">Chuvoso</option>
                                <option value="Ventos Fortes">Ventos Fortes</option>
                                <option value="Equilibrado" selected>Equilibrado</option>
                                <option value="Seco">Seco</option>
                                <option value="Árido">Árido</option>
                            </select>
                        </div>
                    </div>
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
    </div>
</main>

<script>
$(document).ready(function(){
    $(document).on('click', '.closebtn', function(){
        var div = $(this).parent();
        div.fadeOut(300, function(){ $(this).remove(); });
    });

    function updateEstilos(selectTemp){
        var temp = $(selectTemp).val();
        var targetEstiloId = $(selectTemp).data('estilo');
        var estiloSelect = $('#' + targetEstiloId);

        if(temp === 'Muito Quente' || temp === 'Quente'){
            estiloSelect.find("option[value*='Neve']").hide();
            if(estiloSelect.val() && estiloSelect.val().indexOf('Neve') !== -1){
                estiloSelect.val('Equilibrado');
            }
        } else if(temp === 'Muito Frio'){
            estiloSelect.find("option[value='Árido']").hide();
            estiloSelect.find("option[value*='Neve']").show();
        } else {
            estiloSelect.find("option").show();
        }
    }

    $('.select-temp').on('change', function(){
        updateEstilos(this);
    });

    $('.select-temp').each(function(){
        updateEstilos(this);
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão para criar climas, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
