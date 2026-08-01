<?php
http_response_code(400);
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");

$page_title = "Erro 400 - Requisição Inválida - CONFUSA.top";
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
        <img src="/errors/400.png" alt="Erro 400" class="error-image">
      </div>
      <div class="error-content">
        <div class="error-code">400</div>
        <h1 class="error-title">Requisição Inválida</h1>
        <p class="error-description">O servidor não pôde processar a requisição devido a uma sintaxe incorreta ou confusa.</p>
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
