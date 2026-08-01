# Guia de Configuração: Servidor MCP PHP na Hospedagem Compartilhada para o ChatGPT

Este guia explica como configurar um servidor MCP (Model Context Protocol) usando a sua hospedagem compartilhada atual (Apache/Linux) de forma fácil, barata (R$ 0 adicionais) e segura para interagir diretamente com o ChatGPT.

---

## Passo 1: Instalação na Hospedagem

Acesse o terminal da sua hospedagem via SSH (na pasta raiz do seu site) e instale o pacote oficial da comunidade PHP:

```bash
composer require php-mcp/server
```

*Nota: Se a sua hospedagem não oferecer acesso SSH, execute o comando acima no seu computador local e envie a pasta `vendor` gerada para o servidor usando um cliente FTP (como o FileZilla).*

---

## Passo 2: Criar o Arquivo do Servidor MCP Seguro

Crie um arquivo chamado `mcp.php` na pasta pública do seu site (geralmente dentro de `public_html/` ou `public/`). 

Este código já inclui uma **camada de proteção obrigatória por token** para evitar que terceiros acessem seus dados, e utiliza o formato **HTTP/SSE**, que é o exigido pelo ChatGPT para conexões web.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use PhpMcp\Server\McpServer;
use PhpMcp\Server\Transport\HttpSseServerTransport;

// ==========================================
// 1. CONFIGURAÇÃO DE SEGURANÇA (TOKEN)
// ==========================================
// Substitua pela sua senha ultra secreta. O ChatGPT precisará dela para se autenticar.
\$api_token = "SUA_SENHA_ULTRA_SECRETA_AQUI";

// Captura e valida os cabeçalhos HTTP recebidos
\$headers = getallheaders();
\(auth_header =\)headers['Authorization'] ?? '';

if (\(auth_header !== "Bearer " . \)api_token) {
    http_response_code(401);
    echo json_encode(["error" => "Acesso não autorizado"]);
    exit;
}

// ==========================================
// 2. INICIALIZAÇÃO DO SERVIDOR MCP (HTTP/SSE)
// ==========================================
\$transport = new HttpSseServerTransport();

server = new McpServer(transport, [
    'name' => 'MeuSitePHP',
    'version' => '1.0.0'
]);

// ==========================================
// 3. CADASTRO DE FERRAMENTAS (TOOLS)
// ==========================================
// Adicione aqui as funções que você deseja que a IA execute no seu site
\$server->registerTool(
    'obter_status_site',
    'Retorna estatísticas gerais do site para a IA',
    [], // Parâmetros extras se necessário (vazio neste exemplo)
    function (\$arguments) {
        // Exemplo: Aqui você pode incluir seus requires normais do PHP para conectar
        // ao banco de dados do seu próprio site e puxar informações reais.
        return "Conexão com o banco ativa. Total de usuários cadastrados: 1.240.";
    }
);

// ==========================================
// 4. EXECUÇÃO
// ==========================================
\$server->start();
```

---

## Passo 3: Vincular ao ChatGPT (Interface Web)

A OpenAI permite conectar servidores MCP remotos utilizando o Modo Desenvolvedor do ChatGPT. Siga estes passos na interface web:

1. Acesse o [ChatGPT](https://chatgpt.com).
2. Clique na sua foto de perfil (canto inferior esquerdo) e selecione **Settings** (Configurações).
3. Vá até a aba **Apps** (ou *Connectors*, dependendo da versão da sua conta).
4. Clique em **Advanced Settings** (Configurações Avançadas) e ative a opção **Developer Mode** (Modo Desenvolvedor).
5. Clique em **Create** (Criar) ou **Add Custom App** (Adicionar App Personalizado).
6. Preencha o formulário com os seguintes dados:
   * **Name:** `Meu Site PHP`
   * **URL:** `https://seu-site.com` (Ajuste para o link real do seu arquivo)
   * **Authentication:** Selecione a opção **Bearer Token** (ou API Key) e insira a senha exata definida na variável `$api_token` do arquivo PHP.
7. Salve as configurações.

---

## Passo 4: Como Utilizar no Chat

1. Inicie uma nova conversa no ChatGPT.
2. Clique no ícone de **`+` (Mais)** ou no seletor de ferramentas dentro da caixa de mensagens.
3. Acesse a seção de ferramentas de desenvolvedor e ative o seu app **"Meu Site PHP"** para este chat específico.
4. Digite um comando natural para testar, por exemplo: 
   > *"Verifique o status do meu site através do servidor MCP e me diga o resultado."*
5. O ChatGPT enviará uma requisição HTTP criptografada em tempo real para a sua hospedagem, validará o token e usará o retorno do PHP para responder a você.
