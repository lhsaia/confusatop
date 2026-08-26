<?php
$current_ranking_page = basename($_SERVER['PHP_SELF']);
?>
<div class="ranking-nav-wrapper">
  <div class="ranking-nav-container">
    <div class="ranking-brand">
      <img src="/images/confusalogo.png" alt="CONFUSA" class="ranking-logo">
      <div class="ranking-title-group">
        <h1 class="ranking-main-title">Ranking de Seleções</h1>
        <span class="ranking-subtitle">CONFUSA Internacional</span>
      </div>
    </div>
    <div class="ranking-menu-nav">
      <a href="/ranking" class="ranking-nav-link <?php echo ($current_ranking_page == 'index.php' || $current_ranking_page == 'ranking') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">military_tech</span>
        <span>Masculino</span>
      </a>
      <a href="/ranking/selecoes.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'selecoes.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">flag</span>
        <span>Seleções</span>
      </a>
      <a href="/ranking/jogoserecordes.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'jogoserecordes.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">history</span>
        <span>Jogos &amp; Recordes</span>
      </a>
      <a href="/ranking/retrospecto.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'retrospecto.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">compare_arrows</span>
        <span>Retrospecto</span>
      </a>
      <a href="/ranking/regras.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'regras.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">menu_book</span>
        <span>Regras</span>
      </a>
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
      <a href="/ranking/importar_jogo.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'importar_jogo.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">upload_file</span>
        <span>Importar</span>
      </a>
      <a href="/ranking/criar_jogo.php" class="ranking-nav-link <?php echo ($current_ranking_page == 'criar_jogo.php') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">add_circle</span>
        <span>Criar Jogo</span>
      </a>
      <?php endif; ?>
      <a href="#" class="ranking-nav-link disabled" title="Em breve">
        <span class="material-symbols-outlined nav-icon">female</span>
        <span>Feminino</span>
      </a>
    </div>
  </div>
</div>