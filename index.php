<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");

// Configurações do Header Dinâmico com o novo CSS
$page_title = "CONFUSA - CONFUSA.top";
$css_login = 'login';
$aux_css = 'home_redesign'; // Carrega o novo css/home_redesign.css
$css_versao = '2.3.1';     // Versionamento atualizado

include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");

// Lógica de links do usuário autenticado vs visitante
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $linkMinhaArea = "/usuario";
} else {
    $linkMinhaArea = "/apenas_membros.php";
}
?>

<main class="redesign-container">

    <!-- Hub Central de Módulos Principais -->
    <h2 class="hub-section-title">Módulos Principais</h2>
    <section class="redesign-grid">

        <!-- Ligas -->
        <a href="/ligas" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/ligas.png?1" alt="Ligas" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Ligas</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Busque informações das suas ligas e equipes favoritas.</p>
            </div>
        </a>

        <!-- Mercado -->
        <a href="/mercado" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/mercado.png" alt="Mercado" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Mercado</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Transferências, negociações e propostas de jogadores e treinadores.</p>
            </div>
        </a>

        <!-- Minha Área -->
        <a href="<?php echo $linkMinhaArea; ?>" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/minhaarea.png?1" alt="Minha Área" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Minha Área</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Gestão da sua conta, times e preferências.</p>
            </div>
        </a>

        <!-- Competições (Subido de categoria e desabilitado) -->
        <div class="hub-card disabled-card">
            <div class="hub-card-hero-image">
                <img src="/images/pacotes.png?1" alt="Competições" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Competições</span>
                    <span class="badge-status">Em Breve</span>
                </h3>
                <p class="hub-card-desc">Monte e configure torneios para simulação online.</p>
            </div>
        </div>

        <!-- Ranking -->
        <a href="/ranking" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/ranking.png" alt="Ranking" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Ranking</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Classificação geral, estatísticas e pontuações históricas das seleções.</p>
            </div>
        </a>

        <!-- Jogos de Clubes -->
        <a href="/ligas/gerenciador/jogos" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/jogos.jpg" alt="Jogos de Clubes" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Jogos de Clubes</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Gerenciador de partidas, placares e confrontos de clubes.</p>
            </div>
        </a>

        <!-- Quadro de Árbitros -->
        <a href="/arbitros" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/arbitro.png?1" alt="Quadro de Árbitros" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Quadro de Árbitros</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Busque trios de arbitragem por toda a CONFUSA.</p>
            </div>
        </a>

    </section>

    <!-- Modalidades & Ferramentas Secundárias -->
    <h2 class="hub-section-title">Jogos & outros esportes</h2>
    <section class="redesign-grid">

        <!-- Octamotor -->
        <a href="/octamotor" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/octamotor.jpg" alt="Octamotor" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Octamotor</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Simulação de esportes de motor.</p>
            </div>
        </a>

        <!-- Escudos Pops -->
        <a href="/escudos_pop" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/escudos_pop.jpg" alt="Escudos Pops" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Escudos Pops</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Você consegue adivinhar de quem são esses escudos minimalistas?</p>
            </div>
        </a>

        <!-- 7 Vidas (Atualizado com a imagem oficial /images/7vidas/index.png) -->
        <a href="/7vidas" class="hub-card">
            <div class="hub-card-hero-image">
                <img src="/images/7vidas/index.png" alt="7 Vidas" onerror="this.src='/images/jogos.jpg';" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>7 Vidas</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Escale sua equipe e sobreviva até quando puder.</p>
            </div>
        </a>

    </section>

    <!-- Outros Links e Ecossistema -->
    <h2 class="hub-section-title">Ecossistema & Comunidade</h2>
    <section class="links-grid">
        <a href="https://confusalive.com" target="_blank" rel="noopener noreferrer" class="sub-link-card">
            <img src="/images/confusalive.png?1" alt="CONFUSA Live" />
            <span>CONFUSA Live</span>
        </a>
        <a href="https://vk.com/futebolsolitario" target="_blank" rel="noopener noreferrer" class="sub-link-card">
            <img src="/images/vk.png?1" alt="VK Futebol Solitário" />
            <span>VK - Futebol Solitário</span>
        </a>
        <a href="https://confusa.wikia.com/wiki/P%C3%A1gina_principal" target="_blank" rel="noopener noreferrer"
            class="sub-link-card">
            <img src="/images/confusopedia.png?1" alt="Confusopédia" />
            <span>Confusopédia</span>
        </a>
        <a href="http://52.203.150.214:8080/Portal_COISO_v3" target="_blank" rel="noopener noreferrer"
            class="sub-link-card">
            <img src="/images/portalcoiso.png?1" alt="Portal COISO" />
            <span>Portal COISO</span>
        </a>
    </section>

</main>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>