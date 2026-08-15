<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transaction.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

if(isset($_GET['team'])){
	$teamId = $_GET['team'];
}

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$transaction = new Transaction($db);
$usuario = new Usuario($db);

$page_title = "Inserir Transação";
$css_filename = "home_redesign";
$aux_css = 'opcoes_redesign';
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    echo '<main class="propostas-container">';
    echo '<div class="propostas-card">';
    echo '<h2 class="propostas-title">' . $page_title . '</h2>';

    $error_msg = '';

    // if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
        if(isset($_POST['time']) && !empty($_POST['tipo']) && !empty($_POST['data']) && $_POST['fluxo'] > -1 && !empty($_POST['valor'])){

            // set product property values
            $transaction->timestamp = $_POST['data'];
            $transaction->transaction_type = $_POST['tipo'];
            $transaction->cash_flow = $_POST['fluxo'];
            $transaction->value = $_POST['valor'];
            $transaction->comment = $_POST['comentario'];
            $transaction->team = $_POST['time'];

            // create
            if($transaction->create()){
                echo "<div class='alert alert-success alert-btn' style='margin-bottom: 20px; padding: 15px; background: rgba(16, 185, 129, 0.12); border: 1px solid #10b981; border-radius: 8px; color: #065f46; font-family: Montserrat, sans-serif;'><span class='closebtn' style='float:right; cursor:pointer;'>&times;</span>Transação inserida com sucesso. ".$error_msg."</div>";
            }
            // if unable to create the product, tell the user
            else{
                echo "<div class='alert alert-danger alert-btn' style='margin-bottom: 20px; padding: 15px; background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; border-radius: 8px; color: #991b1b; font-family: Montserrat, sans-serif;'><span class='closebtn' style='float:right; cursor:pointer;'>&times;</span>Não foi possível inserir a transação. ".$error_msg."</div>";
            }
        }  else {
            echo "<div class='alert alert-danger alert-btn' style='margin-bottom: 20px; padding: 15px; background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; border-radius: 8px; color: #991b1b; font-family: Montserrat, sans-serif;'><span class='closebtn' style='float:right; cursor:pointer;'>&times;</span>Não foi possível inserir a transação, campos obrigatórios em branco</div>";
        }
    }
?>

<script type="application/javascript">
$(document).ready(function() {
    $(document).on('click', '.closebtn', function() {
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 600);
    });
});
</script>

<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF'] . "?team=" . $teamId ; ?>'>
    <div class="opcoes-secao">
        <label for="input_data">Data</label>
        <input required type='date' name='data' id="input_data" />

        <label for="select_tipo">Tipo</label>
        <?php
            // ler tipos do banco de dados
            $stmt = $transaction->getOptions();
            echo "<select required name='tipo' id='select_tipo'>";
            echo "<option value=''>Selecione o tipo...</option>";
            while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row_category);
                echo "<option value='{$id}'>{$nome}</option>";
            }
            echo "</select>";
        ?>

        <label for="select_fluxo">Fluxo de Caixa</label>
        <select required name='fluxo' id='select_fluxo'>
            <option value='' selected disabled>Selecione o fluxo...</option>
            <option value='0'>Despesa</option>
            <option value='1'>Receita</option>
        </select>

        <label for="input_valor">Valor (F$)</label>
        <input required type='number' name='valor' id='input_valor' min='0'/>

        <label for="input_comentario">Comentário</label>
        <input type='text' name='comentario' id='input_comentario'/>

        <input type='hidden' name='time' value='<?php echo $teamId ?>' />

        <button type="submit" name="criar" id="salvar" style="margin-top: 20px;">Inserir Transação</button>
    </div>
</form>

<div style="margin-top: 30px;">
    <a href="/times/resumo_financeiro.php?id=<?php echo $teamId; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
       onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
        ← Voltar para Resumo Financeiro
    </a>
</div>

<?php
    echo '</div>';
    echo '</main>';
} else {
    echo '<main class="propostas-container">';
    echo '<div class="propostas-card">';
    echo '<p style="color: #ef4444; font-family: Montserrat, sans-serif;">Usuário sem permissão para criar transações, por favor faça o login.</p>';
    echo '</div>';
    echo '</main>';
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
