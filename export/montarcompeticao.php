<?php
// header + login
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/export_torneios.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$time = new Time($db);
$torneio = new ExportTorneio($db);

// query caixa de seleção países
$stmtPais = $pais->read(null,true,null);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome, $federacao);
    $listaPaises[] = $addArray;
}

// query caixa de seleção torneios
$stmtTorneio = $torneio->readAll();
$listaTorneios = array();
while ($row_torneios = $stmtTorneio->fetch(PDO::FETCH_ASSOC)){
    extract($row_torneios);
    $addArray = array($ID, $Nome, $Federacao, $Genero, $NumParticipantes, $Participantes, $Sede);
    $listaTorneios[] = $addArray;
}

// query caixa de seleção times
$stmtTime = $time->read(null,false);
$listaTimes = array();
while ($row_times = $stmtTime->fetch(PDO::FETCH_ASSOC)){
    extract($row_times);
    $addArray = array($id, $nome, $paisTime, $Sexo);
    $listaTimes[] = $addArray;
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Gerador de pacotes";
$css_filename = "indexRanking";
$aux_css = "ligas";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

// inicializar formulario
?>


<div id="quadro-container">
  <h2><?php echo $page_title?></h2>

  <hr>
  <div id="errorbox"></div>
  <iframe id="package_download" hidden>
    <head>
      <meta http-Equiv="Cache-Control" Content="no-cache" />
      <meta http-Equiv="Pragma" Content="no-cache" />
      <meta http-Equiv="Expires" Content="0" />
  </head></iframe>
  <form id='criar_campeonato'>

    <!-- <fieldset>
      <legend> Definições: </legend> -->

      <!-- // nome da competição (textbox) -->
      <label for='input_nome'>Competição:</label>
      <select id='input_nome' name='nomecampeonato' class='smallform'>
        <option value=0>Outra</option>
        <?php
        foreach ($listaTorneios as $torneioSelecionado){
          echo "<option data-sede='{$torneioSelecionado[6]}' data-lista='{$torneioSelecionado[5]}' data-numpart='{$torneioSelecionado[4]}' data-genero='{$torneioSelecionado[3]}' data-federacao='{$torneioSelecionado[2]}' value='{$torneioSelecionado[0]}'>{$torneioSelecionado[1]}</option>";
        }
         ?>
      </select>

      <!-- // masculino ou feminino (select) -->
      <label for='input_genero'>M / F?</label>
      <select id='input_genero' name='generocampeonato' class='smallform'>
        <option value=0>Masculino</option>
        <option value=1>Feminino</option>
      </select>

      <!-- // federação (select) -->
      <label for='input_federacao'>Federação:</label>
      <select id='input_federacao' name='federacaocampeonato' class='smallform'>
        <option value=0>CONFUSA</option>
        <option value=1>FEASCO</option>
        <option value=2>FEMIFUS</option>
        <option value=3>COMPACTA</option>
      </select>

      <!-- // numero de times (number) -->
      <label for='input_numero'>Número de equipes:</label>
      <input type='number' min='2' max='62' id='input_numero' name='timescampeonato' class='smallform'/>

      <div id="quadro_equipes_torneio">

      <!-- // sede [opcional] (select) -->
      <label for='input_sede'>Sede:</label>
      <select id='input_sede' name='sedecampeonato' class='smallform'>
        <option value=0>Sem sede fixa</option>
        <?php
        foreach ($listaPaises as $paisSelecionado){
          echo "<option value='{$paisSelecionado[0]}'>{$paisSelecionado[1]}</option>";
        }
         ?>
      </select>

      <div id="quadro_equipes_torneio">


<?php

for ($i = 1; $i < 63;$i++){
  echo "<div class='par_pais_equipe' id='par_pais_equipe_{$i}' hidden>";
  echo "<select id='selecaoPais{$i}' name='pais{$i}' class='smallform selecaoPais' placeholder='País...'>";
  echo "<option value=0>Selecione o país...</option>";
  foreach ($listaPaises as $paisSelecionado){
    echo "<option data-federacao='{$paisSelecionado[2]}' value='{$paisSelecionado[0]}'>{$paisSelecionado[1]}</option>";
  }
  echo "</select>";
  echo "<select id='selecaoTime{$i}' name='equipe{$i}' class='smallform selecaoTime' placeholder='Equipe...'>";
  echo "<option value=0>Selecione o time...</option>";
  foreach ($listaTimes as $timeSelecionado){
    echo "<option data-genero='{$timeSelecionado[3]}' data-pais='{$timeSelecionado[2]}' value='{$timeSelecionado[0]}'>{$timeSelecionado[1]}</option>";
  }
  echo "</select>";
  echo "</div>";
}

 ?>

    <!-- </fieldset> -->
  </div>
<br>
      <input id='submitButton' type='submit' value='Verificar e exportar' class='ui-button ui-widget ui-corner-all'/>

  </form>
</div>

<script>

  $("#submitButton").hide();

function mostrar_times(){
  $(".par_pais_equipe").each(function(){

    var codigo = parseInt($(this).attr('id').replace( /^\D+/g, ''));
    var numero_times = parseInt($("#input_numero").val());

    if (codigo <= numero_times){
      $(this).show();
    } else {
      $(this).hide();
    }

    if(numero_times >= 2){
      $("#submitButton").show();
    } else {
      $("#submitButton").hide();
    }
  });
}

$("#input_numero").change(function(){
  mostrar_times();


});

function mudar_federacao(federacao){
  if(federacao != 0){
    $(".selecaoPais option").each(function(){
      var federacao_time = parseInt($(this).attr('data-federacao'));
      if (federacao_time === federacao){
        $(this).show();
        $(this).prop("disabled", false);

      } else {
        $(this).hide();
        $(this).prop("disabled", "disabled");
      }
    });
  } else {
    $(".selecaoPais option").each(function(){
        $(this).show();
    });
  }
}

$("#input_federacao").change(function(){
  var codigo_federacao = parseInt($(this).val());
  mudar_federacao(codigo_federacao);
  $(".selecaoTime , .selecaoPais").each(function(){
    $(this).val(0);
  });
});

$("#input_nome").change(function(){
  var codigo_federacao = parseInt($("#input_federacao").val());
  mudar_federacao(codigo_federacao);
});

$("#input_numero").change(function(){
  var codigo_federacao = parseInt($("#input_federacao").val());
  mudar_federacao(codigo_federacao);
});

$("#input_genero").change(function(){
  $(".selecaoTime , .selecaoPais").each(function(){
    $(this).val(0);
  });
});


$(".selecaoPais").change(function(){
  var codigo_time = parseInt($(this).attr('id').replace( /^\D+/g, ''));
  var pais_selecionado = parseInt($(this).val());
  var genero_selecionado = parseInt($("#input_genero").val());
  $("#selecaoTime" + codigo_time + " option").each(function(){
    var pais_time = parseInt($(this).attr('data-pais'));
    var genero_time = parseInt($(this).attr('data-genero'));
    if (pais_time === pais_selecionado){
      if(genero_time === genero_selecionado){
        $(this).show();
        $(this).prop("disabled", false);

      } else {
        $(this).hide();
        $(this).prop("disabled", "disabled");
      }

    } else {
      $(this).hide();
      $(this).prop("disabled", "disabled");
    }
  });
});




$("#input_nome").change(function(){
  var competicao_selecionada = parseInt($(this).val());
  var pre_lista_times = $("option:selected", this).attr("data-lista");
  if(pre_lista_times !== "" && competicao_selecionada !== 0){
    var lista_times = pre_lista_times.split(',');
    var time_inicial = 1;
    lista_times.forEach(function(entry) {
        $("#selecaoTime"+time_inicial).val(entry);
        time_inicial++;
        //console.log(entry);
    });
  } else {
    $(".selecaoTime , .selecaoPais").each(function(){
      $(this).val(0);
    });
  }
  console.log(competicao_selecionada);
  if(competicao_selecionada != 0){
    var federacao_selecionada = parseInt($("option:selected", this).attr("data-federacao"));
    var genero_selecionado = parseInt($("option:selected", this).attr("data-genero"));
    var num_participantes = parseInt($("option:selected", this).attr("data-numpart"));
    var sede = parseInt($("option:selected", this).attr("data-sede"));
    $("#input_federacao").prop('disabled', 'disabled');
    $("#input_genero").prop('disabled', 'disabled');
    $("#input_federacao").val(federacao_selecionada);
    $("#input_genero").val(genero_selecionado);
    $("#input_numero").val(num_participantes);
    $("#input_sede").val(sede);
    mostrar_times();
  } else {
    $("#input_federacao").prop('disabled', false);
    $("#input_genero").prop('disabled', false);
  }
});


$("#submitButton").click(function(e){
  e.preventDefault();
  var array_times = [];
  $(".selecaoTime").each(function(){
    var time_selecionado = parseInt($(this).val());
    if(time_selecionado != 0){
      array_times.push(time_selecionado);
    }
  });

  var codigo_federacao = $("#input_federacao").val();
  var codigo_genero = $("#input_genero").val();
  var num_equipes = $("#input_numero").val();
  var codigo_competicao = $("#input_nome").val();
  var codigo_sede = $("#input_sede").val();

  //var json_array = JSON.stringify(array_times);
if(array_times.length > 0){
  $.ajax({
         type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
         url         : 'verificar_exportacao.php', // the url where we want to POST
         data        : {array_times : array_times, codigo_competicao : codigo_competicao, num_equipes : num_equipes, codigo_federacao : codigo_federacao, codigo_genero : codigo_genero, codigo_sede : codigo_sede}, // our data object
         dataType    : 'json', // what type of data do we expect back from the server
         encode          : true
     }).done(function(response) {

         if(response.success){
         //$('#errorbox').append('<div class="alert alert-danger">Não foi possível exportar o banco de dados, '+data.error+'</div>');
         //window.open('exportar_database.php', 'download_window', 'toolbar=0,location=no,directories=0,status=0,scrollbars=0,resizeable=0,width=1,height=1,top=0,left=0');
         //window.focus();
         $("#errorbox").empty();
         $('#errorbox').append("<div class='alert alert-success'>Banco de dados verificado, a exportação iniciará em instantes! Aguarde.</div>");
         //$('html, body').css("cursor", "wait");
         //window.location = 'exportar_database_imp2.php';

         var formData = new FormData();
         var federacao_campeonato = $("#input_federacao").val();
         var codigo_campeonato = $("#input_nome").val();
         var sede_campeonato = $("#input_sede").val();
         formData.append('array_times', array_times);
         formData.append('federacao_campeonato', federacao_campeonato);
         formData.append('codigo_campeonato', codigo_campeonato);
         formData.append('sede_campeonato', sede_campeonato);

         $.ajax({
                type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                url         : 'exportar_pacote.php', // the url where we want to POST
                data        : formData, // our data object
                dataType    : 'json', // what type of data do we expect back from the server
                processData: false,
               contentType: false,
               cache: false
            }).done(function(new_response) {

                if(new_response.success){
                  document.getElementById("package_download").src = new_response.filename;

                } else {

                $('#errorbox').append("<div class='alert alert-danger'>Banco de dados não pode ser exportado pelos seguintes motivos:</br>"+response.errors+"</div>");

                }
        }).fail(function(new_response) {
                  $('#errorbox').append("<div class='alert alert-danger'>Houve um erro não esperado no processamento da exportação, por favor contacte o admin.<div>");

                });
         //console.log(array_times);
       //});
         } else {
         $("#errorbox").empty();
         $('#errorbox').append("<div class='alert alert-danger'>Banco de dados não pode ser exportado pelos seguintes motivos:</br>"+response.errors+"</div>");

         }
 }).fail(function(response) {
           $('#errorbox').append("<div class='alert alert-danger'>Houve um erro não esperado no processamento da exportação, por favor contacte o admin.<div>");

         });
  //console.log(array_times);


} else {
  $("#errorbox").empty();
  $('#errorbox').append("<div class='alert alert-danger'>Nenhum time selecionado!</div>");
}

});

</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

// footer
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
