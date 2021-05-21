<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NewsCategory extends Model
{
    protected $table = "dove_news_category";

    /**
     * 获取所有新闻分类列表
     * @return mixed
     */
    public function getAllCategory()
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('news_cat_id, cat_name, cat_explain, uptime, creatime'))
            ->get();
    }
}
