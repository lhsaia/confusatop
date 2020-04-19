<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA.top - Sobre";
$css_filename = "mainindex";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
<div class='divForm'>
<form id='formcontato' name="contactform" method="post" action="enviar_email.php">
<table width="450px">
<tr>
 <td valign="top">
  <label for="first_name">Nome</label>
 </td>
 <td valign="top">
  <input  type="text" name="nome" maxlength="50" size="30" required>
 </td>
</tr>
<tr>
 <td valign="top">
  <label for="email">E-mail</label>
 </td>
 <td valign="top">
  <input  type="text" name="email" maxlength="80" size="30" required>
 </td>
</tr>
<tr>
 <td valign="top">
  <label for="comments">Comentários, críticas, sugestões</label>
 </td>
 <td valign="top">
  <textarea class='textComments' name="comentarios" maxlength="1000" cols="25" rows="6" required></textarea>
 </td>
</tr>
<tr>
 <td valign="top">
  <label for="email_confirmation">E-mail confirmation</label>
 </td>
 <td valign="top">
  <input  type="text" name="email_confirmation" maxlength="80" size="30">
 </td>
</tr>
<tr>
 <td colspan="2" style="text-align:center">
  <input type="submit" name='submit' id='submitMail' class='submitbtn' value="Enviar">
 </td>
</tr>
</table>
</form>
</div>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
