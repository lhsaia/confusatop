<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");

$page_title = "Gerenciar Jogo de Seleções";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "home_redesign";
$extra_css = "jogos_clubes_redesign";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);
$competicao = new Competicao($db);

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_admin = $is_logged_in && ((int)($_SESSION['admin_status'] ?? 0) === 1);

// Acesso: Criação (qualquer usuário logado); Edição (apenas administradores)
if (!$is_logged_in) {
    echo "<div class='ranking-container'><div class='ranking-card' style='text-align:center; padding:3.5rem 1.5rem; font-family:Montserrat,sans-serif;'>
        <span class='material-symbols-outlined' style='font-size: 3.5rem; color: #ef4444; margin-bottom: 1rem;'>lock</span>
        <h2 style='font-family: Outfit, sans-serif; color: #1e293b; margin-bottom: 0.5rem; font-size: 1.5rem;'>Acesso Restrito</h2>
        <p style='color: #64748b; font-size: 0.95rem; max-width: 500px; margin: 0 auto 1.5rem auto;'>Você precisa estar logado para criar partidas no Ranking oficial.</p>
        <a href='/ranking' class='btn-clubes-secondary'>
            <span class='material-symbols-outlined' style='font-size: 1.15rem;'>arrow_back</span>
            <span>Voltar ao Ranking</span>
        </a>
    </div></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
    exit;
}

if ($match_id > 0 && !$is_admin) {
    echo "<div class='ranking-container'><div class='ranking-card' style='text-align:center; padding:3.5rem 1.5rem; font-family:Montserrat,sans-serif;'>
        <span class='material-symbols-outlined' style='font-size: 3.5rem; color: #ef4444; margin-bottom: 1rem;'>lock</span>
        <h2 style='font-family: Outfit, sans-serif; color: #1e293b; margin-bottom: 0.5rem; font-size: 1.5rem;'>Acesso Restrito</h2>
        <p style='color: #64748b; font-size: 0.95rem; max-width: 500px; margin: 0 auto 1.5rem auto;'>Apenas administradores têm permissão para editar jogos existentes no Ranking oficial.</p>
        <a href='/ranking' class='btn-clubes-secondary'>
            <span class='material-symbols-outlined' style='font-size: 1.15rem;'>arrow_back</span>
            <span>Voltar ao Ranking</span>
        </a>
    </div></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
    exit;
}

$match_info = null;
$events = [];
$lineup = [];

