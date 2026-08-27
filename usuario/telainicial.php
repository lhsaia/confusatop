<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Tela inicial - ".($nomereal ?? '');
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo "<div class='propostas-container'>";
echo "<div class='propostas-card'>";
echo "<h2 class='propostas-title'>Tela inicial - ".($nomereal ?? '')."</h2>";

echo "</div>";
echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
