<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Acesso negado.");
}

$is_admin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1' && empty($_SESSION['impersonated']));

if (!isset($_SESSION['pending_import']) || empty($_SESSION['pending_import'])) {
    header("Location: /times/importar_time.php");
    exit;
}

$pending = $_SESSION['pending_import'];
$type = $pending['type']; // 1 = player, 2 = team
$associations = isset($_POST['associations']) ? $_POST['associations'] : [];
$team_associations = isset($_POST['team_associations']) ? $_POST['team_associations'] : [];

include($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/estadio.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/clima.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/tecnico.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/liga.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");
include($_SERVER['DOCUMENT_ROOT'] . "/objetos/arbitros.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);
$pais = new Pais($db);
$time = new Time($db);
$estadio = new Estadio($db);
$clima = new Clima($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$usuario = new Usuario($db);

$ligaSelecionada = $pending['liga'];
$timeSelecionado = $pending['time'];
$sexo = $pending['sexo'];
$nacionalidadeSelecionada = $pending['nacionalidade'];
$paisLigaSelecionada = $pending['pais_liga_selecionada'];

// Determinar país alvo
$target_pais_id = (int)($paisLigaSelecionada ?? 0);
if ($target_pais_id === 0 && !empty($ligaSelecionada)) {
    $stmt_lp = $db->prepare("SELECT Pais FROM liga WHERE ID = ?");
    $stmt_lp->execute([$ligaSelecionada]);
    $target_pais_id = (int)$stmt_lp->fetchColumn();
    $paisLigaSelecionada = $target_pais_id;
}
if ($target_pais_id === 0 && !empty($timeSelecionado)) {
    $stmt_p_club = $db->prepare("SELECT Pais FROM clube WHERE ID = ?");
    $stmt_p_club->execute([$timeSelecionado]);
    $target_pais_id = (int)$stmt_p_club->fetchColumn();
}

// Para não-admins, validação rigorosa de segurança para impedir qualquer associação entre países diferentes
if (!$is_admin) {
    if ($type == 2) {
        $stmt_own = $db->prepare("SELECT p.id FROM paises p WHERE p.id = ? AND p.dono = ?");
        $stmt_own->execute([$target_pais_id, $_SESSION['user_id']]);
        if (!$stmt_own->fetchColumn()) {
            die("Acesso negado. Você não tem permissão para gerenciar este país.");
        }
    }

    // 1. Validar Clube
    if (isset($team_associations['clube']) && ($team_associations['clube']['action'] ?? '') === 'match' && !empty($team_associations['clube']['player_id'])) {
        $check_club_id = (int)$team_associations['clube']['player_id'];
        $st_vc = $db->prepare("SELECT c.ID FROM clube c INNER JOIN liga l ON c.liga = l.ID INNER JOIN paises p ON l.pais = p.id WHERE c.ID = ? AND (c.Pais = ? OR l.pais = ?) AND p.dono = ?");
        $st_vc->execute([$check_club_id, $target_pais_id, $target_pais_id, $_SESSION['user_id']]);
        if (!$st_vc->fetchColumn()) {
            $team_associations['clube']['action'] = 'new';
            unset($team_associations['clube']['player_id']);
        }
    }

    // 2. Validar Técnico
    if (isset($team_associations['tecnico']) && ($team_associations['tecnico']['action'] ?? '') === 'match' && !empty($team_associations['tecnico']['player_id'])) {
        $check_tec_id = (int)$team_associations['tecnico']['player_id'];
        $st_vt = $db->prepare("SELECT a.ID FROM tecnico a LEFT JOIN contratos_tecnico c ON c.tecnico = a.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON c.clube = b.ID WHERE a.ID = ? AND ( (b.ID IS NOT NULL AND b.Pais = ?) OR (b.ID IS NULL AND a.Pais = ?) ) AND a.Sexo = ?");
        $st_vt->execute([$check_tec_id, $target_pais_id, $target_pais_id, $sexo]);
        if (!$st_vt->fetchColumn()) {
            $team_associations['tecnico']['action'] = 'new';
            unset($team_associations['tecnico']['player_id']);
        }
    }

    // 3. Validar Estádio
    if (isset($team_associations['estadio']) && ($team_associations['estadio']['action'] ?? '') === 'match' && !empty($team_associations['estadio']['player_id'])) {
        $check_est_id = (int)$team_associations['estadio']['player_id'];
        $st_ve = $db->prepare("SELECT ID FROM estadio WHERE ID = ? AND Pais = ?");
        $st_ve->execute([$check_est_id, $target_pais_id]);
        if (!$st_ve->fetchColumn()) {
            $team_associations['estadio']['action'] = 'new';
            unset($team_associations['estadio']['player_id']);
        }
    }

    // 4. Validar Jogadores (JAMAIS vincular jogadores de outros países)
    if (!empty($associations) && is_array($associations)) {
        foreach ($associations as $idx => &$assoc) {
            if (isset($assoc['action']) && $assoc['action'] === 'match' && !empty($assoc['player_id'])) {
                $check_jog_id = (int)$assoc['player_id'];
                $st_vj = $db->prepare("SELECT j.ID FROM jogador j LEFT JOIN contratos_jogador c ON c.jogador = j.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON c.clube = b.ID WHERE j.ID = ? AND ( (b.ID IS NOT NULL AND b.Pais = ?) OR (b.ID IS NULL AND j.Pais = ?) ) AND j.Sexo = ?");
                $st_vj->execute([$check_jog_id, $target_pais_id, $target_pais_id, $sexo]);
                if (!$st_vj->fetchColumn()) {
                    $assoc['action'] = 'new';
                    unset($assoc['player_id']);
                }
            }
        }
        unset($assoc);
    }
}

if (simplexml_load_string($pending['xml_content']) == false) {
    $xml = simplexml_load_string(function_exists('mb_convert_encoding') ? mb_convert_encoding($pending['xml_content'], 'UTF-8', 'ISO-8859-1') : utf8_encode($pending['xml_content']));
} else {
    $xml = simplexml_load_string($pending['xml_content']);
}

$usuario->atualizarAlteracao($_SESSION['user_id']);

$is_success = false;
$error_msg = '';

if ($type == 1) {
    // Player import
    $arquivo_tratamento = "/jogadores/tratamento_jogador.php";
    
    // Set up id_jogador_existente for treatment script
    if (isset($associations[0])) {
        $assoc = $associations[0];
        if ($assoc['action'] === 'match' && !empty($assoc['player_id'])) {
            $id_jogador_existente = $assoc['player_id'];
        }
    }
    
    include($_SERVER['DOCUMENT_ROOT'] . $arquivo_tratamento);
    $redirect_url = "/jogadores/importar_jogador.php";
} else {
    // Team import
    $arquivo_tratamento = "/times/tratamento_time.php";
    include($_SERVER['DOCUMENT_ROOT'] . $arquivo_tratamento);
    $redirect_url = "/times/importar_time.php";
}

// Clean session
unset($_SESSION['pending_import']);

if ($is_success) {
    $_SESSION['import_message'] = "Importação e associação realizadas com sucesso!";
    $_SESSION['import_status'] = "success";
} else {
    $_SESSION['import_message'] = "Houve um erro na importação: " . $error_msg;
    $_SESSION['import_status'] = "error";
}

header("Location: " . $redirect_url);
exit;
