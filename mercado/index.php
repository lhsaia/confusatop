<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Mercado CONFUSA";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'usuario';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);

?>

<div id="tabela-quadros-mercado">

<a href='transferencias.php?type=busca' class='quadro-flex-mercado quadro-animado' disabled><img src="/images/mercado/buscajogadores.webp" /><span>Busca de Jogadores</span></a>
<a href='transferencias.php?type=buscaTecnico' class='quadro-flex-mercado quadro-animado' disabled><img src="/images/mercado/buscatecnico.webp" /><span>Busca de Técnicos</span></a>
<a href='transferencias.php?type=jogadores' class='quadro-flex-mercado quadro-animado'><img src="/images/mercado/maisvaliosos.webp" /><span>Jogadores mais Valiosos</span></a>
<a href='transferencias.php?type=ultimas' class='quadro-flex-mercado quadro-animado'><img src="/images/mercado/ultimastransferencias.webp" /><span>Últimas Transferências</span></a>
<a href="transferencias.php?type=maiores" class='quadro-flex-mercado quadro-animado'><img src="/images/mercado/maiorestransferencias.webp" /><span>Maiores Transferências</span></a>
<a href='transferencias.php?type=janelas' class='quadro-flex-mercado quadro-animado'><img src="/images/mercado/janelatransferencias.webp" /><span>Janelas de Transferência</span></a>

<?php


echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
