<?php


namespace App\Http\Controllers\GoodsManagement;


use App\Http\Controllers\Controller;
use App\Models\GoodsSpec;
use App\Models\GoodsSpecTemp;
use App\Models\GoodsSpecTempToCategory;
use App\Models\GoodsSpecValue;
use App\Models\GoodsToCategory;
use App\Utils\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecTempController extends Controller
{

    /**
     * 规格模板列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $specNameTemp = GoodsSpecTemp::query()->select('id', 'pid', 'spec_name')->with(['specValues' => function ($query) {
            $query->select('id', 'pid', 'spec_value');
        }])->where('pid', 0)->orderBy('created_at', 'desc');
        $total = $specNameTemp->count();
        if ($page != -1) {
            $specNameTemp->offset(($page - 1) * $pageSize)->limit($pageSize);
        }
        $specNameTemp = $specNameTemp->get()->toArray();
        return ResponseHelper::success([
            'total' => $total,
            'data_list' => $specNameTemp
        ]);
    }

    /**
     * 新增规格信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $pid = $request->input('pid', 0);
        $specName = $request->input('spec_name', '');
        $specValue = $request->input('spec_value', '');
        if ($pid != 0) {
            $pidExist = GoodsSpecTemp::query()->where('id', $pid)->first();
            if (!$pidExist)
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "规格名称不存在");
            if (empty($specValue))
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "规格值不能为空");
            $specValueExist = GoodsSpecTemp::query()->where('spec_value', $specValue)->where('pid', $pid)->first();
            if ($specValueExist)
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "规格值已存在");
        }
        if ($pid == 0) {
            if (empty($specName))
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "规格名称不能为空");
            $specNameExist = GoodsSpecTemp::query()->where('spec_name', $specName)->first();
            if ($specNameExist)
                return ResponseHelper::fail(ResponseHelper::ERROR_CODE, "规格名称已存在");

        }
        $newId = GoodsSpecTemp::query()->insertGetId([
            'spec_name' => $specName,
            'spec_value' => $specValue,
            'pid' => $pid,
            'created_at' => time()
        ]);
        return ResponseHelper::success([
            'new_id' => $newId
        ]);
    }

    /**
     * 删除规格模板
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $specTemp = GoodsSpecTemp::query()->where('id', $id)->first();
        if (!$specTemp)
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, '规格模板不存在');
        DB::beginTransaction();
        try {
            //清商品规格关系
            GoodsSpec::query()->where('tid', $id)->update([
                'tid' => 0
            ]);
            GoodsSpecValue::query()->where('tid', $id)->update([
                'tid' => 0
            ]);
            //清类目关系
            GoodsSpecTempToCategory::query()->where('tid', $id)->delete();
            //删自己
            $specTemp->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::fail(ResponseHelper::ERROR_CODE, $e->getMessage());
        }
        return ResponseHelper::success();
    }
}
