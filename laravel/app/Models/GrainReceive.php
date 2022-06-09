<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrainReceive extends Model
{
    protected $table = "dove_grain_receive";
    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $firm_id
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $block_type, $block_id, $firm_id, $page_size)
    {
        $results =  DB::table('dove_grain_receive as receive')
            ->select(DB::raw('receive.date as data_time, receive.receive_id, receive.issuer, receive.factory_id, factory.name as factory_name, receive.block_id, block.name as block_name, block.block_type, block.type_name, receive.number, receive.use_number, receive.grain_id, grain.grain_name as grain_name, receive.remarks'))
            ->leftJoin('dove_user as user', 'user.id', '=', 'receive.uid')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'receive.factory_id')
            ->leftJoin('dove_block as block', 'block.id', '=', 'receive.block_id')
            ->leftJoin('dove_items as item', 'item.id', '=', 'receive.item_id')
            ->leftJoin('dove_grain as grain', 'grain.grain_id', '=', 'receive.grain_id')
            ->where('factory.firm_id', $firm_id);
        if($start_time && $end_time){
            $results = $results->whereBetween('receive.date', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $results = $results->where('receive.date', '=',$start_time);
        }elseif (empty($start_time) && $end_time)
        {
            $results = $results->where('receive.date', '=',$end_time);
        }
        if($block_type)
            $results = $results->where('block.block_type', $block_type);
        if($block_id)
            $results = $results->where('receive.block_id', $block_id);
        if($factory_id)
            $results = $results->where('receive.factory_id', $factory_id);
        $results = $results
            ->orderBy('receive_id','desc');
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
     * @param $data_time
     * @param $factory_id
     * @param $block_id
     * @param $user_id
     * @param $issuer
     * @param $grain_id
     * @param $remarks
     * @param $number
     * @param $use_number
     * 新增异常数据
     * @return mixed
     */
    public function addData($factory_id, $block_id, $user_id, $data_time, $issuer, $grain_id, $number, $use_number, $remarks)
    {
        try{
            $insertArray = [
                'issuer' => $issuer,
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'block_id' => $block_id,
                'date' => $data_time,
                'grain_id' => $grain_id,
                'item_id' => $grain_id,
                'number' => $number,
                'use_number' => $use_number,
                'remarks' => $remarks,
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
     * @param $receive_id
     * @param $data_time
     * @param $factory_id
     * @param $block_id
     * @param $user_id
     * @param $issuer
     * @param $grain_id
     * @param $number
     * @param $remarks
     * @param $use_number
     * @return mixed
     */
    public function updateData($receive_id, $factory_id,  $user_id, $block_id, $data_time, $issuer, $grain_id, $number, $use_number, $remarks)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'issuer' => $issuer,
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'block_id' => $block_id,
                'date' => $data_time,
                'grain_id' => $grain_id,
                'item_id' => $grain_id,
                'number' => $number,
                'use_number' => $use_number,
                'remarks' => $remarks,
                'uptime' => time()
            ];
            DB::table($this->table)
                ->where('receive_id', $receive_id)
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
     * @param $receive_id
     * 删除数据
     * @return mixed
     */
    public function delData($receive_id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('receive_id', $receive_id)
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
     * @param $receive_id
     * 查询数据详情
     * @return mixed
     */
    public function getInfo($receive_id)
    {
        return $results =  DB::table($this->table)
            ->select(DB::raw('receive_id, issuer, uid, factory_id, block_id, number, use_number, grain_id, item_id, remarks'))
            ->where('receive_id', $receive_id)
            ->first();
    }

    /**
     * @param $firm_id
     * @param $data_time
     * 饲料出库统计
     * @return mixed
     */
    public function getAmount($firm_id, $data_time)
    {
        return $result = DB::table('dove_grain_receive as grain_receive')
            ->select(DB::raw('sum(grain_receive.use_number) as sum_use_number'))
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'grain_receive.factory_id')
            ->where("factory.firm_id",$firm_id)
            ->where('grain_receive.date', $data_time)
            ->first();
    }
}
