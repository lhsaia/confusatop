<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$paises = new Pais($db);


$page_title = "Inserir usuário";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo"<div>";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && $_SESSION['admin_status']=='1'){

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
    if ( isset( $_POST['nomeusuario'] ) && !empty( $_POST['nomeusuario'] ) && !empty ( $_POST['email'] ) ) {




        // criando uma nova senha aleatória
        /**
         * Generate a random string, using a cryptographically secure
         * pseudorandom number generator (random_int)
         *
         * For PHP 7, random_int is a PHP core function
         * For PHP 5.x, depends on https://github.com/paragonie/random_compat
         *
         * @param int $length      How many characters do we want?
         * @param string $keyspace A string of all possible characters
         *                         to select from
         * @return string
         */
        function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ./-?#*=+_')
        {
            $pieces = [];
            $max = mb_strlen($keyspace, '8bit') - 1;
            for ($i = 0; $i < $length; ++$i) {
                $pieces []= $keyspace[random_int(0, $max)];
            }
            return implode('', $pieces);
        }

        //senha hash
        $presenha = random_str(12);
        $senhahash = password_hash($presenha,PASSWORD_DEFAULT);

        $usuario->nomeusuario = $_POST['nomeusuario'];
        $usuario->senha = $senhahash;
        $usuario->email = $_POST['email'];
        $usuario->nome = $_POST['nomereal'];

        // criar usuario
        if($usuario->inserir()){
            echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Usuário inserido com sucesso</div>";
            //Enviar email com senha para usuário
            $novoemail = $usuario->email;
            $novonome = $usuario->nome;
            $nomeusuario = $usuario->nomeusuario;

            $to = $novoemail;
            $from = "no-reply@confusa.top";

            $headers = "From: " . $from . "\r\n";

            $subject = "Seja bem-vindo ao site CONFUSA.TOP!";
            $body = "Olá " . $novonome . "!\r\n" .
                "Suas informações para login seguem abaixo:\r\n".
                "Usuário: " . $nomeusuario . "\r\n" .
                "Senha: ". $presenha. "\r\n" .
                "Você conseguirá trocar sua senha escolhendo a opção 'Trocar senha' na barra de tarefas do site";

            if (mail($to, $subject, $body, $headers, "-f " . $from))
            {
                $email_success = true;
            }

            //Pesquisar usuário e vincular países

            $novoIdUsuario = $usuario->idByEmail($novoemail);

            foreach($_POST['paises_vinculados'] as $vincular){
                $paises->vincularUsuario($vincular, $novoIdUsuario);
            }


        } else {
            echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o usuário, possível duplicata</div>";

        }

        } else {
            echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível inserir o usuário, campos em branco</div>";
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
                    <td class="td_inv input_nome_time">Username:</td>
                    <td class="td_inv input_nome_time"><input type='text' name='nomeusuario' class='form-control'></td>
                </tr>
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Email:</td>
                    <td class="td_inv input_nome_time"><input type='email' name='email' class='form-control'></td>
                </tr>
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Nome:</td>
                    <td class="td_inv input_nome_time"><input type='text' name='nomereal' class='form-control'></td>
                </tr>
                <!--vinculação de países inicio -->
                <tr class="tr_inv">
                    <td class="td_inv input_nome_time">Países vinculados</td>
                    <td class="td_inv input_nome_time">
                <?php
                    // ler times do banco de dados
                    $stmt = $paises->read();

                    // put them in a select drop-down
                    echo "<select size='15' class='form-control' name='paises_vinculados[]' multiple>";
                    echo "<option>Selecione país...</option>";

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
                    <td class="td_inv input_nome_time"><input type=submit name="criar" id="inserir" class="btn"/></td>
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
