<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$jogador = new Jogador($db);
$pais = new Pais($db);
$usuario = new Usuario($db);
$time = new Time($db);

$page_title = "Criar Jogador";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'criar';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// se jogador foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && isset($_POST['nascimento']) && $_POST['pais'] != 0 && !empty($_POST['comboPosicoes'])){


    // atributos basicos dos jogadores
    $jogador->nomeJogador = $_POST['nome'];
    $jogador->nascimento = $_POST['nascimento'];
    $jogador->mentalidade = $_POST['mentalidade'];
    $jogador->cobradorFalta = $_POST['cobradorFalta'];
    $jogador->pais = $_POST['pais'];
    $jogador->valor = $_POST['valor'];
    $jogador->nivel = $_POST['nivel'];
    $jogador->determinacao = $_POST['determinacao'];
    $jogador->determinacaoOriginal = $_POST['determinacao'];
    $jogador->sexo = $_POST['sexo'];

    if(isset($_POST['clube'])){
		$clube = $_POST['clube'];
	} else {
		$clube = 0;
	}

    //var_dump($_POST);
	
    //posicoes
    $prePosicoes = $_POST['comboPosicoes'];
    $stringPosicoes = '';

    if(array_search(1,$prePosicoes) === 0){
        $isGoleiro = true;
    } else {
        $isGoleiro = false;
    }

	
	//exit();
    //var_dump($prePosicoes);

    for($i = 0;$i < 15;$i++){
        $codigo = $i+1;
        if(array_search($codigo,$prePosicoes) !== false){
            $stringPosicoes .= "1";
        } else {
            $stringPosicoes .= "0";
        }
    }


    $jogador->stringPosicoes = $stringPosicoes;

    if($isGoleiro){
        //atributos complexos dos goleiros
        $jogador->reflexos = $_POST['reflexos'];
        $jogador->seguranca = $_POST['seguranca'];
        $jogador->saidas = $_POST['saidas'];
        $jogador->jogoAereo = $_POST['jogoaereo'];
        $jogador->lancamentos = $_POST['lancamentos'];
        $jogador->defesaPenaltis = $_POST['defesapenaltis'];
        $jogador->marcacao = 0;
        $jogador->desarme = 0;
        $jogador->visaoJogo = 0;
        $jogador->movimentacao = 0;
        $jogador->cruzamentos = 0;
        $jogador->cabeceamento = 0;
        $jogador->tecnica = 0;
        $jogador->controleBola = 0;
        $jogador->finalizacao = 0;
        $jogador->faroGol = 0;
        $jogador->velocidade = 0;
        $jogador->forca = 0;
    } else {
            //atributos complexos dos jogadores
        $jogador->marcacao = $_POST['marcacao'];
        $jogador->desarme = $_POST['desarme'];
        $jogador->visaoJogo = $_POST['visaojogo'];
        $jogador->movimentacao = $_POST['movimentacao'];
        $jogador->cruzamentos = $_POST['cruzamentos'];
        $jogador->cabeceamento = $_POST['cabeceamento'];
        $jogador->tecnica = $_POST['tecnica'];
        $jogador->controleBola = $_POST['controlebola'];
        $jogador->finalizacao = $_POST['finalizacao'];
        $jogador->faroGol = $_POST['farogol'];
        $jogador->velocidade = $_POST['velocidade'];
        $jogador->forca = $_POST['forca'];
        $jogador->reflexos = 0;
        $jogador->seguranca = 0;
        $jogador->saidas = 0;
        $jogador->jogoAereo = 0;
        $jogador->lancamentos = 0;
        $jogador->defesaPenaltis = 0;
    }




    //var_dump(get_object_vars($jogador));

    // create the product
    if($jogador->create(true)){
        $idJogadorCriado = $db->lastInsertId();
        if($clube != 0){
          if($jogador->transferir($idJogadorCriado,$clube,0,0,-1)){
            echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Jogador inserido e transferido com sucesso!</div>";
          } else {
            echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Jogador inserido com sucesso, mas houve falha na transferência.</div>";
          }
        } else {
          echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Jogador inserido com sucesso!</div>";
        }
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir o jogador!</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir jogador, campos em branco!</div>";
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

    <table class='table table-below float-table'>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Nome</td>
            <td class="td_inv input_nome_time"><input type='text' name='nome' id='nomeJogador' class='form-control inputHerdeiro' /></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Nascimento</td>
            <td class="td_inv input_nome_time"><input type='date' id='nascimentoJogador' name='nascimento' class='form-control inputHerdeiro' /></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Mentalidade</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $jogador->listaMentalidade();

                // put them in a select drop-down
                echo "<select class='form-control' id='mentalidade'  name='mentalidade'>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$ID}'>{$Nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Cobrador de Falta</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $jogador->listaCobradorFalta();

                // put them in a select drop-down
                echo "<select class='form-control' id='cobrador' name='cobradorFalta'>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$ID}'>{$Nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Valor</td>
            <td class="td_inv input_nome_time"><input type='number' id='valor' name='valor' value='0' min='0' class='form-control inputHerdeiro' /></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Nivel</td>
            <td class="td_inv input_nome_time"><input type='number' id='nivel' value='60' max='99' min='1' name='nivel' class='form-control inputHerdeiro' /></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Determinação</td>
            <td class="td_inv input_nome_time"><input type='number' id='determinacao' max='5' min='1' name='determinacao' class='form-control inputHerdeiro' value='3'/></td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Masc/Fem</td>
            <td class="td_inv input_nome_time">

                <select class='form-control' id='sexo' name='sexo'>
                <option value='0'>Homem</option>
                <option value='1'>Mulher</option>
                </select>
            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Nacionalidade</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $pais->read();

                // put them in a select drop-down
                echo "<select class='form-control' id='pais' name='pais'>";
                echo "<option value='0'>-</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Time (opcional)</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $time->read($_SESSION['user_id'], null);

                // put them in a select drop-down
                echo "<select class='form-control' id='clube' name='clube'>";
                echo "<option value='0'>Sem clube</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

        <tr class="tr_inv spec_height">
            <td class="td_inv input_nome_time">Posições</td>
            <td class="td_inv input_nome_time">
                <?php
                // ler times do banco de dados
                $stmt = $jogador->selectPosicoes();

                // put them in a select drop-down
                echo "<select multiple class='comboPosicoes form-control' id='posicoes' name='comboPosicoes[]'>";
                echo "<option value='1'>G</option>";
                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$ID}'>{$Sigla}</option>";
                }

                echo "</select>";
                ?>
            </td>
        </tr>

