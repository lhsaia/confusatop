# Guia Tático e Mecânicas do Motor Hexacolor YMT v2.10+

Este documento reúne todas as descobertas técnicas, fórmulas matemáticas e regras de simulação extraídas diretamente dos arquivos de bytecode e banco de dados do motor de simulação Java (**`HexacolorYMTv2.jar`** e **`matchengine.db3`**), servindo como referência para montagem de elencos, estratégias táticas, convocações de seleções e uso das ferramentas de inteligência artificial via MCP no portal **CONFUSA.top**.

---

## 1. Duelos Matemáticos de Atributos Durante as Jogadas

O motor do Hexacolor calcula as jogadas confrontando diretamente pares e trios ponderados de atributos dos atletas em disputa de bola.

### 1.1. Disputas de Linha (Ataque vs Defesa)
* **Passe / Quebra de Linhas:** A **Visão de Jogo (VSJ)** e a **Movimentação (MOV)** do atacante/meia são confrontadas pela **Marcação (MAR)** do defensor.
* **Drible / Condução de Bola:** O **Controle de Bola (CNB)** e a **Técnica (TEC)** do atacante são confrontados pelo **Desarme (DES)** do defensor.
* **Transição em Velocidade:** A **Velocidade (VEL)** e a **Movimentação (MOV)** definem quem chega primeiro à bola em infiltrações e contra-ataques.
* **Disputa Aérea de Área:** O **Cabeceamento (CAB)** e a **Força (FOR)** dos atacantes disputam contra o **Cabeceamento (CAB)** e a **Força (FOR)** dos zagueiros.

### 1.2. O Duelo com o Goleiro (Finalização vs Goleiro)
Na tabela `finalizacoes` da engine, cada tipo de finalização aciona uma fórmula matemática específica:
* **Chute Direto:** Confronta **Finalização (FIN)** do atacante contra **Reflexos (REF)** do goleiro.
* **Infiltração Cara-a-Cara / Rebote de Área:** Confronta **Faro de Gol (FRG)** (80%) + **Finalização/Técnica** (20%) contra **Saídas (SAI)** (80%) + **Reflexos/Segurança** (20%) do goleiro.
* **Cabeceio na Área:** Confronta **Cabeceamento (CAB)** do atacante contra **Jogo Aéreo (JOA)** do goleiro (ou 80% Reflexos + 20% Jogo Aéreo).
* **Segurança do Goleiro:** Reduz a chance de rebote vivo para os atacantes adversários.
* **Defesa de Pênaltis:** Atributo decisivo para cobranças de penalidades máximas em tempo normal e disputas de pênaltis.

---

## 2. Bônus Matemáticos de Criação e Defesa por Posição

Na tabela `bonusposcriacao` da engine de simulação, cada posição em campo adiciona coeficientes físicos multiplicadores nas fases ofensivas e defensivas:

| Posição | Invasão (Avanço) | Finalização | Cabeceio Direita (CabD) | Cabeceio Esquerda (CabE) | Defesa de Invasão | Defesa de Finalização | Defesa Aérea D (DefCabD) | Defesa Aérea E (DefCabE) |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **G (Goleiro)** | - | - | - | - | - | - | - | - |
| **Z (Zagueiro Central)** | 0.10 | 0.05 | 0.10 | 0.10 | **0.40** | **0.35** | **0.45** | **0.45** |
| **V (Volante)** | 0.15 | 0.25 | 0.10 | 0.10 | **0.40** | **0.50** *(Maior do jogo!)* | 0.25 | 0.25 |
| **LD (Lateral Direito)** | 0.15 | 0.15 | 0.45 | 0.05 | 0.30 | 0.25 | **0.60** | 0.05 |
| **LE (Lateral Esquerdo)** | 0.15 | 0.15 | 0.05 | 0.45 | 0.30 | 0.25 | 0.05 | **0.60** |
| **AD (Ala Direito)** | 0.20 | 0.20 | 0.55 | 0.05 | 0.25 | 0.20 | 0.50 | 0.05 |
| **AE (Ala Esquerdo)** | 0.20 | 0.20 | 0.05 | 0.55 | 0.25 | 0.20 | 0.05 | 0.50 |
| **MC (Meia Central)** | 0.30 | 0.30 | 0.25 | 0.25 | 0.20 | 0.20 | 0.25 | 0.25 |
| **MA (Meia Ofensivo/Armador)** | 0.35 | **0.55** *(Maior arremate!)* | 0.20 | 0.20 | 0.15 | 0.25 | 0.15 | 0.15 |
| **MD / ME (Meias Abertos)** | 0.25 | 0.25 | 0.55 | 0.55 | 0.20 | 0.15 | 0.50 | 0.50 |
| **PD / PE (Pontas)** | 0.35 | 0.25 | **0.70** *(Máximo cruzamento!)* | **0.70** | 0.15 | 0.20 | 0.25 | 0.25 |
| **Am (Atacante Movimentação)** | **0.65** *(Maior infiltração!)* | 0.45 | 0.20 | 0.20 | 0.15 | 0.15 | 0.10 | 0.10 |
| **Aa (Centroavante de Área)** | 0.30 | **0.40** | **0.50** | **0.50** | 0.10 | 0.10 | 0.05 | 0.05 |

