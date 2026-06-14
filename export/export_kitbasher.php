<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$time = new Time($db);
$liga = new Liga($db);

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	//escrever informações da liga
	$codigo_liga = $_POST['codigo_liga'];
	$nome_liga = $liga->nomeLiga($codigo_liga);

	$today = date("Y-m-d H:i:s");

	$saving_file_name = "kb_" . $nome_liga. ".csv";
	$saving_file_name = str_replace("/","_",$saving_file_name);
	$saving_file_name = str_replace(" ","_",$saving_file_name);
	
	$exportFiles = array();
	
	function rgbToHex($rgb){
		$rgbarr = explode(" ", chunk_split($rgb,3, " "));
		return sprintf("#%02x%02x%02x", $rgbarr[0], $rgbarr[1], $rgbarr[2]);
	}
	
	//loop foreach times
	$stmt_times = $time->readAll(0,10000,null, $codigo_liga);
	$lista_times = array();
	while ($row_times = $stmt_times->fetch(PDO::FETCH_ASSOC)){
		extract($row_times);
		$lista_times[] = array($Nome,$ID,null,"ALL","ALL",$TresLetras,"escudos/" . $Escudo, rgbToHex($Uni1Cor1), rgbToHex($Uni1Cor2), rgbToHex($Uni1Cor3), rgbToHex($Uni2Cor1), rgbToHex($Uni2Cor2), rgbToHex($Uni2Cor3),null,null,null,null,null,null);
		$exportFiles[] = [$_SERVER['DOCUMENT_ROOT']."/images/escudos/" . $Escudo, "escudos/".$Escudo];
	}
	
	$csv_handler = fopen ($_SERVER['DOCUMENT_ROOT']."/export/kitbasher_repo/" . $saving_file_name,'w');
	
			
	
	
	foreach ($lista_times as $unico_time) {
		//fputcsv($csv_handler, $unico_time, ',', "");
		$array = str_replace('"', '', $unico_time);
		fputs($csv_handler, implode(',', $array)."\n\r");
		
	}

	fclose ($csv_handler);

	$full_path_name = $_SERVER['DOCUMENT_ROOT']."/export/kitbasher_repo/" . $saving_file_name;
	
	
	
	//criar zip e fazer exportação
	$zip_name = "kitbasher_repo/" .$nome_liga.'_kitbasher.zip'; //the real path of your final zip file on your system
	
	if(file_exists($_SERVER['DOCUMENT_ROOT']. "/export/" . $zip_name)){
		unlink($_SERVER['DOCUMENT_ROOT']. "/export/" . $zip_name);
	}
	
	$zip = new ZipArchive;
	$zip->open($zip_name, ZIPARCHIVE::CREATE);
	
	$zip->addFile($full_path_name, $saving_file_name);
	
	foreach($exportFiles as $file)
	{
		$zip->addFile($file[0],$file[1]);

	}
	
	$zip->close();

	die(json_encode([ 'success'=> true, 'filename'=>"/export/" . $zip_name]));

}



die(json_encode([ 'success'=> false ]));

?>
