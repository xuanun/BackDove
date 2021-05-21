<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ItemType extends Model
{
    protected $table = "dove_item_type";

    /**
     * 获取全部物品类型
     * @return mixed
     */
    public function getAllType()
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id, type_name'))
            ->get();
    }
}
