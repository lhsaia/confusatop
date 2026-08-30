<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

$page_title = "Editar Partida (Clubes)";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = "home_redesign";
$extra_css = "jogos_clubes_redesign";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$match_info = null;
$events = [];
$lineup = [];

if($match_id > 0) {
    $match_info = $jogo->getSingleMatchInfo($match_id);
    if($match_info) {
        $stmtEvents = $db->prepare("SELECT * FROM jogos_clube_eventos WHERE id_jogo = ? ORDER BY tempo ASC, minutos ASC");
        $stmtEvents->execute([$match_id]);
        $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

        $stmtLineup = $db->prepare("SELECT * FROM jogos_clube_escalacao WHERE id_partida = ?");
        $stmtLineup->execute([$match_id]);
        $lineup = $stmtLineup->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Pré-processamento da escalação
$teamLineups = [1 => [], 2 => []]; 
$coaches = [1 => [], 2 => []];
if($match_info) {
    $timeA_id = (int)$match_info['timeA_id'];
    $timeB_id = (int)$match_info['timeB_id'];
    
    foreach($lineup as $p) {
        $pTeamId = (int)$p['id_time'];
        $tId = 0;
        
        if($timeA_id > 0 && $pTeamId === $timeA_id) {
            $tId = 1;
        } elseif($timeB_id > 0 && $pTeamId === $timeB_id) {
            $tId = 2;
        } elseif(isset($p['lado']) && ($p['lado'] == 'A' || $p['lado'] == '1')) {
            $tId = 1;
        } elseif(isset($p['lado']) && ($p['lado'] == 'B' || $p['lado'] == '2')) {
            $tId = 2;
        }
        
        // Fallback se id_time for 0 ou manual
        if($tId === 0) {
            if(count($teamLineups[1]) + count($coaches[1]) < 18) {
                $tId = 1;
            } else {
                $tId = 2;
            }
        }
        
        if($tId > 0) {
            if($p['posicao'] == 'S' && $p['id_jogador'] == 0 && (stripos($p['nome_jogador'], 'Tecnico') !== false || stripos($p['nome_jogador'], 'Técnico') !== false)) {
                $p['posicao'] = 'T';
            }
            if($p['posicao'] == 'T') { $coaches[$tId][] = $p; } 
            else { $teamLineups[$tId][] = $p; }
        }
    }
}

$stmtPos = $db->prepare("SELECT Sigla, Nome FROM posicoes ORDER BY ID ASC");
$stmtPos->execute();
$allPositions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

$hasCoachPos = false;
foreach($allPositions as $pos) { if($pos['Sigla'] == 'T') { $hasCoachPos = true; break; } }
if(!$hasCoachPos) { array_push($allPositions, ['Sigla' => 'T', 'Nome' => 'Técnico']); }

/**
 * Função de renderização mantendo seus ícones originais de substituição
 */
function renderPlayerRow($side, $idx, $p, $allPositions, $isCoach = false) {
    $prefix = $isCoach ? "coach_$idx" : $idx;
    $isManual = (empty($p['id_jogador']) || $p['id_jogador'] <= 0);
    $rowClass = $isCoach ? 'starter' : ($p['titular'] ? 'starter' : 'bench');
    ?>
    <tr class="lineup-row <?php echo $rowClass; ?>">
        <td class="px-1 text-center">
            <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][pos]" class="form-control form-control-sm px-1 text-center fw-bold" style="font-size: 0.75rem; border-radius: 6px;">
                <?php foreach($allPositions as $pos): ?>
                    <option value="<?php echo $pos['Sigla']; ?>" <?php echo (strcasecmp((string)($p['posicao'] ?? ''), (string)$pos['Sigla']) === 0) ? 'selected' : ''; ?>><?php echo $pos['Sigla']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][id_jogador]" class="form-control form-control-sm select-player-api" <?php echo $isCoach ? 'data-is-coach="1"' : ''; ?>>
                <option value="-1">--- NÃO CONSTA NO BANCO ---</option>
                <?php if(!$isManual): ?>
                    <option value="<?php echo $p['id_jogador']; ?>" selected><?php echo htmlspecialchars($p['nome_jogador']); ?></option>
                <?php endif; ?>
            </select>
            <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][nome_jogador]" class="form-control form-control-sm manual-player-name <?php echo !$isManual ? 'd-none' : ''; ?>" value="<?php echo htmlspecialchars($p['nome_jogador'] ?? ''); ?>" placeholder="Nome">
        </td>
        <td class="px-1"><input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][num]" class="form-control form-control-sm text-center px-1" value="<?php echo $p['numero'] ?? ''; ?>" placeholder="#"></td>
        <td class="text-center">
            <input class="form-check-input titular-toggle" type="checkbox" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][titular]" value="1" <?php echo ($p['titular'] ?? false) || $isCoach ? 'checked' : ''; ?> <?php echo $isCoach ? 'disabled' : ''; ?>>
        </td>
        <td>
            <?php if(!$isCoach): ?>
            <div class="sub-minutes-container justify-content-center">
                <div class="flex-column d-flex align-items-center">
                    <span class="material-symbols-outlined sub-icon sub-in mb-1">arrow_upward</span>
                    <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][sub_in]" class="form-control form-control-xs text-center" value="<?php echo $p['entrada_minuto'] ?? ''; ?>">
                </div>
                <div class="flex-column d-flex align-items-center">
                    <span class="material-symbols-outlined sub-icon sub-out mb-1">arrow_downward</span>
                    <input type="text" name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][sub_out]" class="form-control form-control-xs text-center" value="<?php echo $p['saida_minuto'] ?? ''; ?>">
                </div>
            </div>
            <?php endif; ?>
        </td>
        <td class="text-center"><span class="material-symbols-outlined text-danger cursor-pointer btn-remove">close</span></td>
    </tr>
    <?php
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .editor-card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); padding: 22px; margin-bottom: 25px; border: 1px solid rgba(0,0,0,0.08); }
    .section-title { font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: 18px; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 8px; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 0.5px; }
    .section-title i, .section-title .material-symbols-outlined { margin-right: 8px; color: #0284c7; font-size: 1.1rem; }
    .form-label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 5px; display: block; }
    .team-editor-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); }
    .team-crest-preview { width: 60px; height: 60px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 2px 6px rgba(0,0,0,0.05); flex-shrink: 0; overflow: hidden; }
    .team-crest-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .lineup-row { border-left: 4px solid transparent; transition: 0.2s; border-bottom: 1px solid rgba(0,0,0,0.04) !important; }
    .lineup-row.starter { border-left-color: #10b981; background-color: #fff; }
    .lineup-row.bench { border-left-color: #f59e0b; background-color: #fcfcfc; opacity: 0.95; }
    .sub-minutes-container { display: flex; align-items: center; gap: 4px; }
    .sub-icon { font-size: 0.75rem; width: 14px; text-align: center; }
    .sub-in { color: #10b981; }
    .sub-out { color: #ef4444; }
    .form-control-xs { padding: 0 !important; height: 26px !important; width: 36px !important; font-size: 0.75rem !important; border-radius: 6px !important; }
    .cursor-pointer { cursor: pointer; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .new-row { animation: fadeIn 0.3s ease-out; }
    .sticky-header {
        position: -webkit-sticky;
        position: sticky;
        top: 60px;
        z-index: 100;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        padding: 14px 22px;
        margin-bottom: 25px;
        border: 1px solid rgba(0,0,0,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: nowrap;
    }
    .unsaved-badge {
        font-size: 0.75rem;
        font-weight: 700;
        color: #ef4444;
        background: #fee2e2;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-right: 15px;
        display: none;
        align-items: center;
        border: 1px solid #fca5a5;
    }
    .unsaved-badge span { margin-right: 5px; font-size: 1rem; }
    
    #lineupTabs .nav-link {
        border-radius: 10px !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 600 !important;
        color: #64748b !important;
        background: #f1f5f9 !important;
        border: 1px solid rgba(0,0,0,0.06) !important;
        transition: all 0.2s ease !important;
    }
    #lineupTabs .nav-link.active {
        background: #0284c7 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
    }
    
    .select2-container--bootstrap4 .select2-selection {
        border-radius: 8px !important;
        border: 1px solid rgba(0,0,0,0.15) !important;
    }
    .select2-container--bootstrap4.select2-container--focus .select2-selection {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 0.2rem rgba(2, 132, 199, 0.15) !important;
    }

    /* Segmented Control para Tipo de Competição (Liga / Copa) */
    .comp-type-toggle-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        gap: 4px;
        width: 100%;
    }
    .comp-type-toggle-group input[type="radio"] {
        display: none;
    }
    .comp-toggle-label {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 12px;
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }
    .comp-toggle-label span.material-symbols-outlined {
        font-size: 1.15rem;
        transition: transform 0.2s ease;
    }
    .comp-toggle-label:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.6);
    }
    .comp-type-toggle-group input[type="radio"]:checked + .comp-toggle-label {
        background: #ffffff;
        color: #0284c7;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        font-weight: 700;
    }
    .comp-type-toggle-group input[type="radio"]:checked + .comp-toggle-label span.material-symbols-outlined {
        color: #0284c7;
        transform: scale(1.1);
    }

    #toast-container { position: fixed; top: 80px; right: 20px; z-index: 9999; }
    .custom-toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        padding: 15px 20px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
        border-left: 5px solid #10b981;
    }
    .custom-toast.error { border-left-color: #ef4444; }
    .custom-toast.warning { border-left-color: #f59e0b; }
    .toast-icon { margin-right: 15px; font-size: 1.2rem; }
    .toast-content { flex-grow: 1; }
    .toast-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 2px; font-family: 'Outfit', sans-serif; }
    .toast-message { font-size: 0.85rem; color: #64748b; }
    .toast-close { cursor: pointer; opacity: 0.5; font-size: 1.1rem; }
    .toast-close:hover { opacity: 1; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { to { transform: translateX(100%); opacity: 0; } }
    .spin { animation: spin 2s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div id="toast-container"></div>

<div class="clubes-container" style="max-width: 1360px;">
    <form id="matchForm" action="salvar_jogo.php" method="POST">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        
        <div class="sticky-header">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.6rem;">sports_soccer</span>
                <h4 class="fw-bold mb-0 text-dark text-nowrap" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;"><?php echo $match_id ? "Editar Partida #".$match_id : "Nova Partida"; ?></h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="unsaved-badge" id="unsavedBadge">
                    <span class="material-symbols-outlined">error</span> Alterações não salvas
                </div>
                <a href="index.php" class="btn-clubes-secondary mr-2 me-2" style="white-space: nowrap; padding: 7px 16px;">CANCELAR</a>
                <button type="submit" class="btn-clubes-primary" style="white-space: nowrap; padding: 7px 18px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem; margin-right: 4px;">save</span>
                    SALVAR PARTIDA
                </button>
            </div>
        </div>

        <!-- 1. Dados e Metadados da Partida -->
        <div class="editor-card mb-4">
            <h5 class="section-title"><span class="material-symbols-outlined">emoji_events</span> Dados da Partida</h5>
            <div class="row align-items-end">
                <div class="col-lg-4 col-md-6 mb-3">
                    <label class="form-label">Competição</label>
                    <div class="comp-type-toggle-group mb-2">
                        <input type="radio" class="comp-type" name="competicao_tipo" id="typeLiga" value="0" <?php echo (!$match_info || $match_info['competicao_tipo'] == 0) ? 'checked' : ''; ?>>
                        <label class="comp-toggle-label" for="typeLiga">
                            <span class="material-symbols-outlined">table_chart</span>
                            <span>Liga</span>
                        </label>

                        <input type="radio" class="comp-type" name="competicao_tipo" id="typeCopa" value="1" <?php echo ($match_info && $match_info['competicao_tipo'] == 1) ? 'checked' : ''; ?>>
                        <label class="comp-toggle-label" for="typeCopa">
                            <span class="material-symbols-outlined">emoji_events</span>
                            <span>Copa</span>
                        </label>
                    </div>
                    <select name="competicao_id" id="selectComp" class="form-control" required>
                        <?php if($match_info): ?>
                            <option value="<?php echo $match_info['competicao_id']; ?>" selected><?php echo $match_info['competition_name']; ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <label class="form-label">Fase</label>
                    <select name="fase" class="form-control" style="height: 38px;">
                        <?php 
                        $fases = [0=>'N/A', 1=>'Fase pré', 2=>'Fase de grupos', 3=>'Oitavas', 4=>'Quartas', 5=>'Semi', 6=>'3º Lugar', 7=>'Repescagem', 8=>'Final', 9=>'16-avos', 10=>'32-avos'];
                        foreach($fases as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($match_info && $match_info['phase'] == $id) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Data do Jogo</label>
                    <?php $valData = ($match_info && $match_info['data']) ? $match_info['data'] : date('Y-m-d'); ?>
                    <input type="date" name="data" class="form-control" style="height: 38px;" value="<?php echo $valData; ?>" required>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Estádio</label>
                    <select name="estadio_id" id="selectStadium" class="form-control">
                        <?php if($match_info && ($match_info['estadio_id'] || $match_info['estadio'])): ?>
                            <option value="<?php echo $match_info['estadio_id'] ? $match_info['estadio_id'] : '0'; ?>" selected><?php echo $match_info['estadio']; ?></option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="estadio_nome" id="estadio_nome" value="<?php echo $match_info ? $match_info['estadio'] : ''; ?>">
                </div>
            </div>
        </div>

        <!-- 2. Confronto e Placar Principal -->
        <div class="editor-card mb-4">
            <h5 class="section-title"><span class="material-symbols-outlined">scoreboard</span> Confronto e Placar</h5>
            <div class="row align-items-center">
                
                <!-- Mandante (Time A) -->
                <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge" style="background: #0284c7; color:#fff; font-size:0.75rem; font-weight:700;">MANDANTE (TIME A)</span>
                            <div class="form-check m-0">
                                <input class="form-check-input manual-toggle" type="checkbox" id="manualA" data-side="A" <?php echo (!$match_info || !$match_info['timeA_id']) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="manualA">Digitar nome manual</label>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="team-crest-preview <?php echo (!$match_info || !$match_info['timeA_id']) ? 'd-none' : ''; ?>" id="crestA">
                                <?php if($match_info && $match_info['timeA_bandeira']): ?>
                                    <img src="/images/escudos/<?php echo $match_info['timeA_bandeira']; ?>" alt="Escudo">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-muted" style="font-size:2em;">shield</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="search-portal-container <?php echo (!$match_info || !$match_info['timeA_id']) ? 'd-none' : ''; ?>" id="searchA">
                                    <select name="timeA_id" id="selectTimeA" class="form-control team-select" data-side="A">
                                        <option value="-1">--- NÃO CONSTA NO BANCO ---</option>
                                        <?php if($match_info && $match_info['timeA_id']): ?>
                                            <option value="<?php echo $match_info['timeA_id']; ?>" selected><?php echo $match_info['timeA_nome']; ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <input type="text" name="timeA_nome" id="nameA" class="form-control <?php echo ($match_info && $match_info['timeA_id']) ? 'd-none' : ''; ?>" placeholder="Nome do Clube Mandante" value="<?php echo $match_info ? $match_info['timeA_nome'] : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label text-center">Gols Mandante</label>
                                <input type="number" min="0" name="timeA_gols" class="form-control text-center fw-bold fs-5" value="<?php echo $match_info ? $match_info['timeA_gols'] : 0; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-center">Pênaltis (Opcional)</label>
                                <input type="number" min="0" name="timeA_penaltis" class="form-control text-center" value="<?php echo $match_info ? $match_info['timeA_penaltis'] : ''; ?>" placeholder="-">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Centro: VS -->
                <div class="col-lg-2 col-md-12 text-center my-2 my-lg-0">
                    <div style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; font-weight: 900; color: #94a3b8;">
                        VS
                    </div>
                    <span class="badge bg-light text-muted border">Placar Final</span>
                </div>

                <!-- Visitante (Time B) -->
                <div class="col-lg-5 col-md-12">
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06);">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge" style="background: #475569; color:#fff; font-size:0.75rem; font-weight:700;">VISITANTE (TIME B)</span>
                            <div class="form-check m-0">
                                <input class="form-check-input manual-toggle" type="checkbox" id="manualB" data-side="B" <?php echo (!$match_info || !$match_info['timeB_id']) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="manualB">Digitar nome manual</label>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="team-crest-preview <?php echo (!$match_info || !$match_info['timeB_id']) ? 'd-none' : ''; ?>" id="crestB">
                                <?php if($match_info && $match_info['timeB_bandeira']): ?>
                                    <img src="/images/escudos/<?php echo $match_info['timeB_bandeira']; ?>" alt="Escudo">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-muted" style="font-size:2em;">shield</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="search-portal-container <?php echo (!$match_info || !$match_info['timeB_id']) ? 'd-none' : ''; ?>" id="searchB">
                                    <select name="timeB_id" id="selectTimeB" class="form-control team-select" data-side="B">
                                        <option value="-2">--- NÃO CONSTA NO BANCO ---</option>
                                        <?php if($match_info && $match_info['timeB_id']): ?>
                                            <option value="<?php echo $match_info['timeB_id']; ?>" selected><?php echo $match_info['timeB_nome']; ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <input type="text" name="timeB_nome" id="nameB" class="form-control <?php echo ($match_info && $match_info['timeB_id']) ? 'd-none' : ''; ?>" placeholder="Nome do Clube Visitante" value="<?php echo $match_info ? $match_info['timeB_nome'] : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label text-center">Gols Visitante</label>
                                <input type="number" min="0" name="timeB_gols" class="form-control text-center fw-bold fs-5" value="<?php echo $match_info ? $match_info['timeB_gols'] : 0; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-center">Pênaltis (Opcional)</label>
                                <input type="number" min="0" name="timeB_penaltis" class="form-control text-center" value="<?php echo $match_info ? $match_info['timeB_penaltis'] : ''; ?>" placeholder="-">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- 3. Lances e Eventos da Partida (Full-width) -->
        <div class="editor-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="section-title mb-0"><span class="material-symbols-outlined">timer</span> Lances & Eventos da Partida</h5>
                    <p class="text-muted small mb-0 mt-1">Gols e cartões da partida. O minuto é opcional (pode deixar em branco caso não saiba o minuto exato).</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="addEventRow" onclick="addNewEventRow()" style="border-radius:8px; font-size:0.75rem; padding: 6px 14px;">
                    <span class="material-symbols-outlined align-middle" style="font-size:1rem;">add</span> Adicionar Lance
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="eventsTable">
                    <thead class="bg-light small">
                        <tr style="border-bottom: 2px solid #eee;">
                            <th width="12%" class="text-center">Minuto</th>
                            <th width="20%">Time</th>
                            <th width="20%">Tipo de Lance</th>
                            <th width="43%">Jogador Envolvido</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($events as $index => $ev): 
                            $sideEv = ($ev['id_time'] == $match_info['timeA_id'] || $ev['nome_time'] == $match_info['timeA_nome']) ? 'A' : 'B';
                            $evMin = ($ev['minutos'] !== null && $ev['minutos'] !== '') ? $ev['minutos'] : '';
                        ?>
                            <tr>
                                <td><input type="number" name="events[<?php echo $index; ?>][minutos]" class="form-control form-control-sm text-center" value="<?php echo $evMin; ?>" placeholder="-"></td>
                                <td>
                                    <select name="events[<?php echo $index; ?>][side]" class="form-control form-control-sm event-side">
                                        <option value="A" <?php echo $sideEv == 'A' ? 'selected' : ''; ?>>Time A (Mandante)</option>
                                        <option value="B" <?php echo $sideEv == 'B' ? 'selected' : ''; ?>>Time B (Visitante)</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="events[<?php echo $index; ?>][tipo]" class="form-control form-control-sm">
                                        <option value="1" <?php echo $ev['tipo'] == 1 ? 'selected' : ''; ?>>Gol</option>
                                        <option value="4" <?php echo $ev['tipo'] == 4 ? 'selected' : ''; ?>>Gol Contra</option>
                                        <option value="2" <?php echo $ev['tipo'] == 2 ? 'selected' : ''; ?>>Cartão Amarelo</option>
                                        <option value="3" <?php echo $ev['tipo'] == 3 ? 'selected' : ''; ?>>Cartão Vermelho</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="player-input-container" data-side="<?php echo $sideEv; ?>">
                                        <select name="events[<?php echo $index; ?>][id_jogador]" class="form-control form-control-sm select-player-api">
                                            <option value="-1">--- NÃO CONSTA NO BANCO ---</option>
                                            <?php if($ev['id_jogador']): ?><option value="<?php echo $ev['id_jogador']; ?>" selected><?php echo $ev['nome_jogador']; ?></option><?php endif; ?>
                                        </select>
                                        <input type="text" name="events[<?php echo $index; ?>][nome_jogador]" class="form-control form-control-sm manual-player-name <?php echo $ev['id_jogador'] ? 'd-none' : ''; ?>" value="<?php echo $ev['nome_jogador']; ?>" placeholder="Nome do Jogador">
                                    </div>
                                </td>
                                <td class="text-center"><span class="material-symbols-outlined text-danger btn-remove cursor-pointer" style="font-size:1.1rem;">delete</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Escalações dos Dois Times (Lado a Lado - Sempre Visíveis) -->
        <div class="row">
            
            <?php foreach(['A' => ['idx' => 1, 'label' => 'MANDANTE (TIME A)', 'badge' => 'Elenco Mandante', 'bg' => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)'], 
                           'B' => ['idx' => 2, 'label' => 'VISITANTE (TIME B)', 'badge' => 'Elenco Visitante', 'bg' => 'linear-gradient(135deg, #475569 0%, #334155 100%)']] as $side => $tMeta): 
                $tIdx = $tMeta['idx'];
            ?>
            <div class="col-lg-6 mb-4">
                <div class="editor-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-2 px-3 rounded-3 shadow-sm" style="background: <?php echo $tMeta['bg']; ?>; color: #fff;">
                            <h6 class="mb-0 fw-bold d-flex align-items-center">
                                <span class="material-symbols-outlined me-2" style="font-size: 1.15rem;">shield</span> 
                                <?php echo $tMeta['label']; ?>
                            </h6>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 8px; font-size: 0.75rem; padding: 6px 12px;" onclick="addLineupRow('<?php echo $side; ?>')">
                            <span class="material-symbols-outlined align-middle" style="font-size: 1rem;">add</span> Adicionar Atleta
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-light small">
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #eee;">
                                    <th width="10%" class="text-center">POS</th>
                                    <th width="45%">JOGADOR</th>
                                    <th width="8%" class="text-center">Nº</th>
                                    <th width="8%" class="text-center">TIT</th>
                                    <th width="24%" class="text-center">SUB (▲ / ▼)</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="lineup<?php echo $side; ?>Container">
                                <?php 
                                foreach($teamLineups[$tIdx] as $idx => $p) renderPlayerRow($side, $idx, $p, $allPositions);
                                if(count($coaches[$tIdx]) > 0): ?>
                                    <tr class="bg-light"><td colspan="6" class="p-1 px-3 small fw-bold text-muted text-center">COMISSÃO TÉCNICA</td></tr>
                                    <?php foreach($coaches[$tIdx] as $cIdx => $pc) renderPlayerRow($side, "coach_$cIdx", $pc, $allPositions, true); ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
    const isManual = $('#manual' + side).is(':checked');
    const row = `<tr class="new-row">
        <td><input type="number" name="events[${eventIdx}][minutos]" class="form-control form-control-sm text-center" placeholder="-"></td>
        <td><select name="events[${eventIdx}][side]" class="form-control form-control-sm event-side"><option value="A">Time A (Mandante)</option><option value="B">Time B (Visitante)</option></select></td>
        <td><select name="events[${eventIdx}][tipo]" class="form-control form-control-sm"><option value="1">Gol</option><option value="4">Gol Contra</option><option value="2">Cartão Amarelo</option><option value="3">Cartão Vermelho</option></select></td>
        <td><div class="player-input-container" data-side="A"><select name="events[${eventIdx}][id_jogador]" class="form-control form-control-sm select-player-api"><option value="-1">--- NÃO CONSTA NO BANCO ---</option></select><input type="text" name="events[${eventIdx}][nome_jogador]" class="form-control form-control-sm manual-player-name d-none" placeholder="Nome do Jogador"></div></td>
        <td class="text-center"><span class="material-symbols-outlined text-danger btn-remove cursor-pointer" style="font-size:1.1rem;">delete</span></td>
    </tr>`;
    $('#eventsTable tbody').append(row);
    
    const $newSelect = $('#eventsTable tbody tr:last .select-player-api');
    if(!isManual) initPlayerSelect($newSelect, 'A');
    else $newSelect.val('-1').trigger('change');
    
    eventIdx++;
    if(typeof markAsDirty === 'function') markAsDirty();
}

$(document).ready(function() {
    initMainSelects();
    initAllPlayerSelects();
    
    // --- DIRTY STATE TRACKING ---
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

    // Monitor changes on all inputs, textareas, and selects
    $(document).on('change input', 'input, textarea, select', function() {
        markAsDirty();
    });

    // Special handling for Select2 changes (since they don't always bubble 'input')
    $(document).on('select2:select select2:unselect', function() {
        markAsDirty();
    });

    // Warn on exit
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = ''; // Chrome requires returnValue to be set
        }
    });

    // --- AJAX SAVE ---
    $('#matchForm').submit(function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined spin">progress_activity</span> SALVANDO...');

        // Append ajax flag
        const formData = $(this).serialize() + '&ajax=1';

        $.post($(this).attr('action'), formData, function(response) {
            if(response.success) {
                markAsClean();
                showToast('Partida salva com sucesso!', 'success');
                
                if(response.match_id) {
                    $('input[name="match_id"]').val(response.match_id);
                    $('.sticky-header h4').text("Editar Partida #" + response.match_id);
                }
            } else {
                showToast('Erro ao salvar: ' + (response.message || 'Erro desconhecido'), 'error');
            }
        }, 'json')
        .fail(function(xhr) {
             showToast('Erro de comunicação com o servidor.', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });

    $(document).on('click', '.btn-remove', function() {
        if(confirm('Remover?')) {
            $(this).closest('tr').remove();
            markAsDirty();
        }
    });

    $(document).on('change', '.titular-toggle', function() {
        $(this).closest('tr').toggleClass('starter', this.checked).toggleClass('bench', !this.checked);
    });

    $('.manual-toggle').change(function() {
        const side = $(this).data('side');
        const isManual = $(this).is(':checked');
        $(`#search${side}, #crest${side}`).toggleClass('d-none', isManual);
        $(`#name${side}`).toggleClass('d-none', !isManual);
        
        const manualId = side === 'A' ? '-1' : '-2';
        if(isManual) $(`#selectTime${side}`).val(manualId).trigger('change');
        refreshInputsBySide(side, isManual);
    });

    $('.comp-type').change(function() {
        $('#selectComp').val(null).trigger('change');
    });

    $(document).on('click', '#addEventRow', function(e) {
        e.preventDefault();
        addNewEventRow();
    });

    $(document).on('change', '.event-side', function() {
        const side = $(this).val();
        const container = $(this).closest('tr').find('.player-input-container');
        container.attr('data-side', side);
        initPlayerSelect(container.find('.select-player-api'), side);
    });

    $(document).on('change', '.select-player-api', function() {
        const isManual = $(this).val() == '-1';
        $(this).siblings('.manual-player-name').toggleClass('d-none', !isManual);
    });

    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        initAllPlayerSelects();
    });

    $(document).on('change', 'select[name*="[pos]"]', function() {
        const isCoach = $(this).val() === 'T';
        const playerSelect = $(this).closest('tr').find('.select-player-api');
        playerSelect.attr('data-is-coach', isCoach ? '1' : '0');
        
        const side = getRowSide(playerSelect);
        initPlayerSelect(playerSelect, side);
    });
});

    function formatTeam (state) {
        if (!state.id) {
            return state.text;
        }
        var escudo = state.escudo ? state.escudo : '0.png';
        var bandeira = state.bandeira ? state.bandeira : '0.png';
        
        var $state = $(
            '<span><img src="/images/escudos/' + escudo + '" class="img-flag shadow-sm" style="width: 20px; height: 20px; object-fit: contain; margin-right: 8px;" /> ' +
            '<img src="/images/bandeiras/' + bandeira + '" class="img-flag shadow-sm border" style="width: 15px; height: 11px; margin-right: 8px;" /> ' +
            state.text + '</span>'
        );
        return $state;
    }

    function formatPlayer (state) {
        if (!state.id) {
            return state.text;
        }
        var bandeira = state.bandeira ? state.bandeira : '0.png';
        
        var $state = $(
            '<span><img src="/images/bandeiras/' + bandeira + '" class="img-flag shadow-sm border" style="width: 15px; height: 11px; margin-right: 8px;" /> ' +
            state.text + '</span>'
        );
        return $state;
    }

    function initMainSelects() {
        $('#selectStadium').select2({ theme: 'bootstrap4', ajax: { url: '/api/estadio/buscar.php', dataType: 'json' } }).on('select2:select', e => $('#estadio_nome').val(e.params.data.text));
        $('#selectComp').select2({ theme: 'bootstrap4', ajax: { url: '/api/competicao/buscar.php', dataType: 'json', data: p => ({ q: p.term, type: $('.comp-type:checked').val() }) } });
        $('.team-select').each(function() {
            const side = $(this).data('side');
            $(this).select2({ 
                theme: 'bootstrap4', 
                ajax: { 
                    url: '/api/clube/buscar.php', 
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.results }; // API returns { results: [...] }
                    }
                },
                placeholder: 'Buscar time...',
                allowClear: true,
                templateResult: formatTeam,
                escapeMarkup: function(m) { return m; }
            }).on('select2:select', function(e) {
                markAsDirty();
                if(e.params.data.id != '-1' && e.params.data.id != '-2') {
                    $(`#crest${side}`).removeClass('d-none').html(`<img src="/images/escudos/${e.params.data.escudo || 'sem_escudo.png'}">`);
                    $(`#name${side}`).val(e.params.data.text).addClass('d-none');
                    $(`#manual${side}`).prop('checked', false);
                } else {
                    handleManualTeamSelection(side);
                }
            }).on('select2:clear', function() {
                handleManualTeamSelection(side);
            });
        });
    }

