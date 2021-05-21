<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Grain extends Model
{
    protected $table = "dove_grain";
    /**
     * @param $factory_id
     * @param $item_id
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($factory_id, $item_id, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('grain_id, factory_id, factory.name, item.item_name as grain_name, unit_price, number, remarks'))
            ->leftJoin('dove_items as item', 'item.id', '=', 'dove_grain.item_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_grain.factory_id');
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        if($item_id)
            $results = $results->where('item.id',$item_id);
        $results = $results
            ->orderBy('grain_id','desc')
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        foreach($results as $v){
            $data['list'][] = $v;
        }
        return  $data;

    }
}
