
<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['admin_status'] != 0){

  $current_user = $_SESSION['user_id'];


  $post_data = $_POST['jsonData']['element-0']['value'];
  if(isset($_POST['editingFileName']) && $_POST['editingFileName'] != ""){
    $filename = $_POST['editingFileName'];
    $exploded_filename = explode("U", $filename);
    $owner = (int) filter_var($exploded_filename[1], FILTER_SANITIZE_NUMBER_INT);
    if($owner != $current_user){
      die(json_encode([ 'success'=> false, 'error'=> ""]));
    }
  } else {
    $filename = "P" . time() . "U" .$current_user .".json";
  }

  $file_path = "/octamotor/blog/posts/";
  $file = fopen($_SERVER['DOCUMENT_ROOT'].$file_path . $filename, "wa+");
  //chmod($filename, 0777);
  fwrite($file, $post_data);
  fclose($file);



}

die(json_encode([ 'success'=> true, 'error'=> ""]));


 ?>
