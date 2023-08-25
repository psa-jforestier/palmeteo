<?php
/**
  generate window direction icon
  **/
  
  
  for ($angle = 0; $angle < 360; $angle += (360/16))
  {
    
    $W = 65;
    $H = 65;
    
    $r = 32;
    
    $im = imagecreate($W, $H);
    imageantialias($im, true);
    $bg = imagecolorallocate($im, 255, 255, 255);
    
    $black = imagecolorallocate($im, 0,0,0);
    $red   = imagecolorallocate($im, 255,0,0);
    $grey  = imagecolorallocate($im, 192,192,192);
    $white = imagecolorallocate($im, 255, 255, 255);
    $grey2 = imagecolorallocate($im, 128,128,128);
    imagefilledellipse($im, $W/2, $H/2, 2*$r, 2*$r, $grey2);
    imagefilledellipse($im, $W/2, $H/2, (2*$r) - 4, (2*$r) - 4, $white);
    for($i = 0; $i < 360; $i = $i + (360/16))
    {
      
      $x = sin(deg2rad($i));
      $y = cos(deg2rad($i));
      imageline($im, 
        ($W/2) + ($r - 6)*$x, ($H/2) + ($r - 6)*$y, 
        ($W/2) + ($r - 2) * $x,
        ($H/2) + ($r - 2) * $y,
        $black);
    }
    imagefilledarc(
      $im,
      $W/2, $H / 2,
      $W/1.3, $H/1.3,
      $angle - 6 - 90, $angle + 6 - 90,
      $red,
      IMG_ARC_CHORD
    );
    var_dump($angle);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    //header("Content-type: image/png");
    imagepng($im, "windicon_".$angle.".png");

    // free memory
    imagedestroy($im);
  }