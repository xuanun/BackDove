<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MedVaccine extends Model
{
    protected $table = "dove_med_vaccin";
    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $block_type, $block_id, $page_size)
    {
        $results =  DB::table('dove_med_vaccin as vaccine')
            ->select(DB::raw('vaccin_id, factory.id as factory_id, factory.name as factory_name, block.block_type, block.type_name, block.id as block_id, block.name as block_name, vaccine.uid as user_id, record_time, symptom, vaccine.number as number, usage_time, dosage, vaccine.item_id, vaccine.druge_id as drugs_id, drugs.drugs_name as item_name, drugs.production, drugs.producedate, drugs.batch_number,  approval, charge, remarks, personnel, breeder, method, feedback'))
            ->leftJoin('dove_block as block', 'block.id', '=', 'vaccine.block_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'block.factory_id')
            ->leftJoin('dove_items as item', 'item.id', '=', 'vaccine.item_id')
            ->leftJoin('dove_drugs as drugs', 'vaccine.druge_id', '=', 'drugs.drugs_id');
//            ->leftJoin('dove_grain as grain', 'grain.grain_id', '=', 'receive.grain_id');
        if($start_time && $end_time){
            $results = $results->whereBetween('vaccine.record_time', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $results = $results->where('vaccine.record_time', '=',$start_time);
        }elseif (empty($start_time) && $end_time)
        {
            $results = $results->where('vaccine.record_time', '=',$end_time);
        }
        if($block_type)
            $results = $results->where('block.block_type', $block_type);
        if($block_id)
            $results = $results->where('block.id', $block_id);
        if($factory_id)
            $results = $results->where('block.factory_id', $factory_id);
        $results = $results
            ->orderBy('vaccine.vaccin_id','desc')
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
     * @param $user_id
     * @param $block_id
     * @param $record_time
     * @param $symptom
     * @param $number
     * @param $usage_time
     * @param $dosage
     * @param $drugs_id
     * @param $item_id
     * @param $approval
     * @param $charge
     * @param $remarks
     * @param $personnel
     * @param $breeder
     * @param $method
     * @param $feedback
     * 新增使用记录
     * @return mixed
     */
    public function addData($user_id, $block_id, $record_time, $symptom, $number, $usage_time, $dosage, $drugs_id, $item_id, $approval, $charge, $remarks, $personnel, $breeder, $method, $feedback)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'block_id' => $block_id,
                'record_time' => $record_time,
                'symptom' => $symptom,
                'number' => $number,
                'usage_time' => $usage_time,
                'usage_y' => date('Y', strtotime($usage_time)),
                'usage_m' => date('m', strtotime($usage_time)),
                'usage_d' => date('d', strtotime($usage_time)),
                'dosage' => $dosage,
                'druge_id' => $drugs_id,
                'item_id' => $item_id,
                'approval' => $approval,
                'charge' => $charge,
                'remarks' => $remarks,
                'personnel' => $personnel,
                'breeder' => $breeder,
                'method' => $method,
                'feedback' => $feedback,
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }


    /**
     * 修改数据
     * @param $vaccine_id
     * @param $user_id
     * @param $block_id
     * @param $record_time
     * @param $symptom
     * @param $number
     * @param $usage_time
     * @param $dosage
     * @param $drugs_id
     * @param $item_id
     * @param $approval
     * @param $charge
     * @param $remarks
     * @param $personnel
     * @param $breeder
     * @param $method
     * @param $feedback
     * @return mixed
     */
    public function editData($vaccine_id, $user_id, $block_id, $record_time, $symptom, $number, $usage_time, $dosage, $drugs_id, $item_id, $approval, $charge, $remarks, $personnel, $breeder, $method, $feedback)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'uid' => $user_id,
                'block_id' => $block_id,
                'record_time' => $record_time,
                'symptom' => $symptom,
                'number' => $number,
                'usage_time' => $usage_time,
                'usage_y' => date('Y', strtotime($usage_time)),
                'usage_m' => date('m', strtotime($usage_time)),
                'usage_d' => date('d', strtotime($usage_time)),
                'dosage' => $dosage,
                'druge_id' => $drugs_id,
                'item_id' => $item_id,
                'approval' => $approval,
                'charge' => $charge,
                'remarks' => $remarks,
                'personnel' => $personnel,
                'breeder' => $breeder,
                'method' => $method,
                'feedback' => $feedback,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('vaccin_id', $vaccine_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }
    /**
     * @param $vaccine_id
     * 删除数据
     * @return mixed
     */
    public function delData($vaccine_id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('vaccin_id', $vaccine_id)
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