if ($match_id > 0) {
    $match_info = $jogo->getSingleMatchInfo($match_id);
    if ($match_info) {
        $stmtEvents = $db->prepare("SELECT * FROM jogos_eventos WHERE id_jogo = ? ORDER BY tempo ASC, minutos ASC");
        $stmtEvents->execute([$match_id]);
        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        $stmtLineup = $db->prepare("SELECT * FROM jogos_escalacao WHERE id_jogo = ?");
        $stmtLineup->execute([$match_id]);
        $lineup = $stmtLineup->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Pré-processamento da escalação
$teamLineups = [1 => [], 2 => []]; 
$coaches = [1 => [], 2 => []];
if ($match_info) {
    $timeA_id = (int)$match_info['timeA_id'];
    $timeB_id = (int)$match_info['timeB_id'];
    
    foreach ($lineup as $p) {
        $pTeamId = (int)$p['id_time'];
        $tId = 0;
        
        if ($timeA_id > 0 && $pTeamId === $timeA_id) {
            $tId = 1;
        } elseif ($timeB_id > 0 && $pTeamId === $timeB_id) {
            $tId = 2;
        } elseif (isset($p['lado']) && ($p['lado'] == 'A' || $p['lado'] == '1')) {
            $tId = 1;
        } elseif (isset($p['lado']) && ($p['lado'] == 'B' || $p['lado'] == '2')) {
            $tId = 2;
        }
        
        if ($tId === 0) {
            if (count($teamLineups[1]) + count($coaches[1]) < 18) {
                $tId = 1;
            } else {
                $tId = 2;
            }
        }
        
        if ($tId > 0) {
            if ($p['posicao'] == 'S' && $p['id_jogador'] == 0 && (stripos($p['nome_jogador'], 'Tecnico') !== false || stripos($p['nome_jogador'], 'Técnico') !== false)) {
                $p['posicao'] = 'T';
            }
            if ($p['posicao'] == 'T') { 
                $coaches[$tId][] = $p; 
            } else { 
                $teamLineups[$tId][] = $p; 
            }
        }
    }
}

// Buscar posições para dropdown
$stmtPos = $db->prepare("SELECT Sigla, Nome FROM posicoes ORDER BY ID ASC");
$stmtPos->execute();
$allPositions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

$hasCoachPos = false;
foreach ($allPositions as $pos) { 
    if ($pos['Sigla'] == 'T') { $hasCoachPos = true; break; } 
}
if (!$hasCoachPos) { 
    array_push($allPositions, ['Sigla' => 'T', 'Nome' => 'Técnico']); 
}

function renderNationalPlayerEditRow($side, $idx, $p, $allPositions, $isCoach = false) {
    $prefix = $isCoach ? "coach_$idx" : $idx;
    $isManual = (empty($p['id_jogador']) || (int)$p['id_jogador'] <= 0);
    $rowClass = $isCoach ? 'starter' : (($p['titular'] ?? 1) ? 'starter' : 'bench');
    ?>
    <tr class="lineup-row <?php echo $rowClass; ?>">
        <td style="width: 55px; text-align: center;">
            <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][pos]" class="form-input-sm form-pos-select">
                <?php foreach($allPositions as $pos): ?>
                    <option value="<?php echo $pos['Sigla']; ?>" <?php echo (strcasecmp((string)($p['posicao'] ?? ''), (string)$pos['Sigla']) === 0) ? 'selected' : ''; ?>><?php echo $pos['Sigla']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="text-align: left;">
            <div class="player-select-wrapper">
                <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][id_jogador]" class="select-player-api" style="width: 100%;" <?php echo $isCoach ? 'data-is-coach="1"' : ''; ?>>
                    <option value="-1" <?php echo $isManual ? 'selected' : ''; ?>>➕ Digitar nome (não consta no banco)</option>
                    <?php if(!$isManual): ?>
                        <option value="<?php echo $p['id_jogador']; ?>" selected><?php echo htmlspecialchars($p['nome_jogador']); ?></option>
                    <?php endif; ?>
                </select>
                <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][nome_jogador]" class="form-input-sm manual-player-name <?php echo !$isManual ? 'd-none' : ''; ?>" value="<?php echo htmlspecialchars($p['nome_jogador'] ?? ''); ?>" placeholder="Digite o nome do atleta..." style="margin-top: 4px;">
            </div>
        </td>
        <td style="width: 45px; text-align: center;">
            <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][num]" class="form-input-sm text-center" value="<?php echo $p['numero'] ?? ''; ?>" placeholder="#">
        </td>
        <td style="width: 45px; text-align: center;">
            <input class="titular-toggle-cb titular-toggle" type="checkbox" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][titular]" value="1" <?php echo (($p['titular'] ?? 1) || $isCoach) ? 'checked' : ''; ?> <?php echo $isCoach ? 'disabled' : ''; ?>>
        </td>
        <td style="width: 95px; text-align: center;">
            <?php if(!$isCoach): ?>
            <div class="sub-minutes-container">
                <div class="sub-minute-col">
                    <span class="material-symbols-outlined sub-icon sub-in">arrow_upward</span>
                    <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][sub_in]" class="form-input-xs text-center" value="<?php echo $p['entrada_minuto'] ?? ''; ?>" placeholder="-">
                </div>
                <div class="sub-minute-col">
                    <span class="material-symbols-outlined sub-icon sub-out">arrow_downward</span>
                    <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][sub_out]" class="form-input-xs text-center" value="<?php echo $p['saida_minuto'] ?? ''; ?>" placeholder="-">
                </div>
            </div>
            <?php endif; ?>
        </td>
        <td style="width: 35px; text-align: center;">
            <button type="button" class="btn-row-action btn-remove" title="Remover atleta">
                <span class="material-symbols-outlined">close</span>
            </button>
        </td>
    </tr>
    <?php
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Layout e Grid Moderno */
.editor-container {
    width: 100%;
    max-width: 1300px;
    margin: 1.5rem auto 3rem auto;
    padding: 0 1rem;
    box-sizing: border-box;
}

.editor-sticky-bar {
    position: sticky;
    top: 70px;
    z-index: 90;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    padding: 14px 20px;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
}

.editor-title-wrap h3 {
    font-family: 'Outfit', sans-serif !important;
    font-size: 1.4rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 !important;
    display: flex;
    align-items: center;
    gap: 8px;
}

.editor-title-wrap .subinfo {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-unsaved {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.editor-actions-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Botões do Editor */
html body .editor-container button,
html body .editor-container .btn-clubes-primary,
html body .editor-container #btnSalvar {
    width: auto !important;
    max-width: fit-content !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
    color: #ffffff !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 600 !important;
    font-size: 0.9rem !important;
    padding: 9px 20px !important;
    margin: 0 !important;
    border-radius: 10px !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    line-height: normal !important;
    text-decoration: none !important;
}

html body .editor-container button:hover,
html body .editor-container .btn-clubes-primary:hover,
html body .editor-container #btnSalvar:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35) !important;
    color: #ffffff !important;
    opacity: 1 !important;
}

html body .editor-container .btn-clubes-secondary {
    width: auto !important;
    max-width: fit-content !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    background: #ffffff !important;
    color: #334155 !important;
    font-family: 'Outfit', sans-serif !important;
    font-weight: 600 !important;
    font-size: 0.88rem !important;
    padding: 8px 16px !important;
    margin: 0 !important;
    border-radius: 10px !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    text-decoration: none !important;
    line-height: normal !important;
}

html body .editor-container .btn-clubes-secondary:hover {
    background: #f8fafc !important;
    border-color: rgba(2, 132, 199, 0.4) !important;
    color: #0284c7 !important;
    transform: translateY(-1px) !important;
}

.editor-form-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 22px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
}

