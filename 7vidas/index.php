<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "7vidas - Desafio de Sobrevivência";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = '../7vidas/style';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// Detect logged in user name
$is_logged_in = false;
$user_name_display = "";
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && isset($_SESSION['nomereal'])) {
    $is_logged_in = true;
    $user_name_display = $_SESSION['nomereal'];
}
?>

<div class="game-container" style="position: relative;">
    <!-- Help Button -->
    <button id="btn-help" class="help-btn" onclick="openHelpModal()" title="Como Jogar">❓</button>
    <div class="game-header">
        <img src="/images/7vidas/index.png" alt="7vidas" class="game-logo-header">
    </div>

    <?php if (!$is_logged_in): ?>
        <div class="alert-not-logged">
            <strong>Atenção:</strong> Você não está logado. Seus recordes não serão salvos no ranking global de 7vidas.
        </div>
    <?php endif; ?>

    <!-- Setup Panel -->
    <div id="setup-panel" class="setup-panel">
        <div class="control-group">
            <span class="control-label">Treinador</span>
            <input type="text" id="team-name-input" class="game-input" value="<?php echo htmlspecialchars($is_logged_in ? $user_name_display : 'Convidado'); ?>" placeholder="Seu Nome" readonly>
        </div>
        <div class="control-group">
            <span class="control-label">Esquema Tático</span>
            <select id="formation-select" class="game-select">
                <?php
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                } catch (Exception $e) {
                    $db = null;
                }
                if (!$db) {
                    try {
                        $db = new PDO("mysql:host=127.0.0.1:3307;dbname=confusa_trn;charset=utf8mb4", "root", "", [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                    } catch (Exception $e) {
                        $db = null;
                    }
                }
                if ($db) {
                    $stmt = $db->query("SELECT nome FROM formacoes ORDER BY nome ASC");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($row['nome'] == '4-3-3 clássico') ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($row['nome']) . '" ' . $selected . '>' . htmlspecialchars($row['nome']) . '</option>';
                    }
                } else {
                    echo '<option value="4-3-3 clássico" selected>4-3-3 clássico</option>';
                    echo '<option value="4-4-2 clássico">4-4-2 clássico</option>';
                    echo '<option value="3-5-2">3-5-2</option>';
                    echo '<option value="5-3-2">5-3-2</option>';
                }
                ?>
            </select>
        </div>
        <div class="control-group">
            <span class="control-label">Modo de Jogo</span>
            <select id="mode-select" class="game-select">
                <option value="classico">Clássico (Ver atributos)</option>
                <option value="almanaque">De Almanaque (Esconder atributos)</option>
            </select>
        </div>
        <button class="btn-start" onclick="startDrafting()">Começar Desafio</button>
    </div>

    <!-- Main Game Layout Grid -->
    <div class="game-grid">
        <!-- Left: Interactive Field -->
        <div id="pitch-container" class="pitch-container" style="display: none;">
            <div class="pitch-center-line"></div>
            <div class="pitch-center-circle"></div>
            <div class="pitch-penalty-area-top"></div>
            <div class="pitch-penalty-area-bottom"></div>
            <!-- Slots will be dynamically inserted here -->
        </div>

        <!-- Right: Drafting panel and Tournament brackets -->
        <div class="sidebar-panel">
            <!-- Tactical Scheme Modifier during gameplay -->
            <div id="mid-game-tactics" style="display: none; background: var(--bg-card); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 12px; gap: 8px; align-items: center; justify-content: space-between; font-family: 'Outfit', sans-serif;">
                <span style="font-weight: bold; font-size: 0.9rem; color: var(--warning);">📋 Alterar Esquema:</span>
                <select id="formation-select-mid" class="game-select" style="width: auto; padding: 4px 8px; font-size: 0.85rem; margin: 0;">
                    <?php
                    // Reuse the database connection logic to populate options
                    if ($db) {
                        $stmt = $db->query("SELECT nome FROM formacoes ORDER BY nome ASC");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($row['nome'] == '4-3-3 clássico') ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($row['nome']) . '" ' . $selected . '>' . htmlspecialchars($row['nome']) . '</option>';
                        }
                    } else {
                        echo '<option value="4-3-3 clássico" selected>4-3-3 clássico</option>';
                        echo '<option value="4-4-2 clássico">4-4-2 clássico</option>';
                        echo '<option value="3-5-2">3-5-2</option>';
                        echo '<option value="5-3-2">5-3-2</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Reset / Restart Button -->
            <button id="reset-btn" class="btn-action" style="display: none; background: var(--danger); color: white; align-self: flex-start; margin-bottom: 5px;" onclick="resetGame()">Reiniciar Jogo</button>

            <!-- Drafting panel -->
            <div id="drafting-controls" class="roulette-container" style="display: none;">
                <h3 style="margin-top: 0; margin-bottom: 15px;">Fase de Draft</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Gire a roleta para sortear um clube. Escolha um jogador e o coloque no campo. Se não gostar, pode pular (máximo 3 vezes!).</p>
                
                <div class="roulette-buttons" style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
                    <button id="btn-roll-club" class="btn-roll" onclick="rollClub()" style="width: 100%;">Girar Roleta</button>
                    <div style="display: flex; gap: 8px; width: 100%;">
                        <button id="btn-skip-club" class="btn-skip" onclick="skipClub()" style="flex: 1; font-size: 0.8rem; padding: 10px 5px;" disabled>Pular Geral (3)</button>
                        <button id="btn-skip-league" class="btn-skip" onclick="skipLeague()" style="flex: 1; font-size: 0.8rem; padding: 10px 5px; background: var(--warning); border-color: var(--warning); color: #000;" disabled>Mesma Liga (3)</button>
                    </div>
                </div>

                <!-- Rolled Club Card -->
                <div class="rolled-team-card">
                    <div class="team-header-info">
                        <img id="rolled-badge" class="team-badge" src="/images/escudos/0.png" alt="Escudo">
                        <div class="team-meta">
                            <div id="rolled-name" class="team-name">-</div>
                            <div id="rolled-country" class="team-country">-</div>
                        </div>
                    </div>
                    
                    <h4 style="text-align: left; margin: 10px 0 5px 0; font-size: 0.85rem; color: var(--text-muted);">SELECIONE UM JOGADOR:</h4>
                    <div id="rolled-players" class="players-grid">
                        <!-- Players of rolled team loaded here -->
                    </div>
                </div>
            </div>

            <!-- Tournament Standings & Bracket Panel -->
            <div id="tournament-panel" class="tournament-panel">
                <div class="tournament-header" style="flex-direction: column; align-items: stretch; gap: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                        <h3 id="tournament-status-title" style="margin: 0; font-size: 1.2rem;">Partida 1</h3>
                        <button id="btn-sim-round" class="btn-action" onclick="playRound()" style="padding: 8px 16px; font-size: 0.85rem; max-width: 130px; width: 100%;">Jogar Partida</button>
                    </div>
                    
                    <!-- Lives & Wins displays -->
                    <div style="display: flex; flex-direction: column; gap: 6px; background: var(--bg-card); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-muted); font-weight: bold;">Vidas:</span>
                            <span id="lives-display" style="letter-spacing: 2px;">💖💖💖💖💖💖💖</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-muted); font-weight: bold;">Vitórias:</span>
                            <span style="color: var(--success); font-weight: bold;"><span id="wins-counter">0</span> Vitórias</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-muted); font-weight: bold;">Estilo Tático:</span>
                            <span id="tactic-style-display" style="font-weight: bold; color: var(--warning);">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-muted); font-weight: bold;">Química do Time:</span>
                            <span id="chemistry-display" style="font-weight: bold; color: var(--primary);">-</span>
                        </div>
                    </div>
                </div>

                <div class="tournament-tabs">
                    <button class="tab-btn active" onclick="switchTab('survival-match')">Próximo Jogo</button>
                    <button class="tab-btn" onclick="switchTab('history')">Histórico</button>
                    <button class="tab-btn" onclick="switchTab('global-ranking')">Recordes</button>
                </div>

                <!-- Next Opponent Tab -->
                <div id="tab-survival-match" class="tab-content active">
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; text-align: center;">
                        <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Próximo Adversário</h4>
                        
                        <img id="next-opp-badge" src="/images/escudos/0.png" style="width: 64px; height: 64px; object-fit: contain; margin-bottom: 10px;">
                        <div id="next-opp-name" style="font-size: 1.25rem; font-weight: bold; margin-bottom: 4px;">-</div>
                        <div id="next-opp-country" style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 12px;">-</div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 10px;">
                            <span>Nível do Oponente:</span>
                            <strong style="color: var(--primary);" id="next-opp-rating">-</strong>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 10px; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234,179,8,0.2); border-radius: 6px; font-size: 0.8rem; color: #f59e0b;">
                        💡 <strong>Regra de Danos:</strong> Empate custa <strong>1 vida</strong> 💔. Derrota custa <strong>2 vidas</strong> 💔💔. Vença o jogo para manter suas vidas intactas!
                    </div>
                </div>

                <!-- History Tab -->
                <div id="tab-history" class="tab-content">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th>Oponente</th>
                                <th>Placar</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="history-body">
                            <!-- Match history rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Global Rankings Tab (Inside Tournament) -->
                <div id="tab-global-ranking" class="tab-content">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th>Treinador</th>
                                <th>Modo</th>
                                <th>Nível Médio</th>
                                <th>Vitórias</th>
                            </tr>
                        </thead>
                        <tbody id="rankings-body-tournament">
                            <!-- Global ranking rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Setup screen rankings box -->
            <div id="ranking-box-setup" class="roulette-container" style="text-align: left;">
                <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 1.15rem; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; color: var(--warning);">🏆 Recordes Globais - 7vidas</h3>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th>Treinador</th>
                                <th>Modo</th>
                                <th>Nível M.</th>
                                <th>Vitórias</th>
                            </tr>
                        </thead>
                        <tbody id="rankings-body-setup">
                            <!-- Global ranking rows -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Match Simulation Ticker Overlay -->
