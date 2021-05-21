<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Drugs extends Model
{
    protected $table = "dove_drugs";

    /**
     * 通过物品ID查询生产厂家
     * @param $category
     * @param $factory_id
     * @return mixed
     */
    public function getIds($category, $factory_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('drugs_id, drugs_name'))
            ->where('category', $category);
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        $results = $results->get();
        return $results;
    }

    /**
     * 通过物品ID查询生产厂家
     * @param $item_id
     * @param $factory_id
     * @return mixed
     */
    public function getProduction($item_id, $factory_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('DISTINCT(production)'))
            ->where('item_id', $item_id);
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        $results = $results->get();
        return $results;
    }

    /**
     * 获取生产批号
     * @param $item_id
     * @param $factory_id
     * @param $production
     * @return mixed
     */
    public function getBatch($item_id, $factory_id, $production)
    {
        return  DB::table($this->table)
            ->select(DB::raw('batch_number'))
            ->where('item_id', $item_id)
            ->where('factory_id', $factory_id)
            ->where('production', $production)
            ->get();
    }

    /**
     * 获取药品ID
     * @param $item_id
     * @param $factory_id
     * @param $production
     * @param $batch_number
     * @return mixed
     */
    public function getDrugId($item_id, $factory_id, $production, $batch_number)
    {
        return  DB::table($this->table)
            ->select(DB::raw('drugs_id, producedate'))
            ->where('item_id', $item_id)
            ->where('factory_id', $factory_id)
            ->where('production', $production)
            ->where('batch_number', $batch_number)
            ->first();
    }

    /**
     * 药品列表
     * @param $data_time
     * @param $factory_id
     * @param $item_id
     * @param $production
     * @param $approved
     * @param $page_size
     * @return mixed
     */
    public function getDataList($data_time, $factory_id, $item_id, $production, $approved, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('drugs_id, item.item_name as item_name, factory_id, producedate, factory.name as factory_name, category, production, batch_number, unit_price, number, approved, creatime'))
            ->leftJoin('dove_items as item', 'item.id', '=', 'dove_drugs.item_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_drugs.factory_id');
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        if($item_id)
            $results = $results->where('item_id', $item_id);
        if($production)
            $results = $results->where('production', $production);
        if($data_time)
            $results = $results->where('producedate', $data_time);
        if($approved)
            $results = $results->where('approved','like',$approved);
        $results = $results
            ->orderBy('drugs_id','desc')
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        foreach($results as $v){
            if($v->category == 1)
                $v->category_name = '药品';
            elseif ($v->category == 2)
                $v->category_name = '疫苗';
            else
                $v->category_name = '';
            $v->creatime = date('Y-m-d', $v->creatime);
            $data['list'][] = $v;
        }
        return  $data;
    }

    /**
     * @param $item_id
     * @param $drugs_name
     * @param $producedate
     * @param $production
     * @param $batch_number
     * @param $category_id
     * @param $number
     * @param $unit_price
     * @param $factory_id
     * @param $approved
     * @param $receiver
     * 药品入库
     * @return mixed
     */
    public function addData( $item_id, $drugs_name, $producedate, $production, $batch_number, $category_id, $number,  $unit_price, $factory_id, $approved, $receiver)
    {
        try {
            $insertArray = [
                'item_id' => $item_id,
                'drugs_name' => $drugs_name,
                'producedate' => $producedate,
                'production' => $production,
                'batch_number' => $batch_number,
                'category' => $category_id,
                'number' => $number,
                'unit_price' => $unit_price,
                'factory_id' => $factory_id,
                'approved' => $approved,
                'receiver' => $receiver,
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
     * @param $batch_number
     * @param $factory_id
     * @param $number
     * @param $type_id
     * 药品出入库更新库存
     * @return mixed
     */
    public function updateData($item_id, $production, $factory_id, $batch_number, $number, $type_id)
    {
        try {
            if($type_id == 1){
                DB::table($this->table)
                    ->where('item_id', $item_id)
                    ->where('production', $production)
                    ->where('factory_id', $factory_id)
                    ->where('batch_number', $batch_number)
                    ->increment('number', $number);
            }elseif($type_id == 2){
                DB::table($this->table)
                    ->where('item_id', $item_id)
                    ->where('production', $production)
                    ->where('factory_id', $factory_id)
                    ->where('batch_number', $batch_number)
                    ->decrement('number', $number);
            }
            $return = ['code' => 20000, 'msg' => '请求成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '请求失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $item_id
     * @param $production
     * @param $batch_number
     * @param $factory_id
     * 查询入库药品存不存在
     * @return mixed
     */
    public function existsDrugs($item_id, $production, $factory_id, $batch_number)
    {
        return DB::table($this->table)
            ->where('item_id', $item_id)
            ->where('production', $production)
            ->where('batch_number', $batch_number)
            ->where('factory_id', $factory_id)
            ->exists();

    }
}
