<?php


namespace App\Utils;


class Helper
{
    public static function microtime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf((floatval($msec) + floatval($sec)) * 1000000);
        return $msectime;
    }

    public static function millitime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf((floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}