function handleManualTeamSelection(side) {
    $(`#crest${side}, #search${side}`).addClass('d-none');
    $(`#name${side}`).removeClass('d-none');
    $(`#manual${side}`).prop('checked', true);
    
    const manualId = side === 'A' ? '-1' : '-2';
    if ($(`#selectTime${side}`).val() != manualId) {
        $(`#selectTime${side}`).val(manualId);
    }
}

function getRowSide($el) {
    // Check for explicit data-side (used in events)
    const sideAttr = $el.closest('[data-side]').attr('data-side');
    if (sideAttr) return sideAttr;
    
    // Check for lineup container (A or B)
    const tbodyId = $el.closest('tbody').attr('id') || '';
    if (tbodyId.includes('A')) return 'A';
    if (tbodyId.includes('B')) return 'B';
    
    // Fallback?
    return 'A'; 
}

function initAllPlayerSelects() {
    $('.select-player-api').each(function() {
        const side = getRowSide($(this));
        if(!$(this).hasClass('d-none')) initPlayerSelect($(this), side);
    });
}

function initPlayerSelect($el, side) {
    const teamId = $(`#selectTime${side}`).val();
    if(!teamId) return;

    // Destroy existing select2 to ensure it picks up new teamId/apiUrl
    if ($el.data('select2')) {
        $el.select2('destroy');
    }

    const isCoach = $el.attr('data-is-coach') === '1';
    const apiUrl = isCoach ? '/api/clube/tecnicos.php' : '/api/clube/jogadores.php';
    const placeholderText = isCoach ? 'Buscar técnico...' : 'Buscar jogador...';
    
    $el.select2({ 
        theme: 'bootstrap4', 
        width: '100%',
        placeholder: placeholderText,
        allowClear: true,
        ajax: { 
            url: apiUrl, 
            dataType: 'json',
            delay: 250,
            data: function(params) {
                // console.log("Searching API with term:", params.term);
                return { 
                    q: params.term, 
                    team_id: teamId 
                };
            }, 
            processResults: d => {
                // If the response is a direct array (our API), wrap it in results
                // If it already has results key, use it.
                // Our API returns an array (either flat players or optgroups)
                return { results: d };
            }
        },
        templateResult: isCoach ? null : formatPlayer, // Only format players for now
        templateSelection: isCoach ? null : formatPlayer,
        escapeMarkup: function(m) { return m; }
    }).on('select2:select', function(e) {
        $(this).siblings('.manual-player-name').val(e.params.data.text).addClass('d-none');
    }).on('select2:clear', function() {
        $(this).val('-1').trigger('change');
    });
}

