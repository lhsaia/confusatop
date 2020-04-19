<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$usuario = new Usuario($db);

$page_title = "Alterar senha";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $idUsuario = $_SESSION['user_id'];
    $nomeUsuario = $_SESSION['username'];

// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['alterar'])){
if(isset($_POST['senha_atual']) && !empty($_POST['senha_atual']) && !empty($_POST['senha_nova']) && !empty($_POST['confirmacao_senha_nova'])){

    $senhaAtual = $_POST['senha_atual'];
    $senhaNova = $_POST['senha_nova'];
    $confirmacaoSenhaNova = $_POST['confirmacao_senha_nova'];

    $mensagem_erro = '';
    $mensagem_sucesso = 'Senha alterada com sucesso!';
    $erros = 0;

    if($senhaNova === $confirmacaoSenhaNova){

    } else {
        $mensagem_erro .= "A senha e a confirmação não coincidem\n";
        $erros++;
    }

    $hash_bd = $usuario->passById($idUsuario);
    $senha_bd = $hash_bd['senha'];

    if( password_verify( $senhaAtual , $senha_bd ) ){

    } else {
        $mensagem_erro .= "A senha informada está incorreta\n";
        $erros++;
    }

    $hashSenhaNova = password_hash($_POST['senha_nova'],PASSWORD_DEFAULT);

    if($erros>0){
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível alterar a senha, ocorreram {$erros} erros:\n {$mensagem_erro}</div>";
    } else {
        if($usuario->alterarSenha($idUsuario, $hashSenhaNova)){
            echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>{$mensagem_sucesso}</div>";
        } else {
            $mensagem_erro .= "Não foi possível realizar a alteração, tente novamente mais tarde.";
            echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível alterar a senha, ocorreram {$erros} erros:\n {$mensagem_erro}</div>";
        }

    }

} else {
    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível alterar a senha, campos em branco.</div>";
}
}
?>

<script type="application/javascript">
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 600);
    }
}
</script>


<form method="POST" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    <table class='table table-below float-table'>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nome usuário</td>
            <td class="td_inv input_nome_time" disabled>
                <?php
                    echo $nomeUsuario;
                ?>
            </td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Senha atual</td>
            <td class="td_inv input_nome_time"><input type='password' name='senha_atual' class='form-control'/></td>
        </tr>



        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Nova senha</td>
            <td class="td_inv input_nome_time"><input type='password' title='<?php echo "Senha deve conter:\n - Pelo menos 8 caracteres\n - Pelo menos um numero\n - Pelo menos uma letra minúscula\n - Pelo menos uma letra maiúscula"  ?>' pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" name='senha_nova' class='form-control'/></td>
        </tr>

        <tr class="tr_inv">
            <td class="td_inv input_nome_time">Confirmação senha</td>
            <td class="td_inv input_nome_time"><input type='password' pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" name='confirmacao_senha_nova' class='form-control'/></td>
        </tr>

        <tr class="tr_inv btn_area">
            <td class="td_inv btn_area"></td>
            <td class="td_inv btn_area">
                <button type="submit" name="alterar" class="btn">Alterar senha</button>
            </td>
        </tr>

    </table>
</form>

<?php

    } else {

    echo "Usuário sem permissão para editar senhas, por favor faça o login.";
}
echo "</div>";
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
