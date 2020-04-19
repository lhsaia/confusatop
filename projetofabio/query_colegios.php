<?php

include_once($_SERVER['DOCUMENT_ROOT']."/projetofabio/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/projetofabio/colegio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/projetofabio/paroquia.php");

$database = new Database(); 
$db = $database->getConnection();
$colegio = new Colegio($db);
$paroquia = new Paroquia($db);

//url api (cidades)
$apiKey = "AIzaSyBbVOjCs2rwrypNf46iaQ07kRA2e4TC088";
$type = "administrative_area_level_2";

//obter lista de cidades
$stmt = $colegio->listarColegios();

$tipoRodada = 0;

if($tipoRodada == 0){

    $colegio->atualizarCidades();

    return;
}

if($tipoRodada == 1){
while($buscaColegio = $stmt->fetch(PDO::FETCH_ASSOC)){

    $colegio->id = $buscaColegio['id'];
    $address = $buscaColegio['cidade'];

    $address = urlencode($address);

    if(strlen($address) != 0){
        
        $query = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&type={$type}&components=country:BR&key={$apiKey}";
        
        //cURL request
        $ch = curl_init($query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        
        //echo '<pre>' . var_export($data, true) . '</pre>';
        
        $output = json_decode($data);
        $latitude = $output->results[0]->geometry->location->lat;
        $longitude = $output->results[0]->geometry->location->lng;
        $colegio->localizacao = $latitude . "," . $longitude;
        $colegio->endComposto = $output->results[0]->formatted_address;
    } else {
        $colegio->localizacao = 0;
        $colegio->endComposto = 0;
    }
    
        $colegio->atualizarLocalizacao();
    
}
}


    









?>