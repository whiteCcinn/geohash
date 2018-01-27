<?php


class GeoHash
{
    const MAX_LAT = 90;
    const MIN_LAT = -90;
    const MAX_LNG = 180;
    const MIN_LNG = -180;

    const EARTH_RADIUS = 6378.137;
    const π            = 3.1415926;

    private static $length = 0;

    private static $latUnit    = 0;
    private static $lngUnit    = 0;
    private static $base32Code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct($length)
    {
        self::$length = $length;
        $this->init();
    }

    private function init()
    {
        self::$latUnit = (self::MAX_LAT - self::MIN_LAT) / (1 << self::$length);
        self::$lngUnit = (self::MAX_LNG - self::MIN_LNG) / (1 << self::$length);
    }

    /**
     * 划分区域，封装二进制码
     * @param $min
     * @param $max
     * @param $loc
     * @param $list
     */
    private function convert($min, $max, $loc, &$list)
    {
        if (count($list) > (self::$length - 1)) {
            return;
        }

        $mid = ($min + $max) / 2;

        if ($loc < $mid) {
            $list[] = '0';
            $this->convert($min, $mid, $loc, $list);
        } else {
            $list[] = '1';
            $this->convert($mid, $max, $loc, $list);
        }
    }

    /**
     * Base32转码
     * @param $bits 二进制编码
     * @return string
     */
    private function base32Encode($bits)
    {
        // 对齐5的倍数，进行base32转码
        $length = strlen($bits);
        $mod    = $length % 5;
        if ($mod > 0) {
            $bits = str_pad($bits, $length + (5 - $mod), '0', STR_PAD_LEFT);
        }

        $bitsSplit = str_split($bits, 5);

        $hashCode = '';
        foreach ($bitsSplit as $bitFive) {
            $hashCode .= self::$base32Code{bindec($bitFive)};
        }

        return $hashCode;
    }

    /**
     * 哈希经纬度
     * @param double $lng 经度
     * @param double $lat 维度
     * @return string
     */
    public function encode($lng, $lat)
    {
        $latList = [];
        $lngList = [];
        $this->convert(self::MIN_LAT, self::MAX_LAT, $lat, $latList);
        $this->convert(self::MIN_LNG, self::MAX_LNG, $lng, $lngList);

        $bits = '';
        for ($i = 0, $c = count($latList); $i < $c; $i++) {
            $bits .= $lngList[$i] . $latList[$i];
        }

        return $this->base32Encode($bits);
    }

    /**
     * 搜索附近9个区域的经纬度
     * @param double $lng 经度
     * @param double $lat 维度
     * @return array
     */
    public function around($lng, $lat)
    {
        $latUnit              = self::$latUnit;
        $lngUnit              = self::$lngUnit;
        $aroundList           = [];
        $aroundList['center'] = $this->encode($lng, $lat);
        $aroundList['top']    = $this->encode($lng, $lat + $latUnit);
        $aroundList['down']   = $this->encode($lng, $lat - $latUnit);
        $aroundList['left']   = $this->encode($lng - $lngUnit, $lat);
        $aroundList['right']  = $this->encode($lng + $lngUnit, $lat);

        $aroundList['left_top']   = $this->encode($lng - $lngUnit, $lat + $latUnit);
        $aroundList['left_down']  = $this->encode($lng - $lngUnit, $lat - $latUnit);
        $aroundList['right_top']  = $this->encode($lng + $lngUnit, $lat + $latUnit);
        $aroundList['right_down'] = $this->encode($lng + $lngUnit, $lat - $latUnit);
        return $aroundList;
    }

    public function distance($centerLng, $centerLat, $pointLng, $pointLat)
    {
        $earthRaidus = self::EARTH_RADIUS;
        $π           = self::π;

        $x1 = cos($centerLat) * cos($centerLng);
        $y1 = cos($centerLat) * sin($centerLng);
        $z1 = sin($centerLat);

        $x2 = cos($pointLat) * cos($pointLng);
        $y2 = cos($pointLat) * sin($pointLng);
        $z2 = sin($pointLat);

        $lineDistance = sqrt(($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2) + ($z1 - $z2) * ($z1 - $z2));

        $realDistance = $earthRaidus * $π * 2 * asin(0.5 * $lineDistance) / 180;

        return $realDistance * 1000;
    }

    public function distance2($centerLng, $centerLat, $pointLng, $pointLat)
    {
        $earthRaidus = self::EARTH_RADIUS;
        $π           = self::π;

        $x1 = $centerLat * $π / 180;
        $x2 = $pointLat * $π / 180;
        $x = $x1 - $x2;

        $y1 = $centerLng * $π / 180;
        $y2 = $pointLng * $π / 180;
        $y = $y1 - $y2;

        $s = 2 * asin();
    }
}

$geohash = new GeoHash(20);
$around  = $geohash->around(113.314748, 23.125851);
print_r($around);

var_dump($point = $geohash->encode(113.314851, 23.125839));

if (in_array($point, $around)) {
    echo 'in_around' . PHP_EOL;
} else {
    echo 'not_in_around' . PHP_EOL;
}

var_dump('distance:' . $geohash->distance(113.314622,23.125826, 113.316477,23.125785));