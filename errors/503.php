<?php
http_response_code(503);
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");

$page_title = "Erro 503 - Serviço Indisponível - CONFUSA.top";
$css_filename = "home_redesign";
$aux_css = "error_pages";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");
?>

<main class="redesign-container">
  <div class="error-container">
    <div class="error-card">
      <div class="error-image-wrapper">
        <img src="/errors/503.png" alt="Erro 503" class="error-image">
      </div>
      <div class="error-content">
        <div class="error-code">503</div>
        <h1 class="error-title">Serviço Indisponível</h1>
        <p class="error-description">O servidor está temporariamente indisponível (por sobrecarga ou manutenção). Por favor, tente novamente mais tarde.</p>
        <div class="error-actions">
          <a href="/" class="btn-error btn-primary-error">
            <span class="material-symbols-outlined">home</span> Voltar ao Início
          </a>
          <button onclick="window.history.back()" class="btn-error btn-secondary-error">
            <span class="material-symbols-outlined">arrow_back</span> Voltar
          </button>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
