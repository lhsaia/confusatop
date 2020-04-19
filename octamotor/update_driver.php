<?php

// verify user_id, open_window, driver_owner, admin_status
$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/driver.php";

$octamotorDatabase = new OctamotorDatabase();
$odb = $octamotorDatabase->getConnection();

$driver = new Driver($odb);

if($driver->updateDriver($id)){
  $is_success = true;
  $error_msg = "Solicitação realizada com sucesso!";
} else {
  $is_success = false;
  $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
}

die(json_encode(["success" => $is_success, "error_msg" => $error_msg]));


 ?>
