# PoltronaScore --- Especificação do projeto

## 1. Objetivo

Criar um site/PWA chamado **PoltronaScore**, inspirado conceitualmente
em aplicativos de placares esportivos, mas voltado para os dados de uma
**página própria de uma comunidade**.

O projeto deve:

-   consultar periodicamente uma página externa da comunidade;
-   extrair os dados relevantes;
-   armazená-los em MySQL;
-   disponibilizar os dados através de uma API PHP;
-   apresentar uma interface moderna em HTML/CSS/JavaScript puro;
-   funcionar como PWA;
-   ter identidade visual própria do PoltronaScore;
-   permitir que o usuário instale o PoltronaScore no celular com nome e
    ícone próprios;
-   rodar em uma hospedagem web convencional com PHP + MySQL + Cron
    Jobs.

Não usar React, Next.js, Node.js ou frameworks de frontend sem
necessidade. O desenvolvedor já trabalha com PHP, MySQL, JavaScript,
HTML e CSS.

------------------------------------------------------------------------

# 2. Stack

## Backend

-   PHP 8.x
-   MySQL/MariaDB
-   PDO para acesso ao banco
-   Cron Job para atualização periódica
-   cURL para acesso à fonte externa
-   DOMDocument/DomXPath ou outra técnica adequada para parsing HTML
-   JSON para comunicação entre backend e frontend

## Frontend

-   HTML5
-   CSS3
-   JavaScript vanilla
-   Fetch API
-   Responsive design
-   PWA / Web App Manifest
-   Service Worker

## Infraestrutura

A aplicação será hospedada em hospedagem compartilhada ou VPS que já
disponibiliza:

-   Apache/Nginx
-   PHP
-   MySQL
-   HTTPS
-   Cron Jobs

------------------------------------------------------------------------

# 3. Arquitetura geral

A arquitetura deve separar claramente:

1.  coleta dos dados;
2.  persistência;
3.  API;
4.  interface;
5.  PWA.

Fluxo:

    Página da comunidade
            |
            | scraping periódico
            v
       scraper.php
            |
            v
          MySQL
            |
            v
       API PHP /api/
            |
            v
     JavaScript frontend
            |
            v
      PoltronaScore PWA

O navegador NÃO deve fazer scraping diretamente da página externa.

O scraper deve rodar no servidor.

Se a página externa estiver indisponível, o PoltronaScore deve continuar
mostrando os últimos dados válidos armazenados no banco.

------------------------------------------------------------------------

# 4. Estrutura de diretórios sugerida

``` text
/
├── index.php
│
├── poltronascore/
│   ├── index.php
│   ├── manifest.json
│   ├── sw.js
│   │
│   ├── css/
│   │   └── poltrona.css
│   │
│   ├── js/
│   │   └── poltrona.js
│   │
│   ├── icons/
│   │   ├── icon-192.png
│   │   ├── icon-512.png
│   │   └── favicon.png
│   │
│   └── assets/
│       └── ...
│
├── api/
│   └── poltronascore/
│       ├── jogos.php
│       ├── jogo.php
│       ├── classificacao.php
│       ├── times.php
│       └── status.php
│
├── config/
│   └── database.php
│
├── lib/
│   ├── scraper.php
│   ├── database.php
│   └── helpers.php
│
├── cron/
│   └── scraper.php
│
└── admin/
    ├── index.php
    ├── scraper.php
    └── ...
```

A estrutura pode ser simplificada caso o projeto inicial seja pequeno,
mas a separação entre scraper, API e frontend deve ser preservada.

------------------------------------------------------------------------

# 5. URL principal

O PoltronaScore deve ficar em:

``` text
https://DOMINIO/poltronascore/
```

Não é necessário usar subdomínio.

O restante do domínio pode continuar sendo outro site/aplicação.

Exemplo:

``` text
https://dominio.com/
https://dominio.com/poltronascore/
```

