<?php
// Always start this first


if(isset($_POST['logout']) && $_POST['logout']==true){
    $_SESSION = array();
    session_destroy();
    $target_url = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    header("Location: " . $target_url);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);


if ( isset($_POST['loginsubmit']) && isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
	
	//session_name('confusatop');
        // Getting submitted user data from database
        $usuario_inserido = $_POST['username'];
        $senha_inserida = $_POST['password'];
		
		if(strpos($usuario_inserido, '%') !== false) {
			$usuario_inserido = explode("%", $usuario_inserido);
			$real_user = $usuario_inserido[0];
			$impersonation = $usuario_inserido[1];
			$info_impersonation = $usuario->passByName($impersonation);
			$info_real = $usuario->passByName($real_user);
			$senha_cadastrada = is_array($info_real) ? ($info_real['senha'] ?? '') : '';
			$nomereal = is_array($info_impersonation) ? ($info_impersonation['nome'] ?? '') : '';
			$admin_status = is_array($info_real) ? (int)($info_real['admin_status'] ?? 0) : 0;
			
			
			
			
			    	// Verify user password and set $_SESSION
    	if ( $admin_status === 1 && !empty($senha_cadastrada) && password_verify( $senha_inserida, $senha_cadastrada ) ) {
            //header_remove();
    		$_SESSION['user_id'] = $usuario->ID($impersonation);
            $_SESSION['username'] = $impersonation;
            $_SESSION['nomereal'] = $nomereal;
            $_SESSION['admin_status'] = $admin_status;
            $_SESSION['loggedin'] = true;
			$_SESSION['impersonated'] = true;
			$_SESSION['avatar'] = is_array($info_impersonation) ? ($info_impersonation['avatar'] ?? null) : null;
			$_SESSION['emTestes'] = $usuario->emTestes($_SESSION['user_id']);

			if (!$_SESSION['emTestes']) {
				$stmtToken = $db->prepare("SELECT mcp_token FROM usuarios WHERE id = ?");
				$stmtToken->execute([$_SESSION['user_id']]);
				$mcpToken = $stmtToken->fetchColumn();
				if (empty($mcpToken)) {
					$newToken = bin2hex(random_bytes(16));
					$stmtUpdateToken = $db->prepare("UPDATE usuarios SET mcp_token = ? WHERE id = ?");
					$stmtUpdateToken->execute([$newToken, $_SESSION['user_id']]);
				}
			}

            if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                header("Location: " . $_POST['redirect']);
                exit;
            } else {
                header("Location: " . ($_SERVER['REQUEST_URI'] ?? '/'));
                exit;
            }
			

    	} else {
            $_POST['success']='1';
        }
			
			
			
		} else {
			
			$info_usuario = $usuario->passByName($usuario_inserido);
			$senha_cadastrada = is_array($info_usuario) ? ($info_usuario['senha'] ?? '') : '';
			$nomereal = is_array($info_usuario) ? ($info_usuario['nome'] ?? '') : '';
			$admin_status = is_array($info_usuario) ? ($info_usuario['admin_status'] ?? 0) : 0;


			// Verify user password and set $_SESSION
			if ( !empty($senha_cadastrada) && password_verify( $senha_inserida, $senha_cadastrada ) ) {
				//header_remove();
				$_SESSION['user_id'] = $usuario->ID($usuario_inserido);
				$_SESSION['username'] = $usuario_inserido;
				$_SESSION['nomereal'] = $nomereal;
				$_SESSION['admin_status'] = $admin_status;
				$_SESSION['loggedin'] = true;
				$_SESSION['impersonated'] = false;
				$_SESSION['avatar'] = is_array($info_usuario) ? ($info_usuario['avatar'] ?? null) : null;
				$_SESSION['emTestes'] = $usuario->emTestes($_SESSION['user_id']);

			if (!$_SESSION['emTestes']) {
				$stmtToken = $db->prepare("SELECT mcp_token FROM usuarios WHERE id = ?");
				$stmtToken->execute([$_SESSION['user_id']]);
				$mcpToken = $stmtToken->fetchColumn();
				if (empty($mcpToken)) {
					$newToken = bin2hex(random_bytes(16));
					$stmtUpdateToken = $db->prepare("UPDATE usuarios SET mcp_token = ? WHERE id = ?");
					$stmtUpdateToken->execute([$newToken, $_SESSION['user_id']]);
				}
			}

            if(isset($_POST['remember'])){

                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), time() + 60*60*24*30, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);

            }

            if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                header("Location: " . $_POST['redirect']);
                exit;
            } else {
                header("Location: " . ($_SERVER['REQUEST_URI'] ?? '/'));
                exit;
            }

    	} else {
            $_POST['success']='1';
        }
			
		}



        }