<?php

        //pacote atributos jogador
        $listaAtributos = array
            (
            array("Marcação","Marcacao",0),
            array("Desarme","Desarme",0),
            array("Visão de Jogo","VisaoJogo",0),
            array("Movimentação","Movimentacao",0),
            array("Cruzamentos","Cruzamentos",0),
            array("Cabeceamento","Cabeceamento",0),
            array("Técnica","Tecnica",0),
            array("Controle de Bola","ControleBola",0),
            array("Finalização","Finalizacao",0),
            array("Faro de Gol","FaroGol",0),
            array("Velocidade","Velocidade",0),
            array("Força","Forca",0),
            array("Reflexos","Reflexos",1),
            array("Segurança","Seguranca",1),
            array("Saídas","Saidas",1),
            array("Jogo Aéreo","JogoAereo",1),
            array("Lançamentos","Lancamentos",1),
            array("Defesa de Pênaltis","DefesaPenaltis",1),
            );

            foreach($listaAtributos as $atributo){
                $inputName = $atributo[1];
                $inputLabel = $atributo[0];
                if($atributo[2] == 0){
                    $tipoAtributo = "atributo_jogador";
                } else {
                    $tipoAtributo = "atributo_goleiro";
                }

                $handlerInsert = '<tr class="tr_inv spec_height row_atributo '.$tipoAtributo.'">
                <td class="td_inv input_nome_time">'.$inputLabel.'</td>
                <td class="td_inv input_nome_time slider_container">
                <div class="slider_itself" id="slider'.$inputName.'">
                    <div id="custom-handle'.$inputName.'" class="ui-slider-handle"></div>
                    <input type="hidden" name="'.strtolower($inputName).'" id="input'.$inputName.'" value=""/>
                </div>
                </td>
                </tr>';

                echo $handlerInsert;

            }

        ?>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="criar" class="btn">Inserir</button>
                <button type="reset" name="reset" class="btn">Limpar</button>
                <button type='button' id="hexagen" class="btn"><i class="fas fa-dice"></i>&nbsp Hexagen</button>
            </td>
        </tr>

    </table>