O PoltronaScore deve ter identidade visual própria mesmo estando no
mesmo domínio.

------------------------------------------------------------------------

# 6. PWA

O PoltronaScore deve ser uma aplicação instalável independentemente do
restante do site.

Manifest:

``` json
{
  "name": "PoltronaScore",
  "short_name": "PoltronaScore",
  "start_url": "/poltronascore/",
  "scope": "/poltronascore/",
  "display": "standalone",
  "theme_color": "#1837E8",
  "background_color": "#1837E8"
}
```

Criar ícones:

-   192x192
-   512x512
-   favicon

O PWA deve abrir diretamente em:

``` text
/poltronascore/
```

quando o usuário tocar no ícone instalado.

O Service Worker deve implementar pelo menos:

-   cache dos assets estáticos;
-   fallback razoável quando a rede estiver indisponível;
-   atualização dos assets sem deixar o usuário preso em versão antiga.

Os dados esportivos devem continuar sendo tratados como dados dinâmicos
e não devem ficar congelados por um cache agressivo.

------------------------------------------------------------------------

# 7. Identidade visual

Nome:

**PoltronaScore**

Conceito:

-   brincadeira/paródia com a ideia de um app de scores;
-   sofá/poltrona como elemento central;
-   aparência de aplicativo esportivo moderno;
-   visual limpo;
-   forte uso de azul;
-   branco para elementos de alto contraste;
-   interface mobile-first.

Logo:

Uma marca com uma poltrona estilizada integrada a um símbolo geométrico
que remeta a score/app esportivo.

O logo deve ser usado no header e o símbolo simplificado deve ser usado
como ícone do PWA.

Não copiar literalmente o logo de nenhuma marca existente. A identidade
deve ser própria do PoltronaScore.

------------------------------------------------------------------------

# 8. Interface

A interface deve ser pensada primeiro para celular.

Estrutura sugerida:

``` text
┌─────────────────────────────┐
│ 🛋️ PoltronaScore            │
│                             │
│ [ Jogos ] [ Classificação ] │
├─────────────────────────────┤
│                             │
│  PRÓXIMOS / AO VIVO         │
│                             │
│  Time A       2              │
│  Time B       1              │
│                             │
│  72'                          │
│                             │
├─────────────────────────────┤
│                             │
│  OUTROS JOGOS               │
│                             │
└─────────────────────────────┘
```

A interface exata deve ser adaptada aos dados reais fornecidos pela
página da comunidade.

Prioridades:

1.  leitura rápida;
2.  bom uso em celular;
3.  navegação simples;
4.  atualização automática;
5.  indicação clara de dados atualizados;
6.  baixo consumo de dados.

------------------------------------------------------------------------

# 9. Atualização automática

O frontend deve consultar a API periodicamente.

Exemplo:

``` javascript
setInterval(carregarDados, 60000);
```

O intervalo deve ser configurável.

Importante:

-   o frontend não deve consultar diretamente a fonte externa;
-   a fonte externa é atualizada pelo scraper;
-   o frontend consulta apenas a API própria.

Também mostrar no frontend algo como:

``` text
Atualizado há 2 min
```

ou:

``` text
Última atualização: 07:35
```

------------------------------------------------------------------------

# 10. Scraper

Criar:

``` text
/cron/scraper.php
```

O scraper será executado pelo Cron.

Exemplo:

``` text
*/5 * * * * php /caminho/cron/scraper.php
```

O intervalo inicial pode ser 5 minutos, mas deve ser facilmente
configurável.

## Fluxo do scraper

1.  iniciar execução;
2.  registrar horário;
3.  acessar a página da comunidade;
4.  validar HTTP status;
5.  extrair os dados;
6.  normalizar os dados;
7.  identificar registros existentes;
8.  fazer INSERT/UPDATE;
9.  registrar sucesso;
10. registrar quantidade de itens;
11. registrar erros;
12. finalizar.

