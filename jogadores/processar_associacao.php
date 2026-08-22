<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Acesso negado.");
}

if (!isset($_SESSION['pending_import']) || empty($_SESSION['pending_import'])) {
    header("Location: /jogadores/importar_jogador.php");
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

if (simplexml_load_string($pending['xml_content']) == false) {
    $xml = simplexml_load_string(utf8_encode($pending['xml_content']));
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
