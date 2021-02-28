<?php

session_start();

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
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

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
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Transação inserida com sucesso. ".$error_msg."</div>";
        //$usuario->atualizarAlteracao($_SESSION['user_id']);
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir a transação. ".$error_msg."</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir a transação, campos obrigatórios em branco</div>";
}
}
?>

<script type="application/javascript">
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 600);
    }
}
</script>


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF'] . "?team=" . $teamId ; ?>'>

    <table class='table table-below float-table'>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Data</td>
            <td class="td_inv input_nome_time"><input  required type='date' name='data' class='form-control' /></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Tipo</td>
            <td class="td_inv input_nome_time">
            <?php
                // ler times do banco de dados
                $stmt = $transaction->getOptions();

                // put them in a select drop-down
                echo "<select required class='form-control' name='tipo'>";
                echo "<option>Selecione o tipo...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
		</tr>
		
		<tr class="tr_inv">
            <td class="td_inv input_nome_time">Fluxo de caixa</td>
            <td class="td_inv input_nome_time">

                <select required class='form-control' name='fluxo'>
					<option selected disabled>Selecione o tipo...</option>
					<option value='0'>Despesa</option>
					<option value='1'>Receita</option>

				</select>
            </td>
		</tr>
		
        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Valor</td>
            <td class="td_inv input_nome_time"><input type='number' name='valor' class='form-control' min='0'/></td>
        </tr>
		
        
        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Comentário</td>
            <td class="td_inv input_nome_time"><input type='text' name='comentario' class='form-control'/></td>
        </tr>
		
		<input type='hidden' name='time' value='<?php echo $teamId ?>' class='form-control'/>

		
        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="criar" class="btn">Inserir</button>
            </td>
        </tr>

    </table>
</form>

<?php

    } else {

    echo "Usuário sem permissão para criar transações, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
