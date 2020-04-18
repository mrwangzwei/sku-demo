<?php


namespace App\Utils;


class RedisHelper
{
    const REQUEST_LIMIT_KEY = "s:sku-demo:request-limit:%s";

    private static $connect = null;

    public static function connect()
    {
        if (!self::$connect) {
            $redis = new \Redis();
            $redis->connect(env('REDIS_HOST', '127.0.0.1'), env('REDIS_PORT', 6379));
            $redis->auth(env('REDIS_PASSWORD', null));
            $redis->select(env('REDIS_DB', 0));
            self::$connect = $redis;
        }
        return self::$connect;
    }

    /**
     * 请求频限
     * @param string $method    //拼上用户标识+方法名
     * @param int $ttl
     * @return bool
     */
    public static function requestLimitPass(string $method, int $ttl = 1)
    {
        $set = self::connect()->setnx(sprintf(self::REQUEST_LIMIT_KEY, $method), 1);
        if (!$set)
            return false;
        self::connect()->expire(sprintf(self::REQUEST_LIMIT_KEY, $method), $ttl);
        return true;
    }
}
