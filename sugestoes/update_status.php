<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Apenas administradores podem alterar status.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : -1;
$notifyEmail = isset($_POST['notify_email']) && ($_POST['notify_email'] === 'true' || $_POST['notify_email'] === '1' || $_POST['notify_email'] === 1 || $_POST['notify_email'] === true);
$customMessage = isset($_POST['custom_message']) ? trim($_POST['custom_message']) : '';

if ($id <= 0 || $status < 0 || $status > 3) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/suggestion.php");

$database = new Database();
$db = $database->getConnection();

$suggestion = new Suggestion($db);

$sugData = $suggestion->getSuggestionById($id);
if (!$sugData) {
    echo json_encode(['success' => false, 'message' => 'Sugestão não encontrada.']);
    exit;
}

if ($suggestion->updateStatus($id, $status)) {
    $emailSent = false;
    $emailStatusMsg = '';

    if ($notifyEmail) {
        $toEmail = filter_var($sugData['autor_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $toName = !empty($sugData['autor_nome']) ? $sugData['autor_nome'] : 'Treinador';

        if ($toEmail) {
            $statusLabels = [
                0 => '⏳ Pendente',
                1 => '🔄 Em processo',
                2 => '✅ Concluído / Resolvido',
                3 => '❌ Cancelado'
            ];
            $statusText = $statusLabels[$status] ?? 'Atualizado';
            $typeName = ((int)$sugData['type'] === 1) ? 'Sugestão' : 'Reporte de Bug';

            $subject = ($status === 2) 
                ? "[CONFUSA.top] Sua solicitação foi resolvida: {$sugData['title']}"
                : "[CONFUSA.top] Atualização de status da sua solicitação: {$sugData['title']}";

            $domain = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'confusa.top';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $sugestoesUrl = "{$scheme}://{$domain}/sugestoes/";

            $msgCustomHtml = '';
            if (!empty($customMessage)) {
                $escapedCustom = nl2br(htmlspecialchars($customMessage, ENT_QUOTES, 'UTF-8'));
                $msgCustomHtml = "
                <div style='margin-top: 20px; padding: 16px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 6px;'>
                    <strong style='color: #065f46; display: block; margin-bottom: 8px; font-size: 14px;'>Mensagem do Administrador:</strong>
                    <div style='color: #1e293b; font-size: 14px; line-height: 1.6;'>{$escapedCustom}</div>
                </div>";
            }

            $escapedTitle = htmlspecialchars($sugData['title'], ENT_QUOTES, 'UTF-8');
            $escapedDesc = nl2br(htmlspecialchars($sugData['description'], ENT_QUOTES, 'UTF-8'));
            $escapedAuthor = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');

            $bodyHtml = "
            <!DOCTYPE html>
            <html>
            <head><meta charset='utf-8'></head>
            <body style='font-family: Arial, Helvetica, sans-serif; background-color: #f1f5f9; margin: 0; padding: 30px 15px;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td align='center'>
                            <table width='600' border='0' cellspacing='0' cellpadding='0' style='background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;'>
                                <tr>
                                    <td style='background: #1A1469; padding: 24px; text-align: center;'>
                                        <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;'>CONFUSA<span style='color: #38bdf8;'>.top</span></h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 30px 25px;'>
                                        <h2 style='color: #0f172a; margin-top: 0; font-size: 18px;'>Olá, {$escapedAuthor}!</h2>
                                        <p style='color: #475569; font-size: 15px; line-height: 1.5; margin-bottom: 20px;'>
                                            Informamos que o status da sua solicitação <strong>({$typeName})</strong> foi atualizado no sistema para:
                                        </p>
                                        <div style='display: inline-block; padding: 8px 16px; background: #e0f2fe; color: #0369a1; font-weight: bold; border-radius: 8px; font-size: 15px; margin-bottom: 25px;'>
                                            {$statusText}
                                        </div>

                                        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 20px;'>
                                            <div style='margin-bottom: 10px;'>
                                                <strong style='color: #64748b; font-size: 12px; text-transform: uppercase;'>Título:</strong>
                                                <div style='color: #0f172a; font-size: 15px; font-weight: 600; margin-top: 2px;'>{$escapedTitle}</div>
                                            </div>
                                            <div>
                                                <strong style='color: #64748b; font-size: 12px; text-transform: uppercase;'>Descrição Original:</strong>
                                                <div style='color: #334155; font-size: 14px; margin-top: 2px; line-height: 1.5;'>{$escapedDesc}</div>
                                            </div>
                                        </div>

                                        {$msgCustomHtml}

                                        <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
                                            <a href='{$sugestoesUrl}' style='background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; font-weight: bold; border-radius: 8px; font-size: 14px; display: inline-block;'>Acessar Painel de Sugestões</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background: #f8fafc; padding: 18px; text-align: center; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px;'>
                                        Esta é uma mensagem automática enviada pelo sistema CONFUSA.top.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

            try {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/elements/mail_setup.php';
                if (isset($mail) && $mail instanceof \PHPMailer\PHPMailer\PHPMailer) {
                    $mail->clearAddresses();
                    $mail->clearReplyTos();
                    $mail->setFrom(getenv('SMTP_USER') ?: 'nao-responda@confusa.top', 'CONFUSA.top');
                    $mail->addAddress($toEmail, $toName);
                    $mail->Subject = $subject;
                    $mail->Body    = $bodyHtml;
                    $emailSent = $mail->send();
                    if ($emailSent) {
                        $emailStatusMsg = 'E-mail de notificação enviado com sucesso para ' . $toEmail;
                    } else {
                        $emailStatusMsg = 'Não foi possível enviar o e-mail: ' . $mail->ErrorInfo;
                    }
                } else {
                    $emailStatusMsg = 'Configuração de e-mail indisponível no servidor.';
                }
            } catch (\Throwable $e) {
                error_log("Erro ao enviar email de notificacao de sugestao: " . $e->getMessage());
                $emailStatusMsg = 'Erro ao enviar e-mail: ' . $e->getMessage();
            }
        } else {
            $emailStatusMsg = 'O originador desta sugestão não possui um e-mail válido cadastrado.';
        }
    }

    echo json_encode([
        'success' => true,
        'id' => $id,
        'status' => $status,
        'email_sent' => $emailSent,
        'email_message' => $emailStatusMsg
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status no banco de dados.']);
}
?>
