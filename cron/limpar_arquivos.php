<?php

chdir("/home/lhsaia/confusa.top/export");

foreach (glob("*.zip") as $filename) {
   echo "$filename size " . filesize($filename) . "\n";
   unlink($filename);
}


chdir("/home/lhsaia/confusa.top/usuario");

foreach (glob("*.zip") as $filename) {
   echo "$filename size " . filesize($filename) . "\n";
   unlink($filename);
}

foreach (glob("*.xls") as $filename) {
   echo "$filename size " . filesize($filename) . "\n";
   unlink($filename);
}

foreach (glob("*.xlsx") as $filename) {
   echo "$filename size " . filesize($filename) . "\n";
   unlink($filename);
}

chdir("/home/lhsaia/confusa.top/export");

foreach (glob("*.xlsx") as $filename) {
   echo "$filename size " . filesize($filename) . "\n";
   unlink($filename);
}


?>