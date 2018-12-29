<?php

$wu_stationid = urlencode('IFRANCON22');
$wu_key = urlencode('klyqj5y1');

$url = 'https://rtupdate.wunderground.com/weatherstation/updateweatherstation.php';

date_default_timezone_set("UTC");
$nowutc = urlencode(date('Y-m-d H:i:s'));

$tempc = rand(200,250) / 10;
$tempf = urlencode(sprintf('%.3f', 32 + ($tempc * 9/5)));

$url = "$url?action=updateraw&ID=$wu_stationid&PASSWORD=$wu_key&dateutc=$nowutc&tempf=$tempf&humidity=&realtime=1&rtfreq=2.5&softwaretype=PHPTEST";

echo "tempc = $tempc \ttempf=$tempf\r\n";
echo "$url\r\n";
curl_get($url);

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	var_dump($result);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $httpcode;
}