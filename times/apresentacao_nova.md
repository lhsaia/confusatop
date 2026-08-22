# Redesign da Página de Apresentação de Times — CONFUSA

## Objetivo

Evoluir a página atual de apresentação de times para uma experiência com mais cara de **revista esportiva / Placar**, mantendo a estrutura e funcionalidades existentes.

A página já possui:

- Cards individuais dos jogadores;
- Escalação posicionada em um campo de futebol;
- Titulares e reservas;
- Informações do clube;
- História do clube;
- Escudo e imagens;
- Técnico;
- Campos editáveis;
- Botões de edição e salvamento.

**Não é necessário reinventar essas funcionalidades.** O foco deve ser visual, editorial e responsivo.

---

# 1. Direção visual

A página deve deixar de parecer uma tela administrativa/documental e assumir uma estética de:

> **Revista esportiva + ficha oficial de clube + Placar**

A informação já existente deve ganhar mais hierarquia visual.

### Características desejadas

- Tipografia esportiva e editorial;
- Títulos grandes e fortes;
- Maior contraste entre títulos, subtítulos e informações secundárias;
- Uso mais impactante das imagens existentes;
- Cards de jogadores mais sofisticados;
- Melhor composição espacial;
- Elementos estatísticos apresentados como destaques;
- Separadores e blocos editoriais;
- Aparência de publicação esportiva;
- Manter identidade visual do próprio time.

---

# 2. Cabeçalho do time

O topo deve funcionar como a **capa da apresentação do clube**.

Exemplo conceitual:

```text
┌──────────────────────────────────────────────┐
│                                              │
│                 [ESCUDO]                     │
│                                              │
│               RUM PIRATA                     │
│          Gasparilla · Atlântida              │
│                                              │
│       Liga Maior de Orion                    │
│                                              │
│   7 TÍTULOS    •    1908    •    50.000      │
│                                              │
└──────────────────────────────────────────────┘
```

O cabeçalho pode utilizar:

- Escudo;
- Nome;
- Cidade/região;
- Liga;
- Ano de fundação;
- Títulos;
- Estádio/capacidade;
- Outras informações relevantes já existentes.

---

# 3. História do clube

A história deve ter aparência de **matéria esportiva**, e não de campo textual administrativo.

Exemplo:

## MAL ACOSTUMADOS A PERDER

*Jejum que já dura 5 anos incomoda, e Rum volta a ficar sem títulos.*

O título da história deve ser visualmente dominante.

Possibilidades:

- Título grande;
- Subtítulo/dek;
- Texto em coluna;
- Imagem do clube ou estádio como elemento editorial;
- Destaques ou citações quando houver conteúdo adequado.

---

# 4. Escalação

A estrutura atual deve ser mantida:

**A escalação já está representada em um campo de futebol e isso é uma característica importante da página.**

O objetivo é apenas melhorar a apresentação visual.

### Melhorias possíveis

- Campo visualmente mais sofisticado;
- Melhor integração dos cards dos jogadores ao gramado;
- Nome mais legível;
- Número/camisa quando disponível;
- Posição;
- Indicador visual do capitão;
- Melhor tratamento das fotos;
- Hierarquia diferente entre nome e informações secundárias.

O campo deve continuar sendo o elemento central da apresentação esportiva.

---

# 5. Cards dos jogadores

Os cards existentes devem ser preservados, mas podem ganhar acabamento mais editorial.

Possíveis melhorias:

- Foto maior;
- Nome em destaque;
- Posição claramente identificada;
- Número da camisa;
- Ano de nascimento;
- Tempo no clube;
- Pequenos indicadores estatísticos;
- Efeito visual de hover no desktop;
- Melhor tratamento de bordas, sombras e fundo.

A ideia é que cada jogador pareça um **card de elenco**, e não uma ficha de cadastro.

---

# 6. Banco de reservas

Manter a estrutura existente, mas criar uma seção visualmente distinta:

## BANCO

Os cards podem ser menores que os titulares, criando uma hierarquia natural.

---

# 7. Técnico

O técnico pode receber uma apresentação própria:

```text
        COMISSÃO TÉCNICA

       [ FOTO DO TÉCNICO ]

          JOEL VIEIRA
             Técnico
```

Se houver informações adicionais disponíveis, elas podem ser apresentadas como metadados.

---

# 8. Responsividade / Mobile

A página deve funcionar nativamente em:

- Desktop;
- Tablet;
- Smartphone.

Não basta simplesmente reduzir a largura da página.

