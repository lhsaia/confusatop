<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
$database = new Database();
$db = $database->getConnection();
$trioArbitragem = new TrioArbitragem($db);
$pais = new Pais($db);

//declaracoes de parametros
$page_title = "Importar árbitro";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 3;
//$importing_reference_page = "arbitros/importar_arbitro";


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

} else {
    echo "Usuário sem permissão para inserir árbitros, por favor faça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