function refreshInputsBySide(side, isManual) {
    const targetContainer = side === 'A' ? ($('#lineupAContainer').add($('.player-input-container[data-side="A"]'))) : ($('#lineupBContainer').add($('.player-input-container[data-side="B"]')));
    targetContainer.find('.select-player-api').each(function() {
        if (isManual) {
            $(this).val('-1').trigger('change');
        } else {
            initPlayerSelect($(this), side);
        }
    });
}

let lineupIdx = {A: <?php echo count($teamLineups[1]); ?>, B: <?php echo count($teamLineups[2]); ?>};
function addLineupRow(side) {
    const idx = lineupIdx[side]++;
    const isManual = $(`#manual${side}`).is(':checked');
    const row = `<tr class="lineup-row starter new-row">
        <td class="px-1"><select name="lineup[${side}][${idx}][pos]" class="form-control form-control-sm px-1 text-center fw-bold" style="font-size: 0.7rem;">
            <?php foreach($allPositions as $pos) echo "<option value='{$pos['Sigla']}'>{$pos['Sigla']}</option>"; ?>
        </select></td>
        <td><select name="lineup[${side}][${idx}][id_jogador]" class="form-control form-control-sm select-player-api"><option value="-1">--- NÃO CONSTA NO BANCO ---</option></select><input type="text" name="lineup[${side}][${idx}][nome_jogador]" class="form-control form-control-sm manual-player-name" placeholder="Nome"></td>
        <td class="px-1"><input type="text" name="lineup[${side}][${idx}][num]" class="form-control form-control-sm text-center"></td>
        <td class="text-center"><input class="form-check-input titular-toggle" type="checkbox" name="lineup[${side}][${idx}][titular]" value="1" checked></td>
        <td><div class="sub-minutes-container justify-content-center">
            <div class="flex-column d-flex align-items-center"><span class="material-symbols-outlined sub-icon sub-in mb-1">arrow_upward</span><input type="text" name="lineup[${side}][${idx}][sub_in]" class="form-control form-control-xs text-center"></div>
            <div class="flex-column d-flex align-items-center"><span class="material-symbols-outlined sub-icon sub-out mb-1">arrow_downward</span><input type="text" name="lineup[${side}][${idx}][sub_out]" class="form-control form-control-xs text-center"></div>
        </div></td>
        <td class="text-center"><span class="material-symbols-outlined text-danger cursor-pointer btn-remove">close</span></td>
    </tr>`;
    $(`#lineup${side}Container`).append(row);
    if(!isManual) initPlayerSelect($(`#lineup${side}Container tr:last .select-player-api`), side);
}

function showToast(message, type = 'success') {
    const icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
    const title = type === 'success' ? 'Sucesso' : (type === 'error' ? 'Erro' : 'Informação');
    const colorClass = type; // success, error, warning
    
    const toastHtml = `
        <div class="custom-toast ${colorClass}">
            <div class="toast-icon text-${type === 'error' ? 'danger' : 'success'}"><span class="material-symbols-outlined">${icon}</span></div>
            <div class="toast-content">
                <div class="toast-title text-${type === 'error' ? 'danger' : 'success'}">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <div class="toast-close"><span class="material-symbols-outlined">close</span></div>
        </div>
    `;
    
    const $toast = $(toastHtml);
    $('#toast-container').append($toast);
    
    // Auto remove after 5s if success
    if (type === 'success') {
        setTimeout(() => {
            $toast.css('animation', 'fadeOut 0.5s ease-out forwards');
            setTimeout(() => $toast.remove(), 500);
        }, 5000);
    }
    
    $toast.find('.toast-close').click(function() {
        $toast.css('animation', 'fadeOut 0.3s ease-out forwards');
        setTimeout(() => $toast.remove(), 300);
    });
}
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php"); ?>