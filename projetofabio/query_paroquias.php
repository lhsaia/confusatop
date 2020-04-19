<?php

include_once("/home/lhsaia/confusa.top/projetofabio/database.php");
include_once("/home/lhsaia/confusa.top/projetofabio/colegio.php");
include_once("/home/lhsaia/confusa.top/projetofabio/paroquia.php");

$database = new Database();
$db = $database->getConnection();
$colegio = new Colegio($db);
$paroquia = new Paroquia($db);

//url api (cidades)
$apiKey = "AIzaSyBbVOjCs2rwrypNf46iaQ07kRA2e4TC088";
$type = "church";

//obter lista de cidades
$stmt = $paroquia->listarParoquias();

$active = 0;

if($active == 1){
while($buscaParoquia = $stmt->fetch(PDO::FETCH_ASSOC)){

    $paroquia->id = $buscaParoquia['id'];
    $nomeBusca = $buscaParoquia['nomeParoquia'];
    $geocodeCidade = $buscaParoquia['centro'];

    $nomeBusca = urlencode($nomeBusca);

    if(strlen($nomeBusca) != 0){


        $query = "https://maps.googleapis.com/maps/api/place/search/json?name={$nomeBusca}&type={$type}&location={$geocodeCidade}&radius=1000000&key={$apiKey}";
        //$query = "https://maps.googleapis.com/maps/api/geocode/json?address={$nomeBusca}&type={$type}&components=country:BR|administrative_area_level_2:{$nomeCidade}&key={$apiKey}";
        
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
        $paroquia->localizacao = $latitude . "," . $longitude;
        $paroquia->endComposto = $output->results[0]->name . " - " . $output->results[0]->vicinity;
    } else {
        $paroquia->localizacao = 0;
        $paroquia->endComposto = 0;
    }
    
        $paroquia->atualizarLocalizacao();

        //return;
    
}
}


?>