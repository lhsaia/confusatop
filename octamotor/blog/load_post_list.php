
<?php

//session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);


  //$current_user = $_SESSION['user_id'];
$file_path = "posts/";
$files = scandir($file_path, 1);
$exportData = Array();

foreach($files as $file){
  if($file != "." && $file != ".."){
    $filename = $file;
    $content = file_get_contents($file_path . $file);
    preg_match('/<h1.*?>(.*)<\/h1>/msi',$content , $title);
    $exploded_filename = explode("U", $filename);
    $timestamp = (int) filter_var($exploded_filename[0], FILTER_SANITIZE_NUMBER_INT);
    $user = (int) filter_var($exploded_filename[1], FILTER_SANITIZE_NUMBER_INT);

    $usuario->setId($user);
    $usuario->readName();
    $author_name = $usuario->getNome();


    $exportData[] = ["title" => $title[1], "fileName" => $filename, "timestamp" => $timestamp, "user" => $user, "author_name" => $author_name];
  }

}


//}

die(json_encode([ 'success'=> true, 'error'=> "", 'post_list' => $exportData]));


 ?>
