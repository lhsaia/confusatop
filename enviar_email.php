<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Enviar email";
$css_filename = "mainindex";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

$post = array();

foreach($_POST as $k => $v){
  $post[$k] = htmlspecialchars(strip_tags($v));
}

var_dump($post);

if(isset($post['submit']) && $post['email_confirmation'] == "" && !preg_match('/www\.|http:|https:/',$post['comentarios'])){
    $from_mail = $post['email'];
    $from_name = $post['nome'];
    $body = $post['comentarios'];
    $to = "lhsaia@gmail.com";
    $msg = wordwrap($body,70);
    $subject = "Contato de " . $from_name . " através do site CONFUSA.top";

    $headers =
        'From: no-reply@confusa.top' . "\r\n" .
        'Reply-To: ' . $from_mail . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
if(mail($to, $subject,$msg, $headers)){
    echo '<div class="alert alert-success">O email foi enviado com sucesso!</div>';
} else {
    $errorMessage = error_get_last()['message'];
    echo '<div class="alert alert-danger">Houve um erro ao enviar o email! '.$errorMessage.'</div>';
}

}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
