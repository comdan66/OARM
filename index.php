<?php

// @include 'model/';

$a = [1,2,3,4];
$a = [2,4,6,8];

for ($i = 0 ; $i < count($a); $i++) {
  if ($a[$i])
    $a[$i] = $a[$i] + 1;

  if ($a[$i] + 2 == $a[$i] + 1)
    $a[$i] = $a[$i] * 2;
}

// x