<?php

$item = $_POST['item'];

if($item == "capacete"){
    die(json_encode(["data" => urlencode("/octamotor/repo/Capacete.psd")]));
} else if($item == "carro"){
    die(json_encode(["data" => urlencode("/octamotor/repo/Carro.zip")]));
} else if($item == "macacao"){
    die(json_encode(["data" => urlencode("/octamotor/repo/Macacao.psd")]));
}



?>