<?php


namespace App\Console\Commands;


use App\Models\GoodsCategory;
use App\User;
use App\Utils\Export;
use App\Utils\UsersImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class Test extends Command
{
    protected $signature = "test";

    protected $description = "test";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        date_default_timezone_set('PRC');

        $filePath = dirname(dirname(dirname(dirname(__FILE__)))) . "/public/eee.xlsx";
        $newFilePath = dirname(dirname(dirname(dirname(__FILE__)))) . "/storage/logs/eee2.xlsx";

        $data = Excel::toArray(new UsersImport(), $filePath);
        unset($data[0][0]);
        $newDatas = [];
        foreach ($data[0] as $item) {
            $orderGoods = json_decode($item[32], 1);
            $extInfo = json_decode($item[33], 1);
            $code = explode('、', $extInfo[0]['content']??'');
            $newDatas[] = [
                "goods_name" => $orderGoods[0]['goods_name'],
                "spec" => $orderGoods[0]['spec_values'],
                "amount" => $orderGoods[0]['amount']['$numberInt'],
                "price" => bcdiv($orderGoods[0]['price']['$numberInt'], 100, 2),
                "final_price" => bcdiv($item[9], 100, 2),
                "pay_at" => date('Y-m-d H:i:s', $item[22]),
                "receiver_name" => $item[12],
                "receiver_phone" => $item[13],
                "receiver_p" => $item[14] . $item[15] . $item[16],
                "receiver_addr" => $item[14] . $item[15] . $item[16] . $item[17],
                "comments" => $item[11],
                "o_number" => $item[6],
                "ship" => "",
                "ship_no" => "",
                "code_no" => count($code),
                "code" => $extInfo[0]['content'] ?? '',
            ];
        }
        $head = ['商品名称', '规格', '购买数量', '商品金额', '实付款金额', '付款时间', '收件人姓名', '收件人手机号', '所在地址', '详细收货地址', '订单备注', '订单号', '物流商家', '快递单号', "核销码数量", "核销码"];

        $newObj = new Export($newDatas, $head);
        Excel::store($newObj, "aaaaa.xlsx");
    }
}