Nunca apagar todos os dados antes de uma atualização.

Se a página externa retornar erro ou dados incompletos, preservar os
dados anteriores.

------------------------------------------------------------------------

# 11. Fonte externa

A fonte é uma **página própria da comunidade**.

Não assumir que a fonte seja uma API pública.

O scraper deve ser implementado especificamente para a estrutura HTML da
página.

Antes de desenvolver o parser definitivo, analisar:

-   HTML;
-   classes CSS;
-   IDs;
-   tabelas;
-   elementos repetitivos;
-   paginação;
-   JavaScript;
-   eventuais endpoints utilizados pela própria página;
-   formato dos dados;
-   identificação única dos registros.

Se a página utilizar JavaScript para montar os dados, verificar primeiro
se existe uma requisição HTTP/JSON que possa ser consumida diretamente.
Se houver, preferir essa abordagem ao scraping visual do HTML.

Se não houver, utilizar parsing de HTML.

Não utilizar browser automation/headless browser sem necessidade.

------------------------------------------------------------------------

# 12. Banco de dados

Criar banco MySQL.

O schema definitivo deve ser ajustado depois que a estrutura real da
página da comunidade for analisada.

Estrutura inicial conceitual:

## matches

``` text
id
external_id
home_team
away_team
start_time
status
home_score
away_score
updated_at
created_at
```

## teams

``` text
id
external_id
name
short_name
logo_url
updated_at
```

## match_events

Se existirem eventos:

``` text
id
match_id
external_id
minute
type
team_id
player
data
created_at
```

## scraper_runs

``` text
id
source
started_at
finished_at
success
items_found
error_message
duration_ms
created_at
```

O campo `external_id` deve ser usado sempre que possível para evitar
duplicação.

------------------------------------------------------------------------

# 13. API

A API deve retornar JSON.

Exemplos:

``` text
GET /api/poltronascore/jogos.php
GET /api/poltronascore/jogo.php?id=123
GET /api/poltronascore/classificacao.php
GET /api/poltronascore/times.php
GET /api/poltronascore/status.php
```

Exemplo de resposta:

``` json
{
  "success": true,
  "updated_at": "2026-08-09T07:35:00-03:00",
  "data": []
}
```

As APIs devem:

-   usar PDO;
-   utilizar prepared statements;
-   validar parâmetros;
-   retornar HTTP status adequado;
-   evitar expor credenciais;
-   evitar SQL diretamente baseado em input do usuário.

------------------------------------------------------------------------

# 14. Status do sistema

Criar endpoint:

``` text
/api/poltronascore/status.php
```

Retornar algo como:

``` json
{
  "success": true,
  "last_scrape": "2026-08-09T07:35:00-03:00",
  "last_scrape_success": true,
  "items_found": 128
}
```

Isso permitirá mostrar no frontend:

``` text
● Dados atualizados
```

ou:

``` text
⚠ Última atualização há 27 min
```

------------------------------------------------------------------------

# 15. Área administrativa

Criar uma área simples para acompanhar o scraper.

Exemplo:

``` text
POLTRONASCORE ADMIN

Última execução
07:35:02

Status
OK

Itens encontrados
128

Tempo
1.82s

Último erro
Nenhum

[Executar scraper agora]
```

A execução manual deve chamar a mesma rotina utilizada pelo Cron.

Não duplicar a lógica.

------------------------------------------------------------------------

# 16. Robustez do scraper

O scraper deve ser tolerante a falhas.

Implementar:

-   timeout;
-   tratamento de HTTP errors;
-   logging;
-   validação de conteúdo;
-   proteção contra HTML inesperado;
-   prevenção de registros duplicados;
-   transação quando fizer sentido;
-   preservação dos dados anteriores em caso de falha.

Exemplo de comportamento:

