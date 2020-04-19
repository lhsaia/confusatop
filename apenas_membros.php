<?php
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA - CONFUSA.top";
$css_filename = "newindex";
$css_login = 'login';
$css_versao = date('h:i:s');

$homeURL = "https://confusa.top";
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  header('Location: '.$homeURL);
}

?>


<!DOCTYPE html>

<?php


include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");


?>

<div id="tabela-quadros">

  <img class='tela-cheia' src="/images/policia.png?1" />
  <span class='texto-sobreposto'>Desculpe, essa área é reservada para membros logados.</span>

</div>

<script>

$('body').
  var logged = $('body').attr('class');
  if (logged.localeCompare("loggedin") == 1){
    window.location("https://confusa.top");
  }



</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
