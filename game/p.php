<?php

stream_set_blocking(STDIN, 0);
$fd = fopen('php://stdin', 'r');
while(true)
{
	$c = fgets($fd, 1);
	if ($c !== false)
		var_dump($c);
}
