# geohash

Class libraries implemented using PHP based on GeoHash algorithms.

For details, please refer to the blog （Chinese）:

https://usblog.crazylaw.cn/index.php/archives/324/

## Usage

Three API methods are provided.

- `around($lng, $lat, $interceptLength = 0)`  Convert the HashCode in the 9 regions of the longitude and latitude.

- `encode($lng, $lat, $interceptLength = 0)`  Converts HashCode to the specified latitude and longitude.

- `distance($centerLng, $centerLat, $pointLng, $pointLat, $lenType = 1, $decimal = 2)`  Calculate the distance between two latitude and longitude.

example：

```php
require_once 'vendor/autoload.php';

// The base hashcode length must be a multiple of 5, otherwise it will automatically be filled to a multiple of 5.
// The longer the base length of hashcode is, the more options are available to intercept, and the longer it is recommended. 10 is usually enough.
$hashCodeLength = 10;

$geohash = new ccinn\GeoHash($hashCodeLength);

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
```
