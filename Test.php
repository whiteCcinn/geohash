<?php

require_once 'GeoHash.php';

// The base hashcode length must be a multiple of 5, otherwise it will automatically be filled to a multiple of 5.
// The longer the base length of hashcode is, the more options are available to intercept, and the longer it is recommended. 10 is usually enough.
$hashCodeLength = 10;

$geohash = new GeoHash($hashCodeLength);

var_dump($geohash->around(113.314748, 23.125851, $interceptLength));

// Search nearby 20 meters
$interceptLength = 8;
$around          = $geohash->around(113.314748, 23.125851, $interceptLength);

var_dump($around, $point = $geohash->encode(113.314851, 23.125839, $interceptLength));

if (in_array($point, $around)) {
    echo 'in_around' . PHP_EOL;
} else {
    echo 'not_in_around' . PHP_EOL;
}

var_dump('distance:' . $geohash->distance(113.314748, 23.125851, 113.314851, 23.125839) . 'm');