### Conclusões Táticas Imediatas da Tabela:
1. **Volantes (V) são fundamentais:** São a posição que mais bloqueia chutes a gol (`DefFinalização: 0.50`). Jogar sem volantes expõe o goleiro a bombardeios diretos.
2. **Pontas (PD/PE) são as melhores fontes de gol aéreo:** Possuem índice de cruzamento `0.70`, ideais para municiar centroavantes de área com alto cabeceamento.
3. **Atacantes Recuados (Am) destroem linhas altas:** O índice de invasão de `0.65` supera qualquer outra posição, criando jogadas cara-a-cara quando apoiados por meias com alta Visão de Jogo.
4. **Laterais (LD/LE) barram jogadas de linha de fundo:** O valor `0.60` de DefCab anula os pontas e alas adversários se o lateral tiver boa Marcação e Velocidade.

---

## 3. O Treinador no Simulador

Os atributos do treinador interferem diretamente na postura do time e nos multiplicadores da engine:

### 3.1. Mentalidades do Técnico (`mentalidadetecnico`)
* **1 - Retranca / Muito Defensiva:** -0.10 no ataque / +0.10 em todas as defesas. (Ideal para 5-3-2 ou 5-4-1).
* **2 - Defensiva:** -0.05 no ataque / +0.05 nas defesas.
* **3 - Balanceada / Normal:** 0.0 (Neutro).
* **4 - Ofensiva:** +0.05 no ataque / -0.05 nas defesas.
* **5 - Ataque Total / Muito Ofensiva:** +0.10 no ataque / -0.10 nas defesas.

### 3.2. Estilos do Técnico (`estilotecnico`)
1. **Explorar Contra-Ataques:** +0.20 em Invasão, -0.10 em Finalização, +0.05 em Defesa de Invasão.
   * *Perfil ideal de atletas:* Atacantes rápidos (Am), pontas velozes (PD/PE), meias com passe longo/visão e zagueiros rápidos.
2. **Cadenciar o Jogo / Posse de Bola:** -0.05 em Invasão, +0.15 em Finalização.
   * *Perfil ideal de atletas:* Linha de meio-campo densa com MCs e MAs técnicos (alto Controle de Bola, Técnica, Visão de Jogo e Finalização de longe).
3. **Neutro / Equilibrado:** Bônus zerados (0.0). Formações equilibradas como 4-4-2 e 4-2-3-1.
4. **Atacar pelas Laterais:** +0.10 em bolas aéreas (CabD e CabE).
   * *Perfil ideal de atletas:* Laterais/Alas (LD/LE/AD/AE) e Pontas (PD/PE) com alto atributo de **Cruzamento**, cruzando para Centroavante (Aa) com alto **Cabeceamento** e **Força**.
5. **Impôr Ritmo Ofensivo / Pressão Alta:** +0.10 em Finalização, +0.05 em bolas aéreas, -0.10 em Defesa de Invasão.
   * *Perfil ideal de atletas:* Ataques com muitos homens (ex: 4-3-3 com pontas agressivos e centroavante), exigindo volantes de alto desarme para cobrir as costas da equipe.

---

## 4. Recálculo Proporcional de Atributos com o Nível (`ControladorAtributos`)

Ao inspecionar o bytecode das classes `editor.ControladorAtributos` e `partida.ControladorDisputas` no arquivo `HexacolorYMTv2.jar`, desvendou-se a fórmula matemática exata que governa a relação entre **Nível** e **Atributos**:

### 4.1. Os Atributos Cadastrados são Proporcionais (Pesos de Perfil)
Os números que constam no cadastro do atleta ou nos sliders do editor (valores de 0.5 a 10.0) não são atributos absolutos fixos na partida, mas sim **pesos de proporção** que definem o estilo do jogador.

Ao carregar o atleta para a simulação, a engine calcula a **Soma Desejada de Atributos** baseada estritamente no seu Nível Geral:
* **Jogadores de Linha:**
  $$\text{Soma Desejada} = \frac{\text{Nível} \times 65.0}{100.0} = \text{Nível} \times 0.65$$
  *(Ex: Um atleta de Nível 80 terá uma soma total de atributos recalculada para exatamente 52.0 pontos).*
* **Goleiros:**
  $$\text{Soma Desejada} = \frac{\text{Nível} \times 50.0}{100.0} = \text{Nível} \times 0.50$$
  *(Ex: Um goleiro de Nível 80 terá uma soma total de atributos recalculada para exatamente 40.0 pontos).*

### 4.2. A Fórmula de Recálculo do Motor
Para cada atributo $i$ informado:
$$\text{Atributo Recalculado}_i = \text{Atributo Original}_i \times \frac{\text{Soma Desejada}}{\sum \text{Atributos Originais}}$$

