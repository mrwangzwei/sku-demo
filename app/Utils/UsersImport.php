<?php


namespace App\Utils;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;

class UsersImport implements ToArray
{
    public function Array(Array $tables)
    {
        return $tables;
    }
}