<div id="sim-overlay" class="sim-overlay">
    <div class="sim-modal">
        <div class="sim-modal-header">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: bold;">Simulando Partida</h3>
        </div>
        <div class="sim-modal-body">
            <!-- Scoreboard -->
            <div class="match-score-board">
                <div class="score-team">
                    <img id="sim-home-badge" src="/images/escudos/0.png" style="width: 45px; height: 45px; object-fit: contain;">
                    <span id="sim-home-name" class="score-team-name">Time A</span>
                </div>
                <div id="sim-home-score" class="score-display">0</div>
                <div class="score-divider">x</div>
                <div id="sim-away-score" class="score-display">0</div>
                <div class="score-team">
                    <img id="sim-away-badge" src="/images/escudos/0.png" style="width: 45px; height: 45px; object-fit: contain;">
                    <span id="sim-away-name" class="score-team-name">Time B</span>
                </div>
            </div>

            <!-- Ticker Events -->
            <div id="sim-ticker" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Real-time commentary will print here -->
            </div>
        </div>
        <div class="sim-modal-footer">
            <button id="btn-close-sim" class="btn-action" style="display: none;" onclick="closeSim()">Prosseguir</button>
        </div>
    </div>
</div>

<!-- Help / Rules Modal Overlay -->
<div id="help-modal" class="sim-overlay" style="display: none; justify-content: center; align-items: center; background: rgba(0, 0, 0, 0.75); z-index: 1000;">
    <div class="sim-modal" style="max-width: 580px; text-align: left; padding: 25px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); display: flex; flex-direction: column; width: 90%; max-height: 90vh;">
        <div class="sim-modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: var(--warning); font-size: 1.4rem; display: flex; align-items: center; gap: 8px;">
                ❓ Como Jogar - 7vidas
            </h3>
        </div>
        <div style="font-size: 0.92rem; color: var(--text-main); line-height: 1.6; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; padding-right: 8px;">
            <p style="margin: 0;">O <strong>7vidas</strong> é um simulador de draft e sobrevivência tática. Seu objetivo é montar o melhor elenco possível e sobreviver ao maior número de rodadas contra clubes do mundo todo no Mundial de Clubes!</p>
            
            <h4 style="color: var(--primary); margin: 5px 0 0 0; font-size: 1.05rem;">1. Fase de Draft (Montagem do Time)</h4>
            <p style="margin: 0;">Gire a roleta para sortear um clube. Escolha um jogador daquele clube e clique no slot do campinho na posição que deseja escalá-lo. Você possui <strong>3 Pulos Gerais</strong> (qualquer clube) e <strong>3 Pulos de Mesma Liga</strong> (clube do mesmo campeonato do time atual) para usar caso não goste do clube sorteado.</p>

            <h4 style="color: var(--primary); margin: 5px 0 0 0; font-size: 1.05rem;">2. Química e Escalação</h4>
            <p style="margin: 0;">Cada jogador rende melhor em sua posição original. Escalar um atleta fora de posição aplica uma <strong>penalidade de -25%</strong> no nível individual dele. Escalar todos os 11 jogadores em suas posições corretas (100% de Química) concede um <strong>bônus especial de +5</strong> no rating geral do seu time!</p>

            <h4 style="color: var(--primary); margin: 5px 0 0 0; font-size: 1.05rem;">3. Estilos Táticos (Ajuste mid-game)</h4>
            <p style="margin: 0;">Táticas ofensivas aumentam a probabilidade de gols de ambas as equipes em 25% na simulação (gerando jogos mais rápidos e cheios de gols). Esquemas táticos defensivos diminuem a probabilidade em 25%, ideal para trancar o jogo e segurar empates contra oponentes difíceis.</p>

            <h4 style="color: var(--primary); margin: 5px 0 0 0; font-size: 1.05rem;">4. Sobrevivência (7 Vidas)</h4>
            <p style="margin: 0;">Você inicia sua jornada com <strong>7 vidas</strong> 💖. A cada rodada da simulação:
            <br>• <strong>Vitória</strong>: Mantém suas vidas intactas.
            <br>• <strong>Empate</strong>: Custa <strong>1 vida</strong> 💔.
            <br>• <strong>Derrota</strong>: Custa <strong>2 vidas</strong> 💔💔.
            <br>O jogo termina quando você perder todas as suas vidas.</p>
        </div>
        <div class="sim-modal-footer" style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 15px; display: flex; justify-content: flex-end;">
            <button class="btn-action" style="width: 120px;" onclick="closeHelpModal()">Entendido!</button>
        </div>
    </div>
</div>

<script src="../7vidas/game.js?v=<?php echo time(); ?>"></script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
