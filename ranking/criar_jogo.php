<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$jogo = new Jogo($db);
$pais = new Pais($db);
$competicao = new Competicao($db);

$page_title = "Inserir jogo";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['timeA_id']) && !empty($_POST['timeA_id']) && !empty($_POST['timeB_id']) && !empty($_POST['data'])){




    // set product property values
    $jogo->timeA_id = $_POST['timeA_id'];
    $jogo->timeA_gols = $_POST['timeA_gols'];
    $jogo->timeB_id = $_POST['timeB_id'];
    $jogo->timeB_gols = $_POST['timeB_gols'];
    $jogo->timeA_penaltis = $_POST['timeA_penaltis'];
    $jogo->timeB_penaltis = $_POST['timeB_penaltis'];
    $jogo->data = $_POST['data'];
    $jogo->campeonato = $_POST['campeonato'];


    // create the product
    if($jogo->inserir()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Jogo inserido com sucesso</div>";
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o jogo, possível duplicata</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o jogo, campos em branco</div>";
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
            <td class="td_inv input_nome_time">Time A</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $pais->read(null,true);

                // put them in a select drop-down
                echo "<select class='form-control' name='timeA_id'>";
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
            <td class="td_inv input_gols">Gols A</td>
            <td class="td_inv input_gols"><input type='text' name='timeA_gols' class='form-control' /></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv x_placar"></td>
            <td class="td_inv x_placar">X</td>

        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_gols">Gols B</td>
            <td class="td_inv input_gols"><input type='text' name='timeB_gols' class='form-control'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Time B</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $pais->read(null,true);

                // put them in a select drop-down
                echo "<select class='form-control' name='timeB_id'>";
                echo "<option>Selecione país...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>
        <!--
        <tr class="tr_inv">
        <td class="td_inv input_gols">Pen?</td>
            <td class="td_inv input_gols"><input type='checkbox' name='penaltis_ocorreram'/></td>
        </tr>
        -->
        <tr class="tr_inv">
            <td class="td_inv input_gols">Pen. A</td>
            <td class="td_inv input_gols"><input type='text' name='timeA_penaltis' class='form-control'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_gols">Pen. B</td>
            <td class="td_inv input_gols"><input type='text' name='timeB_penaltis' class='form-control'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_data">Data</td>
            <td class="td_inv input_data"><input type='date' name='data' class='form-control' min='2006-01-01'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Campeonato</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $competicao->read();

                // put them in a select drop-down
                echo "<select class='form-control' name='campeonato'>";
                echo "<option>Selecione campeonato...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

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

    echo "Usuário sem permissão para editar jogos, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
