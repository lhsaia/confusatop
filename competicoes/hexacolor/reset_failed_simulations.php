<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

// Apenas administradores podem rodar o reset
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['admin_status'] !== '1') {
    header('HTTP/1.0 403 Forbidden');
    die("Acesso restrito a administradores.");
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

// Selecionar todos os jogos que constam como simulados (status = 1)
$query = "SELECT * FROM competicao_jogos WHERE status = 1";
$stmt = $db->query($query);
$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Verificando integridade das simulações...</h2>";
$resets = 0;

foreach ($jogos as $jogo) {
    $idPartida = $jogo['id'];
    $pathSalvo = $jogo['path']; // ex: "FLAxFLU - 02-08-2026"
    
    // Se o path no banco de dados estiver vazio, a súmula nunca foi gerada
    if (empty($pathSalvo)) {
        $db->query("UPDATE competicao_jogos SET timeA_gols = NULL, timeB_gols = NULL, path = NULL, status = 0 WHERE id = $idPartida");
        $resets++;
        echo "Jogo ID $idPartida resetado (caminho da súmula estava em branco).<br>";
        continue;
    }
    
    // Procurar de forma recursiva o arquivo correspondente na pasta de Partidas
    $dirPartidas = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/";
    $arquivoEncontrado = false;
    
    if (is_dir($dirPartidas)) {
        $it = new RecursiveDirectoryIterator($dirPartidas);
        foreach(new RecursiveIteratorIterator($it) as $file) {
            if ($file->isFile() && $file->getBasename('.hyl') === $pathSalvo) {
                $arquivoEncontrado = true;
                break;
            }
        }
    }
    
    // Se o arquivo da súmula não existe fisicamente no servidor, a simulação deu erro
    if (!$arquivoEncontrado) {
        $db->query("UPDATE competicao_jogos SET timeA_gols = NULL, timeB_gols = NULL, path = NULL, status = 0 WHERE id = $idPartida");
        $resets++;
        echo "Jogo ID $idPartida resetado (Súmula '$pathSalvo.hyl' não foi localizada no servidor).<br>";
    }
}

echo "<br><strong>Varredura concluída!</strong> Total de jogos pendentes restaurados: $resets";
?>