``` text
Fonte OK
→ atualiza banco

Fonte indisponível
→ não altera dados
→ registra erro

HTML mudou
→ validação falha
→ não sobrescreve dados válidos
→ registra erro
```

Isso é importante porque o site deve continuar funcionando mesmo quando
o scraper tiver problemas.

------------------------------------------------------------------------

# 17. Performance

O frontend deve ser leve.

Evitar:

-   frameworks grandes;
-   bibliotecas desnecessárias;
-   polling excessivo;
-   imagens pesadas;
-   consultas SQL repetitivas.

Usar:

-   índices no MySQL;
-   queries específicas;
-   JSON enxuto;
-   cache quando apropriado;
-   atualização incremental.

------------------------------------------------------------------------

# 18. Segurança

Não colocar credenciais do MySQL no JavaScript.

Configuração do banco deve ficar em arquivo fora do acesso público,
quando possível.

Exemplo:

``` php
$pdo = new PDO(
    'mysql:host=localhost;dbname=DATABASE;charset=utf8mb4',
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
```

Usar prepared statements.

Se existir autenticação administrativa:

-   password_hash/password_verify;
-   sessão segura;
-   CSRF protection;
-   proteção da área `/admin/`.

------------------------------------------------------------------------

# 19. Fases de desenvolvimento

## Fase 1 --- Descoberta

Antes de escrever o scraper definitivo:

1.  analisar a página da comunidade;
2.  identificar exatamente quais dados serão coletados;
3.  identificar estrutura HTML;
4.  verificar se existe endpoint JSON;
5.  definir entidades;
6.  definir schema MySQL.

## Fase 2 --- Backend

Implementar:

-   conexão MySQL;
-   tabelas;
-   scraper;
-   logging;
-   Cron;
-   API.

## Fase 3 --- Frontend

Implementar:

-   layout mobile;
-   dashboard;
-   jogos;
-   detalhes;
-   classificação;
-   estados de loading;
-   estados de erro;
-   última atualização.

## Fase 4 --- PWA

Implementar:

-   manifest;
-   ícones;
-   service worker;
-   instalação;
-   splash/background;
-   cache.

## Fase 5 --- Refinamento

Adicionar:

-   animações discretas;
-   filtros;
-   busca;
-   favoritos, se fizer sentido;
-   histórico;
-   estatísticas;
-   notificações, se forem viáveis.

------------------------------------------------------------------------

# 20. Princípio arquitetural mais importante

O projeto deve manter esta separação:

``` text
SCRAPING
    ↓
BANCO
    ↓
API
    ↓
FRONTEND/PWA
```

Não misturar scraping com apresentação.

O scraper conhece a página externa.

A API conhece o banco.

O frontend conhece a API.

Isso permitirá trocar a fonte externa no futuro sem precisar reescrever
o aplicativo.

------------------------------------------------------------------------

# 21. Resultado esperado

Ao final, o usuário deverá acessar:

``` text
https://dominio.com/poltronascore/
```

e encontrar uma aplicação visualmente independente, responsiva e
instalável como PWA.

O usuário poderá instalar o PoltronaScore no Android/iOS compatível e
terá:

``` text
🛋️ PoltronaScore
```

como aplicativo na tela inicial.

O site principal continuará funcionando normalmente no mesmo domínio.

O sistema de backend será atualizado automaticamente pelo Cron e o
frontend exibirá os dados armazenados no MySQL.

------------------------------------------------------------------------

# 22. Próximo passo obrigatório

Não começar criando um scraper genérico.

Primeiro receber/analisar a **URL da página da comunidade** que será a
fonte dos dados.

A partir dela:

1.  identificar os dados disponíveis;
2.  propor o schema MySQL definitivo;
3.  identificar a melhor técnica de coleta;
4.  implementar o scraper;
5.  criar a API;
6.  montar o frontend PoltronaScore;
7.  configurar o PWA.

O código deve ser compatível com uma hospedagem PHP/MySQL convencional.
