<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['admin_status'] !== '1') {
    die("Acesso negado.");
}

$docRoot = dirname(__DIR__, 2);
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($isWindows) {
    $dir = str_replace('\\', '/', __DIR__);
    $jarPath = $dir . "/HexacolorYMTv2.jar";
    $jsonPath = $dir . "/agenda/json.txt";
    $cmd = "java -Djava.awt.headless=true -jar \"$jarPath\" -m \"$jsonPath\"";
} else {
    $javaBin = $docRoot . "/java_station/jdk/jdk1.8.0_231/bin/java";
    $libPath = $docRoot . "/competicoes/hexacolor/lib";
    $tmpDir = $docRoot . "/java_station/tmp";
    $jarPath = $docRoot . "/competicoes/hexacolor/HexacolorYMTv2.jar";
    $jsonPath = $docRoot . "/competicoes/hexacolor/agenda/json.txt";
    $cmd = "$javaBin -Djava.awt.headless=true -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPath";
}

echo "<h3>Testando Execução do Java via proc_open</h3>";
echo "Comando executado:<br><code style='background:#f4f4f4;padding:5px;display:block;'>" . htmlspecialchars($cmd) . "</code><br>";

// Validando caminhos
echo "<h4>Verificação de Arquivos:</h4>";
echo "Java Bin existe? " . (file_exists($javaBin) ? "SIM" : "NÃO (" . htmlspecialchars($javaBin) . ")") . "<br>";
echo "JAR existe? " . (file_exists($jarPath) ? "SIM" : "NÃO") . "<br>";
echo "JSON existe? " . (file_exists($jsonPath) ? "SIM" : "NÃO") . "<br>";
echo "Diretório temporário existe? " . (is_dir($tmpDir) ? "SIM" : "NÃO") . "<br>";

$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    echo "<h4>Resultados:</h4>";
    echo "Exit Code: " . $return_value . "<br>";
    echo "STDOUT:<br><pre style='background:#f4f4f4;padding:10px;'>" . htmlspecialchars($stdout) . "</pre>";
    echo "STDERR:<br><pre style='background:#f4f4f4;padding:10px;color:red;'>" . htmlspecialchars($stderr) . "</pre>";
} else {
    echo "Erro ao abrir o processo Java.";
}
?>
