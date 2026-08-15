<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$emailDestino = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($emailDestino) || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
    die("Uso: test_smtp.php?email=seu-email-de-teste@dominio.com\nPor favor passe um e-mail valido via parametro GET.");
}

echo "=== INICIANDO DIAGNOSTICO SMTP ===\n";

// Carrega o PHPMailer e a configuracao
require_once __DIR__ . '/elements/mail_setup.php';

// Ativa depuracao maxima do SMTP
$mail->SMTPDebug = 4;
$mail->Debugoutput = function($str, $level) {
    echo "[$level] $str\n";
};

try {
    $mail->clearAddresses();
    $mail->clearReplyTos();
    $mail->setFrom(getenv('SMTP_USER'), 'Diagnostico CONFUSA.top');
    $mail->addAddress($emailDestino);
    $mail->Subject = "Teste de Diagnostico SMTP - CONFUSA.TOP";
    
    // Corpo do email simples
    $mail->Body    = "<h1>Teste de Diagnostico</h1><p>Esta e uma mensagem de teste enviada para depuracao do servidor SMTP.</p>";
    $mail->AltBody = "Esta e uma mensagem de teste para depuracao.";

    echo "\nTentando enviar e-mail para: $emailDestino...\n\n";
    
    $enviado = $mail->send();
    
    if ($enviado) {
        echo "\n=== SUCESSO: E-mail enviado com sucesso! ===\n";
    } else {
        echo "\n=== FALHA: O e-mail nao foi enviado. ===\n";
    }
} catch (Exception $e) {
    echo "\n=== EXCECAO DETECTADA ===\n";
    echo "Mensagem de erro: " . $e->getMessage() . "\n";
    echo "Mailer ErrorInfo: " . $mail->ErrorInfo . "\n";
}
