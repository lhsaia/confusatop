<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
$userId = $_SESSION['user_id'] ?? 0;
$userNome = $_SESSION['nomereal'] ?? '';
$idPais = $_POST['idPais'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/simplexlsx/SimpleXLSXGen.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/paises.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$jogador = new Jogador($db);

//buscar posicoes dos jogadores e adicionar na query
$stmt = $jogador->exportacao($idPais);

use Shuchkin\SimpleXLSXGen;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $rows = [
        [
            'ID', 'Nome', 'Nascimento', 'Nacionalidade', 'Nivel', 'Mentalidade', 'Cobrador de Falta',
            'Determinação', 'G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'MA',
            'PD', 'PE', 'Am', 'Aa',
            '<style bgcolor="#808080">Time</style>',
            '<style bgcolor="#808080">Sexo</style>'
        ]
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
        $rows[] = [
            $row['idJogador'],
            $row['nomeJogador'],
            $row['Nascimento'],
            $testeNacionalidade,
            $row['Nivel'],
            $row['Mentalidade'],
            $row['CobradorFalta'],
            $row['DeterminacaoOriginal'],
            $row['StringPosicoes'][0] ?? '',
            $row['StringPosicoes'][1] ?? '',
            $row['StringPosicoes'][2] ?? '',
            $row['StringPosicoes'][3] ?? '',
            $row['StringPosicoes'][4] ?? '',
            $row['StringPosicoes'][5] ?? '',
            $row['StringPosicoes'][6] ?? '',
            $row['StringPosicoes'][7] ?? '',
            $row['StringPosicoes'][8] ?? '',
            $row['StringPosicoes'][9] ?? '',
            $row['StringPosicoes'][12] ?? '',
            $row['StringPosicoes'][10] ?? '',
            $row['StringPosicoes'][11] ?? '',
            $row['StringPosicoes'][13] ?? '',
            $row['StringPosicoes'][14] ?? '',
            '<style bgcolor="#808080">' . htmlspecialchars((string)$row['Time'], ENT_QUOTES, 'UTF-8') . '</style>',
            '<style bgcolor="#808080">' . htmlspecialchars((string)$row['sexo'], ENT_QUOTES, 'UTF-8') . '</style>'
        ];
    }

    $today = date("Y-m-d H:i:s");
    $saving_file_name = "Base_portal_" . $userNome. "_".$idPais."_".$today.".xlsx";
    $saving_file_name = str_replace("/","_",$saving_file_name);
    $saving_file_name = str_replace(" ","_",$saving_file_name);

    $xlsx = SimpleXLSXGen::fromArray($rows);
    $savePath = __DIR__ . "/" . $saving_file_name;
    $xlsx->saveAs($savePath);

    die(json_encode([ 'success'=> true, 'filename'=>$saving_file_name]));
}

die(json_encode([ 'success'=> false ]));
?>
