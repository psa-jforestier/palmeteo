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

To implement  :  https://www.pwsweather.com/obs/KFLTARPO8.html
https://openweathermap.org/stations

http://www.chartjs.org/samples/latest/charts/line/basic.html

https://canvasjs.com/html5-javascript-dynamic-chart/
https://www.pwsweather.com/
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
$CONFIG['output'] = 'default'; // default, wu, owm, folder
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
			if ($CONFIG['output'] == 'folder')
			{
				$CONFIG['folder'] = $argv[$i+1];
				$i++;
			}
			break;
		}
		case "-f":
		case "--format": {
			$CONFIG['format'] = $argv[$i+1];
			$i++;
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

echo "Reading data file from $file and send them to ".$CONFIG[$CONFIG['output']]."\r\n";
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

function sendToOpenWeatherMap($data)
// https://openweathermap.org/stations
{
	global $CONFIG;
	
	$sensorId = $data['sensorId'];
	$OWM = @$CONFIG['OWM'][$sensorId];
	
	if ($OWM == null)
	{
		// 1st time we see this sensor in this roll. 
		// Try to get OWM station ID
		// List all stations for this account
		$owmId = null;
		$url = 'http://api.openweathermap.org/data/3.0/stations?APPID='.$CONFIG['openWeatherMap.appId'];
		$res = curl_get_json($url);
		foreach($res['content'] as $owdStations)
		{
			if ($owdStations->external_id == $sensorId)
			{
				$owmId = $owdStations->id;
				break;
			}
		}
		if ($owmId === null) // No station found
		{
			// Create a new station
			$url = 'http://api.openweathermap.org/data/3.0/stations?APPID='.$CONFIG['openWeatherMap.appId'];
			$post_params = array(
				'external_id'=>"$sensorId",
				'name'=>"Sensor $sensorId at ".$CONFIG['location.name'],
				'latitude'=>$CONFIG['location.lat'],
				'longitude'=>$CONFIG['location.lon'],
				'altitude'=>$CONFIG['location.alt'],
			);
			$res = curl_post_json($url, $post_params);
			if ($res['code'] == 201)
			{
				$CONFIG['OWM'][$sensorId] = $res['content'];
				$OWM = $CONFIG['OWM'][$sensorId];
				$owmId = $res['content']->ID;
			}
		}
		if ($CONFIG['verbose'] === true)
			echo "Sensor $sensorId match with OWM station id $owmId\n";
	}
	else
	{
		$owmId = $OWM->ID;
	}
	// Post data to this station
	$url = 'http://api.openweathermap.org/data/3.0/measurements?APPID='.$CONFIG['openWeatherMap.appId'];
	$owdData = array(
			'station_id'=>$owmId,
			'dt'=>0 + $data['date_ts'],
			'temperature'=>0 + $data['temp']
	);
	if (is_numeric($data['hygro']))
		$owdData['humidity'] = 0 + $data['hygro'];
	$post_params = array($owdData);
	$res = curl_post_json($url, $post_params);
	if ($res['code'] != 204)
	{
		var_dump($res);
		return 0;
	}
	else
	{
		echo ".";
		return 200;
	}

}

function curl_get_json($url)
{
	global $CONFIG;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	if($CONFIG['verbose'] === true)
	{
		var_dump(trim($result));
	}
	//var_dump($result);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);	
	if($httpcode >=400 && $httpcode < 600 && $CONFIG['verbose'] === true)
	{
		echo "Server reply with a $httpcode code\r\n";
	}
	return array('code'=>$httpcode, 'content'=>json_decode($result));
}

function curl_post_json($url, $data) // data is an array
{
	global $CONFIG;
	$data_string = json_encode($data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data_string))
	);
	$result = curl_exec($ch);
	if($result === false)
	{
		trigger_error(curl_error($ch));
	}
	if($CONFIG['verbose'] === true)
	{
		var_dump(trim($result));
	}	
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($httpcode >=400 && $httpcode < 600 && $CONFIG['verbose'] === true)
	{
		echo "Server reply with a $httpcode code\r\n";
	}
	curl_close($ch);
	return array('code'=>$httpcode, 'content'=>json_decode($result));
}

function curl_delete_json($url)
{
	global $CONFIG;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'));
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	if($CONFIG['verbose'] === true)
	{
		var_dump(trim($result));
	}	
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($httpcode >=400 && $httpcode < 600 && $CONFIG['verbose'] === true)
	{
		echo "Server reply with a $httpcode code\r\n";
	}
	curl_close($ch);
	return array('code'=>$httpcode, 'content'=>json_decode($result));
}
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
		// $url = $url . "&indoortempf=$temp_f&indoorhumidity=$hygro";
		// WU have a bug : cant send data to different sensor
		return 200;
	}
	else
	{
		if ($data['sensorId'] == $CONFIG['sensorIdExternal'])
		{
			$url = $url . "&tempf=$temp_f";
		}
		else
		{
			//$sensorWuid = $CONFIG['sensorIdMapping'][$data['sensorId']];
			//$url = $url . "&temp${sensorWuid}f=$temp_f&humidity=$hygro";
			// WU have a bug : cant send data to different sensor
			return 200;
		}
	}
	$url = $url . "&realtime=1&rtfreq=2.5&softwaretype=".urlencode("PalmeteoPHPLib");
	if ($CONFIG['verbose'] === true)
	{
		echo "Weatherunderground data handler : $url\r\n";
	}
	echo ".";
	
	if ($CONFIG['dryrun'] === false)
	{
		$r = curl_get($url);
		//die;
		return $r;
	}
	
	return 200;
}