Após essa multiplicação proporcional:
1. Aplica-se o limite máximo de **7.0** para linha (ou **10.0** para goleiro) e piso de **0.5**.
2. Qualquer excesso gerado pelos limites é redistribuído equilibradamente entre os demais atributos do atleta.

### 4.3. Como a Engine Resolve os Duelos (`partida.ControladorDisputas`)
Nos métodos `gerarPontoCorteExecucaoJogadaBalizamentoInternacional2022` e `gerarPontoCorteFinalizacaoBalizamentoInternacional2022`, o motor confronta diretamente a soma do **Nível + Atributo Recalculado**:
$$\Delta = (\text{Nível}_{\text{atacante}} + \text{Atributo}_{\text{atacante}}) - (\text{Nível}_{\text{defensor}} + \text{Atributo}_{\text{defensor}})$$

Isso comprova matematicamente:
1. **O Nível Geral é o balizador dominante:** O atributo é uma fração que soma sobre o Nível base. Um atleta Nível 85 com atributo recalculado 5.5 totaliza `90.5` no duelo, enquanto um atleta Nível 72 com atributo 6.5 totaliza apenas `78.5`.
2. **Atributo isolado nunca salva nível baixo:** Nenhum atleta fraco (nível baixo) supera um atleta de nível alto em consistência de duelos, pois a base inteira da fórmula é atrelada ao Nível.

---

## 5. Regras Críticas e Penalidades

### ⚠️ Hierarquia da Engine: Nível Geral + Atributos Específicos
No motor Hexacolor YMT v2.10, o **Nível Geral (Nível Real = Nível + Modificador)** e os **Atributos Específicos** influenciam o jogador conjuntamente:
1. **O Nível Geral é o balizador primário:** Ele determina a consistência global, tempo de reação, inteligência posicional e tomada de decisão durante todos os 90 minutos de jogo. Um atleta de Nível 85 supera expressivamente um atleta de Nível 74 em regularidade geral.
2. **Os Atributos Específicos definem o perfil e os duelos setorizados:** Cruzamentos, Marcação, Desarme, Cabeceamento, Finalização, etc., resolvem as jogadas daquela posição específica.
3. **Regra de Seleção:** Os titulares e convocados principais devem ser sempre os atletas de **maior Nível Geral disponível no setor**. Os atributos individuais e o estilo do técnico servem para desempatar e escolher os melhores atletas entre patamares similares de nível (ex: entre um lateral nível 84 e outro 83). Nunca preterir um jogador de patamar muito mais alto apenas por um atributo isolado de um atleta fraco.

### ⚠️ Tolerância Zero à Improvisação de Posição
O Hexacolor YMT v2.10 armazena um vetor binário de 15 bits (`StringPosicoes`) para cada atleta:
`[G, LD, LE, Z, AD, AE, V, MD, ME, MC, PD, PE, MA, Am, Aa]`

* Se um atleta for escalado em uma posição onde seu bit for `0`, **o simulador aplica uma redução severa em seus atributos durante a partida**.
* Tanto na montagem de clubes quanto em convocações de seleções, **jamais improvise atletas fora de suas posições naturais**.

### ⚠️ Status de Aposentadoria
* Atletas marcados com `disponibilidade = -1` (ou string `'Aposentado'`), `-2` (Expatriados) ou `-3` (Falecidos) estão fora de atividade e **nunca** devem ser convocados ou escalados.
* A convocação e análise de elencos no MCP filtra rigorosamente esses estados.

### ⚠️ Critérios de Desempate e Batedores
* **Capitão:** Jogadores com mentalidade *Líder* e boa determinação/idade reduzem oscilações anímicas do time.
* **Pênaltis:** Priorizar atletas com mentalidade *Sangue Frio* ou *Raçudo*, bom nível e atributo de cobrança.

---

## 6. Ferramentas Integradas no MCP CONFUSA

O arquivo [`mcp_server.php`](file:///d:/OneDrive/Documents/GitHub/confusatop/mcp_server.php) disponibiliza as seguintes ferramentas que operam sob essas diretrizes:

1. **`analisar_taticas`**:
   Lê o elenco completo do clube, identifica o treinador contratado, extrai todos os atributos e cruza com a tabela de bônus do motor, devolvendo à IA as regras exatas para recomendar o esquema ideal e os 11 titulares.
2. **`sugerir_convocacao_selecao`**:
   Varre todos os atletas elegíveis de um país para uma seleção (Principal, Olímpica, Sub-20 ou Sub-18), cruza com o Estilo e Mentalidade do treinador selecionado e entrega os melhores atletas ranqueados por setor para a formulação de:
   * A lista final de convocados (sem buracos posicionais).
   * A escalação dos 11 titulares sem nenhuma improvisação.
   * A estratégia de jogo ideal para vencer os duelos matemáticos do motor.
