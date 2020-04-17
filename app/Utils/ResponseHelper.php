<?php


namespace App\Utils;


class ResponseHelper
{
    const ERROR_CODE = -1;

    public static function success(array $data = [], string $msg = "success", int $code = 1)
    {
        return response()->json([
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        ]);
    }

    public static function fail(int $code, string $msg = "request error", array $data = [])
    {
        return response()->json([
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        ]);
    }
}
