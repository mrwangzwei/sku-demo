<?php


namespace App\Modules\Goods;


use App\Models\GoodsSpec;
use App\Models\GoodsSpecTemp;
use App\Models\GoodsSpecValue;
use Illuminate\Support\Arr;

class Spec
{
    private static $lastErrorMsg = "";

    private static $lastErrorCode = 0;

    public static function getLastErrorCode()
    {
        return self::$lastErrorCode;
    }

    public static function getLastErrorMsg()
    {
        return self::$lastErrorMsg;
    }

    /**
     * 验证规格信息是否符合转换规则
     * @param $specInfo
     * @param $skuInfo
     * @return bool
     */
    public static function checkSpecCombineEnable($specInfo, $skuInfo)
    {
        $combineSpec = self::combineSpecInfo($specInfo);
        $skuSpecInfo = Arr::pluck($skuInfo, "spec_info");
        if ($combineSpec != $skuSpecInfo)
            return false;
        return true;
    }

    /**
     * 验证规格信息是否可用（如果spu(tid)标识不可用，就置为0）
     * @param $specInfo
     * @return bool|array
     */
    public static function checkSpecSaveEnable($specInfo)
    {
        $ptids = [];
        $ctids = [];
        //验证是否有重复规格信息
        $specName = Arr::pluck($specInfo, 'name');
        if (count($specName) != count(array_unique($specName)))
            return false;
        foreach ($specInfo as $spec) {
            $spec['tid'] != 0 && $ptids[] = $spec['tid'];
            foreach ($spec['spec_value'] as $specValue) {
                $specValue['tid'] != 0 && $ctids[] = $specValue['tid'];
            }
            if (count($ctids) != count(array_unique($ctids)))
                return false;
            $specValueName = Arr::pluck($spec['spec_value'], 'name');
            if (count($specValueName) != count(array_unique($specValueName)))
                return false;
        }
        //格式化不可用的spu标识
        if (!empty($ptids))
            $ptids = GoodsSpecTemp::query()->where('pid', 0)
                ->whereIn('id', $ptids)->pluck('id')->toArray();
        if (!empty($ctids))
            $ctids = GoodsSpecTemp::query()->where('pid', "<>", 0)
                ->whereIn('id', $ctids)->pluck('id')->toArray();
        $newSpecInfo = [];
        foreach ($specInfo as $spec) {
            !in_array($spec['tid'], $ptids) && $spec['tid'] = 0;
            foreach ($spec['spec_value'] as &$specValue) {
                !in_array($specValue['tid'], $ctids) && $specValue['tid'] = 0;
            }
            $newSpecInfo[] = $spec;
        }
        return $newSpecInfo;
    }

    /**
     * 根据给定规格构造库存规格信息
     * @param $specInfo
     * @return array
     * $specInfo = [
     *                  [
     *                      'id'         => 1,
     *                      'name'       => '颜色',
     *                      'spec_value' => [
     *                          [
     *                              'id'   => 12,
     *                              'name' => '黑色'
     *                          ],
     *                          [
     *                              'id'   => 0,
     *                              'name' => '白色'
     *                          ]
     *                      ]
     *                  ],
     *                  [
     *                      'id'         => 0,
     *                      'name'       => '尺寸',
     *                      'spec_value' => [
     *                          [
     *                              'id'   => 0,
     *                              'name' => 'XL'
     *                          ],
     *                          [
     *                              'id'   => 0,
     *                              'name' => 'XXL'
     *                          ]
     *                      ]
     *                  ],
     *              ]
     * $result   = [
     *                  [
     *                      'spec_value_ids'=>[
     *                          12,0
     *                      ]
     *                      'spec_values'=>[
     *                          '黑色', 'XL'
     *                      ]
     *                  ],
     *                  [
     *                      'spec_value_ids'=>[
     *                          12,0
     *                      ]
     *                      'spec_values'=>[
     *                          '黑色', 'XXL'
     *                      ]
     *                  ],
     *                  [
     *                      'spec_value_ids'=>[
     *                          0,0
     *                      ]
     *                      'spec_values'=>[
     *                          '白色', 'XL'
     *                      ]
     *                  ],
     *                  [
     *                      'spec_value_ids'=>[
     *                          0,0
     *                      ]
     *                      'spec_values'=>[
     *                          '白色', 'XXL'
     *                      ]
     *                  ]
     *              ];
     */
    public static function combineSpecInfo($specInfo)
    {
        $count = count($specInfo) - 1;
        $result = [];
        if ($count == 0) {
            foreach ($specInfo[0]['spec_value'] as $specValues) {
                $result[] = [
                    'spec_value_ids' => [$specValues['id']],
                    'spec_values' => [$specValues['name']]
                ];
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $tmp = [];
                if ($i == 0) {
                    $results = $specInfo[$i]['spec_value'];
                    $result = [];
                    foreach ($results as $val) {
                        $result[] = [
                            'spec_value_ids' => [$val['id']],
                            'spec_values' => [$val['name']]
                        ];
                    }
                }
                foreach ($result as $first) {
                    foreach ($specInfo[$i + 1]['spec_value'] as $second) {
                        if (is_array($first['spec_value_ids'])) {
                            $specValueId = array_merge($first['spec_value_ids'], [$second['id']]);
                        } else {
                            $specValueId = [$first['spec_value_ids'], $second['id']];
                        }
                        if (is_array($first['spec_values'])) {
                            $specValue = array_merge($first['spec_values'], [$second['name']]);
                        } else {
                            $specValue = [$first['spec_values'], $second['name']];
                        }
                        $tmp[] = [
                            'spec_value_ids' => $specValueId,
                            'spec_values' => $specValue
                        ];
                    }
                }
                $result = $tmp;
            }
        }
        return $result;
    }