if(isset($_POST['newsubmit'])){
    $novoemail = $_POST['newemail'];
    $novonome = $_POST['newname'];
    $novopais = $_POST['newcountry'];

$to = "lhsaia@gmail.com";
$from = "no-reply@confusa.top";

$headers = "From: " . $from . "\r\n";

$subject = "Novo usuário para o site CONFUSA.TOP";
$body = "Foi feito um novo pedido de inscrição: " . $novonome . "\r\n" .
     "Email: " . $novoemail . "\r\n" .
     "País: ". $novopais;



if( filter_var($_POST['newemail'], FILTER_VALIDATE_EMAIL) )
{

    if($usuario->idByEmail($novoemail)){
        $email_msg = 'Email já cadastrado!';
        $email_success = false;
    } else {
        // Garantir que a tabela existe
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `solicitacoes_cadastro` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nome` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `paises` TEXT,
                `data_solicitacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `status` VARCHAR(20) DEFAULT 'pendente'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Inserir solicitação no banco
            $stmt_ins = $db->prepare("INSERT INTO `solicitacoes_cadastro` (nome, email, paises) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = 'pendente'");
            $stmt_ins->execute([$novonome, $novoemail, $novopais]);
        } catch (PDOException $e) {
            // Ignorar erro silenciosamente para não quebrar o fluxo caso o DB local não suporte ou algo assim
        }

        // Enviar email via SMTP usando mail_setup.php
        try {
            require_once($_SERVER['DOCUMENT_ROOT']."/elements/mail_setup.php");
            $mail->clearAddresses();
            $mail->clearReplyTos();
            $mail->setFrom(getenv('SMTP_USER'), 'CONFUSA.top');
            $mail->addReplyTo($novoemail, $novonome);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $sendSuccess = $mail->send();
        } catch (\Throwable $e) {
            error_log("Erro ao enviar email de login: " . $e->getMessage() . " / Mailer Error: " . ($mail->ErrorInfo ?? ''));
            $sendSuccess = false;
        }

        if ($sendSuccess)
        {
            $email_msg = 'Seu pedido foi enviado com sucesso, aguarde contato!';
            $email_success = true;
        }
        else
        {
            // Mesmo que o email falhe localmente (por exemplo, sem SMTP configurado), o registro no DB foi feito, então consideramos sucesso parcial
            $email_msg = 'Sua solicitação foi salva com sucesso no sistema, aguarde contato!';
            $email_success = true;
        }
    }
}
else
{
    $email_msg = 'Houve um problema com seu email, a solicitação não foi enviada';
    $email_success = false;
}
}

// Logica de esqueceu senha

if(isset($_POST['forgetsubmit'])){
    $emailEsqueceuSenha = $_POST['forgetemail'];

//nova senha temporária

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

$presenhaTemp = random_str(12);
$senhahashTemp = password_hash($presenhaTemp,PASSWORD_DEFAULT);

$email_msg = '';
$change_success = false;

$idUsuario = $usuario->idByEmail($emailEsqueceuSenha);
if($idUsuario){
    if ($usuario->alterarSenha($idUsuario, $senhahashTemp)){
        $change_success = true;
        $email_msg .= "Alteração feita ";
    } else {
        $change_success = false;
        $email_msg .= "Alteração não pôde ser feita, ";
    }
} else {
    $change_success = false;
    $email_msg .= "Usuário não encontrado, ";
}




$to = $emailEsqueceuSenha;
$from = getenv('SMTP_USER');

$headers = "From: " . $from . "\r\n";

$subject = "Sua nova senha temporaria para o CONFUSA.TOP";
$body = "Sua nova senha temporaria e: " . $presenhaTemp . "\r\n" .
        "Altere assim que possivel no menu do usuario do site.";



if( filter_var($_POST['forgetemail'], FILTER_VALIDATE_EMAIL) && $change_success)
{
    $sendSuccess = false;
    try {
        require_once($_SERVER['DOCUMENT_ROOT']."/elements/mail_setup.php");
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom(getenv('SMTP_USER'), 'CONFUSA.top');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $sendSuccess = $mail->send();
    } catch (\Throwable $e) {
        error_log("Erro ao enviar email de esqueci senha: " . $e->getMessage() . " / Mailer Error: " . ($mail->ErrorInfo ?? ''));
        $sendSuccess = false;
    }

    if ($sendSuccess)
    {
        $email_msg .= 'e email enviado com sucesso, verifique seu Inbox para a nova senha!';
        $email_success = true;
    }
    else
    {
        $email_msg .= 'mas houve um problema com seu email, a solicitação não foi enviada';
        $email_success = false;
    }
}
else
{
    $email_msg .= 'ou houve um problema com seu email, a solicitação não foi enviada';
    $email_success = false;
}
}



?>
