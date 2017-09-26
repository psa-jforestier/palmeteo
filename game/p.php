<?php
//http://wiki.andreas-duffner.de/index.php/Install_ncurses_for_php_on_Debian
/**
 apt-get install php5-dev libncursesw5-dev php-pear
 pecl install ncurses
 **/
 
$walls = [
		0b0000=>"...\n...\n...",
		0b0001=>"..▐\n..▐\n..▐",
		0b0010=>"▌..\n▌..\n▌..",
		0b0011=>"▌.▐\n▌.▐\n▌.▐",
		0b0100=>"...\n...\n▄▄▄",
		0b0101=>"..▐\n..▐\n▄▄▟",
		0b0110=>"▌..\n▌..\n▙▄▄",
		0b0111=>"▌.▐\n▌.▐\n▙▄▟",
		0b1000=>"▀▀▀\n...\n...",
		0b1001=>"▀▀▜\n..▐\n..▐",
		0b1010=>"▛▀▀\n▌..\n▌..",
		0b1011=>"▛▀▜\n▌.▐\n▌.▐",
		0b1100=>"▀▀▀\n...\n▄▄▄",
		0b1101=>"▀▀▜\n..▐\n▄▄▟",
		0b1110=>"▛▀▀\n▌..\n▙▄▄",
		0b1111=>"▛▀▜\n▌.▐\n▙▄▟"
		];
	$area = array(
		[0,1,2],
		[3,4,5],
		[6,7,8]
	);
	var_dump($area);
	while(true)
	{
		echo $walls[rand(0,15)];
		echo "\r\n";
	}
ncurses_init();
$fullscreen = ncurses_newwin ( 0, 0, 0, 0);
ncurses_getmaxyx($fullscreen,$a,$b);
ncurses_end();
echo "Width:$b\nHeight:$a\n";
?>