    /**
     * 保存||更新规格信息
     * @param $gid
     * @param $specInfo
     * @return bool|array
     */
    public static function saveSpecInfo($gid, $specInfo)
    {
        //删除不再使用的规格信息
        $oldSpecIds = GoodsSpec::query()->where('gid', $gid)->where('deleted_at', 0)
            ->pluck('id')->toArray();
        $oldSpecValueIds = GoodsSpecValue::query()->where('gid', $gid)->where('deleted_at', 0)
            ->pluck('id')->toArray();
        $newSpecIds = [];
        $newSpecValueIds = [];
        foreach ($specInfo as $spec) {
            $spec['id'] != 0 && $newSpecIds[] = $spec['id'];
            foreach ($spec['spec_value'] as $specValue) {
                $specValue['id'] != 0 && $newSpecValueIds[] = $specValue['id'];
            }
        }
        //奇怪的东西混进来了，传入的规格id不属于此商品
        if (!empty(array_diff($newSpecIds, $oldSpecIds)) || !empty(array_diff($newSpecValueIds, $oldSpecValueIds))) {
            return false;
        }
        $delSpecIds = array_diff($oldSpecIds, $newSpecIds);
        $delSpecValueIds = array_diff($oldSpecValueIds, $newSpecValueIds);
        if (!empty($delSpecIds))
            GoodsSpec::query()->whereIn('id', $delSpecIds)->update([
                'deleted_at' => time()
            ]);
        if (!empty($delSpecValueIds))
            GoodsSpecValue::query()->whereIn('id', $delSpecValueIds)->update([
                'deleted_at' => time()
            ]);
        //再新增||更新新规格信息
        foreach ($specInfo as &$spec) {
            if ($spec['id'] != 0) {
                GoodsSpec::query()->where('id', $spec['id'])->update([
                    "name" => $spec['name'],
                    'updated_at' => time()
                ]);
            } else {
                $spec['id'] = GoodsSpec::query()->insertGetId([
                    'gid' => $gid,
                    'name' => $spec['name'],
                    'tid' => $spec['tid'],
                    'created_at' => time()
                ]);
            }
            foreach ($spec['spec_value'] as &$specValue) {
                if ($specValue['id'] != 0) {
                    GoodsSpecValue::query()->where('id', $specValue['id'])->update([
                        "name" => $specValue['name'],
                        'updated_at' => time()
                    ]);
                } else {
                    $specValue['id'] = GoodsSpecValue::query()->insertGetId([
                        'gid' => $gid,
                        'spec_id' => $spec['id'],
                        'name' => $specValue['name'],
                        'tid' => $specValue['tid'],
                        'created_at' => time()
                    ]);
                }

            }
        }
        $specInfo = self::combineSpecInfo($specInfo);
        return $specInfo;
    }

    /**
     * 查询商品规格信息
     * @param $gid
     * @return array
     */
    public static function getGoodsSpecInfo($gid)
    {
        $specInfo = [];
        $specNames = GoodsSpec::query()->where('deleted_at', 0)->where('gid', $gid)->get()->toArray();
        if (!empty($specNames)) {
            foreach ($specNames as $specName) {
                $specValueInfo = [];
                $specValues = GoodsSpecValue::query()->where('gid', $gid)->where('spec_id', $specName['id'])
                    ->get()->toArray();
                if (!empty($specValues)) {
                    foreach ($specValues as $specValue) {
                        $specValueInfo[] = [
                            'id' => $specValue['id'],
                            'name' => $specValue['name'],
                            'tid' => $specValue['tid']
                        ];
                    }
                }
                $specInfo[] = [
                    'id' => $specName['id'],
                    'name' => $specName['name'],
                    'tid' => $specName['tid'],
                    'spec_value' => $specValueInfo
                ];
            }
        }
        return $specInfo;
    }
}
