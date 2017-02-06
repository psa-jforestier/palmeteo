<?php
header("Cache-Control: max-age=1"); // Anti varnish
define('DB_USER','root');
define('DB_PASS','');
define('DB_HOST','localhost');
define('DB_DBASE','weather');
include('database.mysql.php');

$data = @$_REQUEST['data'];

/**
http://weather.forestier.xyz/upload.php?data=%7B%22date%22%3A%222016-12-15+08%3A55%3A37%22%2C%22date_ts%22%3A1481788537%2C%22sensorId%22%3A%2233%22%2C%22temp%22%3A%2219.90%22%2C%22hygro%22%3A%22nan%22%2C%22info%22%3A0%2C%22signal%22%3A687%2C%22noise%22%3A3750%7D
**/

if ($data != '')
{
	$data = json_decode($data);
	$db = open_db();
	$date = db_escape_string($data->date);
	$date_ts = db_escape_string($data->date_ts);
	$sensorid = db_escape_string($data->sensorId);
	$temp = db_escape_string($data->temp);
	$hygro = db_escape_string($data->hygro);
	$info = db_escape_string($data->info);
	$signal = db_escape_string($data->signal);
	$noise = db_escape_string($data->noise);

	$q = "insert into report set
	`date` = '$date', sensorid='$sensorid', temp='$temp', reportdate=now(),
	`signal`='$signal', noise='$noise',
	info='$info'";
	
	$res = insert($q);
	$mysqlErr = mysql_errno($db);	
	
	if ($mysqlErr == 1062)
	{
		$err = false;
		$msg = 'OK. Data previously inserted';
	}
	else if ($res === 0)
	{		
		$err = true;
		$msg = 'Failed to execute query. err='.$mysqlErr;
	}	
	else
	{
		$err = false;
		$msg = 'OK';
	}
	
}
else
{
	$err = true;
	$msg = 'missing data parameters';
}

if ($err === true)
{
	http_response_code(500);
}
echo json_encode(array('err'=>$err, 'msg'=>$msg));