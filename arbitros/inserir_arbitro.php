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
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome_arbitro']) && !empty($_POST['nome_aux2']) && !empty($_POST['nome_aux1']) && !empty($_POST['nome_arbitro'])){




    // set product property values
    $arbitro->nomeArbitro = $_POST['nome_arbitro'];
    $arbitro->nomeAuxiliarUm = $_POST['nome_aux1'];
    $arbitro->nomeAuxiliarDois = $_POST['nome_aux2'];
    $arbitro->estilo = $_POST['estilo_arbitro'];
    $arbitro->pais = $_POST['nacionalidade_arbitro'];




    // create the product
    if($arbitro->create()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Árbitro inserido com sucesso</div>";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o árbitro, possível duplicata</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o árbitro, campos em branco</div>";
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


<form method="POST" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <table class='table table-below float-table'>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome árbitro</td>
            <td class="td_inv input_nome_time">
                <input type='text' name='nome_arbitro' id='nome_arbitro' class='form-control' />
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome auxiliar 1</td>
            <td class="td_inv input_nome_time">
                <input type='text' name='nome_aux1' id='nome_aux1' class='form-control' />
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome auxiliar 2</td>
            <td class="td_inv input_nome_time">
                <input type='text' name='nome_aux2' id='nome_aux2' class='form-control' />
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Estilo</td>
            <td class="td_inv input_nome_time">
                <select class="form-control" name="estilo_arbitro" id='estilo_arbitro'>
                    <option value="1">Gosta de deixar o jogo rolar</option>
                    <option value="2">Prefere conversar a dar cartões</option>
                    <option selected value="3">Moderado</option>
                    <option value="4">Rígido</option>
                    <option value="5">Carrasco</option>
                </select>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nacionalidade</td>
            <td class="td_inv input_nome_time">
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
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time"><i class="fas fa-dice"></i>&nbsp Sexo</td>
            <td class="td_inv input_nome_time">
                <select class="form-control" name="sexo_arbitro" id='sexo_arbitro'>
                    <option selected value="0">Masculino</option>
                    <option value="1">Feminino</option>
                    <option value="2">Misto</option>
                </select>
            </td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="criar" class="btn">Inserir</button>
                <button type='button' id="hexagen" class="btn"><i class="fas fa-dice"></i>&nbsp Hexagen</button>
            </td>
        </tr>

    </table>
</form>

<script>

$("#hexagen").on("click",function(){
    var nacionalidade = $("#pais_arbitro").val();
    var sexo = $("#sexo_arbitro").val();

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

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                //preencher campos
                $("#nome_arbitro").val(data.arb_info.nomeArbitro);
                $("#nome_aux1").val(data.arb_info.nomeAuxiliarUm);
                $("#nome_aux2").val(data.arb_info.nomeAuxiliarDois);
                $("#estilo_arbitro").val(data.arb_info.estilo);
                $("#pais_arbitro").val(data.arb_info.pais);

            }

            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });

});

</script>

<?php

    } else {

    echo "Usuário sem permissão para inserir árbitros, por favor faça o login.";
}
echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
echo "</body>";
echo "</html>";
?>
