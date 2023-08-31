<?php
/**
 ** Generate a windrose diagram based on OpenWeatherMap data
 ** Input : API key, location id, date to start the history of wind data
 **
 historical : https://api.openweathermap.org/data/2.5/onecall/timemachine?units=metric&id=3023645&dt=<nowtimestamp - 5 day>
 Windrose in javascript from here : https://jscharting.com/examples/chart-types/radar-polar/wind-rose/#
 **/
 
include_once('config.php');

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if( !$result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return array('code'=>$httpcode, 'content'=>$result);
}

function WindSpeedToWindArea($speed)
{
  if ($speed <= 2) return "0-2";
  if ($speed <= 5) return "2-5";
  if ($speed <= 7) return "5-7";
  if ($speed <= 10) return "7-10";
  if ($speed <= 15) return "10-15";
  if ($speed <= 20) return "15-20";
  return "20+";
}

$now = time();
$historical_data = array();
for($i = 0; $i<= 5 ; $i++)
{
  
  $startdate = $now - ($i * 24 * 60 * 60);
  $url = 'https://api.openweathermap.org/data/2.5/onecall/timemachine?units=metric&appid='.$CONFIG['openWeatherMap.appId'].'&'.$CONFIG['openWeatherMap.latlong']."&dt=$startdate";
  // echo $url;
  $data = curl_get($url);
  if ($data['code'] == 200)
  {
    $data = json_decode($data['content'], true);
    foreach($data['hourly'] as $data_hourly)
    {
      $date = $data_hourly['dt'];
      $wind_speed = $data_hourly['wind_speed']; // in m/s
      $wind_deg = $data_hourly['wind_deg'];
      $wind_gust = @$data_hourly['wind_gust'];
      // in some case, deg is not a 10-multiple, so we round it
      $wind_deg = 10 * round($wind_deg / 10);
      if ($wind_deg == 0) $wind_deg = 360;
      //echo date('r', $date);
      //echo ";$date;$wind_speed;$wind_gust;$wind_deg\n";
      $historical_data[$date] = array($wind_speed, $wind_gust, $wind_deg);
    }
  }
}
$nbdata = count($historical_data);
//echo "$nbdata data collected\n";

$databyangles = array();
foreach($historical_data as $date=>$data)
{
  
  $speed = $data[0];
  $angle = $data[2];
  $speedarea = WindSpeedToWindArea($speed);
  $databyangle = @$databyangles[$angle];
  if ($databyangle == NULL)
  {
    $databyangle = array('0-2'=>0, '2-5'=>0, '5-7'=>0, '7-10'=>0, '10-15'=>0, '15-20'=>0, '20+'=>0);
  }
  $databyangle[$speedarea] = $databyangle[$speedarea] + 1;
  $databyangles[$angle] = $databyangle;
}

echo "angle,speed,percent\n";
for($i = 0; $i <= 360; $i+=10)
{
  $databyangle = @$databyangles[$i];
  //if ($databyangle == NULL)
  //{
  //  $databyangle = array('0-2'=>0, '2-5'=>0, '5-7'=>0, '7-10'=>0, '10-15'=>0, '15-20'=>0, '20+'=>0);
  //}
  if ($databyangle != NULL)
    foreach($databyangle as $speedarea=>$nb)
    {
      $nb = sprintf("%.3f", $nb / $nbdata);
      echo "$i,$speedarea,$nb\n";
    }
}