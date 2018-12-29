<?php
$stdin = fopen('php://stdin', 'r');
stream_set_timeout($stdin, 1);
while (1) {
  $temp="";
    while (1) {
      if(stream_select($read = array($stdin), $write = NULL, $except = NULL, 0))
        $temp .= ord(fgetc($stdin));
      else break;
    }
	var_dump($temp);
    // F1 : $temp == 27914949126
    // ALT+F1 : $temp = 2727914949126
    // ....
   
    usleep(50000);
}