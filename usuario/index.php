<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Tela inicial - ".$_SESSION['nomereal'];
//$css_filename = "indexRanking";
$css_filename = "newindex";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

echo "<div id='tabela-quadros-usuario' class=''>";

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);
$tecnico = new Tecnico($db);
$usuario = new Usuario($db);
$propostasPendentes = $jogador->contarPropostas($_SESSION['user_id'],true);
$propostasPendentesTecnico = $tecnico->contarPropostas($_SESSION['user_id'],true);

//$propostasPendentes = 10;
$tempoDesatualizado = $usuario->alteracoesPosteriores($_SESSION['user_id']);
$horas = round($tempoDesatualizado/3600,1);

?>

<div id='errorbox'></div>


<a href='meuspaises.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/paises.jpg" /><span>Países</span></a>
<a href="minhasligas.php" class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/ligas.jpg" /><span>Ligas</span></a>
<a href='meustimes.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/times.jpeg" /><span>Times</span></a>
<a href='meusjogadores.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/jogadores.jpg" /><span>Jogadores</span></a>
<a href='meustecnicos.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/tecnicos.png" /><span>Técnicos</span></a>
<a href='meusestadios.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/estadios.jpg" /><span>Estádios</span></a>
<a href='meusclimas.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/clima.jpg" /><span>Climas</span></a>
<a href='meusparametros.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/parametro.jpg" /><span>Parâmetros HYMT</span></a>


<a href='minhaexportacao.php' id='quadro-exportar' href='' title='<?php echo ($tempoDesatualizado > 0 ? "Alterações feitas ".$horas ." horas após o último download" :"Banco de dados atualizado") ?>' class='<?php echo ($tempoDesatualizado > 0 ? "export_pending":"") ?> quadro-flex-usuario quadro-animado azul-marinho exportar'><img src="/images/hymt.jpg" /><span>Exportar para HYMT</span><?php  echo (($tempoDesatualizado > 0) ?  "<div id='tempoDesatualizado'><i class='fas fa-bell'></i></div>" : ""); ?></a>

<a href='minhaspropostas.php' id="propostas" class='quadro-flex-usuario quadro-animado azul-marinho' ><img src="/images/proposta_jogador.jpg" /><span>Propostas de Jogadores</span><?php  echo (($propostasPendentes > 0) ?  "<div class='propostasPendentes'>".$propostasPendentes."</div>" : ""); ?> </a>

<a href='minhaspropostastecnicos.php' id="propostasTecnicos" class='quadro-flex-usuario quadro-animado azul-marinho' ><img src="/images/proposta_tecnico.jpg" /><span>Propostas de Técnicos</span><?php  echo (($propostasPendentesTecnico > 0) ?  "<div class='propostasPendentes'>".$propostasPendentesTecnico."</div>" : ""); ?> </a>

<a href='jogadores_exterior.php' class='quadro-flex-usuario quadro-animado azul-marinho'><img src="/images/exterior.jpeg" /><span>Jogadores no exterior</span></a>



<?php


echo('</div>');

?>

<script>

 // $(".exportar").click(function(event){

    // //


    // $.ajax({
        // url         : 'verificar_exportacao.php', // the url where we want to POST
        // dataType: 'json'
    // }).done(function(response) {

        // if(response.success){
        // //$('#errorbox').append('<div class="alert alert-danger">Não foi possível exportar o banco de dados, '+data.error+'</div>');
        // //window.open('exportar_database.php', 'download_window', 'toolbar=0,location=no,directories=0,status=0,scrollbars=0,resizeable=0,width=1,height=1,top=0,left=0');
        // //window.focus();
        // $("#errorbox").empty();
        // $('#errorbox').append("<div class='alert alert-success'>Banco de dados verificado, a exportação iniciará em instantes! Aguarde.</div>");
        // $('#quadro-exportar').addClass('disabled');
        // $('html, body').css("cursor", "wait");
        // window.location = 'exportar_database_imp2.php';
        // } else {
        // $("#errorbox").empty();
        // $('#errorbox').append("<div class='alert alert-danger'>Banco de dados não pode ser exportado pelos seguintes motivos:</br>"+response.errors+"</div>");
        // $('#quadro-exportar').addClass('export_denied');
        // }
// }).fail(function(response) {
          // $('#errorbox').append("<div class='alert alert-danger'>Houve um erro não esperado no processamento da exportação, por favor contacte o admin.<div>");
          // $('#quadro-exportar').addClass('export_denied');
        // });
 // event.preventDefault();
 // });

 // window.onblur = function() {
  // $('html, body').css("cursor", "auto");
// }


</script>


<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
