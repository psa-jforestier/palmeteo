<?php

  function toBeaufort($speedkmh)
  {// https://fr.wikipedia.org/wiki/%C3%89chelle_de_Beaufort

    $BEAUF = array(
      ["beauf"=>0, "k"=>1,  "r"=>200, "v"=>200, "b"=>200],
      ["beauf"=>1, "k"=>5,  "r"=>204, "v"=>255, "b"=>255],
      ["beauf"=>2, "k"=>11, "r"=>0x99, "v"=>255, "b"=>255],
      ["beauf"=>3, "k"=>19, "r"=>0x66, "v"=>255, "b"=>255],
      ["beauf"=>4, "k"=>28, "r"=>0, "v"=>255, "b"=>255],
      ["beauf"=>5, "k"=>38, "r"=>0, "v"=>0xcc, "b"=>255],
      ["beauf"=>6, "k"=>49, "r"=>0, "v"=>0x99, "b"=>255],
      ["beauf"=>7, "k"=>61, "r"=>0, "v"=>0x66, "b"=>255],
      ["beauf"=>8, "k"=>74, "r"=>0, "v"=>0, "b"=>255],
      ["beauf"=>9, "k"=>88, "r"=>0, "v"=>0, "b"=>0xcc],
      ["beauf"=>10, "k"=>102, "r"=>0, "v"=>0, "b"=>0x99],
      ["beauf"=>11, "k"=>117, "r"=>0, "v"=>0, "b"=>0x66],
      ["beauf"=>12, "k"=>999, "r"=>0, "v"=>0, "b"=>0]
    );
    foreach($BEAUF as $b)
    {
      if ($speedkmh <= $b['k'])
        return $b;
    }
    return $BEAUF[12];
  }


  $dir = @$_REQUEST['dir'] + -90;
  $speed = @$_REQUEST['speed'] + 0.0;
  $speedunit = @$_REQUEST['speedunit'];
  
  $i = 0;
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
    }
    $i++;
  }
  
  
  
  if ($speedunit == "kmh")
  {
    $speedkmh = $speed;
    $speedms = $speed/(60*60/1000);
  } 
  else if ($speedunit = "ms")
  {
    $speedms = $speed;
    $speedkmh = $speed * (60 * 60 / 1000);
  }
  else
  {
    
  }
  
  $W = 480;
  $H = 480;
 
  $im = imagecreatefrompng('Windrose.svg.480.png');
  $green  = imagecolorallocate($im, 0, 255, 0);
  $blue  = imagecolorallocate($im, 0, 0, 255);
  $white = imagecolorallocate($im, 255, 255, 255);
  $W = imagesx($im);
  $H = imagesy($im);
  
  $x = cos(deg2rad($dir));
  $y = sin(deg2rad($dir));
  
  $beauf = toBeaufort($speedkmh);

  $color = imagecolorallocate($im, $beauf['r'], $beauf['v'], $beauf['b']);

 imageline($im, 
    //($W/2) + ($W/3 * $x), ($H/2) + ($H/3 * $y), 
    $W/2, $H/2,
    ($W/2) + ($W/2 * $x), ($H/2) + ($H/2 * $y), 
    $green);
imagefilledarc(
    $im,
    $W/2 + (8*$x), $H/2 + (8*$y),
    $W/1.4, $H/1.4 ,
    $dir-6,
    $dir+6,
    $white,
   IMG_ARC_CHORD
);
  imagefilledarc(
    $im,
    $W/2 + (8*$x), $H/2 + (8*$y),
    $W/1.4, $H/1.4 ,
    $dir-5,
    $dir+5,
    $color,
   IMG_ARC_PIE
);

  imagealphablending($im, false);
  imagesavealpha($im, true);
  header("Content-type: image/png");
  imagepng($im);

  // free memory
  imagedestroy($im);
?>

