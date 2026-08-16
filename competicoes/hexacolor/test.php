<?php

$output = shell_exec("/home/lhsaia/confusa.top/java_station/jdk/jdk1.8.0_231/bin/java -Djava.library.path=/confusa.top/java_station/hexacolor/lib -Djava.io.tmpdir=/home/lhsaia/confusa.top/java_station/tmp -jar HexacolorYMTv2.jar -m agenda/json.txt 2>&1\n");
//$output = shell_exec("/home/lhsaia/confusa.top/java_station/jdk/jdk-11.0.7/bin/java -jar /home/lhsaia/confusa.top/java_station/helloworld.jar");

print_r($output);

?>