A estrutura deve sofrer **reflow** conforme a largura disponível.

## Desktop

Priorizar:

- Composição horizontal;
- Campo em tamanho grande;
- Cards em múltiplas colunas;
- História em layout editorial;
- Maior aproveitamento de espaço.

## Tablet

Reduzir:

- Número de colunas;
- Espaçamentos;
- Tamanho de elementos secundários.

## Mobile

Priorizar:

- Uma coluna;
- Cabeçalho compacto;
- Informações do clube em grid;
- História em largura total;
- Campo ocupando praticamente toda a largura disponível;
- Cards em 2 colunas quando possível;
- Banco em cards menores;
- Técnico abaixo do elenco.

Exemplo:

```text
┌─────────────────────┐
│       [ESCUDO]      │
│                     │
│      RUM PIRATA     │
│   Gasparilla        │
│                     │
│  7 títulos · 1908   │
└─────────────────────┘

       HISTÓRIA

MAL ACOSTUMADOS A
PERDER

Jejum que já dura...


       ESCALAÇÃO

┌─────────────────────┐
│                     │
│       JOGADOR       │
│                     │
│   JOGADOR   JOGADOR │
│                     │
│ JOGADOR JOGADOR ... │
│                     │
│       GOLEIRO       │
│                     │
└─────────────────────┘

         BANCO

[card] [card]

[card] [card]

       TÉCNICO
```

---

# 9. CSS responsivo

Adicionar o viewport:

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

Evitar larguras fixas como:

```css
width: 1200px;
```

Preferir:

```css
width: min(1200px, calc(100% - 32px));
```

E utilizar media queries:

```css
@media (max-width: 768px) {
    /* layout mobile */
}
```

A recomendação é criar um CSS específico para a apresentação:

```text
team_presentation.css
```

com três níveis principais:

```text
Desktop
   ↓
Tablet
   ↓
Mobile
```

---

# 10. Separar apresentação de edição

A funcionalidade atual de edição e salvamento deve ser mantida.

Entretanto, visualmente, a página deve priorizar o **modo apresentação**.

A edição pode continuar sendo ativada pelo mecanismo existente.

O objetivo é que, no estado normal, o usuário veja uma página que pareça pronta para publicação.

---

# 11. Modo impressão / screenshot

Como a página atualmente é utilizada para tirar screenshot, o layout deve ter também uma preocupação específica com captura.

Criar regras:

```css
@media print {
    ...
}
```

E, se fizer sentido posteriormente, considerar um modo específico:

**"Gerar apresentação"**

com proporção otimizada para compartilhamento, por exemplo:

- 16:9;
- A4;
- formato vertical para redes sociais.

Isso pode ser uma evolução posterior e não precisa fazer parte da primeira implementação.

---

# 12. Estrutura técnica recomendada

Não é necessário alterar a arquitetura PHP/MySQL.

Manter:

```text
team_presentation.php
        │
        ├── dados do time
        ├── dados dos jogadores
        ├── escalação
        ├── história
        └── edição/salvamento
```

E concentrar a evolução inicial em:

```text
team_presentation.css
```

Possivelmente com pequenos ajustes no HTML apenas quando necessários para melhorar a composição responsiva.

---

# 13. Prioridade de implementação

### Fase 1 — Mobile

1. Corrigir viewport;
2. Remover dependências de larguras fixas;
3. Adaptar cabeçalho;
4. Adaptar informações do clube;
5. Adaptar campo;
6. Adaptar cards dos jogadores;
7. Adaptar banco;
8. Adaptar história;
9. Testar em telas de aproximadamente 360–430 px.

### Fase 2 — Visual editorial

1. Redesenhar cabeçalho;
2. Melhorar tipografia;
3. Dar maior destaque à história;
4. Refinar cards;
5. Refinar campo;
6. Melhorar separadores e blocos;
7. Criar hierarquia visual entre titular, reserva e comissão técnica.

### Fase 3 — Screenshot / publicação

1. CSS de impressão;
2. Ajustes para captura;
3. Eventual modo poster;
4. Eventual exportação para imagem/PDF.

---

# Resultado esperado

A página deve continuar sendo **a mesma ferramenta**, com os mesmos dados e funcionalidades, mas passar de:

> **"tela para montar um time e tirar screenshot"**

para:

> **"uma página oficial de apresentação de um clube de futebol, com estética de revista esportiva."**

O mobile deve ser tratado como uma segunda composição responsiva da mesma página, e não simplesmente como uma versão desktop encolhida.