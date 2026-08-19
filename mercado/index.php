<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Mercado CONFUSA";
$css_filename = "home_redesign";
$aux_css = 'mercado_redesign';
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);

?>

<main class="redesign-container">
    <h2 class="hub-section-title">Mercado</h2>
    
    <section class="redesign-grid">
        <!-- Busca de Jogadores -->
        <a href='transferencias.php?type=busca' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/buscajogadores.webp" alt="Busca de Jogadores" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Busca de Jogadores</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Pesquise jogadores na base de dados por atributos, posição, idade ou nacionalidade.</p>
            </div>
        </a>

        <!-- Busca de Técnicos -->
        <a href='transferencias.php?type=buscaTecnico' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/buscatecnico.webp" alt="Busca de Técnicos" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Busca de Técnicos</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Procure por técnicos disponíveis para treinar equipes no mercado.</p>
            </div>
        </a>

        <!-- Jogadores mais Valiosos -->
        <a href='transferencias.php?type=jogadores' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/maisvaliosos.webp" alt="Jogadores mais Valiosos" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Jogadores mais Valiosos</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Veja os atletas mais valorizados do futebol mundial e seus valores de mercado.</p>
            </div>
        </a>

        <!-- Últimas Transferências -->
        <a href='transferencias.php?type=ultimas' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/ultimastransferencias.webp" alt="Últimas Transferências" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Últimas Transferências</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Acompanhe as movimentações mais recentes de atletas e treinadores entre clubes.</p>
            </div>
        </a>

        <!-- Maiores Transferências -->
        <a href="transferencias.php?type=maiores" class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/maiorestransferencias.webp" alt="Maiores Transferências" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Maiores Transferências</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Confira os recordes históricos de valores pagos por transferências no CONFUSA.</p>
            </div>
        </a>

        <!-- Janelas de Transferência -->
        <a href='transferencias.php?type=janelas' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/mercado/janelatransferencias.webp" alt="Janelas de Transferência" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Janelas de Transferência</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Acompanhe o período de inscrições de atletas e o status das janelas em cada liga.</p>
            </div>
        </a>
    </section>
</main>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
