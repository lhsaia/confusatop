<?php 

$command = escapeshellcmd('python_scrapper.py');
//$output = shell_exec($command);
$output = shell_exec("python ". $command . " 2>&1");
echo $output;

?>