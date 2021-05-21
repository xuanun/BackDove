<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DrugsLog extends Model
{
    protected $table = "dove_drugs_log";
    /**
     * 药品出入库列表
     * @param $type_id
     * @param $record_time
     * @param $factory_id
     * @param $category_id
     * @param $production
     * @param $approved
     * @param $out_reason
     * @param $page_size
     * @return mixed
     */
    public function getDataList($type_id, $record_time, $factory_id, $category_id, $production, $approved, $out_reason, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('log_id, record_time, supplier, production, producedate, batch_number, category as category_id, number, unit, unit_price, price, approved, receiver, type as type_id, remarks, out_reason, factory_id, factory.name as factory_name, item_id, item.item_name as item_name'))
            ->leftJoin('dove_items as item', 'item.id', '=', 'dove_drugs_log.item_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_drugs_log.factory_id');
        if($type_id)
            $results = $results->where('type', $type_id);
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        if($category_id)
            $results = $results->where('category', $category_id);
        if($production)
            $results = $results->where('production', $production);
        if($record_time)
            $results = $results->where('record_time', $record_time);
        if($approved)
            $results = $results->where('approved','like',$approved);
        if($out_reason)
            $results = $results->where('out_reason','like',$out_reason);
        $results = $results
            ->orderBy('record_time','desc')
            ->orderBy('log_id','desc')
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        foreach($results as $v){
            if($v->category_id == 1)
                $v->category_name = '药品';
            elseif ($v->category_id == 2)
                $v->category_name = '疫苗';
            else
                $v->category_name = '';
            $data['list'][] = $v;
        }
        return  $data;
    }

    /**
     * @param $user_id
     * @param $record_time
     * @param $item_id
     * @param $drugs_name
     * @param $supplier
     * @param $producedate
     * @param $production
     * @param $batch_number
     * @param $category_id
     * @param $number
     * @param $unit
     * @param $unit_price
     * @param $price
     * @param $factory_id
     * @param $approved
     * @param $receiver
     * @param $type_id
     * @param $remarks
     * @param $out_reason
     * 药品入库
     * @return mixed
     */
    public function addLog($user_id, $record_time, $item_id, $drugs_name, $supplier, $producedate, $production, $batch_number, $category_id, $number, $unit, $unit_price, $price, $factory_id, $approved, $receiver, $type_id, $remarks, $out_reason)
    {
        try {
            $insertArray = [
                'uid' => $user_id,
                'record_time' => $record_time,
                'item_id' => $item_id,
                'drugs_name' => $drugs_name,
                'supplier' => $supplier,
                'producedate' => $producedate,
                'production' => $production,
                'batch_number' => $batch_number,
                'category' => $category_id,
                'number' => $number,
                'unit' => $unit,
                'unit_price' => $unit_price,
                'price' => $price,
                'factory_id' => $factory_id,
                'approved' => $approved,
                'receiver' => $receiver,
                'type' => $type_id,
                'remarks' => $remarks,
                'out_reason' => $out_reason,
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
     * @param $log_id
     * @param $user_id
     * @param $record_time
     * @param $item_id
     * @param $drugs_name
     * @param $supplier
     * @param $producedate
     * @param $production
     * @param $batch_number
     * @param $category_id
     * @param $number
     * @param $unit
     * @param $unit_price
     * @param $price
     * @param $factory_id
     * @param $approved
     * @param $receiver
     * @param $type_id
     * @param $remarks
     * @param $out_reason
     * 药品入库
     * @return mixed
     */
    public function editLog($log_id, $user_id, $record_time, $item_id, $drugs_name, $supplier, $producedate, $production, $batch_number, $category_id, $number, $unit, $unit_price, $price, $factory_id, $approved, $receiver, $type_id, $remarks, $out_reason)
    {
        try {
            $updateArray = [
                'uid' => $user_id,
                'record_time' => $record_time,
                'item_id' => $item_id,
                'drugs_name' => $drugs_name,
                'supplier' => $supplier,
                'producedate' => $producedate,
                'production' => $production,
                'batch_number' => $batch_number,
                'category' => $category_id,
                'number' => $number,
                'unit' => $unit,
                'unit_price' => $unit_price,
                'price' => $price,
                'factory_id' => $factory_id,
                'approved' => $approved,
                'receiver' => $receiver,
                'type' => $type_id,
                'remarks' => $remarks,
                'out_reason' => $out_reason,
                'creatime' => time(),
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('log_id', $log_id)
                ->update($updateArray);
            $return = ['code' => 20000, 'msg' => '修改成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '修改失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }


    /**
     * @param $log_id
     * 删除数据
     * @return mixed
     */
    public function delLog($log_id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('log_id', $log_id)
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

}
