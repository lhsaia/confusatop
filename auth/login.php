<?php
session_start();

require 'db.php';

// parâmetros OAuth
$client_id = $_GET['client_id'] ?? $_POST['client_id'] ?? null;
$redirect_uri = $_GET['redirect_uri'] ?? $_POST['redirect_uri'] ?? null;

// Ensure login_info handles login properly
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// se já estiver logado, volta pro authorize
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: /auth/authorize.php?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri
    ]));
    exit;
}

$page_title = "Login - confusauth";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// busca nome do client (opcional, só UI)
$stmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

?>

<style>
.confusauth-container {
    max-width: 400px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
}
.confusauth-container h2 {
    color: #fca311; /* Confusa orange */
    margin-bottom: 20px;
    font-family: 'Montserrat', sans-serif;
}
.confusauth-container p.client-info {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
}
.confusauth-container input[type=text],
.confusauth-container input[type=password] {
    width: 100%;
    padding: 12px;
    margin: 8px 0 16px;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
    border-radius: 4px;
}
.confusauth-container button {
    background-color: #fca311;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
    width: 100%;
    border-radius: 4px;
    font-weight: bold;
    font-size: 16px;
    transition: background-color 0.3s;
}
.confusauth-container button:hover {
    background-color: #e08b00;
}
</style>

<div class="confusauth-container">
    <img src="/auth/confusauth.png" alt="confusauth" style="max-width: 100%; height: auto; margin-bottom: 20px;">
    <p class="client-info">Faça login com sua conta <strong>Confusa.top</strong> para acessar: <br><em><?php echo htmlspecialchars($client['name'] ?? 'o aplicativo externo'); ?></em></p>
    
    <form method="POST" action="">
             
        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client_id); ?>">
        <input type="hidden" name="redirect_uri" value="<?php echo htmlspecialchars($redirect_uri); ?>">

        <label for="username"><b>Nome de usuário ou email</b></label>
        <input id="username" type="text" placeholder="Seu usuário..." name="username" required>

        <label for="password"><b>Senha</b></label>
        <input id="password" type="password" placeholder="Sua senha..." name="password" required>

        <button type="submit" name="loginsubmit" class="submitbtn">Login</button>
        <label>
            <input type="checkbox" checked="checked" name="remember" data-role="none"> Lembrar-me
        </label>
    </form>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
