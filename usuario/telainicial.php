<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Tela inicial - ".$nomereal;
$css_filename = "indexRanking";
$aux_css = "arbitro";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo "<div id='quadro-container'>";
echo "<h2>Tela inicial - ".$nomereal."</h2>";
echo "<hr>";


echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
