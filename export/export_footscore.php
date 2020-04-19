<?php

session_start();
//$userId = $_SESSION['user_id'];
//$userNome = $_SESSION['nomereal'];

require_once("/home/lhsaia/confusa.top/vendor/autoload.php");
include_once("/home/lhsaia/confusa.top/config/database.php");
include_once("/home/lhsaia/confusa.top/objetos/paises.php");
include_once("/home/lhsaia/confusa.top/objetos/jogador.php");
include_once("/home/lhsaia/confusa.top/objetos/time.php");
include_once("/home/lhsaia/confusa.top/objetos/liga.php");
include_once("/home/lhsaia/confusa.top/objetos/tecnico.php");
include_once("/home/lhsaia/confusa.top/objetos/estadio.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$jogador = new Jogador($db);
$time = new Time($db);
$liga = new Liga($db);
$tecnico = new Tecnico($db);
$estadio = new Estadio($db);

//buscar posicoes dos jogadores e adicionar na query
//$stmt = $jogador->exportacao($idPais);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Aligmnent;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

$sheet_row = 2;
//$output_sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$reader = IOFactory::createReader('Xlsx');
$output_sheet = $reader->load("footscore_repo/template.xlsx");

//escrever informações da liga
$codigo_liga = $_POST['codigo_liga'];
$nome_liga = $liga->nomeLiga($codigo_liga);
$output_sheet->getActiveSheet()->setCellValue('B1', $nome_liga);

//loop foreach times
$stmt_times = $time->readAll(0,10000,null, $codigo_liga);
$lista_times = array();
while ($row_times = $stmt_times->fetch(PDO::FETCH_ASSOC)){
    extract($row_times);
   $lista_times[$ID] = $Nome;

}

//criar array de celulas iniciais de time

$coluna_nome_time = array('B', 'X', 'AT', 'BP');
$coluna_media = array('D', 'Z', 'AV', 'BR');

//escrever informações do time
$index = 0;
foreach($lista_times as $numero_time => $time_footscore){
    
    if($index > 32){
        break;
    }
    $coluna = $index % 4;
    $linha = floor($index/4) * 32 + 3;
    
    //cabeçalho time
    $info_cabecalho = [
        [$time_footscore, NULL, NULL, NULL, NULL,"=ROUND(AVERAGE(".$coluna_media[$coluna].($linha +2).":".$coluna_media[$coluna].($linha + 12)."),0)" , NULL, NULL, NULL, NULL, NULL,'ELA', 'PEN', 'SEG', 'SAI', 'REF'],
        ['Pos.', 'Jogadores', 'Nível', 'Idade', 'País', NULL, NULL, 'Funções', NULL, NULL, 'Faltas', 'FIN', 'PAS', 'DRI', 'DES', 'MAR', 'CAB', 'CRU', 'VEL', 'Det.', 'Men.']
        ];

    $index++;
    
    //buscar tecnico
    $stmt_tecnico = $tecnico->exportacao(null, $numero_time);
    $row_tecnico = $stmt_tecnico->fetch(PDO::FETCH_ASSOC);
    
    //buscar estadio
    $stmt_estadio = $estadio->exportacao(null, $numero_time);
    $row_estadio = $stmt_estadio->fetch(PDO::FETCH_ASSOC);
    
    //buscar jogadores
    $stmt_jogadores = $jogador->exportacao(null,$numero_time, TRUE);
    $index_jogador = 0;
    
        while ($row = $stmt_jogadores->fetch(PDO::FETCH_ASSOC)){
            
            if($index_jogador > 22){
                break;
            }
            
            if($index_jogador == 11){
                $info_cabecalho[] = [NULL];
            }
            
            $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
            $tratamentoNacionalidade = explode(".",$testeNacionalidade);
            $testeNacionalidade = $tratamentoNacionalidade[0];
            
            $isGoleiro = ($row['StringPosicoes'][0] == "1" ? true : false);
            
            $testePosicao = ($row['titularidade'] == 0 ? "S" : $row['posicao']);
            
            $listaPosicoes = $jogador->listaPosicoes($row['StringPosicoes']);
            $listaPosicoes = explode("-", $listaPosicoes);
                   
            $array_jogador_unico = [];
            array_push($array_jogador_unico, $testePosicao, $row['nomeJogador'], $row['Nivel'], $row['Idade'], $testeNacionalidade);
            
            for($i = 0;$i<5;$i++){
                $posicaoParaInserir = isset($listaPosicoes[$i]) ? $listaPosicoes[$i] : "";
                array_push($array_jogador_unico, $posicaoParaInserir);
            }
            
            array_push($array_jogador_unico, $row['CobradorFalta']);
            
            $fs = array("ELA" => gkConvertion($row['Reflexos'], $row['Saidas'], $row['JogoAereo']),
                        "PEN" => gkConvertion($row['DefesaPenaltis']),
                        "SEG" => gkConvertion($row['Seguranca']),
                        "SAI" => gkConvertion($row['JogoAereo'], $row['Saidas']),
                        "REF" => gkConvertion($row['Reflexos']),
                        "FIN" => lnConvertion($row['Finalizacao'], $row['FaroGol']),
                        "PAS" => lnConvertion($row['Tecnica'], $row['VisaoJogo']),
                        "DRI" => lnConvertion($row['ControleBola']),
                        "DES" => lnConvertion($row['Desarme']),
                        "MAR" => lnConvertion($row['Marcacao']),
                        "CAB" => lnConvertion($row['Cabeceamento']),
                        "CRU" => lnConvertion($row['Cruzamentos']),
                        "VEL" => ceil((lnConvertion($row['Movimentacao']) + $row['Velocidade'])/2));
            
            if($isGoleiro){
                array_push($array_jogador_unico, $fs['ELA'], $fs['PEN'], $fs['SEG'], $fs['SAI'], $fs['REF'],"","","");
            } else {
                array_push($array_jogador_unico, $fs['FIN'], $fs['PAS'], $fs['DRI'], $fs['DES'], $fs['MAR'], $fs['CAB'], $fs['CRU'], $fs['VEL']);
            }
            
            array_push($array_jogador_unico, $row['DeterminacaoOriginal'], $row['Mentalidade']);

            $info_cabecalho[] = $array_jogador_unico;
            $index_jogador++;
            
            
        }
        
        //escrever time
        $output_sheet->getActiveSheet()->fromArray(
        $info_cabecalho,   // The data to set
        NULL,        // Array values with this value will not be set
        $coluna_nome_time[$coluna] . $linha     // Top left coordinate of the worksheet range where
                     //    we want to set these values (default is A1)
                     

    );
    
        //escrever tecnico
        $nome_tecnico = explode("[",$row_tecnico['Nome']);
        $pais_tecnico = substr($nome_tecnico[1], 0, -1);
        $nome_tecnico = $nome_tecnico[0];
        
        $info_tecnico = [
        [NULL, $nome_tecnico, $row_tecnico['Nivel'], $row_tecnico['Idade'], $pais_tecnico, $row_tecnico['Mentalidade'],$row_tecnico['Estilo']],
        [NULL, $row_estadio['Nome'] . " [" . $row_estadio['Capacidade'] . "]"]
        ]
        ;
        
        
        $output_sheet->getActiveSheet()->fromArray(
        $info_tecnico,   // The data to set
        NULL,        // Array values with this value will not be set
        $coluna_nome_time[$coluna] . ($linha+29)     // Top left coordinate of the worksheet range where
                     //    we want to set these values (default is A1)
                     

    );
        
        //escrever estadio
            
        // $nomeJogador = str_replace("'", "''", $row['nomeJogador']);
        // $megaQuery .= "INSERT OR IGNORE INTO jogador VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";


        // if($row['StringPosicoes'][0] == 1){
        //     $megaQuery .= "INSERT OR IGNORE INTO atributosgoleiro VALUES ('{$row['idJogador']}', '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}'); ";

        //     $somaZero = abs(($row['Nivel'] * 0.55) - ($row['somaAtributos']));
        //     if($somaZero > 0.5){
        //         $megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
        //     }

        // } else {
        //     $megaQuery .= "INSERT OR IGNORE INTO atributosjogador VALUES ('{$row['idJogador']}', '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}'); ";

        //     $somaZero = abs(($row['Nivel'] * 0.7) - ($row['somaAtributos']));
        //     if($somaZero > 0.5){
        //         $megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
        //     }
        //}

    }
    

// $output_sheet->getActiveSheet()->getStyle('X1:Y1')
//     ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
// $output_sheet->getActiveSheet()->getStyle('X1:Y1')
//     ->getFill()->getStartColor()->setARGB('FF808080');



// while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

//     $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
//     $output_sheet->getActiveSheet()->setCellValue('A'.$sheet_row, $row['idJogador']);
//     $output_sheet->getActiveSheet()->setCellValue('B'.$sheet_row, $row['nomeJogador']);
//     $output_sheet->getActiveSheet()->setCellValue('C'.$sheet_row, $row['Nascimento']);
//     $output_sheet->getActiveSheet()->setCellValue('D'.$sheet_row, $testeNacionalidade);
//     $output_sheet->getActiveSheet()->setCellValue('E'.$sheet_row, $row['Nivel']);
//     $output_sheet->getActiveSheet()->setCellValue('F'.$sheet_row, $row['Mentalidade']);
//     $output_sheet->getActiveSheet()->setCellValue('G'.$sheet_row, $row['CobradorFalta']);
//     $output_sheet->getActiveSheet()->setCellValue('H'.$sheet_row, $row['DeterminacaoOriginal']);
//     $output_sheet->getActiveSheet()->setCellValue('I'.$sheet_row, $row['StringPosicoes'][0]);
//     $output_sheet->getActiveSheet()->setCellValue('J'.$sheet_row, $row['StringPosicoes'][1]);
//     $output_sheet->getActiveSheet()->setCellValue('K'.$sheet_row, $row['StringPosicoes'][2]);
//     $output_sheet->getActiveSheet()->setCellValue('L'.$sheet_row, $row['StringPosicoes'][3]);
//     $output_sheet->getActiveSheet()->setCellValue('M'.$sheet_row, $row['StringPosicoes'][4]);
//     $output_sheet->getActiveSheet()->setCellValue('N'.$sheet_row, $row['StringPosicoes'][5]);
//     $output_sheet->getActiveSheet()->setCellValue('O'.$sheet_row, $row['StringPosicoes'][6]);
//     $output_sheet->getActiveSheet()->setCellValue('P'.$sheet_row, $row['StringPosicoes'][7]);
//     $output_sheet->getActiveSheet()->setCellValue('Q'.$sheet_row, $row['StringPosicoes'][8]);
//     $output_sheet->getActiveSheet()->setCellValue('R'.$sheet_row, $row['StringPosicoes'][9]);
//     $output_sheet->getActiveSheet()->setCellValue('S'.$sheet_row, $row['StringPosicoes'][12]);
//     $output_sheet->getActiveSheet()->setCellValue('T'.$sheet_row, $row['StringPosicoes'][10]);
//     $output_sheet->getActiveSheet()->setCellValue('U'.$sheet_row, $row['StringPosicoes'][11]);
//     $output_sheet->getActiveSheet()->setCellValue('V'.$sheet_row, $row['StringPosicoes'][13]);
//     $output_sheet->getActiveSheet()->setCellValue('W'.$sheet_row, $row['StringPosicoes'][14]);
//     $output_sheet->getActiveSheet()->setCellValue('X'.$sheet_row, $row['Time']);
//     $output_sheet->getActiveSheet()->setCellValue('Y'.$sheet_row, $row['sexo']);
//     $output_sheet->getActiveSheet()->getStyle('X'.$sheet_row.':Y'.$sheet_row)
//         ->getFill()->getStartColor()->setARGB('FF808080');
//     $output_sheet->getActiveSheet()->getStyle('X'.$sheet_row.':Y'.$sheet_row)
//         ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
//     $sheet_row = $sheet_row + 1;

// }

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
$saving_file_name = "Footscore_elencos_" . $nome_liga. "_".$today.".xlsx";
$saving_file_name = str_replace("/","_",$saving_file_name);
$saving_file_name = str_replace(" ","_",$saving_file_name);
$writer->save($saving_file_name);

$full_path_name = "/export/" . $saving_file_name;

//$saving_file_name = "./".$saving_file_name;

// deleting new spreadsheet
 $output_sheet->disconnectWorksheets();
 unset($output_sheet);

 die(json_encode([ 'success'=> true, 'filename'=>$full_path_name]));

}

function gkConvertion(...$numberArray){
    $totalParameter = 0;
    $numberOfParameters = 0;
    foreach($numberArray as $number){
        $number = ceil($number);
        if($number >= 7){
            $totalParameter += $number - 5;
            $numberOfParameters ++;
        } else if($number == 6){
            $totalParameter += 2;
            $numberOfParameters ++;
        } else if($number < 6){
            $totalParameter += 1;
            $numberOfParameters ++;
        }
    }
    return ceil($totalParameter / $numberOfParameters);
}

function lnConvertion(...$numberArray){
    $totalParameter = 0;
    $numberOfParameters = 0;
    foreach($numberArray as $number){
        $number = ceil($number);
        if($number >= 4){
            $totalParameter += $number - 2;
            $numberOfParameters ++;
        } else if($number == 3){
            $totalParameter += 2;
            $numberOfParameters ++;
        } else if($number < 3){
            $totalParameter += 1;
            $numberOfParameters ++;
        }
    }
    return ceil($totalParameter / $numberOfParameters);
}


die(json_encode([ 'success'=> false ]));

?>
