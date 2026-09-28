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
        require_once($_SERVER['DOCUMENT_ROOT']."/elements/mail_template.php");

        $contentContactHtml = "
        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='6' style='font-size: 14px;'>
                <tr>
                    <td style='color: #64748b; font-weight: 600; width: 25%;'>Nome:</td>
                    <td style='color: #0f172a; font-weight: 700;'>" . htmlspecialchars($from_name) . "</td>
                </tr>
                <tr>
                    <td style='color: #64748b; font-weight: 600;'>E-mail:</td>
                    <td style='color: #0284c7; font-weight: 600;'>" . htmlspecialchars($from_mail) . "</td>
                </tr>
            </table>

            <div style='margin-top: 14px; padding-top: 14px; border-top: 1px solid #e2e8f0;'>
                <strong style='color: #64748b; font-size: 12px; text-transform: uppercase;'>Mensagem:</strong>
                <div style='color: #334155; font-size: 14px; line-height: 1.6; margin-top: 6px;'>" . nl2br(htmlspecialchars($body)) . "</div>
            </div>
        </div>";

        $bodyHtml = renderConfusaEmail([
            'title' => 'Nova Mensagem de Contato',
            'subtitle' => 'Recebida através do formulário de contato do portal.',
            'badge' => [
                'text' => 'Fale Conosco',
                'bg' => '#e0e7ff',
                'color' => '#4338ca'
            ],
            'content_html' => $contentContactHtml
        ]);

        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom('no-reply@confusa.top', 'Contato CONFUSA.top');
        $mail->addReplyTo($from_mail, $from_name);
        $mail->addAddress($to);
        $mail->Subject = "[CONFUSA.top] Mensagem de " . $from_name;
        $mail->Body    = $bodyHtml;
        $mail->isHTML(true);
        
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
