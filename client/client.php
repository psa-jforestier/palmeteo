<?php
/**
 ** Client side of Palmeteo.
 ** Must be launch in command line.
 **
 ** params : client.php [file_to_read] [--dry-run]
 **
 ** timeout 50 rtl_fm -f 868.26e6 -M fm -s 500k -r 75k -g 42 -A fast - | bin/rtl_868 -v -dr | php client.php -v -dr
 **/
/**
CREATE TABLE `report` (
 `id` bigint(20) NOT NULL,
 `date` datetime NOT NULL,
 `sensorid` varchar(16) COLLATE utf8_bin NOT NULL,
 `temp` float NOT NULL,
 `reportdate` datetime NOT NULL,
 `sgnal` int(11) NOT NULL,
 `noise` int(11) NOT NULL,
 UNIQUE KEY `id` (`id`),
 KEY `date` (`date`),
 KEY `sensorid` (`sensorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
**/

if (!file_exists('config.php'))
{
	throw new Exception("Missing config file 'config.php'. Initialize one based on the .dist version.");
} else include_once('config.php');


// Simple command line parser
$CONFIG['format'] = 'csv'; // csv , json
$CONFIG['verbose'] = false;
$CONFIG['dryrun'] = false;
$i = 1;
$file = "php://stdin";
$verbose = false;
while($i < $argc)
{
	$param = $argv[$i];
	switch($param) {
		case "-h":
		case "-help":
		case "--help" : {
			// Print help
			break;
		}
		case "-v":
		case "--verbose": {			
			$CONFIG['verbose'] = true;
			break;
		}
		case "-f":
		case "--format": {
			$CONFIG['format'] = $argv[$i++];
			break;
		}
		case "-dr":
		case "--dry-run": {
			$CONFIG['dryrun'] = true;
			break;
		}
		default : {
			$file = $param;
		}
	}
	$i++;
}


echo "Reading data file from $file and send them to ".$CONFIG['serverEndpoint']."\r\n";
if ($CONFIG['verbose']) {
	if ($CONFIG['dryrun']) echo " Dry run is enable, nothing will be send to endpoint.\r\n";
	echo " Input format is ".$CONFIG['format'].".\r\n";
}


$fpos = 0;
$fd = fopen($file, 'rb');
global $NBSEND;
$NBSEND = 0;
while(true)
{
	$fbefore = ftell($fd);
	$s = trim(fgets($fd));
	//echo "$s\r";
	$r = handleString($s);
	if ($r == 200)
	{
		$NBSEND++;
		if ($NBSEND % 10 == 9)
		{
			echo  " $NBSEND\r\n";
		}
	}
	else
	{
		echo "Unable to post data $s. Return code is $r. Wait a while and try again.\r\n";
		sleep(4);
		fseek($fd, $fbefore);
	}
	
	if (feof($fd))
	{
		$fpos = ftell($fd);
		echo "\r\n sleep ... at pos $fpos\r\n";
		sleep(4);
		// reopen file
		fclose($fd);
		$fd = fopen($file, 'rb');
		if (fseek($fd, $fpos) == -1)
		{
			echo "Unable to seek at the end of file. Truncated ? Restarting from the begining\r\n";
			fclose($fd);
			$fd = fopen($file, 'rb');
		}
	}
}

function handleString($s)
{
	global $CONFIG;
	$d = @split(',', $s);
	$date = trim($d[0]);
	$date_ts = 0 + trim($d[1]);
	$sensorId = trim($d[2]);
	$temp = trim($d[3]);
	$hygro = trim($d[4]);
	$info = 0+ trim($d[5]);
	$sig = trim($d[6]);
	$signal = $noise = 0;
	if ($sig != '')
	{
		
		if (preg_match('/\((.*)\/(.*)\)/', $sig, $arr))
		{
			$signal = 0 + $arr[1];
			$noise = 0 + $arr[2];
		}
	}
	
	
	$data = array('date'=>$date, 'date_ts'=>$date_ts, 'sensorId'=>$sensorId, 'temp'=>$temp, 'hygro'=>$hygro, 'info'=>$info, 'signal'=>$signal, 'noise'=>$noise);
	echo ".";
	$url = $CONFIG['serverEndpoint'].'?data='.urlencode(json_encode($data));
	if ($CONFIG['verbose'] === true)
	{
		var_dump($data);
	}
	if ($CONFIG['dryrun'] === false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		if( ! $result = curl_exec($ch))
		{
			trigger_error(curl_error($ch));
			
		}
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch); 
	}
	else
		$httpcode = 200; // Dryrun simulate a 200 return code
	echo ".";
	return $httpcode;
}