.editor-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.editor-card-header h4 {
    font-family: 'Outfit', sans-serif !important;
    font-size: 1.15rem !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    margin: 0 !important;
    display: flex;
    align-items: center;
    gap: 8px;
}

.editor-card-header h4 .material-symbols-outlined {
    color: #0284c7;
    font-size: 1.35rem;
}

.match-scoreboard-editor {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 20px;
    align-items: center;
}

@media (max-width: 860px) {
    .match-scoreboard-editor {
        grid-template-columns: 1fr;
    }
}

.team-box {
    background: #f8fafc;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 14px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.team-flag-box {
    width: 64px;
    height: 44px;
    background: #ffffff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    flex-shrink: 0;
    overflow: hidden;
}

.team-flag-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-content {
    flex: 1;
    min-width: 0;
}

.team-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0284c7;
    margin-bottom: 6px;
    display: block;
}

.score-center-box {
    text-align: center;
    padding: 0 10px;
}

.score-inputs-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.score-input {
    width: 64px !important;
    height: 52px !important;
    font-family: 'Outfit', sans-serif !important;
    font-size: 1.8rem !important;
    font-weight: 800 !important;
    color: #0284c7 !important;
    text-align: center !important;
    border-radius: 12px !important;
    border: 1px solid rgba(0, 0, 0, 0.15) !important;
    background: #ffffff !important;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    outline: none;
}

.score-input:focus {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
}

.score-times {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #94a3b8;
}

.penalties-box {
    margin-top: 8px;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 4px 8px;
    display: inline-block;
}

.penalties-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
}

