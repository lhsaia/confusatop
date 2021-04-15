<?php
// Always start this first

if(isset($_POST['logout']) && $_POST['logout']==true){
$_SESSION = array();
session_destroy();
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);


if ( isset($_POST['loginsubmit']) && isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
        // Getting submitted user data from database
        $usuario_inserido = $_POST['username'];
        $senha_inserida = $_POST['password'];
		
		if(strpos($usuario_inserido, '%') !== false) {
			$usuario_inserido = explode("%", $usuario_inserido);
			$real_user = $usuario_inserido[0];
			$impersonation = $usuario_inserido[1];
			$info_impersonation = $usuario->passByName($impersonation);
			$info_real = $usuario->passByName($real_user);
			$senha_cadastrada = $info_real['senha'];
			$nomereal = $info_impersonation['nome'];
			$admin_status = (int)$info_real['admin_status'];
			
			
			
			
			    	// Verify user password and set $_SESSION
    	if ( $admin_status === 1 && password_verify( $senha_inserida, $senha_cadastrada ) ) {
            //header_remove();
    		$_SESSION['user_id'] = $usuario->ID($impersonation);
            $_SESSION['username'] = $impersonation;
            $_SESSION['nomereal'] = $nomereal;
            $_SESSION['admin_status'] = $admin_status;
            $_SESSION['loggedin'] = true;
			$_SESSION['impersonated'] = true;
			$_SESSION['emTestes'] = $usuario->emTestes($_SESSION['user_id']);
			

    	} else {
            $_POST['success']='1';
        }
			
			
			
		} else {
			
			        $info_usuario = $usuario->passByName($usuario_inserido);
        $senha_cadastrada = $info_usuario['senha'];
        $nomereal = $info_usuario['nome'];
        $admin_status = $info_usuario['admin_status'];


    	// Verify user password and set $_SESSION
    	if ( password_verify( $senha_inserida, $senha_cadastrada ) ) {
            //header_remove();
    		$_SESSION['user_id'] = $usuario->ID($usuario_inserido);
            $_SESSION['username'] = $usuario_inserido;
            $_SESSION['nomereal'] = $nomereal;
            $_SESSION['admin_status'] = $admin_status;
            $_SESSION['loggedin'] = true;
			$_SESSION['impersonated'] = false;
			$_SESSION['emTestes'] = $usuario->emTestes($_SESSION['user_id']);

            if(isset($_POST['remember'])){

                $params = session_get_cookie_params();
                setcookie(session_name(), $_COOKIE[session_name()], time() + 60*60*24*7, $params["path"], $params["domain"], $secure = TRUE, $httponly = TRUE);

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
        if (mail($to, $subject, $body, $headers, "-f " . $from))
        {
            $email_msg = 'Seu email foi enviado com sucesso, aguarde contato!';
            $email_success = true;
        }
        else
        {
            $email_msg = 'Houve um problema com seu email, a solicitação não foi enviada';
            $email_success = false;
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

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ./-?#*=+_')
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
$from = "no-reply@confusa.top";

$headers = "From: " . $from . "\r\n";

$subject = "Sua nova senha temporária para o CONFUSA.TOP";
$body = "Sua nova senha temporária é: " . $presenhaTemp . "\r\n" .
        "Altere assim que possível no menu do usuário do site.";



if( filter_var($_POST['forgetemail'], FILTER_VALIDATE_EMAIL) && $change_success)
{
    if (mail($to, $subject, $body, $headers, "-f " . $from))
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
