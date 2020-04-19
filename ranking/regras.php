<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ranking de Seleções - Regras";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'regras';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

?>

<div id="ranking-container">
<div align="center" id="ranking">
<div id='regras'>
<h2>Regras do ranking de seleções</h2>

<hr>

<p>O Ranking de Seleções da CONFUSA é baseado no sistema Elo, desenvolvido pelo Dr. Arpad Elo. É usado amplamente no xadrez para ranquear jogadores, e tem sido aos poucos adotado para outros esportes como o futebol (a própria FIFA passou a usar recentemente uma variação do sistema, de forma a corrigir algumas distorções).</p>
<p>Confusa.top utiliza também uma variação desse sistema, baseado amplamente no utilizado pelo site <a href="https://eloratings.net">eloratings.net</a>, com pesos pelo tipo de partida, um ajuste para a vantagem do time da casa, e um ajuste sobre a diferença de gols na partida.</p>
<p>Nosso ranking considera todas as partidas internacionais oficiais entre membros da CONFUSA, cujos resultados puderam ser encontrados.</p>
<p>A tendência na classificação tende a convergir para a verdadeira força das equipes, comparadas com as demais, após cerca de 30 partidas. A pontuação para times com menos de 30 jogos disputados devem ser consideradas em um caráter mais temporário, e irão variar mais.</p>
<p>A pontuação é calculada jogo a jogo e baseada nas seguintes fórmulas:</p>

<p><strong>P<sub>n</sub> = P<sub>a</sub> + C × G × (R - R<sub>e</sub>)</strong></p>
<p><strong>P<sub>n</sub>&nbsp</strong> são os novos pontos, <strong>P<sub>a</sub></strong> são os pontos antigos (pré-partida).</p>

<p><strong>C&nbsp</strong> é a constante de peso dependente do campeonato disputado:</p>

<p class='doubleindent'><strong>60&nbsp</strong> para Copa do Mundo ou Olimpíadas;</p>
<p class='doubleindent low-padding'><strong>50&nbsp</strong> para FEASCOPA, Três Mares, Escudo da Távola e a extinta Angehäit Döröt;</p>
<p class='doubleindent low-padding'><strong>40&nbsp</strong> para Eliminatórias da Copa, de Regionais e de Olimpíadas;</p>
<p class='doubleindent low-padding'><strong>30&nbsp</strong> para mundiais de base e Copa das Confederações;</p>
<p class='doubleindent low-padding'><strong>20&nbsp</strong> para amistosos.</p>
<p class='doubleindent low-padding'><strong>C&nbsp</strong> é então ajustada pela diferença de gols no jogo (<strong>G</strong>), de acordo com os seguintes valores:</p>

<p class='tripleindent'><strong>1&nbsp</strong> quando a diferença de gols é de 1</p>
<p class='tripleindent low-padding'><strong>1.5&nbsp</strong> quando a diferença de gols é de 2</p>
<p class='tripleindent low-padding'><strong>(11 + D<sub>G</sub>) × 0.125&nbsp</strong> quando a diferença de gols é de 3 ou mais, onde <strong>D<sub>G</sub></strong> é a diferença de gols.</p>

<p><strong>R&nbsp</strong> é o resultado do jogo (<strong>1</strong> para vitória, <strong>0.5</strong> para empate, e <strong>0</strong> para derrota).</p>

<p><strong>R<sub>e</sub>&nbsp</strong> é o resultado esperado (expectativa de vitória), através da seguinte fórmula:</p>

<p><strong>R<sub>e</sub> = 1 / (10<sup>(-d<sub>p</sub>/400)</sup> + 1)</strong></p>
<p><strong>d<sub>p</sub>&nbsp</strong> é a diferença de pontuação no ranking entre as equipes, <strong>+100</strong> para o dono da casa (válido apenas para amistosos e eliminatórias).</p>

</div>
</div>
</div>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
