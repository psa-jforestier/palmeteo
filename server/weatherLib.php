<?php

include_once('database.mysql.php');

function weatherGetAllSensorId($db, $fromDate = NULL, $toDate = NULL)
{
	$q = "SELECT distinct(sensorid) FROM `report` where (
	1 = 1 
	";
	if ($fromDate !== NULL)
		$q.="and 'date' >= '$fromDate' ";
	if ($toDate !== NULL)
		$q.="and 'date' <= '$toDate'";
	$q.= " ) order by sensorid";
	$s = selectArray($q);
	
	$r = array();
	foreach($s as $v)
	{		
		$r[] = $v['sensorid'];
	}
	return $r;
}

function weatherGetSensorsWithName($db, $sensors)
{
	$r = array();
	foreach($sensors as $sensId)
	{
		$r[] = array('id'=>$sensId, 'name'=>$sensId);;
	}
	return $r;
}

function weatherGetDataForSensors($db, $fromDate = NULL, $toDate = NULL, $sensor)
{
    /**
    SELECT 
sensorid, report.date, 
avg(report.temp) as avgtemp, 
FLOOR((UNIX_TIMESTAMP(report.date) / 60)) as minute FROM `report` where ( 1 = 1 and sensorid = 33 ) 
group by minute
order by minute, 'date', sensorid
**/
    $q = "SELECT 
        sensorid, 
        report.date as `date`,  
        UNIX_TIMESTAMP(report.date) as date_ts,
        format(avg(report.temp), 1) as avgtemp,
        FLOOR((UNIX_TIMESTAMP(report.date) / 60)) as minute
    FROM `report` where (
	1 = 1 
	";
	if ($fromDate !== NULL)
		$q.="and 'date' >= '$fromDate' ";
	if ($toDate !== NULL)
		$q.="and 'date' <= '$toDate'";
    if ($sensor !== NULL)
        $q.= "and sensorid ='$sensor'";
	$q.= " ) 
    group by minute
    order by minute,sensorid, 'date'
    ";
   
	$s = selectArray($q);
    return $s;
}