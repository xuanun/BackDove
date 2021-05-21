<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MedRecord extends Model
{
    protected $table = "dove_med_record";
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
        $results =  DB::table('dove_med_record as record')
            ->select(DB::raw('record_id, factory.id as factory_id, factory.name as factory_name, block.block_type, block.type_name, block.id as block_id, block.name as block_name, record.uid as user_id, record_time, symptom, record.number as number, usage_time, day, dosage, record.item_id, record.druge_id as drugs_id, drugs.drugs_name as item_name, drugs.production, drugs.batch_number,  approval, feedback'))
            ->leftJoin('dove_block as block', 'block.id', '=', 'record.block_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'block.factory_id')
            ->leftJoin('dove_items as item', 'item.id', '=', 'record.item_id')
            ->leftJoin('dove_drugs as drugs', 'record.druge_id', '=', 'drugs.drugs_id');
//            ->leftJoin('dove_grain as grain', 'grain.grain_id', '=', 'receive.grain_id');
        if($start_time && $end_time){
            $results = $results->whereBetween('record.record_time', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $results = $results->where('receive.record_time', '=',$start_time);
        }elseif (empty($start_time) && $end_time)
        {
            $results = $results->where('receive.record_time', '=',$end_time);
        }
        if($block_type)
            $results = $results->where('block.block_type', $block_type);
        if($block_id)
            $results = $results->where('block.block_id', $block_id);
        if($factory_id)
            $results = $results->where('block.factory_id', $factory_id);
        $results = $results
            ->orderBy('record.record_id','desc')
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
     * @param $day
     * @param $dosage
     * @param $drugs_id
     * @param $item_id
     * @param $approval
     * @param $feedback
     * 新增使用记录
     * @return mixed
     */
    public function addData($user_id, $block_id, $record_time, $symptom, $number, $usage_time, $day, $dosage, $drugs_id, $item_id, $approval, $feedback)
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
                'day' => $day,
                'dosage' => $dosage,
                'druge_id' => $drugs_id,
                'item_id' => $item_id,
                'approval' => $approval,
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
     * @param $record_id
     * @param $user_id
     * @param $block_id
     * @param $record_time
     * @param $symptom
     * @param $number
     * @param $usage_time
     * @param $day
     * @param $dosage
     * @param $drugs_id
     * @param $item_id
     * @param $approval
     * @param $feedback
     * @return mixed
     */
    public function editData($record_id, $user_id, $block_id, $record_time, $symptom, $number, $usage_time, $day, $dosage, $drugs_id, $item_id, $approval, $feedback)
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
                'day' => $day,
                'dosage' => $dosage,
                'druge_id' => $drugs_id,
                'item_id' => $item_id,
                'approval' => $approval,
                'feedback' => $feedback,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('record_id', $record_id)
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
     * @param $record_id
     * 删除数据
     * @return mixed
     */
    public function delData($record_id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('record_id', $record_id)
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
