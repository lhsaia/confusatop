
<?php

//session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

  $post_code = $_POST['postCode'];

  //$current_user = $_SESSION['user_id'];
  $file_path = "posts/";
  $post_data = file_get_contents($file_path . $post_code);
  


//}

die(json_encode([ 'success'=> true, 'error'=> "", 'post_data' => $post_data]));


 ?>
