<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$usuario = new Usuario($db);

$page_title = "Preferências da Conta";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $idUsuario = $_SESSION['user_id'];
    $nomeUsuario = $_SESSION['username'];

    // Load user's current real name and avatar from database
    $queryUserInfo = "SELECT nome, avatar FROM usuarios WHERE id = ? LIMIT 1";
    $stmtUser = $db->prepare($queryUserInfo);
    $stmtUser->execute([$idUsuario]);
    $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $realName = $userInfo['nome'] ?? '';
    $avatarUrl = $userInfo['avatar'] ?? '';

    // if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_preferencias'])){
        $novoNomeReal = trim($_POST['nome_real'] ?? '');
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $senhaNova = $_POST['senha_nova'] ?? '';
        $confirmacaoSenhaNova = $_POST['confirmacao_senha_nova'] ?? '';

        $mensagem_erro = '';
        $mensagem_sucesso = '';
        $erros = 0;
        $alterouNome = false;
        $alterouSenha = false;
        $alterouAvatar = false;

        // 1. Handle Real Name Update
        if (!empty($novoNomeReal) && $novoNomeReal !== $realName) {
            $queryUpdateNome = "UPDATE usuarios SET nome = ? WHERE id = ?";
            $stmtUpdateNome = $db->prepare($queryUpdateNome);
            if ($stmtUpdateNome->execute([$novoNomeReal, $idUsuario])) {
                $realName = $novoNomeReal;
                $_SESSION['nomereal'] = $novoNomeReal;
                $mensagem_sucesso .= "Nome de exibição atualizado com sucesso! ";
                $alterouNome = true;
            } else {
                $mensagem_erro .= "Falha ao atualizar o nome de exibição.\n";
                $erros++;
            }
        }

        // 2. Handle Password Update (only if current password is provided)
        if (!empty($senhaAtual)) {
            if (empty($senhaNova) || empty($confirmacaoSenhaNova)) {
                $mensagem_erro .= "Para alterar a senha, você deve preencher os campos de nova senha e confirmação.\n";
                $erros++;
            } else {
                if ($senhaNova === $confirmacaoSenhaNova) {
                    $hash_bd = $usuario->passById($idUsuario);
                    $senha_bd = $hash_bd['senha'];

                    if (password_verify($senhaAtual, $senha_bd)) {
                        $hashSenhaNova = password_hash($senhaNova, PASSWORD_DEFAULT);
                        if ($usuario->alterarSenha($idUsuario, $hashSenhaNova)) {
                            $mensagem_sucesso .= "Senha alterada com sucesso! ";
                            $alterouSenha = true;
                        } else {
                            $mensagem_erro .= "Não foi possível realizar a alteração da senha, tente novamente mais tarde.\n";
                            $erros++;
                        }
                    } else {
                        $mensagem_erro .= "A senha atual informada está incorreta.\n";
                        $erros++;
                    }
                } else {
                    $mensagem_erro .= "A nova senha e a confirmação não coincidem.\n";
                    $erros++;
                }
            }
        } elseif (!empty($senhaNova) || !empty($confirmacaoSenhaNova)) {
            $mensagem_erro .= "Você deve informar a sua senha atual para autorizar a alteração de senha.\n";
            $erros++;
        }

        // 3. Handle Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadFileDir = $_SERVER['DOCUMENT_ROOT'] . '/images/avatars/';
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $newFileName = 'avatar_' . $idUsuario . '_' . time() . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $avatarPath = '/images/avatars/' . $newFileName;
                    $queryUpdateAvatar = "UPDATE usuarios SET avatar = ? WHERE id = ?";
                    $stmtUpdateAvatar = $db->prepare($queryUpdateAvatar);
                    if ($stmtUpdateAvatar->execute([$avatarPath, $idUsuario])) {
                        if (!empty($avatarUrl) && strpos($avatarUrl, '/images/avatars/') === 0 && file_exists($_SERVER['DOCUMENT_ROOT'] . $avatarUrl)) {
                            @unlink($_SERVER['DOCUMENT_ROOT'] . $avatarUrl);
                        }
                        $avatarUrl = $avatarPath;
                        $_SESSION['avatar'] = $avatarPath;
                        $mensagem_sucesso .= "Avatar atualizado com sucesso! ";
                        $alterouAvatar = true;
                    } else {
                        $mensagem_erro .= "Falha ao salvar o caminho do avatar no banco de dados.\n";
                        $erros++;
                    }
                } else {
                    $mensagem_erro .= "Houve um erro ao salvar o arquivo do avatar.\n";
                    $erros++;
                }
            } else {
                $mensagem_erro .= "Extensão de arquivo não permitida para o avatar. Use JPG, JPEG, PNG, GIF ou WEBP.\n";
                $erros++;
            }
        }

        if ($erros > 0) {
            echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Não foi possível salvar as alterações, ocorreram {$erros} erros:<br>" . nl2br(htmlspecialchars($mensagem_erro)) . "</div>";
        } elseif ($alterouNome || $alterouSenha || $alterouAvatar) {
            echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>{$mensagem_sucesso}</div>";
        }
    }
