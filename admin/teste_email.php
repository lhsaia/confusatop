<?php
declare(strict_types=1);

// Configuração e Autenticação
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)$_SESSION['admin_status'] !== 1) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

// Carregar variáveis do .env
$envPath = $_SERVER['DOCUMENT_ROOT'] . '/.env';
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

$smtpHost = getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'mail.confusa.top');
$smtpUser = getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? '');
$smtpPass = getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? '');
$smtpSecure = getenv('SMTP_SECURE') ?: ($_ENV['SMTP_SECURE'] ?? 'ssl');
$smtpPort = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 465));

// Teste de conexão de socket TCP rápido
$socketStatus = 'Não testado';
$socketColor = '#94a3b8';
$socketError = '';
if (!empty($smtpHost) && $smtpPort > 0) {
    $timeout = 4;
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, $timeout);
    if ($fp) {
        $socketStatus = 'Conectado (Porta acessível)';
        $socketColor = '#10b981';
        fclose($fp);
    } else {
        $socketStatus = 'Falha de Conexão (' . htmlspecialchars($errstr ?: "Código: $errno") . ')';
        $socketColor = '#ef4444';
        $socketError = $errstr;
    }
}

// Obter usuário logado para e-mail padrão
$database = new Database();
$db = $database->getConnection();
$userEmail = '';
if ($db && isset($_SESSION['user_id'])) {
    $stmtUser = $db->prepare("SELECT email FROM usuarios WHERE id = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_id']]);
    $userEmail = $stmtUser->fetchColumn() ?: '';
}

// Obter alguns clubes para o seletor de simulação de proposta
$clubesExemplo = [];
if ($db) {
    try {
        $stmtClubes = $db->query("SELECT ID, Nome, Escudo FROM clube WHERE Escudo IS NOT NULL AND Escudo != '' ORDER BY Nome ASC LIMIT 100");
        $clubesExemplo = $stmtClubes->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        // Silêncio
    }
}

$feedback_msg = '';
$feedback_type = '';
$debug_output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatario = trim($_POST['destinatario'] ?? '');
    $tipoEmail = trim($_POST['tipo_email'] ?? 'basico');
    $clubeId = (int)($_POST['clube_id'] ?? 0);
    $nomeJogador = trim($_POST['nome_jogador'] ?? 'Neymar Jr');
    $valorProposta = trim($_POST['valor_proposta'] ?? '25 000 000');
    $enableDebug = !empty($_POST['enable_debug']);

    if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        $feedback_msg = 'Por favor, informe um endereço de e-mail de destinatário válido.';
        $feedback_type = 'danger';
    } else {
        // Montar conteúdo do e-mail de acordo com o tipo
        $nomeClube = 'São Paulo FC';
        $escudoClube = 'Sao Paulo.png';

        if ($clubeId > 0 && $db) {
            $stmtC = $db->prepare("SELECT Nome, Escudo FROM clube WHERE ID = ? LIMIT 1");
            $stmtC->execute([$clubeId]);
            $clubeData = $stmtC->fetch(PDO::FETCH_ASSOC);
            if ($clubeData) {
                $nomeClube = $clubeData['Nome'];
                $escudoClube = $clubeData['Escudo'];
            }
        }

        $escudoUrl = !empty($escudoClube) ? 'https://confusa.top/images/escudos/' . implode('/', array_map('rawurlencode', explode('/', $escudoClube))) : '';
        $imgHtml = !empty($escudoUrl) ? "<img align='middle' height='60' src='{$escudoUrl}' alt='" . htmlspecialchars($nomeClube, ENT_QUOTES, 'UTF-8') . "'/>" : '';

        require_once($_SERVER['DOCUMENT_ROOT'] . "/elements/mail_template.php");

        if ($tipoEmail === 'proposta_jogador') {
            $subject = "[CONFUSA.top] [TESTE] " . $nomeClube . " fez uma proposta por " . $nomeJogador;
            
            $contentHtml = "
            <div style='text-align: center; margin-bottom: 20px;'>
                {$imgHtml}
                <div style='font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 8px;'>" . htmlspecialchars($nomeClube) . "</div>
            </div>

            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='6' style='font-size: 14px;'>
                    <tr>
                        <td style='color: #64748b; font-weight: 600; width: 35%;'>Jogador:</td>
                        <td style='color: #0f172a; font-weight: 700;'>" . htmlspecialchars($nomeJogador) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Tipo:</td>
                        <td style='color: #0f172a; font-weight: 600;'>Venda Definitiva</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Valor da Oferta:</td>
                        <td style='color: #16a34a; font-weight: 700; font-size: 16px;'>F$ " . htmlspecialchars($valorProposta) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Duração:</td>
                        <td style='color: #475569;'>Tempo Indeterminado</td>
                    </tr>
                </table>

                <div style='margin-top: 16px; padding: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 6px; text-align: left;'>
                    <strong style='color: #0284c7; font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 4px;'>Mensagem do Clube:</strong>
                    <div style='color: #334155; font-size: 14px; font-style: italic;'>\"Olá! Este é um e-mail de teste de proposta disparado pelo Painel de Diagnóstico do CONFUSA.top.\"</div>
                </div>
            </div>

            <p style='color: #475569; font-size: 14px; line-height: 1.5; margin: 0; text-align: center;'>
                Acesse o portal para <strong>aceitar</strong>, <strong>rejeitar</strong> ou realizar uma <strong>contraproposta</strong>.
            </p>";

            $html_content = renderConfusaEmail([
                'title' => 'Nova Proposta de Transferência (Simulação)',
                'subtitle' => 'Seu clube recebeu uma proposta oficial de negociação no mercado.',
                'badge' => [
                    'text' => 'Mercado de Transferências',
                    'bg' => '#e0f2fe',
                    'color' => '#0369a1'
                ],
                'content_html' => $contentHtml,
                'btn' => [
                    'url' => 'https://confusa.top/mercado/transferencias.php',
                    'text' => 'Acessar Central de Transferências'
                ]
            ]);
        } elseif ($tipoEmail === 'proposta_tecnico') {
            $subject = "[CONFUSA.top] [TESTE] " . $nomeClube . " fez uma proposta pelo técnico " . $nomeJogador;
            
            $contentHtml = "
            <div style='text-align: center; margin-bottom: 20px;'>
                {$imgHtml}
                <div style='font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 8px;'>" . htmlspecialchars($nomeClube) . "</div>
            </div>

            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='6' style='font-size: 14px;'>
                    <tr>
                        <td style='color: #64748b; font-weight: 600; width: 35%;'>Técnico:</td>
                        <td style='color: #0f172a; font-weight: 700;'>" . htmlspecialchars($nomeJogador) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Nível:</td>
                        <td style='color: #0284c7; font-weight: 700;'>85</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Tipo:</td>
                        <td style='color: #0f172a; font-weight: 600;'>Contratação de Comissão Técnica</td>
                    </tr>
                </table>

                <div style='margin-top: 16px; padding: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 6px; text-align: left;'>
                    <strong style='color: #0284c7; font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 4px;'>Mensagem do Clube:</strong>
                    <div style='color: #334155; font-size: 14px; font-style: italic;'>\"Disparo de validação de envio de e-mails para treinadores do sistema.\"</div>
                </div>
            </div>

            <p style='color: #475569; font-size: 14px; line-height: 1.5; margin: 0; text-align: center;'>
                Acesse o portal para <strong>aceitar</strong>, <strong>rejeitar</strong> ou realizar uma <strong>contraproposta</strong>.
            </p>";

            $html_content = renderConfusaEmail([
                'title' => 'Proposta de Contratação de Técnico (Simulação)',
                'subtitle' => 'Seu clube recebeu uma oferta para contratação de treinador.',
                'badge' => [
                    'text' => 'Comissão Técnica',
                    'bg' => '#e0f2fe',
                    'color' => '#0369a1'
                ],
                'content_html' => $contentHtml,
                'btn' => [
                    'url' => 'https://confusa.top/mercado/transferencias.php',
                    'text' => 'Acessar Central de Transferências'
                ]
            ]);
        } else {
            // Teste simples
            $subject = "[CONFUSA.top] E-mail de Teste do Sistema - " . date('d/m/Y H:i:s');
            
            $contentHtml = "
            <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px; color: #166534;'>
                ✓ <strong>Parabéns!</strong> O servidor SMTP do CONFUSA.top está configurado e enviando mensagens corretamente com o layout oficial padronizado.
            </div>

            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 10px;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='6' style='font-size: 14px;'>
                    <tr>
                        <td style='color: #64748b; font-weight: 600; width: 35%;'>Data/Hora:</td>
                        <td style='color: #0f172a; font-weight: 600;'>" . date('d/m/Y H:i:s') . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Destinatário:</td>
                        <td style='color: #0284c7; font-weight: 600;'>" . htmlspecialchars($destinatario) . "</td>
                    </tr>
                    <tr>
                        <td style='color: #64748b; font-weight: 600;'>Servidor SMTP:</td>
                        <td style='color: #334155;'>" . htmlspecialchars($smtpHost . ':' . $smtpPort) . "</td>
                    </tr>
                </table>
            </div>";

            $html_content = renderConfusaEmail([
                'title' => 'Diagnóstico de Conectividade SMTP',
                'subtitle' => 'Validação de entrega e formatação de e-mails do sistema.',
                'badge' => [
                    'text' => 'Teste SMTP',
                    'bg' => '#dcfce7',
                    'color' => '#15803d'
                ],
                'content_html' => $contentHtml,
                'btn' => [
                    'url' => 'https://confusa.top/admin/teste_email.php',
                    'text' => 'Abrir Ferramenta de Diagnóstico'
                ]
            ]);
        }

        // Carregar e configurar PHPMailer
        try {
            $baseUtils = $_SERVER['DOCUMENT_ROOT'] . '/utils/PHPMailer/src';
            if (file_exists($baseUtils . '/Exception.php')) {
                require_once $baseUtils . '/Exception.php';
                require_once $baseUtils . '/PHPMailer.php';
                require_once $baseUtils . '/SMTP.php';
            } else {
                require_once '/home/lhsaia/confusa.top/utils/PHPMailer/src/Exception.php';
                require_once '/home/lhsaia/confusa.top/utils/PHPMailer/src/PHPMailer.php';
                require_once '/home/lhsaia/confusa.top/utils/PHPMailer/src/SMTP.php';
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Capturar logs de debug se solicitado
            $debugBuffer = '';
            if ($enableDebug) {
                $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function ($str, $level) use (&$debugBuffer) {
                    $debugBuffer .= "[" . date('H:i:s') . " - Nível $level] " . trim($str) . "\n";
                };
            }

            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port       = $smtpPort;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->CharSet = 'UTF-8';
            $mail->clearAddresses();
            $mail->clearReplyTos();
            $mail->setFrom('no-reply@confusa.top', 'CONFUSA.top');
            $mail->addAddress($destinatario);
            $mail->Subject = $subject;
            $mail->Body    = $html_content;
            $mail->isHTML(true);

            $sent = $mail->send();

            if ($sent) {
                $feedback_msg = 'E-mail de teste enviado com sucesso para: ' . htmlspecialchars($destinatario);
                $feedback_type = 'success';
            } else {
                $feedback_msg = 'O envio falhou. Verifique as configurações de SMTP.';
                $feedback_type = 'danger';
            }

            if (!empty($debugBuffer)) {
                $debug_output = $debugBuffer;
            }

        } catch (\Throwable $e) {
            $feedback_msg = 'Erro ao disparar e-mail: ' . $e->getMessage();
            $feedback_type = 'danger';
            if (!empty($debugBuffer)) {
                $debug_output = $debugBuffer;
            }
        }
    }
}

