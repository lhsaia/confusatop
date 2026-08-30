<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

require_once($_SERVER['DOCUMENT_ROOT']."/elements/mail_setup.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$paises = new Pais($db);


$feedback_html = '';
if(isset($_SESSION['flash_msg'])){
    $feedback_html = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && (int)$_SESSION['admin_status']===1){
        if ( isset( $_POST['nomeusuario'] ) && !empty( $_POST['nomeusuario'] ) && !empty ( $_POST['email'] ) ) {

            if (!function_exists('random_str')) {
                function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
                {
                    $pieces = [];
                    $max = mb_strlen($keyspace, '8bit') - 1;
                    for ($i = 0; $i < $length; ++$i) {
                        $pieces []= $keyspace[random_int(0, $max)];
                    }
                    return implode('', $pieces);
                }
            }

            //senha hash
            $presenha = random_str(12);
            $senhahash = password_hash($presenha,PASSWORD_DEFAULT);

            $usuario->nomeusuario = $_POST['nomeusuario'];
            $usuario->senha = $senhahash;
            $usuario->email = $_POST['email'];
            $usuario->nome = $_POST['nomereal'];
            if(isset($_POST['membro'])) {
                $usuario->emTeste = 0;
            } else {
                $usuario->emTeste = 1;
            }
            
            // criar usuario
            if($usuario->inserir()){
                $msg_extra = '';
                //Enviar email com senha para usuário
                $novoemail = $usuario->email;
                $novonome = $usuario->nome;
                $nomeusuario = $usuario->nomeusuario;
                

                $body = "Olá " . $novonome . "!\r\n" .
                    "Suas informações para login seguem abaixo:\r\n".
                    "Usuário: " . $nomeusuario . "\r\n" .
                    "Senha: ". $presenha. "\r\n" .
                    "Você conseguirá trocar sua senha escolhendo a opção 'Trocar senha' na barra de tarefas do site";
                    
                    
                $mail->setFrom('admin@confusa.top', 'Confusa.top');
                $mail->addAddress($novoemail);               //Name is optional
                $mail->Subject = "Seja bem-vindo ao site CONFUSA.TOP!";
                $mail->Body    = $body;
                try {
                    if ($mail->send())
                    {
                        $email_success = true;
                    }
                } catch (\Throwable $e) {
                    error_log("Erro ao enviar email de criar usuario: " . $e->getMessage() . " / Mailer Error: " . ($mail->ErrorInfo ?? ''));
                    $email_success = false;
                }

                //Pesquisar usuário e vincular países

                $novoIdUsuario = $usuario->idByEmail($novoemail);

                if(isset($_POST['paises_vinculados'])){
                    foreach($_POST['paises_vinculados'] as $vincular){
                        $paises->vincularUsuario($vincular, $novoIdUsuario);
                    }
                }

                // Se veio de uma solicitação de inscrição, atualizar o status dela no banco
                if (isset($_GET['solicitacao_id'])) {
                    try {
                        $solicitacao_id = (int)$_GET['solicitacao_id'];
                        $stmt_upd = $db->prepare("UPDATE `solicitacoes_cadastro` SET status = 'aprovado' WHERE id = ?");
                        $stmt_upd->execute([$solicitacao_id]);
                        $msg_extra = " (Solicitação de cadastro aprovada)";
                    } catch (PDOException $e) {
                        // ignora erros silenciosamente
                    }
                }

                $_SESSION['flash_msg'] = "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Usuário inserido com sucesso" . $msg_extra . "</div>";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;

            } else {
                $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o usuário, possível duplicata</div>";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

        } else {
            $_SESSION['flash_msg'] = "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o usuário, campos em branco</div>";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$page_title = "Inserir usuário";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo "<div class='criar-usuario-container'>";
?>
<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 25px;">
    <div>
        <h1 class="admin-gradient-title">Criar Novo Usuário</h1>
        <p style="margin: 5px 0 0 0; color: #94a3b8; font-size: 14px;">Cadastre administradores ou membros no banco de dados</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="/admin/index.php" class="admin-btn admin-btn-secondary">Voltar ao Painel</a>
    </div>
</div>
<?php
echo $feedback_html;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && (int)$_SESSION['admin_status']===1){
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

        <form method="POST" action='<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['solicitacao_id']) ? '?solicitacao_id=' . urlencode($_GET['solicitacao_id']) : ''); ?>'>
            <table class='table table-below float-table'>
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Username:</td>
                    <td class="td_inv input_nome_time"><input type='text' name='nomeusuario' value='<?php echo isset($_GET['nomereal']) ? htmlspecialchars(strtolower(str_replace(' ', '', $_GET['nomereal']))) : ''; ?>' class='admin-input'></td>
                </tr>
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Email:</td>
                    <td class="td_inv input_nome_time"><input type='email' name='email' value='<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>' class='admin-input'></td>
                </tr>
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Nome Real:</td>
                    <td class="td_inv input_nome_time"><input type='text' name='nomereal' value='<?php echo isset($_GET['nomereal']) ? htmlspecialchars($_GET['nomereal']) : ''; ?>' class='admin-input'></td>
                </tr>
				
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">É membro da CONFUSA?</td>
                    <td class="td_inv input_nome_time checkbox_container" style="padding-top: 15px;">
                        <input type="checkbox" name='membro' style="transform: scale(1.3); margin-left: 5px; cursor: pointer;">
                    </td>
                </tr>
				
                <!--vinculação de países inicio -->
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time" style="padding-top: 15px;">Países vinculados:</td>
                    <td class="td_inv input_nome_time" style="padding-top: 15px;">
                <?php
                    // ler times do banco de dados
                    $stmt = $paises->read(null,null,null);

                    // put them in a select drop-down
                    echo "<select size='15' class='admin-select' name='paises_vinculados[]' multiple style='min-height: 220px;'>";
                    echo "<option disabled>Selecione os países (Ctrl + clique para múltiplos)...</option>";

                    while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                        extract($row_category);
                        echo "<option value='{$id}'>{$nome}</option>";
                    }

                echo "</select>";
                ?>
            </td>
        </tr>
                <!--vinculação de países fim -->
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time"></td>
                    <td class="td_inv input_nome_time" style="padding-top: 25px;"><input type="submit" name="criar" id="inserir" class="admin-btn admin-btn-primary" value="Inserir Usuário"/></td>
                </tr>


            </table>


        </form>

<?php

    } else {

    echo "Usuário sem permissão para criar usuários.";
}

echo "</div>";
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
