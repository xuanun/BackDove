<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CageCategory extends Model
{
    protected $table = "dove_defusing_type";
    /**
     * 通过鸽子名字查询类型ID
     * @param $category_name
     * @return mixed
     */
    public function getCageCategoryId($category_name)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('type_id as category_id'))
            ->where('alias', $category_name)
            ->first();
        return empty($results) ? '' : $results->category_id;
    }

    /**
     * 查询全部鸽子类型
     * @return mixed
     */
    public function getAllCategory()
    {
        return DB::table($this->table)
            ->select(DB::raw('type_id as category_id, alias as category_name'))
            ->get();
    }

}
