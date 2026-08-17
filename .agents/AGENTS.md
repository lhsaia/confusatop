# Regras do Projeto CONFUSA.top

## Atualização do Histórico de Novidades (updates.json)

Sempre que você realizar alterações de novas funcionalidades, melhorias de layout ou correções solicitadas pelo usuário neste ambiente:
1. Você **deve** atualizar o arquivo `/updates.json` na raiz do projeto.
2. Adicione uma nova entrada no topo do array JSON (mantendo a ordem da mais recente para a mais antiga) com o seguinte formato:
   ```json
   {
     "date": "DD/MM/AAAA",
     "title": "Título Curto e Direto da Melhoria",
     "description": "Uma explicação concisa sobre o que mudou e qual o benefício para o usuário do site."
   }
   ```
3. Certifique-se de que a sintaxe JSON permaneça perfeitamente válida.


## Diretrizes de Design Visual 2.0 (Brand Kit & UI System)

Sempre que criar novas páginas ou atualizar layouts existentes no CONFUSA.top, você **deve** seguir a especificação abaixo para manter consistência visual e estética premium:

### 1. Paleta de Cores e Temas CSS
Use os seguintes tokens e estilos conforme a área do site:
* **Tema Escuro (Exclusivo da Home Hub):**
  - **Fundo do Site:** `#1A1469` (azul clássico do CONFUSA) combinado com um gradiente escuro de leitura `#090d16`.
  - **Cor de Fundo dos Cards:** `rgba(15, 23, 42, 0.75)` (fundo escuro translúcido com glassmorphism real).
  - **Bordas dos Cards:** Borda muito fina e translúcida `1px solid rgba(255, 255, 255, 0.1)`.
* **Tema Claro (Padrão para Ligas, Competições, Clubes e Detalhes do Jogador):**
  - **Fundo do Site:** `#f1f5f9` (cinza-azulado claro) com gradiente suave (`rgba(248, 250, 252, 0.85)` a `rgba(226, 232, 240, 0.95)`).
  - **Cor do Texto:** `#0f172a` (azul escuro/slate) para ótima legibilidade.
  - **Cor de Fundo dos Cards:** `rgba(255, 255, 255, 0.8)` (branco translúcido com desfoque de fundo/backdrop-filter).
  - **Bordas dos Cards:** Borda leve `1px solid rgba(0, 0, 0, 0.08)`.
* **Hover dos Cards:** Ganha brilho ciano (`rgba(56, 189, 248, 0.4)` no escuro ou `rgba(56, 189, 248, 0.2)` no claro) e elevação física (`transform: translateY(-6px)`).
* **Acentos:** Ciano (`#38bdf8` / `#0284c7`), Esmeralda (`#34d399`) e Âmbar (`#fbbf24`).
* **Tipografia:** `Kanit` para títulos principais e `Montserrat` para textos corridos e legibilidade da interface.

### 2. Padrão de Cards e Hubs
* **Imagem de Destaque Superior:** Os blocos de conteúdo ou atalhos devem conter uma imagem ou banner que ocupa toda a metade superior do card (`height: 160px` com `object-fit: cover` e `border-radius: 18px 18px 0 0`).
* **Efeito Hover de Imagem:** A imagem deve dar um zoom suave de `scale(1.06)` ao passar o mouse.
* **Transições:** Sempre use aceleração por hardware (GPU) para suavidade usando transições de `transform` e `opacity` em vez de alterar dimensões físicas (`width` / `height`).

### 3. Cabeçalho (Header) e Rodapé (Footer)
* **Header Dinâmico:** O `#top-bar` deve ser estilizado como uma barra escura translúcida e flutuante (`backdrop-filter: blur(12px)`). O nome de usuário (`#logged-user`) deve ficar sempre centralizado horizontalmente de forma absoluta.
* **Menu Hamburguer Slide-In:** O menu hamburguer deve abrir em um painel lateral que desliza da direita para a esquerda (`right: -320px` para `right: 0`), mantendo o ícone de abrir e o ícone de fechar (X) exatamente no mesmo pixel físico para alternação de cliques. O menu fechado deve ser completamente ocultado com `display: none` para evitar scroll horizontal na página.
* **Footer Sticky:** O `#bottom-bar` não deve flutuar sobre a página. Ele deve ficar sempre encostado no final do fluxo da página (utilizando `margin-top: auto` no flexbox do container pai). Na Home Hub, o botão de voltar deve ficar ocultado.
* **Ações de Formulários (Inline):** Barras flutuantes de rodapé não devem obstruir botões de formulário no mobile. Botões de ação como "Salvar" resultantes de modificações locais no perfil (como edição de atributos) devem ser renderizados **inline**, dentro do próprio card do formulário, integrados ao visual do site, e não flutuantes.

