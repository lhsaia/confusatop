<?php

   include_once("/home/lhsaia/confusa.top/config/database.php");
include_once("/home/lhsaia/confusa.top/objetos/jogador.php");
    $database = new Database();
    $db = $database->getConnection();
$jogador = new Jogador($db);

$error_count = 0;
if($jogador->eliminarTransferenciasNegadas()){

} else {
$error_count++;
}

if($jogador->desconvocarSub21ForaIdade()){

} else {
$error_count++;
}

if($jogador->desconvocarSub20ForaIdade()){

} else {
$error_count++;
}

if($jogador->desconvocarSub18ForaIdade()){

} else {
$error_count++;
}

if($error_count > 0){
    echo "Erro!";
} else {
    echo "Sucesso!";
}


?>