</form>

  <script>
  $( function() {
    var handle = $( "#custom-handleMarcacao" );
    $( "#sliderMarcacao" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputMarcacao").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputMarcacao").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4 // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleDesarme" );
    $( "#sliderDesarme" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputDesarme").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputDesarme").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleCruzamentos" );
    $( "#sliderCruzamentos" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputCruzamentos").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputCruzamentos").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleCabeceamento" );
    $( "#sliderCabeceamento" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputCabeceamento").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputCabeceamento").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleFinalizacao" );
    $( "#sliderFinalizacao" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputFinalizacao").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputFinalizacao").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleFaroGol" );
    $( "#sliderFaroGol" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputFaroGol").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputFaroGol").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleMovimentacao" );
    $( "#sliderMovimentacao" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputMovimentacao").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputMovimentacao").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleTecnica" );
    $( "#sliderTecnica" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputTecnica").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputTecnica").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );
  $( function() {
    var handle = $( "#custom-handleVisaoJogo" );
    $( "#sliderVisaoJogo" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputVisaoJogo").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputVisaoJogo").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleControleBola" );
    $( "#sliderControleBola" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputControleBola").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputControleBola").val(ui.value);
      },
        min: 1, // min value
        max: 7, // max value
        step: 1,
        value: 4, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleForca" );
    $( "#sliderForca" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputForca").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputForca").val(ui.value);
      },
        min: 1, // min value
        max: 5, // max value
        step: 1,
        value: 3, // default value of slider
    });
  } );


  $( function() {
    var handle = $( "#custom-handleVelocidade" );
    $( "#sliderVelocidade" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputVelocidade").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputVelocidade").val(ui.value);
      },
        min: 1, // min value
        max: 5, // max value
        step: 1,
        value: 3, // default value of slider
    });
  } );

    $( function() {
    var handle = $( "#custom-handleReflexos" );
    $( "#sliderReflexos" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputReflexos").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputReflexos").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleSaidas" );
    $( "#sliderSaidas" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputSaidas").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputSaidas").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleLancamentos" );
    $( "#sliderLancamentos" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputLancamentos").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputLancamentos").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleSeguranca" );
    $( "#sliderSeguranca" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputSeguranca").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputSeguranca").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleJogoAereo" );
    $( "#sliderJogoAereo" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputJogoAereo").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputJogoAereo").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

      $( function() {
    var handle = $( "#custom-handleDefesaPenaltis" );
    $( "#sliderDefesaPenaltis" ).slider({
      create: function() {
        handle.text( $( this ).slider( "value" ) );
        $("#inputDefesaPenaltis").val($( this ).slider( "value" ));
      },
      change: function( event, ui ) {
        handle.text( ui.value );
        $("#inputDefesaPenaltis").val(ui.value);
      },
        min: 1, // min value
        max: 10, // max value
        step: 1,
        value: 5, // default value of slider
    });
  } );

  $(".comboPosicoes").on("change", function(){
      if($(this).val() == 1){
          $(this).prop("multiple", false);
          $(".atributo_jogador").each(function(){
              $(this).hide();
          });
          $(".atributo_goleiro").each(function(){
              $(this).show();
          });
      } else {
          $(this).prop("multiple", true);
          $(".atributo_jogador").each(function(){
              $(this).show();
          });
          $(".atributo_goleiro").each(function(){
              $(this).hide();
          });
      }
  });


  $(function () {
  $("#determinacao").keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 5 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });
  $("#determinacao").keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 5 && parseInt($(this).val()) >= 1))
      ;
    else
      $(this).val($(this).data("old"));
  });
});

  $(function () {
  $("#nivel").keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 99 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });
  $("#nivel").keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 99 && parseInt($(this).val()) >= 1))
      ;
    else
      $(this).val($(this).data("old"));
  });
});


