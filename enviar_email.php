<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Enviar email";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "contato_redesign";
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
    $msg = wordwrap($body, 70);
    $subject = "Contato de " . $from_name . " através do site CONFUSA.top";

    $sendSuccess = false;
    $errorMessage = '';

    try {
        require_once($_SERVER['DOCUMENT_ROOT']."/elements/mail_setup.php");
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom('no-reply@confusa.top', 'Contato CONFUSA.top');
        $mail->addReplyTo($from_mail, $from_name);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $sendSuccess = $mail->send();
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }

    if($sendSuccess){
        echo '<div class="alert alert-success">O email foi enviado com sucesso!</div>';
    } else {
        echo '<div class="alert alert-danger">Houve um erro ao enviar o email! '.$errorMessage.'</div>';
    }
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