$page_title = "Diagnóstico e Teste de E-mails";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-dashboard-container">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 30px; text-align: center;">
        <h1 class="admin-gradient-title">Diagnóstico e Teste de E-mails</h1>
        <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 15px;">Verifique o status do servidor SMTP e realize disparos de teste de propostas e notificações</p>
    </div>

    <?php if (!empty($feedback_msg)): ?>
        <div class="alert alert-<?php echo $feedback_type; ?>" style="margin-bottom: 25px; padding: 15px 20px; border-radius: 8px; font-weight: 500;">
            <?php echo $feedback_msg; ?>
        </div>
    <?php endif; ?>

    <!-- Status do SMTP -->
    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 22px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <span class="material-symbols-outlined" style="font-size: 28px; color: var(--admin-accent-cyan);">mail</span>
            <h2 style="font-size: 20px; color: #f8fafc; font-weight: 600; margin: 0;">Status do Servidor SMTP</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div style="background: rgba(15, 23, 42, 0.5); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Host SMTP</span>
                <strong style="color: #f1f5f9; font-size: 15px; word-break: break-all;"><?php echo htmlspecialchars($smtpHost); ?></strong>
            </div>
            <div style="background: rgba(15, 23, 42, 0.5); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Porta / Segurança</span>
                <strong style="color: #f1f5f9; font-size: 15px;"><?php echo $smtpPort; ?> (<?php echo strtoupper(htmlspecialchars($smtpSecure)); ?>)</strong>
            </div>
            <div style="background: rgba(15, 23, 42, 0.5); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Usuário SMTP</span>
                <strong style="color: #f1f5f9; font-size: 15px; word-break: break-all;"><?php echo !empty($smtpUser) ? htmlspecialchars($smtpUser) : '<span style="color:#ef4444;">Não configurado</span>'; ?></strong>
            </div>
            <div style="background: rgba(15, 23, 42, 0.5); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Senha SMTP</span>
                <strong style="font-size: 15px;"><?php echo !empty($smtpPass) ? '<span style="color:#10b981;">●●●●●●●● (Configurada)</span>' : '<span style="color:#ef4444;">Ausente</span>'; ?></strong>
            </div>
            <div style="background: rgba(15, 23, 42, 0.5); padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Conectividade TCP</span>
                <strong style="color: <?php echo $socketColor; ?>; font-size: 14px;"><?php echo $socketStatus; ?></strong>
            </div>
        </div>
    </div>

    <!-- Formulário de Teste -->
    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(56, 189, 248, 0.25); border-left: 4px solid var(--admin-accent-cyan); border-radius: 10px; padding: 24px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <span class="material-symbols-outlined" style="font-size: 28px; color: var(--admin-accent-cyan);">send</span>
            <h2 style="font-size: 20px; color: #f8fafc; font-weight: 600; margin: 0;">Disparar E-mail de Teste</h2>
        </div>

        <form method="POST" action="/admin/teste_email.php">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 20px;">
                
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px;">E-mail Destinatário *</label>
                    <input type="email" name="destinatario" value="<?php echo htmlspecialchars($_POST['destinatario'] ?? $userEmail); ?>" required class="admin-input" style="width: 100%; padding: 10px 14px; font-size: 14px; border-radius: 6px;" placeholder="seu-email@exemplo.com">
                    <small style="color: #94a3b8; font-size: 12px;">Preenchido por padrão com o e-mail da sua conta de administrador.</small>
                </div>

                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px;">Modelo / Tipo de E-mail *</label>
                    <select name="tipo_email" id="tipo_email" class="admin-select" style="width: 100%; padding: 10px 14px; font-size: 14px; border-radius: 6px;" onchange="toggleCamposProposta(this.value);">
                        <option value="basico" <?php echo (($_POST['tipo_email'] ?? '') === 'basico') ? 'selected' : ''; ?>>E-mail de Teste Simples (Diagnóstico do Sistema)</option>
                        <option value="proposta_jogador" <?php echo (($_POST['tipo_email'] ?? '') === 'proposta_jogador') ? 'selected' : ''; ?>>Simulação de Proposta de Transferência (Jogador)</option>
                        <option value="proposta_tecnico" <?php echo (($_POST['tipo_email'] ?? '') === 'proposta_tecnico') ? 'selected' : ''; ?>>Simulação de Proposta de Transferência (Técnico)</option>
                    </select>
                </div>

            </div>

            <!-- Campos Extras para Simulação de Proposta -->
            <div id="campos_proposta" style="display: <?php echo (isset($_POST['tipo_email']) && in_array($_POST['tipo_email'], ['proposta_jogador', 'proposta_tecnico'])) ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px; padding: 16px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #94a3b8; margin-bottom: 6px;">Clube Proponente (Testar Escudo)</label>
                    <select name="clube_id" class="admin-select" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 6px;">
                        <?php foreach ($clubesExemplo as $cl): ?>
                            <option value="<?php echo (int)$cl['ID']; ?>" <?php echo ((int)($_POST['clube_id'] ?? 0) === (int)$cl['ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl['Nome'] . ' (' . $cl['Escudo'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 11px;">Permite validar escudos com espaços e caracteres especiais.</small>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #94a3b8; margin-bottom: 6px;">Nome do Atleta / Técnico</label>
                    <input type="text" name="nome_jogador" value="<?php echo htmlspecialchars($_POST['nome_jogador'] ?? 'Neymar Jr'); ?>" class="admin-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 6px;">
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #94a3b8; margin-bottom: 6px;">Valor da Proposta (F$)</label>
                    <input type="text" name="valor_proposta" value="<?php echo htmlspecialchars($_POST['valor_proposta'] ?? '25 000 000'); ?>" class="admin-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="enable_debug" value="1" <?php echo !empty($_POST['enable_debug']) ? 'checked' : ''; ?>>
                    Exibir Log Detalhado do SMTP (Debug output)
                </label>

                <button type="submit" class="admin-btn admin-btn-primary" style="padding: 10px 24px; font-size: 15px;">
                    <span class="material-symbols-outlined">outgoing_mail</span>
                    Enviar E-mail de Teste
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($debug_output)): ?>
        <!-- Painel de Debug SMTP -->
        <div style="background: #020617; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 10px; padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span class="material-symbols-outlined" style="color: #38bdf8; font-size: 24px;">code</span>
                <h3 style="color: #f8fafc; font-size: 16px; margin: 0; font-weight: 600;">Log de Comunicação SMTP (Debug):</h3>
            </div>
            <pre style="background: rgba(0,0,0,0.6); padding: 15px; border-radius: 6px; color: #38bdf8; font-family: monospace; font-size: 12px; line-height: 1.5; max-height: 350px; overflow-y: auto; white-space: pre-wrap; margin: 0; border: 1px solid rgba(255,255,255,0.05);"><?php echo htmlspecialchars($debug_output); ?></pre>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="/admin/index.php" class="admin-btn admin-btn-secondary">
            <span class="material-symbols-outlined">arrow_back</span>
            Voltar ao Painel do Administrador
        </a>
    </div>
</div>

<script>
function toggleCamposProposta(tipo) {
    var campos = document.getElementById('campos_proposta');
    if (tipo === 'proposta_jogador' || tipo === 'proposta_tecnico') {
        campos.style.display = 'grid';
    } else {
        campos.style.display = 'none';
    }
}
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
