<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Disinfect extends Model
{
    protected $table = "dove_disinfect";
    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $mode
     * @param $drugs_id
     * @param $firm_id
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $block_type, $block_id, $mode, $drugs_id, $firm_id, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('dove_disinfect.disinfect_id, dove_disinfect.record_time, user.user_name, user.id as user_id, factory.id as factory_id, factory.name as factory_name, block.id as block_id, block.name as block_name, block.block_type, block.type_name,  dove_disinfect.mode, drugs.drugs_id, drugs.drugs_name as drugs_name, dove_disinfect.number, dove_disinfect.remarks, drugs.production, drugs.batch_number'))
            ->leftJoin('dove_user as user', 'user.id', '=', 'dove_disinfect.uid')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_disinfect.factory_id')
            ->leftJoin('dove_block as block', 'block.id', '=', 'dove_disinfect.block_id')
            ->leftJoin('dove_drugs as drugs', 'drugs.drugs_id', '=', 'dove_disinfect.drugs_id')
            ->leftJoin('dove_items as item', 'drugs.item_id', '=', 'item.id');
        if($start_time && $end_time){
            $results = $results->whereBetween('dove_disinfect.record_time', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $results = $results->where('dove_disinfect.record_time', '=',$start_time);
        }elseif (empty($start_time) && $end_time)
        {
            $results = $results->where('dove_disinfect.record_time', '=',$end_time);
        }
        if($block_type)
            $results = $results->where('block.block_type', $block_type);
        if($block_id)
            $results = $results->where('dove_disinfect.block_id', $block_id);
        if($factory_id)
            $results = $results->where('dove_disinfect.factory_id', $factory_id);
        if($mode)
            $results = $results->where('dove_disinfect.mode',$mode);
        if($drugs_id)
            $results = $results->where('dove_disinfect.drugs_id',$drugs_id);
        $results = $results
            ->where('dove_disinfect.firm_id',$firm_id)
            ->orderBy('disinfect_id','desc');
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
     * @param $record_time
     * @param $factory_id
     * @param $block_id
     * @param $user_id
     * @param $mode
     * @param $drugs_id
     * @param $number
     * @param $remarks
     * @param $firm_id
     * 新增异常数据
     * @return mixed
     */
    public function addData($record_time,  $factory_id, $block_id, $user_id, $mode, $drugs_id, $number, $remarks, $firm_id )
    {
        try{
            $insertArray = [
                'record_time' => $record_time,
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'block_id' => $block_id,
                'mode' => $mode,
                'drugs_id' => $drugs_id,
                'number' => $number,
                'remarks' => $remarks,
                'firm_id' => $firm_id,
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
     * 修改消杀数据
     * @param $disinfect_id
     * @param $record_time
     * @param $factory_id
     * @param $block_id
     * @param $user_id
     * @param $mode
     * @param $drugs_id
     * @param $number
     * @param $remarks
     * @param $firm_id
     * @return mixed
     */
    public function updateData($disinfect_id, $record_time,  $factory_id, $block_id, $user_id, $mode, $drugs_id, $number, $remarks, $firm_id)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'record_time' => $record_time,
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'block_id' => $block_id,
                'mode' => $mode,
                'drugs_id' => $drugs_id,
                'number' => $number,
                'remarks' => $remarks,
                'firm_id' => $firm_id,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('disinfect_id', $disinfect_id)
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
     * @param $disinfect_id
     * 删除数据
     * @return mixed
     */
    public function delData($disinfect_id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('disinfect_id', $disinfect_id)
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
