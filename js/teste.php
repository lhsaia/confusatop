<?php 

//inicialização das variáveis
$nome_arquivos = pathinfo((basename($_SERVER['PHP_SELF'])), PATHINFO_FILENAME);
$texto_footer = "&copy;2018 frontpage design by <a href='https://www.linkedin.com/in/luís-cereda'>Luis Cereda</a>";
include_once 'parametros.php';

//inicialização do cabeçalho
include_once 'header.php'; 

//definição dos elementos da tela
echo "<canvas id='canvas'></canvas>";
echo "<div id='centralAnimacao'>";
echo "<a href='#' id = 'escudo' class = 'itensAnimados fadeInDireita unclickable'><p hidden>Fundação do time</p><p hidden>{$link_escudo}</p></a>";
echo "<a href='#' id = 'estrela1' class = 'itensAnimados estrela estrela1Aparecer unclickable'><p hidden>Títulos do amador</p><p hidden>{$link_estrela_baixo}</p></a>";
echo "<a href='#' id = 'estrela2' class = 'itensAnimados estrela estrela2Aparecer unclickable'><p hidden>Título profissional</p><p hidden>{$link_estrela_alto}</p></a>";
echo "<a href='#' id = 'estrela3' class = 'itensAnimados estrela estrela3Aparecer unclickable'><p hidden>Títulos do amador</p><p hidden>{$link_estrela_baixo}</p></a>";
echo "<a href='#' id = 'estadio' class = 'itensAnimados estadioAparecer unclickable'><p hidden>Estádios</p><p hidden>{$link_estadio}</p></a>";
echo "<a href='#' id = 'palmeiras' class = 'itensAnimados palmeirasAparecer unclickable'><p hidden>Vitória contra o <br>Palmeiras de Leão</p><p hidden>{$link_palmeiras}</p></a>";
echo "<a href='#' id = 'americana' class = 'itensAnimados americanaAparecer unclickable'><p hidden>Mudança de nome para Americana</p><p hidden>{$link_americana}</p></a>";
echo "</div>";
echo "<div id = 'titulo' class = 'itensAnimados tituloAparecer'><h1>VASCO DE AMERICANA</h1> <h2>UM PASSADO DE GLÓRIAS</h2><span id='subtexto'>Clique sobre 
os ícones para saber mais<br>Ou <a href='{$link_comece_aqui}'>comece aqui</a></span></div>";
echo "<p id = 'ajuda' class='hidden'></p>";
 
//inicialização do script JS
echo "<script src='".$nome_arquivos.".js'></script>";

//inicialização do rodapé
include_once 'footer.php'; 

?>