<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA - CONFUSA.top";
//$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'newindex';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<div id="tabela-quadros">


<a href="ligas" class='quadro-flex quadro-animado' id="ligas"><img src="/images/ligas.png?1" /><span>Ligas</span></a>
<a href="mercado" class="quadro-flex quadro-animado" id="mercado"><img src="/images/mercado.png" /><span>Mercado</span></a>
<?php
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  $linkMinhaArea = "usuario" ;
  $linkPacotes = "export/montarcompeticao.php";
} else {
  $linkMinhaArea = "apenas_membros.php";
  $linkPacotes = "apenas_membros.php";
}

echo "<a href='{$linkMinhaArea}' class='quadro-flex quadro-animado' id='usuario'><img src='/images/minhaarea.png?1' /><span>Minha área</span></a>";
echo "<a href='{$linkPacotes}' class='quadro-flex quadro-animado' id='pacotes'><img src='/images/pacotes.png?1' /><span>Gerador de Pacotes</span></a>";

?>
<a href="ranking" class="quadro-flex quadro-animado" id="ranking"><img src="/images/ranking.png" /><span>Ranking</span></a>
<a href="arbitros" class="quadro-flex quadro-animado" id="arbitros"><img src="/images/arbitro.png?1" /><span>Quadro de árbitros</span></a>
<a href="escudos_pop" class="quadro-flex quadro-animado" id="escudos_pop"><img src="/images/escudos_pop.jpg" /><span>Escudos Pops</span></a>
<a href="octamotor" class="quadro-flex quadro-animado" id="octamotor"><img src="/images/octamotor.jpg" /><span>Octamotor</span></a>
<div class="outros-links-container">
<span class='legenda'>Outros links</span>
<a href="http://confusalive.com" class="quadro-animado meio-quadro sup dir" id="live"><img src="/images/confusalive.png?1" /><span>CONFUSA Live</span></a>
<a href="http://vk.com/futebolsolitario" class="quadro-animado meio-quadro sup esq" id="vk"><img src="/images/vk.png?1" /><span>VK - Futebol Solitário</span></a>
<a href="http://confusa.wikia.com/wiki/P%C3%A1gina_principal" class="quadro-animado meio-quadro inf dir" id="confusopedia"><img src="/images/confusopedia.png?1" /><span>Confusopédia</span></a>
<a href="http://52.203.150.214:8080/Portal_COISO_v3" class="quadro-animado meio-quadro inf esq" id="portal"><img src="/images/portalcoiso.png?1" /><span>Portal COISO</span></a>
</div>
</div>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