function sendToFolder($data, $where)
{
	global $CONFIG;
	if ($CONFIG['verbose'] === true)
	{
		echo "Will create data file in $where\n";
	}
	$filenames = array(
		$where.basename('sensor_model_'.$data['model']),
		$where.basename('sensor_id_'.$data['sensorId']),
		$where.basename('sensor_model_'.$data['model'].'_id_'.$data['sensorId'])
	);
	foreach($filenames as $f)
	{
    if ($CONFIG['verbose'] === true)
    {
      echo "file $f\n";
    }
		
    /**
		fputs($fd, 
			sprintf("Temp=%s;Hygro=%s;Rain_mm=%s;Rain_raw=%s;Lowbat=%d;Date_TS=%d;Date=%s;Id=%s;Model=%s\n",
				($data['temp'] ?? "n/a"), // https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce
				($data['hygro'] === NULL ? "n/a" : $data['hygro']),
        ($data['rain_mm'] ?? 'n/a'),
        ($data['rain_raw'] ?? 'n/a'),
				$data['lowbatt'],
				$data['date_ts'],
				$data['date'],
				$data['sensorId'],
				$data['model'])
			);
      **/
    $filecontent = '';
    // If it is a temp/hygro sensor
    if ($data['temp']!==NULL)
    {
      $filecontent .= sprintf("Temp=%s;Hygro=%s;",
        ($data['temp'] ?? "n/a"),
        ($data['hygro'] ?? "n/a")
      );
    }
    // if it is a rain sensor
    if ($data['rain_mm'] !== NULL)
    {
      $filecontent .= sprintf("Rain_mm=%s;Rain_raw=%s;",
        ($data['rain_mm'] ?? "n/a"),
        ($data['rain_raw'] ?? "n/a")
      );
    }
    $filecontent .= sprintf("Lowbat=%d;Date_TS=%d;Date=%s;Id=%s;Model=%s\n",
      $data['lowbatt'],
      $data['date_ts'],
      $data['date'],
      $data['sensorId'],
      $data['model']
    );
    if ($CONFIG['verbose'] === true)
      echo $filecontent;
    // for all kind of sensor
    $fd = fopen($f, 'w');
    fputs($fd, $filecontent);
		fclose($fd);
	}

	return 200;
				                 
}
function sendToDefault($data)
{
	global $CONFIG;
	$url = $CONFIG['serverEndpoint'].'?data='.urlencode(json_encode($data));

	if ($CONFIG['verbose'] === true)
	{
		echo "Default data handler : ".urldecode($url)."\r\n";
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
	global $CONFIG;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	if($CONFIG['verbose'] === true)
	{
		var_dump(trim($result));
	}
	//var_dump($result);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $httpcode;
}
function handleString($s)
{		
	global $CONFIG;
	if ($CONFIG['format'] == 'csv')
	{
		$d = @split(',', $s);
		if (count($d) < 7)
		{
			if ($CONFIG['verbose'] === true)
				echo "Skipping line because it is an invalid csv : $s\r\n";
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
	}
	else if ($CONFIG['format'] == 'json')
	{
		$d = json_decode($s, true, 4);
    if ($d === null || $d == "" ||
      (!isset($d['time']) || !isset($d['id']) )
    )
    {
      echo "Skipping line because it is an invalid json : $s\r\n";
			return 200;
    }

		if (!isset($d['date_ts']))
			$d['date_ts'] = strtotime($d['time']); // Must be a localtime
    if (isset($d['battery']))
      $d['lowbatt'] = (@$d['battery'] != 'OK' ? '1' : '0');
    else if (isset($d['battery_ok']))
      $d['lowbatt'] = ($d['battery_ok'] == '1' ? '0' : '1');
		$data = array('date'=>$d['time'], 'date_ts'=>$d['date_ts'], 'sensorId'=>$d['id'], 
			'model'=>$d['model'],
			'temp'=>@$d['temperature_C'], 'hygro'=>@$d['humidity'], 
      'rain_mm' => @$d['rain_mm'], 'rain_raw' => @$d['rain_raw'],
			'info'=>'', 
			'newbatt'=>$d['newbattery'], 'lowbatt'=>$d['lowbatt'], 
			'signal'=>0, 'noise'=>0);
	}
	else
	{
		die('No format specified.');
	}
	if ($CONFIG['verbose'] === true)
	{
		print_r($data);
	}
	$result = 0;
	if ($CONFIG['output'] === 'default')
		$result = sendToDefault($data);
	else if ($CONFIG['output'] === 'wu')
		$result = sendToWeatherUnderground($data);
	else if ($CONFIG['output'] === 'owm')
		$result = sendToOpenWeatherMap($data);
	else if ($CONFIG['output'] == 'folder')
		$result = sendToFolder($data, $CONFIG['folder']);
	else
		die('No output format specified');
	return $result;
}
