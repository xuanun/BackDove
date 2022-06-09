<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrainLog extends Model
{
    protected $table = "dove_grain_log";
    /**
     * @param $date_time
     * @param $factory_id
     * @param $item_id
     * @param $type_id
     * @param $reason
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($date_time, $factory_id, $item_id, $type_id, $reason, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('grain_id as id, record_time, unit, production, factory_id, factory.name, item.id as item_id, grain_name, unit_price, number, type as type_id, price, supplier, examiner, reason, return_time, manager, remarks, borrowing, uptime'))
            ->leftJoin('dove_items as item', 'item.id', '=', 'dove_grain_log.item_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_grain_log.factory_id');
        if($reason)
            $results = $results->where('reason', $reason);
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        if($date_time)
            $results = $results->where('record_time', $date_time);
        if($item_id)
            $results = $results->where('item.id',$item_id);
        $results = $results
            ->where('type',$type_id)
            ->orderBy('grain_id','desc');
        if($page_size)
        {
            $results = $results->paginate($page_size);
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
        }else{
            $results = $results->get();
            $data['list'] = [];
            foreach($results as $v){
                $data['list'][] = $v;
            }
            return  $data;
        }
    }

    /**
     * 修改粮食出入库数据
     * @param $id
     * @param $user_id
     * @param $date_time
     * @param $factory_id
     * @param $item_id
     * @param $goods_name
     * @param $production
     * @param $number
     * @param $unit_price
     * @param $unit
     * @param $price
     * @param $supplier
     * @param $examiner
     * @param $manager
     * @param $type_id
     * @param $reason
     * @param $borrowing
     * @param $remarks
     * @param $return_time
     * @return mixed
     */
    public function editFood($id, $user_id, $date_time, $factory_id, $item_id, $goods_name, $production, $number, $unit_price, $unit, $price, $supplier, $examiner, $manager, $type_id, $reason, $borrowing, $remarks, $return_time)
    {
        try{
            $updateArray = [
                'uid' => $user_id,
                'record_time' => $date_time,
                'factory_id' => $factory_id,
                'item_id'=> $item_id,
                'grain_name' => $goods_name,
                'production' => $production,
                'number' => $number,
                'unit_price' => $unit_price,
                'unit'=> $unit,
                'price' => $price,
                'supplier' => $supplier,
                'examiner' => $examiner,
                'manager' => $manager,
                'type' => $type_id,
                'reason' => $reason,
                'borrowing' => $borrowing,
                'remarks' => $remarks,
                'return_time' => $return_time,
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('grain_id', $id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 添加粮食出入库数据
     * @param $user_id
     * @param $date_time
     * @param $factory_id
     * @param $item_id
     * @param $goods_name
     * @param $production
     * @param $number
     * @param $unit_price
     * @param $price
     * @param $supplier
     * @param $examiner
     * @param $type_id
     * @param $manager
     * @param $reason
     * @param $borrowing
     * @param $remarks
     * @param $return_time
     * @param $unit
     * @return mixed
     */
    public function addLog($user_id, $date_time, $factory_id, $item_id, $goods_name, $production, $number, $unit_price, $price, $supplier, $examiner, $type_id, $manager, $reason, $borrowing, $remarks, $return_time, $unit)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'record_time' => $date_time,
                'factory_id' => $factory_id,
                'production' => $production,
                'grain_name' => $goods_name,
                'item_id' => $item_id,
                'unit' => $unit,
                'unit_price' => $unit_price,
                'number' => $number,
                'price' => $price,
                'supplier' => $supplier,
                'examiner' => $examiner,
                'remarks' => $remarks,
                'type' => $type_id,
                'reason' => $reason,
                'return_time' => $return_time,
                'borrowing' => $borrowing,
                'manager' => $manager,
                'creatime' => time(),
                'uptime' => time()
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $id
     * 删除数据
     * @return mixed
     */
    public function delData($id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('grain_id', $id)
                ->delete();
            if($id){
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
            }
            else{
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }

    /**
     * @param $id
     * 查询数据详情
     * @return mixed
     */
    public function getInfo($id)
    {
        return $results =  DB::table($this->table)
            ->select(DB::raw('uid, record_time, factory_id, item_id, grain_name, production, number, unit_price, price, supplier, examiner, type, manager, reason, borrowing, remarks, unit'))
            ->where('grain_id', $id)
            ->first();
    }
}
