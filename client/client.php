<?php
/**
 ** Client side of Palmeteo.
 ** Must be launch in command line.
 **
 ** params : client.php [file_to_read] [-dr | --dry-run] [-l | --loop] [-re | --retry-on-error]
 **
 ** timeout 50 rtl_fm -f 868.26e6 -M fm -s 500k -r 75k -g 42 -A fast - | bin/rtl_868 -v | php client.php -v -dr
 **  rtl_fm -f 868.26e6 -M fm -s 500k -r 75k -g 42 -A fast - | ../bin/rtl_868 -v > temperature.dat
 **  cat temperature.dat | php client.php -v -dr
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
$CONFIG['loop'] = false;
$CONFIG['retryonerror'] = false;
$CONFIG['output'] = 'default'; // default, wu
$returncode = 0;
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
		case "-o":
		case "--output": {
			$CONFIG['output'] = $argv[$i+1];
			$i++;
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
		case "-l":
		case "--loop": {
			$CONFIG['loop'] = true;
			break;			
		}
		case "-re":
		case "--retry-on-error": {
			$CONFIG['retryonerror'] = true;
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
$exit = false;
while(!$exit)
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
		echo "Unable to post data $s. Return code is $r.\r\n";
		$returncode = ($returncode == 0 ? 1 : 2); // =1 at 1st error, =2 on 2nd and + error
		if ($CONFIG['retryonerror'] === true)
		{
			echo "Wait a while and try again.\r\n";
			sleep(4);
			fseek($fd, $fbefore);
		}
	}
	
	if (feof($fd))
	{
		if ($CONFIG['loop'])
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
		else
		{
			fclose($fd);
			$exit = true;
		}
	}
}

exit($returncode);

function sendToWeatherUnderground($data)
// http://wiki.wunderground.com/index.php/PWS_-_Upload_Protocol
{
	global $CONFIG;
	$dateutc = gmdate('Y-m-d H:i:s', strtotime($data['date']));
	$temp_f = null;
	if (is_numeric($data['temp']))
		$temp_f = sprintf('%.3f', 32 + ($data['temp'] * 9/5));
	$hygro = null;
	if (is_numeric($data['hygro']))
		$hygro = $data['hygro'];
	$url = 
	//"https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php"
	"https://rtupdate.wunderground.com/weatherstation/updateweatherstation.php"
	."?action=updateraw&ID=".urlencode($CONFIG['weatherUnderground.stationId'])."&PASSWORD=".urlencode($CONFIG['weatherUnderground.key'])."&dateutc=".urlencode($dateutc);
	
	// Is it the indoor sensor
	if ($data['sensorId'] == $CONFIG['sensorIdInternal'])
	{
		$url = $url . "&indoortempf=$temp_f&indoorhumidity=$hygro";
	}
	else
	{
		$sensorWuid = $CONFIG['sensorIdMapping'][$data['sensorId']];
		$url = $url . "&temp${sensorWuid}f=$temp_f&humidity=$hygro";
	}
	$url = $url . "&realtime=1&rtfreq=2.5&softwaretype=".urlencode("PalmeteoPHPLib");
	if ($CONFIG['verbose'] === true)
	{
		echo "Weatherunderground data handler : $url\r\n";
	}
	echo ".";
	if ($CONFIG['dryrun'] === false)
	{
		return curl_get($url);
	}
	return 200;
}
function sendToDefault($data)
{
	global $CONFIG;
	$url = $CONFIG['serverEndpoint'].'?data='.urlencode(json_encode($data));

	if ($CONFIG['verbose'] === true)
	{
		echo "Default data handler : $url\r\n";
	}
	if ($CONFIG['dryrun'] === false)
	{
		$httpcode = curl_get($url);
	}
	else
		$httpcode = 200; // Dryrun simulate a 200 return code
	echo ".";
	return $httpcode;
}

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	//var_dump($result);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $httpcode;
}
function handleString($s)
{
	global $CONFIG;
	$d = @split(',', $s);
	if (count($d) < 7)
	{
		if ($CONFIG['verbose'] === true)
			echo "Invalid line, skipping\r\n";
		return 200;
	}
	if ($CONFIG['verbose'] === true)
		echo "Processing line : \"$s\"\r\n";
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
	if ($CONFIG['verbose'] === true)
	{
		print_r($data);
	}
	$result = 0;
	if ($CONFIG['output'] === 'default')
		$result = sendToDefault($data);
	else if ($CONFIG['output'] === 'wu')
		$result = sendToWeatherUnderground($data);
	return $result;
}