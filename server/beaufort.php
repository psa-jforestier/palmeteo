<?php

  global $BEAUFORT;
  $BEAUFORT = array(
      // beaufort index, max km, label, red, green, blue
      ["beauf"=>0, "k"=>1,    "l"=>"0-1", "r"=>200, "v"=>200, "b"=>200],
      ["beauf"=>1, "k"=>5,    "l"=>"1-5", "r"=>204, "v"=>255, "b"=>255],
      ["beauf"=>2, "k"=>11,   "l"=>"6-11", "r"=>0x99, "v"=>255, "b"=>255],
      ["beauf"=>3, "k"=>19,   "l"=>"12-19","r"=>0x66, "v"=>255, "b"=>255],
      ["beauf"=>4, "k"=>28,   "l"=>"20-28","r"=>0, "v"=>255, "b"=>255],
      ["beauf"=>5, "k"=>38,   "l"=>"29-38","r"=>0, "v"=>0xcc, "b"=>255],
      ["beauf"=>6, "k"=>49,   "l"=>"39-48","r"=>0, "v"=>0x99, "b"=>255],
      ["beauf"=>7, "k"=>61,   "l"=>"50-61","r"=>0, "v"=>0x66, "b"=>255],
      ["beauf"=>8, "k"=>74,   "l"=>"62-74","r"=>0, "v"=>0, "b"=>255],
      ["beauf"=>9, "k"=>88,   "l"=>"75-88","r"=>0, "v"=>0, "b"=>0xcc],
      ["beauf"=>10, "k"=>102, "l"=>"89-102","r"=>0, "v"=>0, "b"=>0x99],
      ["beauf"=>11, "k"=>117, "l"=>"103-117","r"=>0, "v"=>0, "b"=>0x66],
      ["beauf"=>12, "k"=>999, "l"=>">118","r"=>0, "v"=>0, "b"=>0]
    );
  function toBeaufort($speedkmh)
  {// https://fr.wikipedia.org/wiki/%C3%89chelle_de_Beaufort

    global $BEAUFORT;
    foreach($BEAUFORT as $b)
    {
      if ($speedkmh <= $b['k'])
        return $b;
    }
    return $BEAUFORT[12];
  }
  
  function beaufortIndexToLabel($idx)
  {
    global $BEAUFORT;
    foreach($BEAUFORT as $b)
    {
      if($b['beauf'] == $idx)
        return $b['l'];
    }
    return '?';
  }