### 4. Boas Práticas e Arquitetura de Código
* **Estilos Separados:** Evite ao máximo colocar tags `<style>` embutidas no meio dos arquivos HTML/PHP. Toda regra visual nova deve ser modularizada e adicionada em arquivos `.css` externos específicos do layout (ex: `css/home_redesign.css`).

### 5. Layouts de Tabelas, Ações e Paginação
* **Ações Inline na Tabela (Modo de Edição):** Ao clicar em editar uma linha de tabela, oculte todos os botões de ação secundários da linha (ex: alterar demografia, ir para seleções, importação/exportação de planilhas). Mantenha visíveis apenas os botões de **Salvar** e **Cancelar** para focar a atenção do usuário no preenchimento dos dados. Restaure a visibilidade de todos os botões ao cancelar ou concluir a edição.
* **Botão de Ação do Cabeçalho:** Botões de criação ou ação primária (como "Criar país", "Criar competição") devem ser alinhados inline no canto direito do título principal utilizando um contêiner flex (`.header-actions-container`) com largura automática (`width: auto`), e nunca ocupar 100% da largura do card.
* **Estilização de Paginação Ativa:** Sempre aplique a estilização de página ativa no item da lista correspondente (`.pagination li.active a`) utilizando a cor de destaque azul (`#0284c7`) e texto branco. Além disso, certifique-se de incluir a classe `.sr-only { display: none !important; }` no CSS específico para ocultar o texto de acessibilidade `(current)` e evitar que ele distorça o tamanho dos botões.

### 6. Responsividade e Alinhamento Absoluto (Mobile First)
* **Prevenção de Rolagens:** Evite criar barras de rolagem horizontal intermediárias em blocos internos do layout (`.propostas-card`, etc.). A rolagem horizontal é proibida em layouts de cards principais; o único scroll permitido no celular é o vertical nativo da página.
* **Tabelas e Botões de Ação:** As colunas de ações em tabelas (Editar/Excluir) devem ter largura pequena fixa (ex: `width: 80px`), usar botões pequenos, quadrados (`width/height: 28px` com bordas leves), e conter a propriedade `white-space: nowrap` para evitar quebras de layout.
* **Alinhamento de Elementos Absolutos (ex: Campinho Tático):** Sempre que utilizar posicionamento absoluto para filhos (como os marcadores de posição no campinho), o contêiner pai **deve** possuir `position: relative !important` e dimensões físicas fixas (largura e altura). Nunca permita que contêineres com filhos absolutos em porcentagem tenham larguras flexíveis (`flex: 1` ou `width: 100%`), pois isso causa o desvio e desalinhamento dos elementos filhos.
* **Componentes Fluidos no Mobile:** Use `@media (max-width: 768px)` para colapsar grids de dados para 1 coluna e garanta que gráficos interativos (como Plotly) possuam largura fluida (`width: 100% !important`) com a propriedade `responsive: true` ativada nas configurações do script.

## Diretrizes de Escopo e Modificação Segura
* **Foco no Pedido:** Você **não deve** fazer qualquer tipo de alteração em trechos de código, textos, traduções ou arquivos que não tenham sido explicitamente solicitados pelo usuário.
* **Preservação de Layouts Estáveis:** Elementos que já foram validados e aprovados pelo usuário não devem sofrer novas modificações ou reestruturações desnecessárias ao implementar novas demandas.
* **Case-Sensitivity em Consultas SQL:** Como o servidor de produção (Linux) diferencia maiúsculas de minúsculas em apelidos (aliases) e nomes de tabelas, garanta que todas as referências SQL usem exatamente a mesma caixa da declaração (ex: se declarou `cd`, use `cd`, nunca `CD`).
* **Fallback de Comandos de Sistema:** Chamadas a comandos de sistema/executáveis via `shell_exec` (como o otimizador `pngquant`) devem sempre possuir tratamento de erro e fallback nativo em PHP (ex: retornar o arquivo sem compressão) para evitar erros fatais se o utilitário estiver ausente ou desativado no servidor.
