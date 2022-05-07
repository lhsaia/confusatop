<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$pais = new Pais($db);
$arbitro = new TrioArbitragem($db);
$usuario = new Usuario($db);

$page_title = "Inserir árbitro";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
if(isset($_POST['nome_arbitro']) && !empty($_POST['nome_aux2']) && !empty($_POST['nome_aux1']) && !empty($_POST['nome_arbitro'])){

    // set product property values
    $arbitro->nomeArbitro = $_POST['nome_arbitro'];
    $arbitro->nomeAuxiliarUm = $_POST['nome_aux1'];
    $arbitro->nomeAuxiliarDois = $_POST['nome_aux2'];
    $arbitro->estilo = $_POST['estilo_arbitro'];
    $arbitro->pais = $_POST['nacionalidade_arbitro'];
	$arbitro->nivel = $_POST['nivel_arbitro'];
	$arbitro->nascimento = $_POST['nascimento_arbitro'];



    // create the product
    if($arbitro->create()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Árbitro inserido com sucesso</div>";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o árbitro, possível duplicata</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o árbitro, campos em branco</div>";
}
}
?>

<script type="application/javascript">

 $(document).ready(function($){
	 
	$('#toolbar').html("<div id='hexagen'><i class='fas fa-dice'></i><span> Hexagen</span></div>");
	 
	var close = document.getElementsByClassName("closebtn");
	var i;
	
	

	for (i = 0; i < close.length; i++) {
		close[i].onclick = function(){
			var div = this.parentElement;
			div.style.opacity = "0";
			setTimeout(function(){ div.style.display = "none"; }, 600);
		}
	}
	
	
$("#hexagen").on("click",function(){
    var nacionalidade = $("#pais_arbitro").val();
    var sexo = $("#genero_arbitro").val();

    var formData = {
        'nacionalidade' : nacionalidade,
        'sexo' : sexo
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/arbitros/hexagen_arbitro.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            if (data.success) {
                //preencher campos
                $("#nome_arbitro").val(data.arb_info.nomeArbitro);
                $("#nome_aux1").val(data.arb_info.nomeAuxiliarUm);
                $("#nome_aux2").val(data.arb_info.nomeAuxiliarDois);
                $("#estilo_arbitro").val(data.arb_info.estilo);
                $("#pais_arbitro").val(data.arb_info.pais);
				$("#nascimento_arbitro").val(data.arb_info.nascimento);

            }

            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });

});


});
</script>


<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>
<div id='errorbox'></div>
<div>
<div id='inscricao'>

<form method="POST" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <label for='nome_arbitro'>Nome árbitro</label>
	<input type='text' name='nome_arbitro' id='nome_arbitro' class='form-control' />

    <label for='nome_aux1'>Nome auxiliar 1</label>
	<input type='text' name='nome_aux1' id='nome_aux1' class='form-control'/>


	<label for='nome_aux2'>Nome auxiliar 2</label>
	<input type='text' name='nome_aux2' id='nome_aux2' class='form-control' />

	<label for='estilo_arbitro'>Estilo</label>
	<select class="form-control" name="estilo_arbitro" id='estilo_arbitro'>
		<option value="1">Gosta de deixar o jogo rolar</option>
		<option value="2">Prefere conversar a dar cartões</option>
		<option selected value="3">Moderado</option>
		<option value="4">Rígido</option>
		<option value="5">Carrasco</option>
	</select>

	<label for='nacionalidade_arbitro' class="td_inv input_nome_time">Nacionalidade</label>
		<?php
		// ler times do banco de dados
		$stmt = $pais->read();

		// put them in a select drop-down
		echo "<select class='form-control' name='nacionalidade_arbitro' id='pais_arbitro'>";
		echo "<option>Selecione país...</option>";

		while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row_category);
			echo "<option value='{$id}'>{$nome}</option>";
		}

		echo "</select>";
		?>
		
	<label for='genero_arbitro'>Gênero</label>
	<select class="form-control" name="genero_arbitro" id='genero_arbitro'>
		<option selected value="0">Masculino</option>
		<option value="1">Feminino</option>
		<option value="2">Misto</option>
	</select>
	
	<label for='nivel_arbitro'>Nível</label>
	<select class="form-control" name="nivel_arbitro" id='nivel_arbitro'>
		<option selected value="0">Nacional</option>
		<option value="1">Regional</option>
		<option value="2">Internacional</option>
	</select>
		
	<label for='nascimento_arbitro'>Nascimento</label>
	<input type='date' id='nascimento_arbitro' name='nascimento_arbitro' class='form-control inputHerdeiro' /></td>
		
	<input type='submit' value='Inserir' id='salvar'/>		

</form>
</div>
</div>

<?php

    } else {
		echo "Usuário sem permissão para inserir árbitros, por favor faça o login.";
	}
echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
echo "</body>";
echo "</html>";
?>
