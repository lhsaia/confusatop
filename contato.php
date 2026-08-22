<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA.top - Contato";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "contato_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<div class="contato-container">
    <h2 class="contato-title">Fale Conosco</h2>
    
    <form id="formcontato" name="contactform" method="post" action="enviar_email.php" class="contato-form">
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="50" required placeholder="Digite seu nome completo...">
        </div>
        
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" maxlength="80" required placeholder="Digite seu endereço de e-mail...">
        </div>
        
        <div class="form-group">
            <label for="comentarios">Comentários, críticas, sugestões</label>
            <textarea id="comentarios" name="comentarios" class="form-control" maxlength="1000" required placeholder="Escreva sua mensagem aqui..."></textarea>
        </div>
        
        <div class="form-group form-group-confirm">
            <label for="email_confirmation">E-mail confirmation</label>
            <input type="text" id="email_confirmation" name="email_confirmation" class="form-control" maxlength="80">
        </div>
        
        <input type="submit" name="submit" id="submitMail" class="submitbtn" value="Enviar Mensagem">
    </form>
</div>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
