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
foreach($sensorsWithName as $s)
{
   
    $data[] = array('id'=>$s['id'], 'name'=>$s['name'], 'values'=>weatherGetDataForSensors($db, $from, $to, $s['id']));
}
//var_dump($data);
?>
<html>
	<head>
    <script type="text/javascript" src="//code.jquery.com/jquery-1.8.3.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi?fake=.js"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  
  <script type='text/javascript'>//<![CDATA[

function drawChart() {
    var joinedData = new google.visualization.DataTable();
    joinedData.addColumn('number','X');
    joinedData.addColumn('number','22');
    joinedData.addColumn('number','23');
    joinedData.addColumn('number','33');
    joinedData.addColumn('number','60');
    <?php
    foreach($data as $i=>$dataForSensor)
    {
        ?>
    
    var data<?=$i?> = new google.visualization.DataTable();
    data<?=$i?>.addColumn('number', 'X');
    data<?=$i?>.addColumn('number', <?= $dataForSensor['name']; ?>);
    data<?=$i?>.addRows([
    <?php
    $j = 0;
    foreach($dataForSensor['values'] as $d)
    {
        $j++;
        echo "[ ", $d['minute']," , ", $d['avgtemp']," ] ";
       
        if ($j < count($dataForSensor['values']))
            echo ", \r\n";
       
       
    }
    ?>
    ]);
        /**joinedData = google.visualization.data.join(joinedData, data<?=$i?>,'full', 
            [[0, 0]], 
            [1], 
            [1]
            );
            **/
        <?php
    }
    ?>
  
    var joinedData = google.visualization.data.join(data0, data1, 'full', [[0, 0]], [1], [1]);
    //joinedData = google.visualization.data.join(joinedData, data2, 'full', [[0, 0]], [2], [1]);
    //joinedData = data1;
    var chart = new google.visualization.LineChart(document.querySelector('#chart_div'));
    chart.draw(joinedData, {
        height: 300,
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