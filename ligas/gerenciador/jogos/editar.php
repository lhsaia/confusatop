<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

$page_title = "Editar Partida (Clubes)";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "match_editor";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
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
    foreach($lineup as $p) {
        $tId = ($p['id_time'] == $match_info['timeA_id']) ? 1 : (($p['id_time'] == $match_info['timeB_id']) ? 2 : 0);
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
            <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][pos]" class="form-control form-control-sm px-1 text-center fw-bold" style="font-size: 0.7rem;">
                <?php foreach($allPositions as $pos): ?>
                    <option value="<?php echo $pos['Sigla']; ?>" <?php echo (strcasecmp((string)($p['posicao'] ?? ''), (string)$pos['Sigla']) === 0) ? 'selected' : ''; ?>><?php echo $pos['Sigla']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="lineup[<?php echo $side; ?>][<?php echo $prefix; ?>][id_jogador]" class="form-control form-control-sm select-player-api" <?php echo $isCoach ? 'data-is-coach="1"' : ''; ?>>
                <option value="0">--- NÃO CONSTA NO BANCO ---</option>
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

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<style>
    body { background-color: #f4f7f6; color: #333; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    #ranking-container { padding: 30px; max-width: 1400px; margin: 0 auto; }
    .editor-card { background: white; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
    .section-title { font-size: 0.95rem; font-weight: 700; color: #1a1469; margin-bottom: 18px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 0.5px; }
    .section-title i, .section-title .material-symbols-outlined { margin-right: 8px; color: #ff6b6b; font-size: 0.8em; }
    .form-label { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; font-weight: 700; margin-bottom: 5px; display: block; }
    .team-editor-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; background: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid #eee; }
    .team-crest-preview { width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex-shrink: 0; overflow: hidden; }
    .team-crest-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .lineup-row { border-left: 4px solid transparent; transition: 0.2s; border-bottom: 1px solid #eee !important; }
    .lineup-row.starter { border-left-color: #28a745; background-color: #fff; }
    .lineup-row.bench { border-left-color: #ffc107; background-color: #fdfdfd; opacity: 0.9; }
    .sub-minutes-container { display: flex; align-items: center; gap: 4px; }
    .sub-icon { font-size: 0.7rem; width: 14px; text-align: center; }
    .sub-in { color: #28a745; }
    .sub-out { color: #dc3545; }
    .btn-xs { padding: 2px 10px; font-size: 0.65rem; font-weight: 800; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.8px; display: inline-flex; align-items: center; justify-content: center; height: 26px; border: none; font-family: 'Inter', system-ui, -apple-system, sans-serif !important; }
    .btn-save-custom { background-color: #28a745 !important; color: #fff !important; width: auto; }
    .btn-save-custom:hover { background-color: #218838 !important; transform: translateY(-1px); }
    .btn-cancel-custom { background-color: #dc3545 !important; color: #fff !important; width: auto; }
    .btn-cancel-custom:hover { background-color: #c82333 !important; transform: translateY(-1px); }
    .form-control-xs { padding: 0 !important; height: 24px !important; width: 34px !important; font-size: 0.7rem !important; }
    .cursor-pointer { cursor: pointer; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .new-row { animation: fadeIn 0.3s ease-out; }
    .sticky-header {
        position: -webkit-sticky;
        position: sticky;
        top: 50px; /* Adjust based on navbar height */
        z-index: 9;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 12px 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: nowrap;
    }
    .unsaved-badge {
        font-size: 0.75rem;
        font-weight: 700;
        color: #dc3545; /* Bootstrap danger color */
        background: #ffe6e6;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-right: 15px;
        display: none; /* Initially hidden */
        align-items: center;
        border: 1px solid #f1b0b7;
    }
    .unsaved-badge i { margin-right: 5px; }
    
    /* Toast Notifications */
    #toast-container { position: fixed; top: 80px; right: 20px; z-index: 9999; }
    .custom-toast {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 15px 20px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
        border-left: 5px solid #28a745;
    }
    .custom-toast.error { border-left-color: #dc3545; }
    .custom-toast.warning { border-left-color: #ffc107; }
    .toast-icon { margin-right: 15px; font-size: 1.2rem; }
    .toast-content { flex-grow: 1; }
    .toast-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 2px; }
    .toast-message { font-size: 0.8rem; color: #666; }
    .toast-close { cursor: pointer; opacity: 0.5; font-size: 1.1rem; }
    .toast-close:hover { opacity: 1; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { to { transform: translateX(100%); opacity: 0; } }
    .spin { animation: spin 2s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div id="toast-container"></div>


<div id="ranking-container">
    <form id="matchForm" action="salvar_jogo.php" method="POST">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        
        <div class="sticky-header">
            <h4 class="fw-bold mb-0 text-dark text-nowrap" style="letter-spacing: -0.5px;"><?php echo $match_id ? "Editar Partida #".$match_id : "Nova Partida"; ?></h4>
            <div class="d-flex align-items-center">
                <div class="unsaved-badge" id="unsavedBadge">
                    <span class="material-symbols-outlined">error</span> Alterações não salvas
                </div>
                <a href="index.php" class="btn btn-xs btn-cancel-custom shadow-sm mr-2 me-2" style="white-space: nowrap;">CANCELAR</a>
                <button type="submit" class="btn btn-xs btn-save-custom shadow-sm" style="white-space: nowrap;">SALVAR PARTIDA</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="editor-card">
                    <h5 class="section-title"><span class="material-symbols-outlined">info</span> Info Geral</h5>
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Competição</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check m-0">
                                    <input class="form-check-input comp-type" type="radio" name="competicao_tipo" id="typeLiga" value="0" <?php echo (!$match_info || $match_info['competicao_tipo'] == 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="typeLiga">Liga</label>
                                </div>
                                <div class="form-check m-0">
                                    <input class="form-check-input comp-type" type="radio" name="competicao_tipo" id="typeCopa" value="1" <?php echo ($match_info && $match_info['competicao_tipo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="typeCopa">Copa</label>
                                </div>
                            </div>
                            <select name="competicao_id" id="selectComp" class="form-control" required>
                                <?php if($match_info): ?>
                                    <option value="<?php echo $match_info['competicao_id']; ?>" selected><?php echo $match_info['competition_name']; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Fase</label>
                            <select name="fase" class="form-control form-control-sm">
                                <?php 
                                $fases = [0=>'N/A', 1=>'Fase pré', 2=>'Fase de grupos', 3=>'Oitavas', 4=>'Quartas', 5=>'Semi', 6=>'3º Lugar', 7=>'Repescagem', 8=>'Final'];
                                foreach($fases as $id => $name): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($match_info && $match_info['phase'] == $id) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data</label>
                            <?php $valData = ($match_info && $match_info['data']) ? $match_info['data'] : date('Y-m-d'); ?>
                            <input type="date" name="data" class="form-control form-control-sm" value="<?php echo $valData; ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Estádio</label>
                            <select name="estadio_id" id="selectStadium" class="form-control form-control-sm">
                                <?php if($match_info && ($match_info['estadio_id'] || $match_info['estadio'])): ?>
                                    <option value="<?php echo $match_info['estadio_id'] ? $match_info['estadio_id'] : '0'; ?>" selected><?php echo $match_info['estadio']; ?></option>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" name="estadio_nome" id="estadio_nome" value="<?php echo $match_info ? $match_info['estadio'] : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php foreach(['A' => 'Mandante', 'B' => 'Visitante'] as $side => $label): 
                        $teamIdKey = "time{$side}_id";
                        $teamNomeKey = "time{$side}_nome";
                        $teamBandKey = "time{$side}_bandeira";
                        $teamGolsKey = "time{$side}_gols";
                        $teamPenKey = "time{$side}_penaltis";
                    ?>
                    <div class="col-md-6">
                        <div class="editor-card">
                            <h5 class="section-title"><span class="material-symbols-outlined">shield</span> <?php echo $label; ?></h5>
                            <div class="team-editor-header">
                                <div class="team-crest-preview <?php echo (!$match_info || !$match_info[$teamIdKey]) ? 'd-none' : ''; ?>" id="crest<?php echo $side; ?>">
                                    <?php if($match_info && $match_info[$teamBandKey]): ?>
                                        <img src="/images/escudos/<?php echo $match_info[$teamBandKey]; ?>">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-muted" style="font-size:2em;">shield</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="search-portal-container <?php echo (!$match_info || !$match_info[$teamIdKey]) ? 'd-none' : ''; ?>" id="search<?php echo $side; ?>">
                                        <label class="form-label small fw-bold mb-1">Buscar no Portal</label>
                                        <select name="time<?php echo $side; ?>_id" id="selectTime<?php echo $side; ?>" class="form-control team-select" data-side="<?php echo $side; ?>">
                                            <option value="<?php echo $side == 'A' ? '-1' : '-2'; ?>">--- NÃO CONSTA NO BANCO ---</option>
                                            <?php if($match_info && $match_info[$teamIdKey]): ?>
                                                <option value="<?php echo $match_info[$teamIdKey]; ?>" selected><?php echo $match_info[$teamNomeKey]; ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input manual-toggle" type="checkbox" id="manual<?php echo $side; ?>" data-side="<?php echo $side; ?>" <?php echo (!$match_info || !$match_info[$teamIdKey]) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small" for="manual<?php echo $side; ?>">Nome manual</label>
                                    </div>
                                    <input type="text" name="time<?php echo $side; ?>_nome" id="name<?php echo $side; ?>" class="form-control mt-2 <?php echo ($match_info && $match_info[$teamIdKey]) ? 'd-none' : ''; ?>" placeholder="Nome do Time" value="<?php echo $match_info ? $match_info[$teamNomeKey] : ''; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Gols</label>
                                    <input type="number" name="time<?php echo $side; ?>_gols" class="form-control text-center fw-bold" value="<?php echo $match_info ? $match_info[$teamGolsKey] : 0; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Pênaltis</label>
                                    <input type="number" name="time<?php echo $side; ?>_penaltis" class="form-control text-center" value="<?php echo $match_info ? $match_info[$teamPenKey] : ''; ?>" placeholder="-">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="editor-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0"><span class="material-symbols-outlined">bolt</span> Eventos</h5>
                        <button type="button" class="btn btn-xs btn-info text-white fw-bold" id="addEventRow"><span class="material-symbols-outlined">add</span> Novo Evento</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="eventsTable">
                            <thead>
                                <tr>
                                    <th width="10%">Min</th><th width="15%">Time</th><th width="20%">Tipo</th><th width="45%">Jogador</th><th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($events as $index => $ev): 
                                    $sideEv = ($ev['id_time'] == $match_info['timeA_id'] || $ev['nome_time'] == $match_info['timeA_nome']) ? 'A' : 'B';
                                ?>
                                    <tr>
                                        <td><input type="number" name="events[<?php echo $index; ?>][minutos]" class="form-control form-control-sm" value="<?php echo $ev['minutos']; ?>"></td>
                                        <td>
                                            <select name="events[<?php echo $index; ?>][side]" class="form-control form-control-sm event-side">
                                                <option value="A" <?php echo $sideEv == 'A' ? 'selected' : ''; ?>>Time A</option>
                                                <option value="B" <?php echo $sideEv == 'B' ? 'selected' : ''; ?>>Time B</option>
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
                                                <input type="text" name="events[<?php echo $index; ?>][nome_jogador]" class="form-control form-control-sm manual-player-name <?php echo $ev['id_jogador'] ? 'd-none' : ''; ?>" value="<?php echo $ev['nome_jogador']; ?>" placeholder="Nome">
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="material-symbols-outlined text-danger btn-remove cursor-pointer">delete</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="editor-card">
                    <h5 class="section-title"><span class="material-symbols-outlined">task</span> Escalação & Reservas</h5>
                    <ul class="nav nav-pills nav-justified mb-3" id="lineupTabs">
                        <li class="nav-item"><button class="nav-link active py-2 small fw-bold" data-bs-toggle="pill" data-bs-target="#tabA" type="button">TIME A</button></li>
                        <li class="nav-item"><button class="nav-link py-2 small fw-bold" data-bs-toggle="pill" data-bs-target="#tabB" type="button">TIME B</button></li>
                    </ul>

                    <div class="tab-content">
                        <?php foreach(['A' => 1, 'B' => 2] as $side => $tIdx): ?>
                        <div class="tab-pane fade <?php echo $side == 'A' ? 'show active' : ''; ?>" id="tab<?php echo $side; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="small fw-bold text-muted uppercase">Escalação</span>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="addLineupRow('<?php echo $side; ?>')"><span class="material-symbols-outlined">add</span> Adicionar</button>
                            </div>
                            <div class="p-2 mb-2 rounded-3 <?php echo $side == 'A' ? 'bg-primary text-white' : 'bg-dark text-white'; ?> shadow-sm">
                                <h5 class="mb-0 fw-bold px-1"><span class="material-symbols-outlined me-2">shield</span> <?php echo $side == 'A' ? 'TIME A (Mandante)' : 'TIME B (Visitante)'; ?></h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="bg-light small">
                                        <tr style="background: #f8f9fa; border-bottom: 2px solid #eee;">
                                            <th width="12%" class="text-center">POS</th><th width="45%">JOGADOR</th><th width="10%" class="text-center">#</th><th width="8%" class="text-center">T</th><th width="20%" class="text-center">MIN</th><th width="5%"></th>
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
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    initMainSelects();
    initAllPlayerSelects();
    
    // --- DIRTY STATE TRACKING ---
    let isDirty = false;

    function markAsDirty() {
        if (!isDirty) {
            isDirty = true;
            $('#unsavedBadge').css('display', 'inline-flex');
        }
    }

    function markAsClean() {
        isDirty = false;
        $('#unsavedBadge').hide();
    }

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
                
                // If it was a new match (match_id=0), we might want to reload to update the URL
                // But specifically responding to "nao recarregar ela inteira" - so we stay.
                // However, technically if it was ID 0, we should update the form ID so subsequent saves work.
                // Assuming response includes match_id.
                if(response.match_id) {
                    $('input[name="match_id"]').val(response.match_id);
                    // Update title if it was "Nova Partida"
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
        if(confirm('Remover?')) $(this).closest('tr').remove();
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

    let eventIdx = <?php echo count($events); ?>;
    $('#addEventRow').click(function() {
        const side = 'A'; // Start as A by default
        const isManual = $(`#manual${side}`).is(':checked');
        const row = `<tr class="new-row">
            <td><input type="number" name="events[${eventIdx}][minutos]" class="form-control form-control-sm"></td>
            <td><select name="events[${eventIdx}][side]" class="form-control form-control-sm event-side"><option value="A">Time A</option><option value="B">Time B</option></select></td>
            <td><select name="events[${eventIdx}][tipo]" class="form-control form-control-sm"><option value="1">Gol</option><option value="4">Gol Contra</option><option value="2">Cartão Amarelo</option><option value="3">Cartão Vermelho</option></select></td>
            <td><div class="player-input-container" data-side="A"><select name="events[${eventIdx}][id_jogador]" class="form-control form-control-sm select-player-api"><option value="-1">--- NÃO CONSTA NO BANCO ---</option></select><input type="text" name="events[${eventIdx}][nome_jogador]" class="form-control form-control-sm manual-player-name" placeholder="Nome"></div></td>
            <td class="text-center"><span class="material-symbols-outlined text-danger btn-remove cursor-pointer">delete</span></td>
        </tr>`;
        $('#eventsTable tbody').append(row);
        
        const $newSelect = $('#eventsTable tbody tr:last .select-player-api');
        if(!isManual) initPlayerSelect($newSelect, 'A');
        else $newSelect.val('-1').trigger('change');
        
        eventIdx++;
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
                if(e.params.data.id != '-1') {
                    $(`#crest${side}`).removeClass('d-none').html(`<img src="/images/escudos/${e.params.data.escudo || 'sem_escudo.png'}">`);
                    $(`#name${side}`).val(e.params.data.text).addClass('d-none');
                    $(`#manual${side}`).prop('checked', false);
                    refreshInputsBySide(side, false);
                } else {
                    handleManualTeamSelection(side);
                }
            }).on('select2:clear', function() {
                handleManualTeamSelection(side);
            }).on('change', function() {
                const manualIdCheck = side === 'A' ? '-1' : '-2';
                if($(this).val() == manualIdCheck) handleManualTeamSelection(side);
            });
        });
    }

function handleManualTeamSelection(side) {
    $(`#crest${side}, #search${side}`).addClass('d-none');
    $(`#name${side}`).removeClass('d-none');
    $(`#manual${side}`).prop('checked', true);
    
    // Set team select to the correct side-specific manual ID
    const manualId = side === 'A' ? '-1' : '-2';
    if ($(`#selectTime${side}`).val() != manualId) {
        $(`#selectTime${side}`).val(manualId).trigger('change');
        return; // The change trigger will call handleManualTeamSelection again
    }
    
    // Switch all players of this side to manual
    refreshInputsBySide(side, true);
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
                console.log("Searching API with term:", params.term);
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