<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Mercado CONFUSA";
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo "<div id='quadro-container'>";
echo "<h2>Mercado CONFUSA</h2>";
echo "<hr>";

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);

?>

<div id='errorbox'></div>

<a href='transferencias.php?type=ultimas' class='novoquadro'><i class="far fa-clock"></i><span>Últimas Transferências</span></a>
<a href="transferencias.php?type=maiores" class='novoquadro'><i class="fas fa-comments-dollar"></i><span>Maiores Transferências</span></a>
<a href='transferencias.php?type=janelas' class='novoquadro'><i class="far fa-calendar-alt"></i><span>Janelas de Transferência</span></a>
<a href='transferencias.php?type=jogadores' class='novoquadro'><i class="fas fa-file-invoice-dollar"></i><span>Jogadores mais Valiosos</span></a>
<a href='transferencias.php?type=busca' class='novoquadro' disabled><i class="fas fa-search"></i><span>Busca de Jogadores</span></a>
<a href='transferencias.php?type=buscaTecnico' class='novoquadro' disabled><i class="fas fa-search-plus"></i><span>Busca de Técnicos</span></a>



<?php


echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
