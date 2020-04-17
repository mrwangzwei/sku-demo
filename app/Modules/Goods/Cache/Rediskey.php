<?php


namespace App\Modules\Goods\Cache;


class Rediskey
{
    const GOODS_DETAIL = 'h:sku-demo:goods-detail';         //商品详情

    const CATEGORY_GOODS_LIST = 'z:set:sku-demo:category-goods_list:%s';    //{类目id}  类目下的商品列表
}
