<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class GoodsInfo extends Model
{
    public $timestamps = false;

    protected $table = "goods_info";

    protected $guarded = [];

    public static function createOrUpdate(array $param, int $gid = 0)
    {
        if ($gid != 0) {
            self::query()->where('id', $gid)->update([
                "goods_name" => $param['goods_name'],
                "description" => $param['description'] ?? "",
                "main_img" => $param['main_img'],
                "banner_imgs" => $param['banner_imgs'] ?? "",
                "banner_video" => $param['banner_video'] ?? "",
                "pay_type" => $param['pay_type'],
                "detail" => $param['detail'] ?? "",
                "updated_at" => time()
            ]);
        } else {
            $status = env('GOODS_DEFAULT_ON_LIST', false) == true ? 1 : 0;
            $gid = self::query()->insertGetId([
                "sid" => $param['sid'] ?? 0,
                "goods_name" => $param['goods_name'],
                "description" => $param['description'] ?? "",
                "main_img" => $param['main_img'],
                "banner_imgs" => $param['banner_imgs'] ?? "",
                "banner_video" => $param['banner_video'] ?? "",
                "pay_type" => $param['pay_type'],
                "detail" => $param['detail'] ?? "",
                "status" => $status,
                "created_at" => time()
            ]);
        }
        return $gid;
    }
}
