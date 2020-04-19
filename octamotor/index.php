<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Home";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = "octamotorIndex";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<div id="container-home-octamotor">
  <div id="container-menu-octamotor">
    <a class="octamotor-menu orange" href="driver_info.php">Pilotos</a>
    <a class="octamotor-menu blue" href="car_info.php">Equipes</a>
    <a class="octamotor-menu white" href="track_info.php">Circuitos</a>
    <a class="octamotor-menu yellow" href="competition_info.php">Competições</a>
    <a class="octamotor-menu red" href="live_info.php">Ao vivo</a>
    <a class="octamotor-menu purple disabled">Estatísticas</a>
  </div>
  <div id="container-image-octamotor">
    <div class="image-wrapper">
    <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/octa_motor.svg"); ?>
    </div>
    <a class="image-wrapper blog-button"  href='blog'>
    <?php echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/images/foca.svg"); ?>
  </a>
  </div>
</div>

<script>


</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
