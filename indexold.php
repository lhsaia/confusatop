<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA - CONFUSA.top";
$css_filename = "mainindex";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<div id="tabela-quadros">

<?php
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    echo '<a href="export/montarcompeticao.php" class="quadro-principal novo" id="pacotes">Gerador de Pacotes</a>';
    echo '<a href="usuario" class="quadro-principal" id="usuario">Minha área</a>';
}
?>

<a href="ligas" class="quadro-principal" id="ligas">Ligas</a>


<a href="mercado" class="quadro-principal" id="mercado">Mercado</a>
<a href="ranking" class="quadro-principal" id="ranking">Ranking</a>
<a href="arbitros" class="quadro-principal" id="arbitros">Quadro de árbitros</a>
<a href="http://confusalive.com" class="quadro-principal" id="live">CONFUSA Live</a>
<a href="http://vk.com/futebolsolitario" class="quadro-principal" id="vk">VK - Futebol Solitário</a>
<a href="http://confusa.wikia.com/wiki/P%C3%A1gina_principal" class="quadro-principal" id="confusopedia">Confusopédia</a>
<a href="http://portalcoiso.info.tm" class="quadro-principal" id="portal">Portal COISO</a>

</div>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
