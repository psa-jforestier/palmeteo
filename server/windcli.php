<?php

/**
 php windcli.php -dir ANGLE -speed SP -speedunit ms -windhisto windhistory.csv -windrosedata winddata.csv
**/
  include_once('beaufort.php');


  

  
  $i = 0;
  $speed = $dir = NULL;
  $windhistofile = NULL;
  $windrosedata = 'php://stdout';
  while($i < @$argc)
  {
    switch($argv[$i]){
      case '-dir': {
        $dir = $argv[$i+1];
        $i++;
        break;
      }
      case '-speed': {
        $speed = $argv[$i+1];
        $i++;
        break;
      }
      case '-speedunit': {
        $speedunit = $argv[$i+1];
        $i++;
        break;
      }
      case '-openweathermapfile': {
        $f = $argv[$i+1];
        $i++;
        $data = json_decode(file_get_contents($f), true);
        $dir = $data['wind']['deg'];
        $speed = $data['wind']['speed'];
        $speedunit = 'ms';
        break;
      }
      case '-windhisto' : {
        $windhistofile = $argv[$i+1];
        $i++;
        break;
      }
      case '-windrosedata' : {
        $windrosedata = $argv[$i+1];
        $i++;
        break;
      }
    }
    $i++;
  }
  
  //if ($speed === NULL || $dir === NULL)
  //{
  //  echo "Missing -dir 180 -speed 10 [-speedunit khm|ms] [-openweathermapfile FILE] [-windhisto FILE.csv] [-windrosedata FILE.csv]";
  //  die();
  //}
  if (@$speedunit == "kmh")
  {
    $speedkmh = $speed;
    $speedms = $speed/(60*60/1000);
  } 
  else if (@$speedunit = "ms")
  {
    $speedms = $speed;
    $speedkmh = $speed * (60 * 60 / 1000);
  }
  
  
  $historical = array();
  if ($windhistofile != '')
  {
    // Read historical file
    if (($handle = @fopen($windhistofile, "r")) !== FALSE) 
    {
      // Reload history
      $row = 1;
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
      {
        if (count($data) == 3)
        {
          $hdata = $data[0];
          $hdir = $data[1];
          $hspeedkmh = $data[2];
          $beauf = toBeaufort($hspeedkmh);
          
          $historical[] = array($hdata, $hdir, $hspeedkmh);
        }
      }
      fclose($handle);
    }
    else
    {
      echo "Unable to read history file $windhistofile\n";
      
    }
  
    if ($dir !== NULL)
    {
      // Append to historical file
      $beauf = toBeaufort($speedkmh);
      $newdata = array(
        date('Y-m-d H:i:s'),
        $dir,
        $speedkmh);
      $historical[] = $newdata;
      
      echo "Write historical file $windhistofile\n";
      // Add a line to history
      $handle = @fopen($windhistofile, "a");
      fputcsv($handle, $newdata);
      fclose($handle);  
    }
  }
  
  // Rebuild stat data including last data (angle, speed, percentage of this speed for this angle)
  $data = array();
  $nbdata = count($historical);
  foreach($historical as $histo)
  {
    $dir = $histo[1];
    $speedkmh = $histo[2];
    $beauf = toBeaufort($speedkmh);
    $beauf = $beauf['beauf'];
    $histodir = @$data[$dir];
    echo "Existing data for this direction $dir :\n";
    var_dump($histodir);
    echo "search for beauf $beauf on existing data\n";
    
    if ($histodir === NULL)
    {
      echo " Not found, creating a new history for this beauf and this dir\n";
      $histodir = array($beauf=>1);
    }
    else
    {
      echo "  found, adding +1 to the count\n";
      $histodir[$beauf] = @$histodir[$beauf] + 1;
    }
    $data[$dir] = $histodir;
  }

  echo "Write CSV data $windrosedata\n";
  if ($nbdata == 0)
  {
    echo "No data to write\n";
  }
  else
  {
    $fd = fopen($windrosedata, 'w');
    fputcsv($fd, ['angle','speed','percent']);
    for ($i = 0; $i < 360; $i++)
    {
      $dir = @$data[$i];
      if ($dir !== NULL)
      {
        echo "FOUND angle $i\n";
        for($b = 0; $b <= 12; $b++)
        {
          $d = @$data[$i][$b];
          if ($d !== NULL)
          {
            $beauflabel = beaufortIndexToLabel($b);
            $percent = sprintf("%.3f", $d/$nbdata);
            echo "Angle $i beauf $b ($beauflabel) seen $d times ($percent % )\n";
            fputcsv($fd, array($i, $beauflabel, $percent));
          }
        }
      }
    }
    fclose($fd);
  }
  