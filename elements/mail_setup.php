<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '/home/lhsaia/confusa.top/utils/PHPMailer/src/Exception.php';
require '/home/lhsaia/confusa.top/utils/PHPMailer/src/PHPMailer.php';
require '/home/lhsaia/confusa.top/utils/PHPMailer/src/SMTP.php';

// Carregar variaveis do .env caso nao estejam no getenv
if (!getenv('SMTP_USER') || !getenv('SMTP_PASS')) {
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {

    //Server settings
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    
    // Configura SMTP dinamicamente com base nas variaveis de ambiente
    $mail->Host       = getenv('SMTP_HOST') ?: 'mail.confusa.top';
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = getenv('SMTP_USER');                    //SMTP username
    $mail->Password   = getenv('SMTP_PASS');                    //SMTP password
    $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'ssl';         //Enable encryption (ssl/tls)
    $mail->Port       = getenv('SMTP_PORT') ?: 465;             //SMTP Port
    
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
        //Content
    $mail->isHTML(true);                                  //Set email format to HTML

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?> 