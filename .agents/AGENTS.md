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

### 1. Paleta de Cores e Tokens CSS
Use as seguintes variáveis CSS definidas no tema global para toda e qualquer interface:
* **Fundo do Site:** `#1A1469` (azul clássico do CONFUSA) combinado com um gradiente escuro de leitura `#090d16`.
* **Cor de Fundo dos Cards:** `rgba(15, 23, 42, 0.75)` (fundo escuro translúcido com glassmorphism real).
* **Bordas dos Cards:** Borda muito fina e translúcida `1px solid rgba(255, 255, 255, 0.1)`.
* **Hover dos Cards:** Ganha brilho ciano `rgba(56, 189, 248, 0.4)` e elevação física (`transform: translateY(-6px)`).
* **Acentos:** Ciano (`#38bdf8`), Esmeralda (`#34d399`) e Âmbar (`#fbbf24`).
* **Tipografia:** `Kanit` para títulos principais e `Montserrat` para textos corridos e legibilidade da interface.

### 2. Padrão de Cards e Hubs
* **Imagem de Destaque Superior:** Os blocos de conteúdo ou atalhos devem conter uma imagem ou banner que ocupa toda a metade superior do card (`height: 160px` com `object-fit: cover` e `border-radius: 18px 18px 0 0`).
* **Efeito Hover de Imagem:** A imagem deve dar um zoom suave de `scale(1.06)` ao passar o mouse.
* **Transições:** Sempre use aceleração por hardware (GPU) para suavidade usando transições de `transform` e `opacity` em vez de alterar dimensões físicas (`width` / `height`).

### 3. Cabeçalho (Header) e Rodapé (Footer)
* **Header Dinâmico:** O `#top-bar` deve ser estilizado como uma barra escura translúcida e flutuante (`backdrop-filter: blur(12px)`). O nome de usuário (`#logged-user`) deve ficar sempre centralizado horizontalmente de forma absoluta.
* **Menu Hamburguer Slide-In:** O menu hamburguer deve abrir em um painel lateral que desliza da direita para a esquerda (`right: -320px` para `right: 0`), mantendo o ícone de abrir e o ícone de fechar (X) exatamente no mesmo pixel físico para alternação de cliques. O menu fechado deve ser completamente ocultado com `display: none` para evitar scroll horizontal na página.
* **Footer Sticky:** O `#bottom-bar` não deve flutuar sobre a página. Ele deve ficar sempre encostado no final do fluxo da página (utilizando `margin-top: auto` no flexbox do container pai). Na Home Hub, o botão de voltar deve ficar ocultado.

### 4. Boas Práticas e Arquitetura de Código
* **Estilos Separados:** Evite ao máximo colocar tags `<style>` embutidas no meio dos arquivos HTML/PHP. Toda regra visual nova deve ser modularizada e adicionada em arquivos `.css` externos específicos do layout (ex: `css/home_redesign.css`).

## Diretrizes de Escopo e Modificação Segura
* **Foco no Pedido:** Você **não deve** fazer qualquer tipo de alteração em trechos de código, textos, traduções ou arquivos que não tenham sido explicitamente solicitados pelo usuário.
* **Preservação de Layouts Estáveis:** Elementos que já foram validados e aprovados pelo usuário não devem sofrer novas modificações ou reestruturações desnecessárias ao implementar novas demandas.
