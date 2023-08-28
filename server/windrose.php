<?php

/** input : histo=history.csv
 **/
 
  //srand(0);
  $histo = 'histo.csv'; // each line : date;angle;speed
  
  $histodata = array();
  // Read history
  if (($handle = @fopen($histo, "r")) !== FALSE) 
  {
    // Reload history
    $row = 1;
    $maxspeed = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
    {
      if (count($data) == 3)
      {
        $row++;
        $hdate = $data[0];
        $hdir = $data[1] + 0.0;
        $angleindex = $hdir * 10;
        $hspeedkmh = $data[2] + 0.0;
        if ($hspeedkmh > $maxspeed)
          $maxspeed = $hspeedkmh;
        
        $historical[] = array($hdate, $hdir, $hspeedkmh);
        
        $histo_angle = @$histodata[$angleindex];
        if ($histo_angle == NULL)
          $histo_angle = array($hspeedkmh);
        else
          $histo_angle[] = $hspeedkmh;
        $histodata[$angleindex] = $histo_angle;
      }
    }
    fclose($handle);
  }

  
  $W = 240;
  $H = 240;
  $r = $W / 2;
  $im = imagecreate($W, $H);
  imageantialias($im, true);
  $bg = imagecolorallocate($im, 255, 255, 255);
  $black = imagecolorallocate($im, 0,0,0);
  $red   = imagecolorallocate($im, 255,0,0);
  $grey = imagecolorallocate($im, 128,128,128);
  $grey1  = imagecolorallocate($im, 192,192,192);
  $grey2 = imagecolorallocate($im, 248,248,248);
  $white = imagecolorallocate($im, 255, 255, 255);
  imagefilledellipse($im, $W/2, $H/2, 2*$r, 2*$r, $grey1);
  imagefilledellipse($im, $W/2, $H/2, (2*$r) - 4, (2*$r) - 4, $white);
  
  // Draw speed point
  for($angle = 0; $angle < 360; $angle++)
  {
    if ($angle % 45 == 0)
    {
      $x = sin(deg2rad($angle));
      $y = cos(deg2rad($angle));
      $r = $W / 2;
      imageline($im, 
        ($W/2) + ($r - 6)*$x, ($H/2) + ($r - 6)*$y, 
        ($W/2) + ($r - 2) * $x,
        ($H/2) + ($r - 2) * $y,
        $black);
    }
    $histo_angle = @$histodata[$angle*10];
    if ($histo_angle != NULL)
    {
      imagefilledarc(
      $im,
      $W/2, $H / 2,
      $W/1.05, $H/1.05,
      $angle - 6 - 90, $angle + 6 - 90,
      $grey2,
      IMG_ARC_PIE
      );
      foreach($histo_angle as $i=>$speed)
      {
        $angle = rand($angle - 3, $angle + 3);
        $x = sin(deg2rad($angle));
        $y = cos(deg2rad($angle));
        $sp = ($speed / $maxspeed) * 0.9;
        $r = ($W/2) * $sp;
        /*imageline($im,
          $W / 2, $H / 2,
          ($W / 2) + ($r * $x), ($H / 2) - ($r * $y),
          $red);
        */
        imagesetpixel($im,
          ($W / 2) + ($r * $x), ($H / 2) - ($r * $y),
          $red);
      }
    }
  }
  $ob = ob_get_status();
  if ($ob['buffer_used'] == 0)
  {
    header("Content-type: image/png");
    imagepng($im);
  }
  // free memory