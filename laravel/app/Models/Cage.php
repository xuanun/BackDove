<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cage extends Model
{
    protected $table = "dove_cage";
    /**
     * 通过仓号ID查询鸽笼个数
     * @param $block_id
     * @return mixed
     */
    public function getCageNum($block_id)
    {
        return $results = DB::table($this->table)
            ->where('block_id', $block_id)
            ->count();
    }

    /**
     * 通过仓号ID查询全部鸽笼
     * @param $block_id
     * @return mixed
     */
    public function getAllCages($block_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id'))
            ->where('block_id', $block_id)
            ->get();
    }

    /**
     * 通过仓号ID查询飞棚仓鸽笼ID
     * @param $block_id
     * @return mixed
     */
    public function getCageID($block_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('id'))
            ->where('block_id', $block_id)
            ->first();
        return empty($results->id) ? '' : $results->id;
    }

    /**
     * 通过仓号ID查询鸽笼
     * @param $block_id
     * @param $start_cage
     * @param $end_cage
     * @return mixed
     */
    public function getCage($block_id, $start_cage, $end_cage)
    {
         $results = DB::table($this->table)
            ->select(DB::raw('id'))
            ->where('block_id', $block_id);
        if($start_cage && $end_cage){
            $results = $results->whereBetween('id', [$start_cage, $end_cage])->get();
        }elseif ($start_cage){
            $results = $results->where('id', '>=', $start_cage)->get();
        }elseif ($end_cage){
            $results = $results->where('id', '=<', $end_cage)->get();
        }else{
            $results = $results->get();;
        }
        return $results;
    }

    /**
     * @param $block_id
     * 新增鸽笼
     * @return mixed
     */
    public function addCage($block_id)
    {
        try{
            $insertArray = [
                'block_id' =>$block_id,
                'created_time' => time(),
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'cage_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 通过鸽笼ID查询鸽笼详情
     * @param $cage_id
     * @return mixed
     */
    public function getCageInfo($cage_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('initiate_time, death, fall_ill, total_sales, cull_total, survival, pigeon, egg, squab, child, youth'))
            ->where('id', $cage_id)
            ->first();
    }

    /**
     * 修改鸽笼数据 乳鸽
     * @param $cage_id
     * @param $block_id
     * @return mixed
     */
    public function editCageBlock($cage_id, $block_id)
    {
        try{
            $UpdateArray = [
                'block_id' => $block_id,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $cage_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return  $return;
    }
    /**
     * 修改鸽笼数据 乳鸽
     * @param $cage_id
     * @param $sick_num
     * @param $cull_num
     * @param $die_num
     * @param $sell_total
     * @param $change_number
     * @param $change_type
     * @return mixed
     */
    public function editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total, $change_type, $change_number)
    {
        $results = $this->getCageInfo($cage_id);
        if (empty($results))
            return  ['code'=>40000,'msg'=>'修改失败, 数据错误, 鸽笼信息不存在', 'data'=>[]];
        try{
            $UpdateArray = [
                'death' => $results->death + $die_num,
                'fall_ill' =>  $results->fall_ill + $sick_num,
                'total_sales' =>  $results->total_sales + $sell_total,
                'cull_total' =>  $results->cull_total + $cull_num,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $cage_id)
                ->update($UpdateArray);
            if($change_type && $change_number > 0)
                DB::table($this->table)
                    ->where('id', $cage_id)
                    ->increment($change_type, $change_number);
            if($change_type && $change_number < 0)
                DB::table($this->table)
                    ->where('id', $cage_id)
                    ->decrement($change_type,  intval(0 - $change_number));
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return  $return;
    }
    /**
     * 通过厂区ID查询全部鸽笼编号
     * @param $factory_id
     * @return mixed
     */
    public function getCages($factory_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('dove_cage.id'))
            ->leftJoin('dove_block as block', 'block.id', '=', 'dove_cage.block_id')
            ->where('block.factory_id', $factory_id)
            ->get();
    }

    /**
     * 查询可销售数据
     * @param $firm_id
     * @param $type_id
     * @param $factory_id
     * @return mixed
     */
    public function getSumData($firm_id, $type_id, $factory_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('sum(dove_cage.pigeon) as sum_pigeon, sum(dove_cage.egg) as sum_egg, sum(dove_cage.squab) as sum_squab,
            sum(dove_cage.child) as sum_child, sum(dove_cage.youth) as sum_youth'))
            ->leftJoin('dove_block as block', 'block.id', '=', 'dove_cage.block_id')
            ->leftJoin('dove_factory as factory', 'block.factory_id', '=', 'factory.id')
            ->where('factory.firm_id', $firm_id);
        if($type_id)
            $results = $results->where('block.block_type', $type_id);
        if($factory_id)
            $results = $results->where('block.factory_id', $factory_id);
        $results = $results->first();

        return $results;
    }

}
