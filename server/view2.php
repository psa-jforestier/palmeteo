<?php
header("Cache-Control: max-age=1"); // Anti varnish
ini_set("display_errors", 1);
define('DB_USER','root');
define('DB_PASS','');
define('DB_HOST','localhost');
define('DB_DBASE','weather');
include('database.mysql.php');
include('weatherLib.php');

// https://developers.google.com/chart/interactive/docs/gallery/linechart
$db = open_db();
if (@$_REQUEST['from'] != '')
	$from = $_REQUEST['from'];
else
	$from = null;
if (@$_REQUEST['to'] != '')
	$to = $_REQUEST['to'];
else
	$to = null;
$sensors = @$_REQUEST['sensors'];
if ($sensors === NULL)
	$sensors = weatherGetAllSensorId($db, $from, $to);
$sensorsWithName = weatherGetSensorsWithName($db, $sensors);
//var_dump($sensorsWithName);
//$sensorsWithName = array();
//$sensorsWithName[] = array('id'=>23, 'name'=>'23');
//$sensorsWithName[] = array('id'=>33, 'name'=>'33');
$data = array();
$data2 = array();
foreach($sensorsWithName as $s)
{
   
    $data[] = array('id'=>$s['id'], 'name'=>$s['name'], 'values'=>weatherGetDataForSensors($db, $from, $to, $s['id']));
    $values = weatherGetDataForSensors($db, $from, $to, $s['id']);
    foreach($values as $v)
    {
        $key = $v['date_ts'];
        $sen = $v['sensorid'];
        $data2["$key"]["$sen"] = $v;
    }
}

?>
<html>
	<head>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi?fake=.js"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  
  <script type='text/javascript'>//<![CDATA[

function drawChart() {
  
    var data = new google.visualization.DataTable();
      data.addColumn('date', 'X');
      <?php
      foreach($sensorsWithName as $s)
      {
        ?>
        data.addColumn('number', '<?=$s['name']?>');
        <?
      }
      ?>
  
     data.addRows([
     <?php
     /**
        [1,  37.8, 80.8, 41.8],
        [2,  30.9, 69.5, 32.4],
        [3,  25.4,   57, 25.7],
        [4,  11.7, 18.8, 10.5],
        [5,  11.9, 17.6, 10.4],
        [6,   8.8, 13.6,  7.7],
        [7,   7.6, 12.3,  9.6],
        [8,  12.3, 29.2, 10.6],
        [9,  16.9, 42.9, 14.8],
        [10, 12.8, 30.9, 11.6],
        [11,  5.3,  7.9,  4.7],
        [12,  6.6,  8.4,  5.2],
        [13,  4.8,  6.3,  3.6],
        [14,  4.2,  6.2,  3.4]
        **/
        $i = 1;
        foreach($data2 as $date=>$data)
        {
            echo "[ new Date(",1000*$date,"), ";
            
            foreach($sensorsWithName as $s)
            {
                $sId = $s['id'];
                $value = @$data[$sId];
                if ($value !== NULL)
                {
                    echo $value['avgtemp'];
                }
                echo", ";
            }
            echo" ] ";
            
            
            
            if ($i < count($data2))
                echo ", ";
            $i++;
            echo "\r\n";
        }
        ?>
      ]);
      
    var chart = new google.visualization.LineChart(document.querySelector('#chart_div'));
    chart.draw(data, {
        height: 600,
        width: 1000,
        interpolateNulls: true
    });
}
google.load('visualization', '1', {packages:['corechart'], callback: drawChart});
//]]> 

</script>
</head>
<body>
  <div id="chart_div"></div>
</body>
</html>