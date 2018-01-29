<?php

/**
 * Use PHP to implement the geohash algorithm.
 * 1. Search for the code using `around()` method in 9 regions nearby.
 * 2. Conversion of latitude and longitude into geohash coding with `encode()` method.
 * 3. Search the distance between two longitude and latitude by `distance()` method.
 *
 * HashCode's Length Reference table :
 * -----------------------------------------
 * hscode-length |width       |height      |
 * 1             |5,009.4km   |4,992.6km   |
 * 2             |1,252.3km   |624.1km     |
 * 3             |156.5km     |156km       |
 * 4             |39.1km      |19.5km      |
 * 5             |4.9km       |4.9km       |
 * 6             |1.2km       |609.4m      |
 * 7             |152.9m      |152.4m      |
 * 8             |38.2m       |19m         |
 * 9             |4.8m        |4.8m        |
 * 10            |1.2m        |59.5cm      |
 * 11            |14.9cm      |14.9cm      |
 * 12            |3.7cm       |1.9cm       |
 * -----------------------------------------
 *
 * Class GeoHash
 *
 * @author Wenhui.Cai <471113744@qq.com>
 */
class GeoHash
{
    const MAX_LAT = 90;
    const MIN_LAT = -90;
    const MAX_LNG = 180;
    const MIN_LNG = -180;

    const EARTH_RADIUS = 6378.137;

    private static $length     = 0;
    private static $latUnit    = 0;
    private static $lngUnit    = 0;
    private static $base32Code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * GeoHash constructor.
     *
     * @param int $length HashCode Accuracy length .
     *                    he base hashcode length must be a multiple of 5, otherwise it will automatically be filled to
     *                    a multiple of 5. The longer the base length of hashcode is, the more options are available to
     *                    intercept, and the longer it is recommended. 10 is usually enough.
     *
     *
     * @return void
     * @throws Exception
     */
    public function __construct($length)
    {
        if ($length <= 0 || !is_integer($length)) {
            throw new Exception('Please set a positive integer greater than 0 use {$length} in __construct() method');
        }
        $this->setLength($length);
        $this->init();
    }

    /**
     * Setter hashcode length.
     *
     * @param int $hashCodeLength Accuracy length
     *
     * @return void
     */
    public function setLength($hashCodeLength)
    {
        self::$length = floor($hashCodeLength * 5 / 2);
    }

    /**
     * Initialize the range of each smallest unit according to length.
     *
     * @return void
     */
    private function init()
    {
        self::$latUnit = (self::MAX_LAT - self::MIN_LAT) / (1 << self::$length);
        self::$lngUnit = (self::MAX_LNG - self::MIN_LNG) / (1 << self::$length);
    }

    /**
     * Partition area, encapsulate binary code.
     *
     * @param $min  Minimum regional boundary value.
     * @param $max  Maximum area boundary.
     * @param $loc  The target coordinates
     * @param $list The latitude and longitude coordinate stores list, is a reference.
     *
     * @return void
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
     * Base32 encoding conversion.
     *
     * @param $bits The binary coding
     *
     * @return string
     */
    private function base32Encode($bits)
    {
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
     * Calculate the geohash code.
     *
     * @param float $lng             longitude
     * @param float $lat             latitude
     * @param int   $interceptLength You need to intercept the hashCode length.
     *
     * @return string
     */
    public function encode($lng, $lat, $interceptLength = 0)
    {
        $latList = [];
        $lngList = [];
        $this->convert(self::MIN_LAT, self::MAX_LAT, $lat, $latList);
        $this->convert(self::MIN_LNG, self::MAX_LNG, $lng, $lngList);

        $bits = '';
        for ($i = 0, $c = count($latList); $i < $c; $i++) {
            $bits .= $lngList[$i] . $latList[$i];
        }

        $base32Code = $this->base32Encode($bits);

        return $interceptLength > 0 && $interceptLength <= self::$length ? substr($base32Code, 0,
            $interceptLength) : $base32Code;
    }

    /**
     * Search the geohash code for the nine nearby areas.
     *
     * @param float $lng             longitude
     * @param float $lat             latitude
     * @param int   $interceptLength You need to intercept the hashCode length.
     *
     * @return array
     */
    public function around($lng, $lat, $interceptLength = 0)
    {
        $latUnit              = self::$latUnit;
        $lngUnit              = self::$lngUnit;
        $aroundList           = [];
        $aroundList['center'] = $this->encode($lng, $lat, $interceptLength);
        $aroundList['top']    = $this->encode($lng, $lat + $latUnit, $interceptLength);
        $aroundList['down']   = $this->encode($lng, $lat - $latUnit, $interceptLength);
        $aroundList['left']   = $this->encode($lng - $lngUnit, $lat, $interceptLength);
        $aroundList['right']  = $this->encode($lng + $lngUnit, $lat, $interceptLength);

        $aroundList['left_top']   = $this->encode($lng - $lngUnit, $lat + $latUnit, $interceptLength);
        $aroundList['left_down']  = $this->encode($lng - $lngUnit, $lat - $latUnit, $interceptLength);
        $aroundList['right_top']  = $this->encode($lng + $lngUnit, $lat + $latUnit, $interceptLength);
        $aroundList['right_down'] = $this->encode($lng + $lngUnit, $lat - $latUnit, $interceptLength);

        return $aroundList;
    }

    /**
     * Calculate the distance between two latitude and longitude.
     *
     * @param float $centerLat latitude
     * @param float $centerLng longitude
     * @param float $pointLat  latitude
     * @param float $pointLng  longitude
     * @param int   $lenType   The default unit is m, and if you need to convert to km, use >1.
     * @param int   $decimal   Retention accuracy
     *
     * @return float
     */
    public function distance($centerLng, $centerLat, $pointLng, $pointLat, $lenType = 1, $decimal = 2)
    {
        $earthRaidus = self::EARTH_RADIUS;

        $radLat1 = $centerLat * pi() / 180.0;
        $radLat2 = $pointLat * pi() / 180.0;
        $a       = $radLat1 - $radLat2;
        $b       = ($centerLng * pi() / 180.0) - ($pointLng * pi() / 180.0);
        $s       = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s       = $s * $earthRaidus;
        $s       = round($s * 1000);
        if ($lenType > 1) {
            $s = $s / 1000;
        }

        return round($s, $decimal);
    }
}