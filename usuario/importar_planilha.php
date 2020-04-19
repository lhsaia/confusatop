<?php

session_start();
$logged_user = $_SESSION['user_id'];

require_once("/home/lhsaia/confusa.top/vendor/autoload.php");
include_once("/home/lhsaia/confusa.top/config/database.php");
include_once("/home/lhsaia/confusa.top/objetos/jogador.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$jogador = new Jogador($db);

$error_msg = "";
$error_count = 0;
$failed_players = "";
$is_success = false;
$player_list = array();

use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

  if(isset($_FILES['planilha_importada']) && !empty($_FILES['planilha_importada'])){
      $filePath = $_FILES['planilha_importada']['tmp_name'];
      $fileType = $_FILES['planilha_importada']['type'];
      $correct_extensions = array("application/vnd.ms-excel", "application/octet-stream","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

      if($filePath != "" && in_array($fileType,$correct_extensions)){

        $inputFileName = $filePath;
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $cell_scan = 2;
        $index_data = (integer)$sheetData[$cell_scan]["A"];

        while( $index_data != ""){
          $player_index = $index_data;
          $player_name = (string)$sheetData[$cell_scan]["B"];
          $birth_time = strtotime($sheetData[$cell_scan]["C"]);
          $player_birth = date('Y-m-d',$birth_time);
          $player_origin = (string)$sheetData[$cell_scan]["D"];
          $player_level = (integer)$sheetData[$cell_scan]["E"];
          $player_ment = (integer)$sheetData[$cell_scan]["F"];
          $player_fk = (integer)$sheetData[$cell_scan]["G"];
          $player_det = (integer)$sheetData[$cell_scan]["H"];
          $player_pos = (integer)$sheetData[$cell_scan]["I"] . (integer)$sheetData[$cell_scan]["J"] . (integer)$sheetData[$cell_scan]["K"] . (integer)$sheetData[$cell_scan]["L"] .
          (integer)$sheetData[$cell_scan]["M"] . (integer)$sheetData[$cell_scan]["N"] . (integer)$sheetData[$cell_scan]["O"] . (integer)$sheetData[$cell_scan]["P"] .
          (integer)$sheetData[$cell_scan]["Q"] . (integer)$sheetData[$cell_scan]["R"] . (integer)$sheetData[$cell_scan]["T"] . (integer)$sheetData[$cell_scan]["U"] .
          (integer)$sheetData[$cell_scan]["S"] . (integer)$sheetData[$cell_scan]["V"] . (integer)$sheetData[$cell_scan]["W"];

          //$player_list[] = [$player_index, $player_name, $player_birth, $player_origin, $player_level, $player_ment, $player_fk, $player_det, $player_pos];
          if($jogador->modificarPlanilhaImportada($logged_user, $player_index, $player_name, $player_birth, $player_origin, $player_level, $player_ment, $player_fk, $player_det, $player_pos)){

          } else {
            $error_count = $error_count + 1;
            $failed_players .= "\n" . $player_name . " (" . $player_index . ")";
          }

          $cell_scan = $cell_scan + 1;
          $index_data = $sheetData[$cell_scan]["A"];
        }

        if($error_count == 0){
          $is_success = true;
        } else {
          $is_success = false;
          $error_msg .= "Houve erros com " . $error_count . " jogadores: \n" . $failed_players;
        }

        // deleting new spreadsheet
         $spreadsheet->disconnectWorksheets();
         unset($spreadsheet);

die(json_encode([ 'success'=> $is_success, 'error_msg' => $error_msg]));

      } else {

          $error_msg .= "Não foi possível concluir a importação. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Tipo ".$fileType." não é permitido.";
          }
          die(json_encode([ 'success'=> false , 'error_msg' => $error_msg]));
      }
  } else {
      die(json_encode([ 'success'=> false , 'error_msg' => "Não foi selecionado um arquivo"]));
  }

} else {
  die(json_encode([ 'success'=> false , 'error_msg' => "Usuário não logado"]));
}



?>
