# Documentação do Motor de Simulação (HexacolorYMTv2.jar)

Este documento reúne todas as descobertas técnicas e regras de funcionamento do motor de simulação Java (`HexacolorYMTv2.jar`) mapeadas durante as análises de bytecode e depuração de fluxo.

---

## 1. Execução do Engine CLI
O motor de simulação roda em modo headless no servidor e é disparado via:
```bash
java -Djava.awt.headless=true -jar HexacolorYMTv2.jar -m <caminho_agenda_json>
```
* **Nota:** O parâmetro `-Djava.awt.headless=true` é obrigatório em servidores Linux sem interface gráfica instalada, evitando falhas de inicialização do subsistema AWT.

---

## 2. Estrutura do JSON de Configuração (`agenda.json` / `json.txt`)

O arquivo fornecido via parâmetro `-m` deve possuir a seguinte estrutura de chaves:

### Chaves Raiz
* `calendarName` (String): Nome da competição (ex: `"2026 - Copa do Mundo"`). Usado para criar a pasta de destino das súmulas.
* `matchdayIndex` (int): Índice da rodada (ex: `1`). Define a subpasta de destino das partidas (ex: `"1º Rodada"`).
* `color1`, `color2`, `color3` (int): Cores primária, secundária e neutra usadas nas exportações visuais.
* `randomInjuriesAmount` (int): Quantidade de lesões aleatórias a serem simuladas por partida.
* `randomInjuriesMaxDuration` (int): Duração máxima (em dias) das lesões geradas de forma aleatória.
* `randomConditionMinimum` (int): Condição física mínima randômica aplicada aos atletas.
* `matches` (JSONArray): Array de objetos contendo as partidas a serem simuladas.

### Chaves do Objeto da Partida (`matches`)
Cada item do array `matches` possui as seguintes chaves mapeadas:
* `databasePath` (String): String de conexão JDBC para o banco SQLite temporário (ex: `"jdbc:sqlite:data/database.db3"`).
* `id` (int): ID da partida no banco central.
* `date` (long): Timestamp UNIX em milissegundos da partida.
* `idTeam1` (int) / `idTeam2` (int): IDs dos clubes mandante e visitante para busca na tabela `clube` do SQLite.
* `neutralGround` (boolean): Define se a partida é jogada em campo neutro.
* `idChosenGround` (int): ID do estádio de destino para busca na tabela `estadio` do SQLite.
* `outTeam1` (JSONArray) / `outTeam2` (JSONArray): Listas de IDs de jogadores suspensos, lesionados ou indisponíveis para a partida (não serão escalados).
* `kitTeam1` (int) / `kitTeam2` (int): IDs de uniforme de mandante/visitante.
* `knockoutTiebraker` (int): Regra de desempate para fases de mata-mata/finais:
  * `0` (ou ausente): Empates são mantidos (fase de grupos).
  * `1`: Prorrogação (Extra Time) e Pênaltis.
  * `2`: Apenas cobranças de Pênaltis (sem prorrogação).
* `knockoutAwayGoals` (boolean): Se `true`, ativa a regra de gols fora de casa no critério de desempate.
* `knockoutHomeMatch` (JSONObject): Dados do jogo de ida para consolidação em confrontos de duas pernas.
* `genericGoalkeepersThreshold` / `genericPlayersThreshold` (int): Notas/limiares de criação de jogadores genéricos automáticos caso os elencos tenham menos de 11 atletas disponíveis.

---

## 3. Lógica Interna de Árbitros (Trio de Arbitragem)

Uma particularidade do motor Java é que **ele não consome nenhum ID de árbitro enviado através do JSON da partida**. Em vez disso:

1. O JAR carrega todos os árbitros diretamente da tabela `trioarbitragem` no SQLite via `DaoTrioArbitragem.buscarTodos()`.
2. A atribuição à partida é puramente posicional no array de jogos da agenda:
   * **Caso `trios.size() >= matches.length`:** O JAR atribui o árbitro baseado no índice sequencial da partida na lista.
   * **Caso `trios.size() < matches.length`:** O JAR sorteia de forma randômica (`Random.nextInt`) sem reposição um trio a partir da lista total para cada partida.
* **Impacto:** Para forçar o motor a usar um árbitro específico selecionado no painel web, a tabela temporária `trioarbitragem` no SQLite de simulação deve conter **somente** o árbitro selecionado para aquela partida durante a execução do JAR.

---

## 4. Retorno e Súmulas
Após processar, o simulador:
1. Grava as estatísticas da partida atualizadas no SQLite temporário (tabelas de elenco, jogador, estatísticas de clubes, etc.).
2. Exporta o resumo e dados detalhados em arquivo no formato proprietário `.hyj`/`.hyl` em:
   `./Partidas/<calendarName>/<matchdayIndex>º Rodada/<clubeA>x<clubeB> - <data>.hyj`
