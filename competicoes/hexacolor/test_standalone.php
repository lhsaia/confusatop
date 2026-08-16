<?php
/**
 * Script de teste para simulação standalone do Hexacolor.
 * Este script gera um JSON de teste e executa o motor HexacolorLite.jar sem depender do banco de dados MySQL central.
 */

// Configurações de Teste (Ajuste conforme necessário)
$nomeComposto = "Teste Standalone 2026";
$idCompeticao = 17; // ID de uma competição que possua um arquivo .db3 em competicoes/databases/
$idPartida = 999;
$idTimeA = 1; // ID de um time existente no SQLite da competição
$idTimeB = 2; // ID de outro time existente no SQLite da competição
$idEstadio = 1;
$isNeutro = false;

// Preparação de dados
$fullDate = time() * 1000 + 5*60*60*1000;
$matchdayIndex = 1;
$databasePath = "jdbc:sqlite:../databases/".$idCompeticao."-database.db3";

// Cores fictícias (Hexacolor JAR as utiliza para a interface/relatórios)
$cores = [
    'partidaCor1' => '#004c99', 
    'partidaCor2' => '#ffffff', 
    'partidaCor3' => '#cc0000'
];

$json_array = array(
    'calendarName' => $nomeComposto,
    'color1' => $cores['partidaCor1'], 
    'color2' => $cores['partidaCor2'], 
    'color3' => $cores['partidaCor3'], 
    'matchdayIndex' => $matchdayIndex,
    'matches' => [array(
        'databasePath' => $databasePath,
        'id' => $idPartida,
        'date' => $fullDate,
        'idTeam1' => $idTimeA,
        'idTeam2' => $idTimeB,
        'kitTeam1' => 0,
        'kitTeam2' => 0,
        'idChosenGround' => $idEstadio,
        'neutralGround' => $isNeutro,
        'outTeam1' => array(),
        'outTeam2' => array(),
        'knockoutTiebraker' => 0,
        'knockoutAwayGoals' => false, 
    )]
);

$json = json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

echo "--- JSON GERADO ---\n";
echo $json . "\n\n";

// Salvar JSON na pasta agenda (certifique-se que a pasta existe)
if(!is_dir(__DIR__ . "/agenda")) mkdir(__DIR__ . "/agenda", 0777, true);
$jsonFilePath = __DIR__ . "/agenda/json_test.txt";

if(file_put_contents($jsonFilePath, $json)){
    echo "JSON salvo em: $jsonFilePath\n";
    
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $dir = str_replace('\\', '/', __DIR__);
    
    if($isWindows){
        $jarPath = $dir . "/HexacolorLite.jar";
        $cmd = "java -jar \"$jarPath\" -m \"$jsonFilePath\" 2>&1";
    } else {
        // Mock do comando de produção se necessário
        $javaBin = "/java_station/jdk/jdk1.8.0_231/bin/java";
        $libPath = "/competicoes/hexacolor/lib";
        $tmpDir = "/java_station/tmp";
        $jarPath = "/competicoes/hexacolor/HexacolorLite.jar";
        $cmd = "export $javaBin -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonFilePath 2>&1";
    }

    echo "Executando: $cmd\n\n";
    $output = shell_exec($cmd);
    
    echo "--- OUTPUT DO MOTOR ---\n";
    echo $output . "\n";
    
    // Verificar se gerou o arquivo de saída (.hyl)
    // O motor costuma gerar em competicoes/hexacolor/Partidas/[NomeComposto]/[Rodada]/...hyl
    echo "\n--- VERIFICANDO RESULTADO ---\n";
    $partidasDir = __DIR__ . "/Partidas";
    if(is_dir($partidasDir)){
        echo "Pasta de Partidas encontrada.\n";
    } else {
        echo "Aviso: Pasta de Partidas não encontrada localmente. O JAR pode estar configurado para salvar em outro local absoluto.\n";
    }
} else {
    echo "Erro ao salvar arquivo JSON.\n";
}
