<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$parametro = new Parametro($db);
$pais = new Pais($db);

$page_title = "Inserir parâmetros";
$css_filename = "indexRanking";
$css_login = "login";
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome'])){


    // set product property values
    $parametro->nome = $_POST['nome'];
    $parametro->dono = $_POST['dono'];
    $parametro->gols = $_POST['gols'];
    $parametro->faltas = $_POST['faltas'];
    $parametro->impedimentos = $_POST['impedimentos'];
    $parametro->cartoes = $_POST['cartoes'];
    $parametro->estilo = $_POST['estilo'];
    if(isset($_POST['selecionado'])){
        $parametro->selecionado = 1;
    } else {
        $parametro->selecionado = 0;
    }
    $parametro->paisPadrao = $_POST['paisPadrao'];
    if(isset($_POST['exibirBandeiras'])){
        $parametro->exibirBandeiras = 1;
    } else {
        $parametro->exibirBandeiras = 0;
    }


    // create the product
    if($parametro->inserir()){
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Parâmetros inseridos com sucesso!</div>";
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir os parâmetros!</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir parâmetros, campos em branco!</div>";
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


<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <input type="hidden" name="dono" value="<?php echo $_SESSION['user_id']?>"/>

    <table class='table table-below float-table'>



        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome</td>
            <td class="td_inv input_nome_time"><input type='text' name='nome' class='form-control' /></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Gols</td>
            <td class="td_inv input_nome_time slider_container">
            <div class='slider_itself' id="sliderGols">
                <div id="custom-handleGols" class="ui-slider-handle"></div>
                <input type='hidden' name='gols' id='inputGols' value=""/>
            </div>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Faltas</td>
            <td class="td_inv input_nome_time slider_container">
            <div class='slider_itself' id="sliderFaltas">
                <div id="custom-handleFaltas" class="ui-slider-handle"></div>
                <input type='hidden' name='faltas' id='inputFaltas' value=""/>
            </div>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Impedimentos</td>
            <td class="td_inv input_nome_time slider_container">
            <div class='slider_itself' id="sliderImpedimentos">
                <div id="custom-handleImpedimentos" class="ui-slider-handle"></div>
                <input type='hidden' name='impedimentos' id='inputImpedimentos' value=""/>
            </div>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Cartões</td>
            <td class="td_inv input_nome_time slider_container">
            <div class='slider_itself' id="sliderCartoes">
                <div id="custom-handleCartoes" class="ui-slider-handle"></div>
                <input type='hidden' name='cartoes' id='inputCartoes' value=""/>
            </div>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Estilo</td>
            <td class="td_inv input_nome_time slider_container">
            <span id='spanEstilo'></span>
            <div class='slider_itself' id="sliderEstilo">
                <input type='hidden' name='estilo' id='inputEstilo' value=""/>
            </div>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Padrão?</td>
            <td class="td_inv input_nome_time checkbox_container"><input type='checkbox' name='selecionado'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">País Padrão</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $pais->read($_SESSION['user_id']);

                // put them in a select drop-down
                echo "<select class='form-control' name='paisPadrao'>";
                echo "<option value='0'>-</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Exibir Bandeiras?</td>
            <td class="td_inv input_nome_time checkbox_container"><input type='checkbox' name='exibirBandeiras'/></td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="criar" class="btn">Inserir</button>
            </td>
        </tr>

    </table>
</form>

  <script>
  $( function() {
    var handle = $( "#custom-handleGols" );
    $( "#sliderGols" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
      },
      slide: function( event, ui ) {
        handle.text( ui.value );
        $("#inputGols").val(ui.value);
      },
        min: 1, // min value
        max: 20, // max value
        step: 1,
        value: 10, // default value of slider
    });
  } );


  $( function() {
    var handle = $( "#custom-handleFaltas" );
    $( "#sliderFaltas" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
      },
      slide: function( event, ui ) {
        handle.text( ui.value );
        $("#inputFaltas").val(ui.value);
      },
        min: 1, // min value
        max: 20, // max value
        step: 1,
        value: 10, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleImpedimentos" );
    $( "#sliderImpedimentos" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
      },
      slide: function( event, ui ) {
        handle.text( ui.value );
        $("#inputImpedimentos").val(ui.value);
      },
        min: 0, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleCartoes" );
    $( "#sliderCartoes" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
      },
      slide: function( event, ui ) {
        handle.text( ui.value );
        $("#inputCartoes").val(ui.value);
      },
        min: 0, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

    $( function() {

        var valueCorrelation = {
            1: "Pelo chão",
            2: "Mais pelo chão",
            3: "Intermediário",
            4: "Mais pelo alto",
            5: "Pelo alto"
    };

    $( "#sliderEstilo" ).slider({
      create: function() {
        $("#spanEstilo").html(valueCorrelation[$( this ).slider( "value" )]);
        $("#inputEstilo").val($( this ).slider( "value" ));
      },
      slide: function( event, ui ) {



        $("#spanEstilo").html(valueCorrelation[ui.value]);

        $("#inputEstilo").val(ui.value);
      },
        min: 1, // min value
        max: 5, // max value
        step: 1,
        value: 3, // default value of slider
    });
  } );
  </script>

<?php

    } else {

    echo "Usuário sem permissão para criar parâmetros, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
