<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrainX extends Model
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
            ->select(DB::raw('grain_id, factory_id, factory.name, grain_name, unit_price, number, remarks'))
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

    /**
     * @param $grain_name
     * 查询列表
     * @return mixed
     */
    public function getInfo($grain_name)
    {
        return $results =  DB::table($this->table)
            ->select(DB::raw('grain_id, grain_name, unit_price, number, remarks'))
            ->where('grain_name',$grain_name)
            ->first();
    }

    /**
     * 通过厂区ID查询全部饲料
     * @param $factory_id
     * @return mixed
     */
    public function getAll($factory_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('grain_id, grain_name'))
            ->where('factory_id', $factory_id)
            ->get();
    }

    /**
     * @param $item_id
     * @param $user_id
     * @param $factory_id
     * @param $production
     * @param $grain_name
     * @param $unit_price
     * @param $number
     * @param $supplier
     * @param $examiner
     * @param $remarks
     * 饲料入库
     * @return mixed
     */
    public function addData($item_id, $user_id, $factory_id, $production, $grain_name, $unit_price, $number,  $unit_price, $supplier, $examiner, $remarks)
    {
        try {
            $insertArray = [
                'item_id' => $item_id,
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'production' => $production,
                'grain_name' => $grain_name,
                'unit_price' => $unit_price,
                'number' => $number,
                'supplier' => $supplier,
                'examiner' => $examiner,
                'remarks' => $remarks,
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code' => 20000, 'msg' => '新增成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '新增失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $item_id
     * @param $production
     * @param $factory_id
     * @param $grain_name
     * @param $number
     * @param $type_id
     *饲料出入库更新库存
     * @return mixed
     */
    public function updateData($item_id, $production, $factory_id, $grain_name, $number, $type_id)
    {
        try {
            if($type_id == 1){
                DB::table($this->table)
                    ->where('item_id', $item_id)
                    ->where('production', $production)
                    ->where('factory_id', $factory_id)
                    ->where('grain_name', $grain_name)
                    ->increment('number', $number);
            }elseif($type_id == 2){
                DB::table($this->table)
                    ->where('item_id', $item_id)
                    ->where('production', $production)
                    ->where('factory_id', $factory_id)
                    ->where('grain_name', $grain_name)
                    ->decrement('number', $number);
            }
            $return = ['code' => 20000, 'msg' => '请求成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '请求失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }

}