$("#cobrador").val(0);
$("#mentalidade").val(4);

$("#hexagen").on("click",function(){
    var nacionalidade = $("#pais").val();
    var codigoPosicao = $("#posicoes option:selected").first().val();
    var sexo = $("#sexo").val();

    if (typeof codigoPosicao == 'undefined'){
        codigoPosicao = 0;
    }

    var formData = {
        'nacionalidade' : nacionalidade,
        'codigoPosicao' : codigoPosicao,
        'sexo' : sexo
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/hexagen.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                //preencher campos
                $("#nomeJogador").val(data.player_info.nomeJogador);
                $("#nascimentoJogador").val(data.player_info.nascimento);
                $("#mentalidade").val(data.player_info.mentalidade);
                $("#cobrador").val(data.player_info.cobradorFalta);
                $("#valor").val(data.player_info.valor);
                $("#nivel").val(data.player_info.nivel);
                $("#determinacao").val(data.player_info.determinacao);
                $("#pais").val(data.player_info.pais);

                //$("#sliderMarcacao").val(data.player_info.marcacao).slider( "refresh" );

                $("#sliderMarcacao").slider( "value", data.player_info.marcacao );
                $("#sliderDesarme").slider( "value", data.player_info.desarme );
                $("#sliderVisaoJogo").slider( "value", data.player_info.visaoJogo );
                $("#sliderMovimentacao").slider( "value", data.player_info.movimentacao );
                $("#sliderCruzamentos").slider( "value", data.player_info.cruzamentos );
                $("#sliderCabeceamento").slider( "value", data.player_info.cabeceamento );
                $("#sliderTecnica").slider( "value", data.player_info.tecnica );
                $("#sliderControleBola").slider( "value", data.player_info.controleBola );
                $("#sliderFinalizacao").slider( "value", data.player_info.finalizacao );
                $("#sliderFaroGol").slider( "value", data.player_info.faroGol );
                $("#sliderVelocidade").slider( "value", data.player_info.velocidade );
                $("#sliderForca").slider( "value", data.player_info.forca );
                $("#sliderReflexos").slider( "value", data.player_info.reflexos );
                $("#sliderSeguranca").slider( "value", data.player_info.seguranca );
                $("#sliderSaidas").slider( "value", data.player_info.saidas );
                $("#sliderJogoAereo").slider( "value", data.player_info.jogoAereo );
                $("#sliderLancamentos").slider( "value", data.player_info.lancamentos );
                $("#sliderDefesaPenaltis").slider( "value", data.player_info.defesaPenaltis );


                var stringPosicoes = data.player_info.stringPosicoes;
                var arrayPosicoes = [];

                for(i = 0;i<15;i++){
                    if(stringPosicoes.charAt(i) == '1'){
                        arrayPosicoes.push(i+1);
                    }
                }

                $("#posicoes").val(arrayPosicoes).change();



            }

            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });

});

function mudar_genero(genero_selecionado){
  $("#clube option").each(function(){
    var genero_time = parseInt($(this).attr('data-sexo'));
    if (genero_time === genero_selecionado){
      $(this).show();
      $(this).prop("disabled", false);

    } else {
      $(this).hide();
      $(this).prop("disabled", "disabled");
    }
  });
}

$("#sexo").change(function(){
  var genero_atual = parseInt($(this).val());

  mudar_genero(genero_atual);
});

$(document).ready(function(){
  mudar_genero(0);
});




  </script>

<?php

    } else {

    echo "Usuário sem permissão para criar jogadores, por favor faça o login.";
}


echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