?>

<script type="application/javascript">
$(document).ready(function($){
    var close = document.getElementsByClassName("closebtn");
    var i;
    for (i = 0; i < close.length; i++) {
        close[i].onclick = function(){
            var div = this.parentElement;
            div.style.opacity = "0";
            setTimeout(function(){ div.style.display = "none"; }, 600);
        }
    }
});
</script>

<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>
<div id='errorbox'></div>
<div>
    <div id='inscricao'>
        <h2 style="color: #f8fafc; font-size: 24px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px;">Preferências da Conta</h2>
        
        <form method="POST" action='<?php echo $_SERVER['PHP_SELF']; ?>' enctype="multipart/form-data">
            
            <label for='username_display'>Nome de usuário (Login)</label>
            <input type='text' id='username_display' class='form-control' value='<?php echo htmlspecialchars($nomeUsuario); ?>' disabled style="background: #0f172a; opacity: 0.7;" />

            <div style="margin-top: 15px; margin-bottom: 15px;">
                <label>Avatar Atual</label>
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 5px;">
                    <img src="<?php echo htmlspecialchars(!empty($avatarUrl) ? $avatarUrl : '/images/default-user.png'); ?>" alt="Avatar" style="width: 64px; height: 64px; border-radius: 50%; border: 2px solid #38bdf8; object-fit: cover;" />
                    <span style="font-size: 13px; color: #94a3b8;">Formatos aceitos: JPG, PNG, WEBP, GIF.</span>
                </div>
            </div>

            <label for='avatar'>Enviar Novo Avatar</label>
            <input type='file' name='avatar' id='avatar' class='form-control' accept="image/*" style="padding: 6px;" />

            <label for='nome_real'>Nome Real (Nome de Exibição)</label>
            <input type='text' name='nome_real' id='nome_real' class='form-control' value='<?php echo htmlspecialchars($realName); ?>' required />

            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #334155;">
                <h4 style="margin: 0 0 10px 0; color: #94a3b8; font-size: 14px; font-weight: 700;">Alterar Senha <span style="font-weight: normal; font-size: 11px; color: #64748b;">(Opcional - preencha apenas se desejar alterar)</span></h4>
            </div>

            <label for='senha_atual'>Senha atual</label>
            <input type='password' name='senha_atual' id='senha_atual' class='form-control' />

            <label for='senha_nova'>Nova senha</label>
            <input type='password' id='senha_nova' name='senha_nova' class='form-control' 
                   title='Senha deve conter:&#10; - Pelo menos 8 caracteres&#10; - Pelo menos um número&#10; - Pelo menos uma letra minúscula&#10; - Pelo menos uma letra maiúscula' 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" />

            <label for='confirmacao_senha_nova'>Confirmação senha</label>
            <input type='password' id='confirmacao_senha_nova' name='confirmacao_senha_nova' class='form-control' 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" />

            <div style="margin-top: 20px; margin-bottom: 20px;">
                <button type="submit" name="salvar_preferencias" class="btn" style="width: 100%; padding: 12px; font-weight: bold;">Salvar Preferências</button>
            </div>
        </form>

        <?php
            // Obter Token MCP
            $queryMcp = "SELECT mcp_token, emTeste FROM usuarios WHERE id = ?";
            $stmtMcp = $db->prepare($queryMcp);
            $stmtMcp->execute([$idUsuario]);
            $userMcp = $stmtMcp->fetch(PDO::FETCH_ASSOC);
            $mcpToken = $userMcp['mcp_token'] ?? null;
            $emTeste = (int)($userMcp['emTeste'] ?? 1);

            if ($emTeste === 0):
        ?>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #334155; color: #cbd5e1; font-family: 'Outfit', 'Inter', sans-serif;">
                <h3 style="margin: 0 0 10px 0; color: #38bdf8; font-size: 18px; font-weight: 700;">Integração MCP (Assistente de IA)</h3>
                <p style="font-size: 13px; color: #94a3b8; margin: 0 0 15px 0; line-height: 1.5;">Conecte o ChatGPT/Claude via MCP para consultar e analisar táticas, elencos e pesquisar no mercado. No campo de autenticação (Authentication), selecione <strong>None</strong> (sem autenticação) e use o link completo com o seu token embutido abaixo.</p>
                
                <div style="margin-top: 12px; font-size: 11px; color: #64748b; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" id="mcp_url_input" readonly value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/mcp_server.php?token=<?php echo urlencode($mcpToken ?? ''); ?>" 
                               style="flex-grow: 1; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #38bdf8; font-family: monospace; font-size: 13px; border-radius: 6px; outline: none; width: 100%;" />
                        <button onclick="navigator.clipboard.writeText(document.getElementById('mcp_url_input').value); alert('Link copiado!');" 
                                class="btn" style="padding: 10px 18px; font-size: 13px; margin: 0; min-width: auto; font-weight: 700; height: auto; cursor: pointer;">Copiar Link</button>
                    </div>
                </div>

                <details style="margin-top: 20px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; padding: 10px;">
                    <summary style="cursor: pointer; font-size: 13px; font-weight: 700; color: #38bdf8; outline: none; user-select: none;">Como configurar no ChatGPT? (Passo a Passo)</summary>
                    <div style="margin-top: 10px; font-size: 12px; color: #cbd5e1; line-height: 1.6;">
                        <strong style="color: #f59e0b;">Primeira vez (Habilitar modo desenvolvedor):</strong>
                        <ol style="margin: 5px 0 15px 20px; padding: 0;">
                            <li>Abra o ChatGPT no navegador.</li>
                            <li>Clique no seu <strong>Avatar</strong> (canto inferior esquerdo ou superior direito).</li>
                            <li>Vá em <strong>Settings (Configurações)</strong>.</li>
                            <li>Acesse a aba <strong>Security and login</strong> (ou Developer) e ative a opção <strong>Developer mode</strong> (Modo Desenvolvedor).</li>
                        </ol>

                        <strong style="color: #f59e0b;">Adicionando o Plugin:</strong>
                        <ol style="margin: 5px 0 10px 20px; padding: 0;">
                            <li>Ainda em <strong>Settings</strong>, clique na aba <strong>Plugins</strong>.</li>
                            <li>Clique em <strong>Browse Plugins</strong> (Navegar pelos Plugins).</li>
                            <li>Clique no botão <strong>+</strong> (ou Adicionar Plugin Pessoal/Customizado).</li>
                            <li>
                                <strong>Ícone (Icon)</strong>: Faça o download do ícone oficial otimizado do Confusatop clicando no link abaixo e envie-o no formulário:
                                <br />
                                <a href="/mcp-logo.png" download="mcp-logo.png" style="color: #38bdf8; text-decoration: underline; font-weight: bold;">Baixar Ícone do Confusatop</a>
                            </li>
                            <li><strong>Name</strong>: Digite um nome para a ferramenta (ex: <code>Confusatop</code>).</li>
                            <li><strong>Connection / MCP Server URL</strong>: Cole o <strong>Link Completo</strong> que você copiou acima.</li>
                            <li><strong>Authentication</strong>: Escolha a opção <strong>None</strong> (Sem autenticação).</li>
                            <li>Marque a caixinha <strong>"I understand and want to continue"</strong> (Entendo e quero continuar).</li>
                            <li>Clique em <strong>Create</strong> (Criar).</li>
                        </ol>
                        <p style="margin: 10px 0 0 0; font-size: 11px; color: #94a3b8;">Pronto! Agora em qualquer conversa do ChatGPT, basta invocar a ferramenta digitando <strong>@</strong> seguido do nome que você definiu (ex: <strong>@Confusatop</strong>) para começar a fazer as consultas!</p>
                    </div>
                </details>
            </div>
        <?php
            endif;
        ?>
    </div>
</div>

<?php
} else {
    echo "<div style='text-align: center; margin: 100px auto; color: #e2e8f0;'>Usuário sem permissão para acessar esta página. Por favor, faça o login.</div>";
}
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
