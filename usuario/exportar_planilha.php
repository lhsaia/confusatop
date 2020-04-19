<?php

session_start();
$userId = $_SESSION['user_id'];
$userNome = $_SESSION['nomereal'];
$idPais = $_POST['idPais'];

require_once("/home/lhsaia/confusa.top/vendor/autoload.php");
include_once("/home/lhsaia/confusa.top/config/database.php");
include_once("/home/lhsaia/confusa.top/objetos/paises.php");
include_once("/home/lhsaia/confusa.top/objetos/jogador.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$jogador = new Jogador($db);

//buscar posicoes dos jogadores e adicionar na query
$stmt = $jogador->exportacao($idPais);

use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

$sheet_row = 2;
$output_sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

$output_sheet->getActiveSheet()->setCellValue('A1', 'ID');
$output_sheet->getActiveSheet()->setCellValue('B1', 'Nome');
$output_sheet->getActiveSheet()->setCellValue('C1', 'Nascimento');
$output_sheet->getActiveSheet()->setCellValue('D1', 'Nacionalidade');
$output_sheet->getActiveSheet()->setCellValue('E1', 'Nivel');
$output_sheet->getActiveSheet()->setCellValue('F1', 'Mentalidade');
$output_sheet->getActiveSheet()->setCellValue('G1', 'Cobrador de Falta');
$output_sheet->getActiveSheet()->setCellValue('H1', 'Determinação');
$output_sheet->getActiveSheet()->setCellValue('I1', 'G');
$output_sheet->getActiveSheet()->setCellValue('J1', 'LD');
$output_sheet->getActiveSheet()->setCellValue('K1', 'LE');
$output_sheet->getActiveSheet()->setCellValue('L1', 'Z');
$output_sheet->getActiveSheet()->setCellValue('M1', 'AD');
$output_sheet->getActiveSheet()->setCellValue('N1', 'AE');
$output_sheet->getActiveSheet()->setCellValue('O1', 'V');
$output_sheet->getActiveSheet()->setCellValue('P1', 'MD');
$output_sheet->getActiveSheet()->setCellValue('Q1', 'ME');
$output_sheet->getActiveSheet()->setCellValue('R1', 'MC');
$output_sheet->getActiveSheet()->setCellValue('S1', 'MA');
$output_sheet->getActiveSheet()->setCellValue('T1', 'PD');
$output_sheet->getActiveSheet()->setCellValue('U1', 'PE');
$output_sheet->getActiveSheet()->setCellValue('V1', 'Am');
$output_sheet->getActiveSheet()->setCellValue('W1', 'Aa');
$output_sheet->getActiveSheet()->setCellValue('X1', 'Time');
$output_sheet->getActiveSheet()->setCellValue('Y1', 'Sexo');

$output_sheet->getActiveSheet()->getStyle('X1:Y1')
    ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$output_sheet->getActiveSheet()->getStyle('X1:Y1')
    ->getFill()->getStartColor()->setARGB('FF808080');



while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

    $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
    $output_sheet->getActiveSheet()->setCellValue('A'.$sheet_row, $row['idJogador']);
    $output_sheet->getActiveSheet()->setCellValue('B'.$sheet_row, $row['nomeJogador']);
    $output_sheet->getActiveSheet()->setCellValue('C'.$sheet_row, $row['Nascimento']);
    $output_sheet->getActiveSheet()->setCellValue('D'.$sheet_row, $testeNacionalidade);
    $output_sheet->getActiveSheet()->setCellValue('E'.$sheet_row, $row['Nivel']);
    $output_sheet->getActiveSheet()->setCellValue('F'.$sheet_row, $row['Mentalidade']);
    $output_sheet->getActiveSheet()->setCellValue('G'.$sheet_row, $row['CobradorFalta']);
    $output_sheet->getActiveSheet()->setCellValue('H'.$sheet_row, $row['DeterminacaoOriginal']);
    $output_sheet->getActiveSheet()->setCellValue('I'.$sheet_row, $row['StringPosicoes'][0]);
    $output_sheet->getActiveSheet()->setCellValue('J'.$sheet_row, $row['StringPosicoes'][1]);
    $output_sheet->getActiveSheet()->setCellValue('K'.$sheet_row, $row['StringPosicoes'][2]);
    $output_sheet->getActiveSheet()->setCellValue('L'.$sheet_row, $row['StringPosicoes'][3]);
    $output_sheet->getActiveSheet()->setCellValue('M'.$sheet_row, $row['StringPosicoes'][4]);
    $output_sheet->getActiveSheet()->setCellValue('N'.$sheet_row, $row['StringPosicoes'][5]);
    $output_sheet->getActiveSheet()->setCellValue('O'.$sheet_row, $row['StringPosicoes'][6]);
    $output_sheet->getActiveSheet()->setCellValue('P'.$sheet_row, $row['StringPosicoes'][7]);
    $output_sheet->getActiveSheet()->setCellValue('Q'.$sheet_row, $row['StringPosicoes'][8]);
    $output_sheet->getActiveSheet()->setCellValue('R'.$sheet_row, $row['StringPosicoes'][9]);
    $output_sheet->getActiveSheet()->setCellValue('S'.$sheet_row, $row['StringPosicoes'][12]);
    $output_sheet->getActiveSheet()->setCellValue('T'.$sheet_row, $row['StringPosicoes'][10]);
    $output_sheet->getActiveSheet()->setCellValue('U'.$sheet_row, $row['StringPosicoes'][11]);
    $output_sheet->getActiveSheet()->setCellValue('V'.$sheet_row, $row['StringPosicoes'][13]);
    $output_sheet->getActiveSheet()->setCellValue('W'.$sheet_row, $row['StringPosicoes'][14]);
    $output_sheet->getActiveSheet()->setCellValue('X'.$sheet_row, $row['Time']);
    $output_sheet->getActiveSheet()->setCellValue('Y'.$sheet_row, $row['sexo']);
    $output_sheet->getActiveSheet()->getStyle('X'.$sheet_row.':Y'.$sheet_row)
        ->getFill()->getStartColor()->setARGB('FF808080');
    $output_sheet->getActiveSheet()->getStyle('X'.$sheet_row.':Y'.$sheet_row)
        ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet_row = $sheet_row + 1;

}

//$output_sheet->setMimeType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");


//echo '<pre>' , var_dump($output_sheet) , '</pre>';

// deleting old references to spreadsheet
//$spreadsheet->disconnectWorksheets();
//unset($spreadsheet);

//writing and downloading results spreadsheet
 //$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($output_sheet);
 //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($output_sheet, "Xlsx");
// header('Content-type: application/vnd.ms-excel');
// header('Content-Disposition: attachment; filename="test_results['.$timeStamp.'].xlsx"');
 //$writer->save($temp_filename);

$today = date("Y-m-d H:i:s");
//$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($output_sheet);
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($output_sheet);
$saving_file_name = "Base_portal_" . $userNome. "_".$idPais."_".$today.".xlsx";
$saving_file_name = str_replace("/","_",$saving_file_name);
$saving_file_name = str_replace(" ","_",$saving_file_name);
$writer->save($saving_file_name);

//$saving_file_name = "./".$saving_file_name;

// deleting new spreadsheet
 $output_sheet->disconnectWorksheets();
 unset($output_sheet);

 die(json_encode([ 'success'=> true, 'filename'=>$saving_file_name]));

}

die(json_encode([ 'success'=> false ]));

?>
