<?php
header("Cache-Control: max-age=1"); // Anti varnish
define('DB_USER','root');
define('DB_PASS','');
define('DB_HOST','localhost');
define('DB_DBASE','weather');
include('database.mysql.php');

$data = @$_REQUEST['data'];

/**
http://weather.forestier.xyz/upload.php?data={"date":"2016-12-15 08:55:37","date_ts":1481788537,"sensorId":"33","temp":"19.90","hygro":"nan","info":0,"signal":687,"noise":3750}
**/

if ($data != '')
{
	$data = json_decode($data);
	
	$date = db_escape_string($data->date);
	$date_ts = db_escape_string($data->date_ts);
	$sensorid = db_escape_string($data->sensorId);
	if (!is_numeric($data->temp))
		$temp = '';
	else
		$temp = db_escape_string($data->temp);

	if (!is_numeric($data->hygro))
		$hygro = '';
	else
		$hygro = db_escape_string($data->hygro);
	$info = db_escape_string($data->info);
	$signal = db_escape_string($data->signal);
	$noise = db_escape_string($data->noise);

	// Check for input value
	if ($date == '') $date = date('Y-M-D H:i:s');
	if ($signal == '') $signal = 0;
	if ($noise == '') $noise = 0;
	// To optimize DB storage, we round the report date at 10s. Thus, the seconds are only a multiple of ten
	$date[18] = '0';

	$q = "insert into report set `date` = '$date', sensorid='$sensorid', 
	temp='$temp', reportdate=now(), `signal`='$signal', noise='$noise', info='$info'
	on duplicate key update 
	temp='$temp', reportdate=now(), `signal`='$signal', noise='$noise', info='$info'";
	$db = open_db();
	$res = insert($q);
	$mysqlErr = mysql_errno($db);	
	$mysqlErrStr = mysql_error($db);
	if ($mysqlErr == 1062)
	{
		$err = 0;
		$msg = 'OK. Data previously inserted';
	}
	else if ($res === 0 && $mysqlErr != 0)
	{		
		$err = 1;
		$msg = 'Failed to execute query. err='.$mysqlErr.' '.$mysqlErrStr. " q=$q";
	}	
	else
	{
		$err = 0;
		$msg = 'OK';
	}
	
}
else
{
	$err = 2;
	$msg = 'Missing data parameters';
}

if ($err !== 0)
{
	http_response_code(500);
}
echo json_encode(array('err'=>$err, 'msg'=>$msg));