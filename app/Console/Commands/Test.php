<?php


namespace App\Console\Commands;


use App\Models\GoodsCategory;
use Illuminate\Console\Command;

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
        $aa = "sadasdsdasdsa%ssssssssss";

        var_dump(sprintf($aa,"1111111"));
    }
}