.penalties-inputs {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.pen-input {
    width: 38px !important;
    height: 28px !important;
    text-align: center !important;
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    border-radius: 6px !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    background: #ffffff !important;
    padding: 0 !important;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.form-group-custom {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group-custom label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.form-control-custom {
    width: 100%;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: #ffffff;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.9rem;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-control-custom:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

/* Tabelas e Estrutura de Larguras Fixas */
.table-editor-custom {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-family: 'Montserrat', sans-serif;
    margin-bottom: 0;
    table-layout: fixed;
}

.table-editor-custom th {
    background: #f8fafc;
    color: #475569;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 10px 8px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.table-editor-custom td {
    padding: 8px 6px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    vertical-align: middle;
    color: #1e293b;
    overflow: visible;
}

.lineup-row {
    border-left: 3px solid transparent;
    transition: background 0.15s ease;
}

.lineup-row.starter {
    border-left-color: #10b981;
    background-color: #ffffff;
}

.lineup-row.bench {
    border-left-color: #f59e0b;
    background-color: #fafbfc;
}

.lineup-row:hover td {
    background-color: rgba(2, 132, 199, 0.02);
}

.form-input-sm {
    padding: 7px 10px;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.88rem;
    color: #0f172a;
    background: #ffffff;
    width: 100%;
    box-sizing: border-box;
    outline: none;
    height: 38px;
}

.form-input-sm:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
}

.form-pos-select {
    font-weight: 700;
    text-align: center;
    background: #f1f5f9;
    padding: 5px 2px;
}

.form-input-xs {
    height: 24px;
    width: 34px;
    padding: 0;
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    box-sizing: border-box;
}

.sub-minutes-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.sub-minute-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.sub-icon {
    font-size: 0.85rem;
    font-weight: bold;
}

.sub-in { color: #10b981; }
.sub-out { color: #ef4444; }

.btn-row-action {
    background: transparent !important;
    border: none !important;
    color: #94a3b8 !important;
    cursor: pointer !important;
    padding: 4px !important;
    margin: 0 !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 6px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: none !important;
    transition: all 0.15s ease !important;
}

.btn-row-action:hover {
    color: #ef4444 !important;
    background: #fee2e2 !important;
    transform: none !important;
    box-shadow: none !important;
}

.btn-row-action .material-symbols-outlined {
    font-size: 1.15rem;
}

.titular-toggle-cb {
    width: 18px;
    height: 18px;
    accent-color: #0284c7;
    cursor: pointer;
}

.lineups-two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 980px) {
    .lineups-two-columns {
        grid-template-columns: 1fr;
    }
}

.lineup-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    color: #ffffff;
}

.header-side-a {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
}

.header-side-b {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
}

.lineup-card-header h5 {
    margin: 0;
    font-family: 'Outfit', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lineup-divider-row td {
    background: #f1f5f9 !important;
    color: #475569 !important;
    font-family: 'Outfit', sans-serif;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 12px;
    text-align: center;
}

.select2-container {
    width: 100% !important;
    text-align: left !important;
}

.select2-container .select2-selection--single {
    background-color: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    border-radius: 8px !important;
    height: 38px !important;
    display: flex !important;
    align-items: center !important;
    padding: 0 4px !important;
    box-sizing: border-box !important;
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
}

.select2-container .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-family: 'Montserrat', sans-serif !important;
    font-size: 0.88rem !important;
    line-height: 36px !important;
    padding-left: 8px !important;
    padding-right: 24px !important;
    margin: 0 !important;
    text-align: left !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.select2-container .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 6px !important;
}

.select2-container .select2-selection__clear {
    display: none !important;
}

.select2-dropdown {
    border-radius: 8px !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    z-index: 99999 !important;
}

.select2-results__options {
    list-style: none !important;
    margin: 0 !important;
    padding: 4px 0 !important;
}

.select2-results__option {
    padding: 8px 12px !important;
    font-family: 'Montserrat', sans-serif !important;
    font-size: 0.88rem !important;
    line-height: 1.4 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    text-align: left !important;
    margin: 0 !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0284c7 !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #f0f9ff !important;
    color: #0284c7 !important;
    font-weight: 600;
}

.select2-item-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.select2-item-flag {
    width: 20px;
    height: 14px;
    object-fit: cover;
    border-radius: 2px;
    vertical-align: middle;
    flex-shrink: 0;
}

.d-none { display: none !important; }

#toastContainer {
    position: fixed;
    top: 80px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.custom-toast {
    min-width: 280px;
    padding: 12px 18px;
    border-radius: 12px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.88rem;
    font-weight: 600;
}

.toast-success { background: #059669; }
.toast-danger { background: #dc2626; }

html body .editor-container .custom-toast button,
.custom-toast .btn-toast-close {
    background: transparent !important;
    color: rgba(255, 255, 255, 0.85) !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    font-size: 1.25rem !important;
    line-height: 1 !important;
    border-radius: 4px !important;
    transform: none !important;
    transition: all 0.2s ease !important;
}

html body .editor-container .custom-toast button:hover,
.custom-toast .btn-toast-close:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    transform: none !important;
}
</style>

<div class="editor-container">
    <div id="toastContainer"></div>

    <form id="matchForm" method="POST" action="/ranking/salvar_jogo.php">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">

        <div class="editor-sticky-bar">
            <div class="editor-title-wrap">
                <h3>
                    <span class="material-symbols-outlined" style="color: #0284c7;">sports_soccer</span>
                    <?php echo $match_id > 0 ? "Editar Partida #$match_id" : "Inserir Jogo de Seleções"; ?>
                </h3>
                <div class="subinfo">
                    <span id="unsavedBadge" class="badge-unsaved" style="display: none;">
                        <span class="material-symbols-outlined" style="font-size: 0.85rem;">edit_note</span> Alterações não salvas
                    </span>
                    <span>Ranking Oficial de Seleções</span>
                </div>
            </div>
            
            <div class="editor-actions-wrapper">
                <a href="/ranking/jogoserecordes.php" class="btn-clubes-secondary">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_back</span>
                    <span>Voltar aos Jogos</span>
                </a>
                <?php if($match_id > 0): ?>
                    <a href="/ranking/match_info.php?match_id=<?php echo $match_id; ?>" class="btn-clubes-secondary" target="_blank">
                        <span class="material-symbols-outlined" style="font-size: 1.1rem; color: #0284c7;">visibility</span>
                        <span>Ver Partida</span>
                    </a>
                <?php endif; ?>
                <button type="button" id="btnSalvar" class="btn-clubes-primary">
                    <span class="material-symbols-outlined" style="font-size: 1.15rem;">save</span>
                    <span>Salvar Partida</span>
                </button>
            </div>
        </div>

        <div class="editor-form-card">
            <div class="editor-card-header">
                <h4>
                    <span class="material-symbols-outlined">flag</span>
                    <span>Seleções e Placar</span>
                </h4>
            </div>
            <div class="match-scoreboard-editor">
                <div class="team-box">
                    <div class="team-flag-box" id="flagA">
                        <img src="/images/bandeiras/<?php echo $match_info['timeA_bandeira'] ?? 'sem_bandeira.png'; ?>">
                    </div>
                    <div class="team-content">
                        <span class="team-label">Seleção A (Mandante)</span>
                        <select name="timeA_id" id="selectTimeA" class="select2-country" style="width: 100%;">
                            <?php if($match_info && $match_info['timeA_id']): ?>
                                <option value="<?php echo $match_info['timeA_id']; ?>" selected><?php echo htmlspecialchars($match_info['timeA_nome']); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="text" name="timeA_nome" id="nameA" class="form-input-sm mt-2 d-none" value="<?php echo htmlspecialchars($match_info['timeA_nome'] ?? ''); ?>" placeholder="Nome da Seleção A">
                    </div>
                </div>

                <div class="score-center-box">
                    <div class="score-inputs-wrap">
                        <input type="number" name="timeA_gols" class="score-input" value="<?php echo $match_info['timeA_gols'] ?? 0; ?>" min="0">
                        <span class="score-times">&times;</span>
                        <input type="number" name="timeB_gols" class="score-input" value="<?php echo $match_info['timeB_gols'] ?? 0; ?>" min="0">
                    </div>
                    <div class="penalties-box">
                        <span class="penalties-label">Pênaltis (Opcional)</span>
                        <div class="penalties-inputs">
                            <input type="number" name="timeA_penaltis" class="pen-input" value="<?php echo $match_info['timeA_penaltis'] ?? ''; ?>" placeholder="-">
                            <span style="font-size: 0.75rem; color: #64748b;">x</span>
                            <input type="number" name="timeB_penaltis" class="pen-input" value="<?php echo $match_info['timeB_penaltis'] ?? ''; ?>" placeholder="-">
                        </div>
                    </div>
                </div>

                <div class="team-box">
                    <div class="team-flag-box" id="flagB">
                        <img src="/images/bandeiras/<?php echo $match_info['timeB_bandeira'] ?? 'sem_bandeira.png'; ?>">
                    </div>
                    <div class="team-content">
                        <span class="team-label" style="color: #475569;">Seleção B (Visitante)</span>
                        <select name="timeB_id" id="selectTimeB" class="select2-country" style="width: 100%;">
                            <?php if($match_info && $match_info['timeB_id']): ?>
                                <option value="<?php echo $match_info['timeB_id']; ?>" selected><?php echo htmlspecialchars($match_info['timeB_nome']); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="text" name="timeB_nome" id="nameB" class="form-input-sm mt-2 d-none" value="<?php echo htmlspecialchars($match_info['timeB_nome'] ?? ''); ?>" placeholder="Nome da Seleção B">
                    </div>
                </div>
            </div>
        </div>

        <div class="editor-form-card">
            <div class="editor-card-header">
                <h4>
                    <span class="material-symbols-outlined">tune</span>
                    <span>Informações Gerais da Partida</span>
                </h4>
            </div>
            <div class="meta-grid">
                <div class="form-group-custom">
                    <label>Competição</label>
                    <select name="campeonato" id="selectComp" class="form-control-custom">
                        <?php
                        $stmtComp = $competicao->read();
                        while ($row_comp = $stmtComp->fetch(PDO::FETCH_ASSOC)){
                            $sel = ($match_info && $match_info['competition_name'] == $row_comp['nome']) ? 'selected' : (($row_comp['id'] == 10) ? 'selected' : '');
                            echo "<option value='{$row_comp['id']}' {$sel}>{$row_comp['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label>Fase</label>
                    <select name="fase" class="form-control-custom">
                        <?php 
                        $fases = [0 => 'N/A', 1 => 'Fase pré', 2 => 'Fase de grupos', 3 => 'Oitavas-de-final', 4 => 'Quartas-de-final', 5 => 'Semi-final', 6 => '3º Lugar', 7 => 'Repescagem', 8 => 'Final', 9 => '16-avos-de-final', 10 => '32-avos-de-final'];
                        $curFase = $match_info['phase'] ?? 0;
                        foreach($fases as $fId => $fNome): ?>
                            <option value="<?php echo $fId; ?>" <?php echo $curFase == $fId ? 'selected' : ''; ?>><?php echo $fNome; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label>Data da Partida</label>
                    <input type="date" name="data" class="form-control-custom" value="<?php echo !empty($match_info['data']) ? date('Y-m-d', strtotime($match_info['data'])) : date('Y-m-d'); ?>">
                </div>

                <div class="form-group-custom">
                    <label>Estádio</label>
                    <input type="text" name="estadio" class="form-control-custom" value="<?php echo htmlspecialchars($match_info['estadio'] ?? ''); ?>" placeholder="Nome do Estádio">
                </div>
            </div>
        </div>

        <div class="editor-form-card">
            <div class="editor-card-header">
                <h4>
                    <span class="material-symbols-outlined">timer</span>
                    <span>Eventos da Partida (Gols e Cartões)</span>
                </h4>
                <button type="button" id="addEventRow" class="btn-clubes-secondary" style="font-size: 0.85rem; padding: 6px 14px;">
                    <span class="material-symbols-outlined" style="font-size: 1.05rem; color: #0284c7;">add</span>
                    <span>Adicionar Evento</span>
                </button>
            </div>

            <div class="tbl_user_data">
                <table class="table-editor-custom" id="eventsTable">
                    <thead>
                        <tr>
                            <th style="width: 75px; text-align: center;">Minuto</th>
                            <th style="width: 200px; text-align: left;">Seleção</th>
                            <th style="width: 175px; text-align: left;">Tipo de Lance</th>
                            <th style="text-align: left;">Jogador Envolvido</th>
                            <th style="width: 45px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($events as $index => $ev): 
                            $sideEv = ($ev['id_time'] == ($match_info['timeA_id'] ?? 0)) ? 'A' : 'B';
                            $evMin = ($ev['minutos'] !== null && $ev['minutos'] !== '') ? $ev['minutos'] : '';
                            $isManualEv = (empty($ev['id_jogador']) || (int)$ev['id_jogador'] <= 0);
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="number" name="events[<?php echo $index; ?>][minutos]" class="form-input-sm text-center" value="<?php echo $evMin; ?>" placeholder="Min" style="max-width: 65px; margin: 0 auto;">
                                </td>
                                <td>
                                    <select name="events[<?php echo $index; ?>][side]" class="form-input-sm event-side">
                                        <option value="A" <?php echo $sideEv == 'A' ? 'selected' : ''; ?>>Seleção A (Mandante)</option>
                                        <option value="B" <?php echo $sideEv == 'B' ? 'selected' : ''; ?>>Seleção B (Visitante)</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="events[<?php echo $index; ?>][tipo]" class="form-input-sm">
                                        <option value="1" <?php echo $ev['tipo'] == 1 ? 'selected' : ''; ?>>⚽ Gol</option>
                                        <option value="4" <?php echo $ev['tipo'] == 4 ? 'selected' : ''; ?>>🚫 Gol Contra</option>
                                        <option value="2" <?php echo $ev['tipo'] == 2 ? 'selected' : ''; ?>>🟨 Cartão Amarelo</option>
                                        <option value="3" <?php echo $ev['tipo'] == 3 ? 'selected' : ''; ?>>🟥 Cartão Vermelho</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="player-input-container" data-side="<?php echo $sideEv; ?>">
                                        <select name="events[<?php echo $index; ?>][id_jogador]" class="select-player-api" style="width: 100%;">
                                            <option value="-1" <?php echo $isManualEv ? 'selected' : ''; ?>>➕ Digitar nome (não consta no banco)</option>
                                            <?php if(!$isManualEv): ?>
                                                <option value="<?php echo $ev['id_jogador']; ?>" selected><?php echo htmlspecialchars($ev['nome_jogador']); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="text" name="events[<?php echo $index; ?>][nome_jogador]" class="form-input-sm manual-player-name <?php echo !$isManualEv ? 'd-none' : ''; ?>" value="<?php echo htmlspecialchars($ev['nome_jogador'] ?? ''); ?>" placeholder="Digite o nome do atleta..." style="margin-top: 4px;">
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-row-action btn-remove" title="Remover evento">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lineups-two-columns">
            <?php foreach(['A' => ['idx' => 1, 'label' => 'MANDANTE (SELEÇÃO A)', 'class' => 'header-side-a'], 
                           'B' => ['idx' => 2, 'label' => 'VISITANTE (SELEÇÃO B)', 'class' => 'header-side-b']] as $side => $tMeta): 
                $tIdx = $tMeta['idx'];
            ?>
            <div class="editor-form-card" style="padding: 16px;">
                <div class="lineup-card-header <?php echo $tMeta['class']; ?>">
                    <h5>
                        <span class="material-symbols-outlined" style="font-size: 1.15rem;">shield</span> 
                        <?php echo $tMeta['label']; ?>
                    </h5>
                    <button type="button" class="btn-clubes-secondary" style="font-size: 0.75rem; padding: 4px 10px; background: rgba(255,255,255,0.9);" onclick="addLineupRow('<?php echo $side; ?>')">
                        <span class="material-symbols-outlined" style="font-size: 0.95rem; color: #0284c7;">add</span>
                        <span>Adicionar Atleta</span>
                    </button>
                </div>

                <div class="tbl_user_data">
                    <table class="table-editor-custom">
                        <thead>
                            <tr>
                                <th style="width: 55px; text-align: center;">POS</th>
                                <th style="text-align: left;">JOGADOR</th>
                                <th style="width: 45px; text-align: center;">Nº</th>
                                <th style="width: 45px; text-align: center;">TIT</th>
                                <th style="width: 95px; text-align: center;">SUB (▲/▼)</th>
                                <th style="width: 35px; text-align: center;"></th>
                            </tr>
                        </thead>
                        <tbody id="lineup<?php echo $side; ?>Container">
                            <?php 
                            foreach($teamLineups[$tIdx] as $idx => $p) renderNationalPlayerEditRow($side, $idx, $p, $allPositions);
                            if(count($coaches[$tIdx]) > 0): ?>
                                <tr class="lineup-divider-row"><td colspan="6">COMISSÃO TÉCNICA</td></tr>
                                <?php foreach($coaches[$tIdx] as $cIdx => $pc) renderNationalPlayerEditRow($side, "coach_$cIdx", $pc, $allPositions, true); ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<script>
let eventIdx = <?php echo count($events); ?> + 500;

function addNewEventRow() {
    const side = 'A';
    const row = `<tr>
        <td style="text-align: center;"><input type="number" name="events[${eventIdx}][minutos]" class="form-input-sm text-center" placeholder="Min" style="max-width: 65px; margin: 0 auto;"></td>
        <td><select name="events[${eventIdx}][side]" class="form-input-sm event-side"><option value="A">Seleção A (Mandante)</option><option value="B">Seleção B (Visitante)</option></select></td>
        <td><select name="events[${eventIdx}][tipo]" class="form-input-sm"><option value="1">⚽ Gol</option><option value="4">🚫 Gol Contra</option><option value="2">🟨 Cartão Amarelo</option><option value="3">🟥 Cartão Vermelho</option></select></td>
        <td><div class="player-input-container" data-side="A"><select name="events[${eventIdx}][id_jogador]" class="select-player-api" style="width: 100%;"><option value="-1" selected>➕ Digitar nome (não consta no banco)</option></select><input type="text" name="events[${eventIdx}][nome_jogador]" class="form-input-sm manual-player-name" placeholder="Digite o nome do atleta..." style="margin-top: 4px;"></div></td>
        <td style="text-align: center;"><button type="button" class="btn-row-action btn-remove" title="Remover evento"><span class="material-symbols-outlined">delete</span></button></td>
    </tr>`;
    $('#eventsTable tbody').append(row);
    
    const $newSelect = $('#eventsTable tbody tr:last .select-player-api');
    initPlayerSelect($newSelect, 'A');
    
    eventIdx++;
    if(typeof markAsDirty === 'function') markAsDirty();
}

$(document).ready(function() {
    initMainSelects();
    initAllPlayerSelects();

    let isDirty = false;

    window.markAsDirty = function() {
        if (!isDirty) {
            isDirty = true;
            $('#unsavedBadge').css('display', 'inline-flex');
        }
    };

    window.markAsClean = function() {
        isDirty = false;
        $('#unsavedBadge').hide();
    };

    $(document).on('change input', 'input, textarea, select', function() {
        markAsDirty();
    });

    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    $('#btnSalvar').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined" style="animation: spin 1s infinite linear; font-size:1.15rem;">sync</span><span>Salvando...</span>');

        const formData = $('#matchForm').serialize() + '&ajax=1';

        $.ajax({
            url: '/ranking/salvar_jogo.php',
            type: 'POST',
            data: formData,
            dataType: 'json'
        }).done(function(response) {
            if(response.success) {
                markAsClean();
                showToast('Partida de seleções salva com sucesso!', 'success');
                if(response.match_id) {
                    $('input[name="match_id"]').val(response.match_id);
                    $('.editor-title-wrap h3').html('<span class="material-symbols-outlined" style="color: #0284c7;">sports_soccer</span> Editar Partida #' + response.match_id);
                }
            } else {
                showToast('Erro: ' + (response.message || 'Falha ao salvar'), 'danger');
            }
        }).fail(function(jqXHR) {
            showToast('Erro de comunicação com o servidor (' + jqXHR.status + ')', 'danger');
        }).always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    $(document).on('click', '.btn-remove', function() {
        if(confirm('Tem certeza que deseja remover esta linha?')) {
            $(this).closest('tr').remove();
            markAsDirty();
        }
    });

    $(document).on('change', '.titular-toggle', function() {
        const isChecked = $(this).is(':checked');
        const $row = $(this).closest('.lineup-row');
        if(isChecked) {
            $row.removeClass('bench').addClass('starter');
        } else {
            $row.removeClass('starter').addClass('bench');
        }
    });

    $(document).on('click', '#addEventRow', function(e) {
        e.preventDefault();
        addNewEventRow();
    });

    $(document).on('change', '.event-side', function() {
        const side = $(this).val();
        const $container = $(this).closest('tr').find('.player-input-container');
        $container.attr('data-side', side);
        const $select = $container.find('.select-player-api');
        initPlayerSelect($select, side);
    });

    function showToast(message, type = 'success') {
        const toastId = 'toast_' + Date.now();
        const toastClass = type === 'success' ? 'toast-success' : 'toast-danger';
        const icon = type === 'success' ? 'check_circle' : 'error';

        const toastHtml = `
            <div id="${toastId}" class="custom-toast ${toastClass}">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:1.2rem;">${icon}</span>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-toast-close" onclick="$('#${toastId}').remove()">&times;</button>
            </div>
        `;
        $('#toastContainer').append(toastHtml);
        setTimeout(() => { $(`#${toastId}`).fadeOut(400, function() { $(this).remove(); }); }, 3500);
    }
});

function initMainSelects() {
    ['A', 'B'].forEach(side => {
        $(`#selectTime${side}`).select2({
            placeholder: 'Buscar país / seleção...',
            allowClear: false,
            width: '100%',
            ajax: {
                url: '/api/paises/buscar.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { 
                        name: params.term || '', 
                        q: params.term || '', 
                        apiKey: 'interna' 
                    };
                },
                processResults: function (data) {
                    let items = data.results || data.paises || data || [];
                    return {
                        results: $.map(items, function (item) {
                            return { 
                                id: item.id, 
                                text: item.text || item.nome, 
                                bandeira: item.bandeira 
                            };
                        })
                    };
                },
                cache: true
            },
            templateResult: function(item) {
                if(!item.id) return item.text;
                const flag = item.bandeira || 'sem_bandeira.png';
                return $(`<span class="select2-item-wrap"><img src="/images/bandeiras/${flag}" class="select2-item-flag"> <span>${item.text}</span></span>`);
            },
            templateSelection: function(item) {
                if(!item.id) return item.text;
                const flag = item.bandeira || $(`#flag${side} img`).attr('src')?.split('/').pop() || 'sem_bandeira.png';
                return $(`<span class="select2-item-wrap"><img src="/images/bandeiras/${flag}" class="select2-item-flag"> <span>${item.text}</span></span>`);
            },
            escapeMarkup: function(m) { return m; }
        }).on('select2:select', function(e) {
            markAsDirty();
            if(e.params.data && e.params.data.id) {
                $(`#flag${side}`).removeClass('d-none').html(`<img src="/images/bandeiras/${e.params.data.bandeira || 'sem_bandeira.png'}">`);
                $(`#name${side}`).val(e.params.data.text);
                
                $(`#lineup${side}Container .select-player-api`).each(function() {
                    initPlayerSelect($(this), side);
                });
                $(`#eventsTable .player-input-container[data-side="${side}"] .select-player-api`).each(function() {
                    initPlayerSelect($(this), side);
                });
            }
        });
    });
}

function getRowSide($el) {
    if($el.closest('#lineupAContainer').length) return 'A';
    if($el.closest('#lineupBContainer').length) return 'B';
    const sideContainer = $el.closest('.player-input-container');
    if(sideContainer.length) return sideContainer.attr('data-side') || 'A';
    return 'A';
}

function initPlayerSelect($select, explicitSide = null) {
    const side = explicitSide || getRowSide($select);
    const countryId = $(`#selectTime${side}`).val();

    if ($select.hasClass("select2-hidden-accessible")) {
        $select.select2('destroy');
    }

    $select.select2({
        placeholder: 'Selecione ou busque o atleta...',
        allowClear: false,
        width: '100%',
        ajax: {
            url: '/api/paises/jogadores.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term, country_id: countryId };
            },
            processResults: function (data) {
                let results = [];
                results.push({ id: '-1', text: '➕ Digitar nome (não consta no banco)' });
                if (Array.isArray(data)) {
                    data.forEach(function(item) {
                        if (item.children) {
                            results.push({
                                text: item.text,
                                children: item.children.map(c => ({ id: c.id, text: c.text, bandeira: c.bandeira }))
                            });
                        } else {
                            results.push({ id: item.id, text: item.text, bandeira: item.bandeira });
                        }
                    });
                }
                return { results: results };
            },
            cache: true
        },
        templateResult: function(item) {
            if (!item.id || item.id === '-1') return item.text;
            let flag = item.bandeira ? `<img src="/images/bandeiras/${item.bandeira}" class="select2-item-flag">` : '';
            return $(`<span class="select2-item-wrap">${flag}<span>${item.text}</span></span>`);
        },
        templateSelection: function(item) {
            if (!item.id || item.id === '-1') return item.text;
            let flag = item.bandeira ? `<img src="/images/bandeiras/${item.bandeira}" class="select2-item-flag">` : '';
            return $(`<span class="select2-item-wrap">${flag}<span>${item.text}</span></span>`);
        },
        escapeMarkup: function(m) { return m; }
    }).on('select2:select', function(e) {
        markAsDirty();
        const data = e.params.data;
        const $container = $(this).closest('td, .player-input-container, .player-select-wrapper');
        const $manualInput = $container.find('.manual-player-name');

        if (data.id === '-1') {
            $manualInput.removeClass('d-none').focus();
            $select.empty().append(new Option('➕ Digitar nome (não consta no banco)', '-1', true, true));
        } else {
            $manualInput.addClass('d-none');
            $manualInput.val(data.text);
            
            // Persistir a option no DOM para serialização correta
            $select.empty().append(new Option(data.text, data.id, true, true));
            $select.val(data.id);
        }
    });
}

function initAllPlayerSelects() {
    $('.select-player-api').each(function() {
        initPlayerSelect($(this));
    });
}

let playerIdx = 1000;
function addLineupRow(side) {
    playerIdx++;
    const row = `
    <tr class="lineup-row starter">
        <td style="width: 55px; text-align: center;">
            <select name="lineup[${side}][${playerIdx}][pos]" class="form-input-sm form-pos-select">
                <?php foreach($allPositions as $pos): ?>
                    <option value="<?php echo $pos['Sigla']; ?>"><?php echo $pos['Sigla']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="text-align: left;">
            <div class="player-select-wrapper">
                <select name="lineup[${side}][${playerIdx}][id_jogador]" class="select-player-api" style="width: 100%;">
                    <option value="-1" selected>➕ Digitar nome (não consta no banco)</option>
                </select>
                <input type="text" name="lineup[${side}][${playerIdx}][nome_jogador]" class="form-input-sm manual-player-name" placeholder="Digite o nome do atleta..." style="margin-top: 4px;">
            </div>
        </td>
        <td style="width: 45px; text-align: center;"><input type="text" name="lineup[${side}][${playerIdx}][num]" class="form-input-sm text-center" placeholder="#"></td>
        <td style="width: 45px; text-align: center;">
            <input class="titular-toggle-cb titular-toggle" type="checkbox" name="lineup[${side}][${playerIdx}][titular]" value="1" checked>
        </td>
        <td style="width: 95px; text-align: center;">
            <div class="sub-minutes-container">
                <div class="sub-minute-col">
                    <span class="material-symbols-outlined sub-icon sub-in">arrow_upward</span>
                    <input type="text" name="lineup[${side}][${playerIdx}][sub_in]" class="form-input-xs text-center" placeholder="-">
                </div>
                <div class="sub-minute-col">
                    <span class="material-symbols-outlined sub-icon sub-out">arrow_downward</span>
                    <input type="text" name="lineup[${side}][${playerIdx}][sub_out]" class="form-input-xs text-center" placeholder="-">
                </div>
            </div>
        </td>
        <td style="width: 35px; text-align: center;">
            <button type="button" class="btn-row-action btn-remove" title="Remover atleta">
                <span class="material-symbols-outlined">close</span>
            </button>
        </td>
    </tr>`;
    
    $(`#lineup${side}Container`).append(row);
    const $newSelect = $(`#lineup${side}Container tr:last .select-player-api`);
    initPlayerSelect($newSelect, side);
    markAsDirty();
}
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
