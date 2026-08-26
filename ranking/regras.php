<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ranking de Seleções - Regras";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'home_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

?>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="ranking-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">menu_book</span>
                    Regras e Metodologia do Ranking
                </h2>
                <h3 class="ranking-card-date">Sistema Elo adaptado para o futebol internacional CONFUSA</h3>
            </div>
        </div>

        <div class="rules-container">
            <p>O <strong>Ranking de Seleções da CONFUSA</strong> é baseado no prestigiado sistema <em>Elo</em>, originalmente desenvolvido pelo físico Dr. Árpád Élő. Usado há décadas no xadrez e hoje adotado pela FIFA, o modelo calcula dinamicamente a troca de pontos entre seleções com base na probabilidade de vitória de cada equipe.</p>
            
            <p>O sistema do <strong>CONFUSA.top</strong> é amplamente inspirado no padrão internacional do <a href="https://eloratings.net" target="_blank" style="color: #0284c7; font-weight: 600; text-decoration: underline;">eloratings.net</a>, agregando pesos por importância da competição, vantagem do mandante (+100 pontos no ranking para o time da casa) e um multiplicador pela diferença de gols na partida.</p>
            
            <p>A classificação converge para a verdadeira força relativa das equipes após aproximadamente <strong>30 partidas disputadas</strong>. Seleções com menos jogos possuem pontuação com maior volatilidade.</p>

            <div class="formula-card">
                <div class="formula-main">P<sub>n</sub> = P<sub>a</sub> + C × G × (R - R<sub>e</sub>)</div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 1.5rem;">
                <p style="margin: 0;"><strong>P<sub>n</sub></strong> : Nova pontuação da equipe após o confronto.</p>
                <p style="margin: 0;"><strong>P<sub>a</sub></strong> : Pontuação anterior da equipe antes da partida.</p>
            </div>

            <h3 style="font-family: 'Kanit', sans-serif; font-size: 1.15rem; color: #1e293b; margin-top: 1.5rem; margin-bottom: 0.5rem;">
                1. Constante de Importância da Partida (C)
            </h3>
            <p>O valor de <strong>C</strong> determina o impacto máximo de pontos de acordo com a relevância do torneio:</p>

            <div class="rules-constants-grid">
                <div class="rules-constant-item">
                    <span class="constant-val">60</span>
                    <span class="constant-desc">Copa do Mundo ou Jogos Olímpicos</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">50</span>
                    <span class="constant-desc">FEASCOPA, Três Mares, Escudo da Távola e Angehäit Döröt</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">40</span>
                    <span class="constant-desc">Eliminatórias da Copa, Regionais e Olimpíadas</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">30</span>
                    <span class="constant-desc">Mundiais de Base e Copa das Confederações</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">20</span>
                    <span class="constant-desc">Amistosos internacionais</span>
                </div>
            </div>

            <h3 style="font-family: 'Kanit', sans-serif; font-size: 1.15rem; color: #1e293b; margin-top: 1.5rem; margin-bottom: 0.5rem;">
                2. Ajuste pelo Saldo de Gols (G)
            </h3>
            <p>O multiplicador <strong>G</strong> recompensa goleadas expressivas:</p>
            
            <div class="rules-constants-grid">
                <div class="rules-constant-item">
                    <span class="constant-val">1.0</span>
                    <span class="constant-desc">Diferença de 1 gol (ou empate)</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">1.5</span>
                    <span class="constant-desc">Diferença de exatamente 2 gols</span>
                </div>
                <div class="rules-constant-item">
                    <span class="constant-val">Fórmula</span>
                    <span class="constant-desc"><strong>(11 + D<sub>G</sub>) × 0.125</strong> para 3 ou mais gols de diferença</span>
                </div>
            </div>

            <h3 style="font-family: 'Kanit', sans-serif; font-size: 1.15rem; color: #1e293b; margin-top: 1.5rem; margin-bottom: 0.5rem;">
                3. Resultado Real (R) e Resultado Esperado (R<sub>e</sub>)
            </h3>
            <p>O valor <strong>R</strong> é o resultado obtido na partida: <strong>1.0</strong> para vitória, <strong>0.5</strong> para empate e <strong>0.0</strong> para derrota.</p>
            <p>O resultado esperado (expectativa de vitória <strong>R<sub>e</sub></strong>) é obtido matematicamente através da diferença de pontos entre os oponentes:</p>

            <div class="formula-card">
                <div class="formula-main">R<sub>e</sub> = 1 / (10<sup>(-d<sub>p</sub> / 400)</sup> + 1)</div>
            </div>

            <p>Onde <strong>d<sub>p</sub></strong> é a diferença de pontos no ranking entre as equipes, somando-se <strong>+100</strong> de bonificação para o dono da casa em partidas com mando de campo.</p>
        </div>
    </div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
