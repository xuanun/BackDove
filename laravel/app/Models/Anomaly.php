<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Anomaly extends Model
{
    protected $table = "dove_anomaly";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * @param $factory_id
     * @param $user_id
     * @param $type_name
     * @param $type_id
     * @param $block_id
     * @param $cage_id
     * @param $category_id
     * @param $category_name
     * @param $die_amount
     * @param $die_rate
     * @param $sick_amount
     * @param $sick_rate
     * @param $inability_amount
     * @param $inability_rate
     * @param $cause
     * @param $anomaly_date
     * 新增异常数据
     * @return mixed
     */
    public function addData($user_id, $factory_id, $type_name, $type_id, $block_id, $cage_id, $category_id, $category_name, $die_amount, $die_rate,
                            $sick_amount, $sick_rate, $inability_amount, $inability_rate, $cause, $anomaly_date )
    {
        try{
            $insertArray = [
                'user_id' => $user_id,
                'factory_id' => $factory_id,
                'type_name' => $type_name,
                'type_id' => $type_id,
                'block_id' => $block_id,
                'cage_id' => $cage_id,
                'category_id' => $category_id,
                'category_name' => $category_name,
                'die_amount' => $die_amount,
                'die_rate' => $die_rate,
                'sick_amount' => $sick_amount,
                'sick_rate' => $sick_rate,
                'inability_amount' => $inability_amount,
                'inability_rate' => $inability_rate,
                'anomaly_status' => self::INVALID,
                'cause' => $cause,
                'data_status' => self::INVALID,
                'anomaly_date' => $anomaly_date,
                'created_time' => time(),
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 通过类型获取新闻
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_id
     * @param $cage_id
     * @param $category_id
     * @param $type_id
     * @param $page_size
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $block_id, $cage_id, $category_id, $type_id, $page_size)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('dove_anomaly.id, anomaly_date, dove_anomaly.factory_id, factory.name as factory_name, dove_anomaly.type_name, block_id, block.name as block_name, cage_id, category_name, die_rate, sick_rate,
            inability_rate, clear_egg_rate, anomaly_status, cause, data_status'))
            ->leftJoin('dove_block as block', 'block.id', '=', 'dove_anomaly.block_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_anomaly.factory_id');
        if($start_time && $end_time){
//            $start_time = strtotime($start_time);
//            $end_time =  strtotime($end_time);
            $results = $results->whereBetween('dove_anomaly.anomaly_date', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
//            $start_time = strtotime($start_time);
            $results = $results->where('dove_anomaly.anomaly_date', '=',$start_time);
        }elseif (empty($start_time) && $end_time)
        {
//            $end_time =  strtotime($end_time);
            $results = $results->where('dove_anomaly.anomaly_date', '=',$end_time);
        }
        if($factory_id){
            $results = $results->where('dove_anomaly.factory_id',$factory_id);
        }
        if($type_id){
            $results = $results->where('dove_anomaly.type_id',$type_id);
        }
        if($block_id){
            $results = $results->where('dove_anomaly.block_id',$block_id);
        }
        if($cage_id){
            $results = $results->where('dove_anomaly.cage_id',$cage_id);
        }
        if($category_id){
            $results = $results->where('dove_anomaly.category_id',$category_id);
        }
        $results = $results
            ->orderBy('dove_anomaly.created_time', 'desc')
            ->paginate($page_size);
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        foreach($results as $v){
            $v->die_rate =  empty($v->die_rate) ? '/' : $v->die_rate.'%';
            $v->sick_rate = empty($v->sick_rate) ? '/' : $v->sick_rate.'%';
            $v->inability_rate =  empty($v->inability_rate) ?  '/' : $v->inability_rate.'%';
            $v->clear_egg_rate =  empty($v->clear_egg_rate) ?  '/' : $v->clear_egg_rate.'%';
            $data['list'][] = $v;
        }
        return  $data;
    }


    /**
     * 修改异常状态
     * @param $anomaly_id
     * @param $anomaly_status
     * @return mixed
     */
    public function editAnomaly($anomaly_id, $anomaly_status)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'anomaly_status' => $anomaly_status,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $anomaly_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

}
