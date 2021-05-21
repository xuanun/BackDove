<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Block extends Model
{
    protected $table = "dove_block";
    /**
     * 通过厂区ID查询全部仓号
     * @param $factory_id
     * @return mixed
     */
    public function getAllBlock($factory_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('id, name, block_type, type_name'));
        if($factory_id)
            $results = $results->where('factory_id', $factory_id);
        return $results = $results->get();
    }
    /**
     * 通过厂区ID查询全部未分配仓号
     * @param $factory_id
     * @param $type_id
     * @param $block_ids
     * @return mixed
     */
    public function getBlock($factory_id, $block_ids)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id, name, block_type, type_name'))
            ->where('factory_id', $factory_id)
            ->whereNotIn('id',$block_ids)
            ->get();
    }

    /**
     * 通过厂区ID查询全部未分配仓号类型
     * @param $factory_id
     * @param $block_ids
     * @return mixed
     */
    public function getBlockType($factory_id, $block_ids)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('block_type, type_name'))
            ->where('factory_id', $factory_id)
            ->whereNotIn('id',$block_ids)
            ->groupBy('block_type')
            ->get();
    }

    /**
     * 查询仓库信息列表
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $firm_id
     * @return mixed
     */
    public function getBlockList($factory_id, $block_type, $block_id, $firm_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw(' dove_block.id, dove_block.factory_id, factory.name as factory_name, dove_block.name, dove_block.block_type, dove_block.type_name'))
            ->leftJoin('dove_factory as factory', 'dove_block.factory_id','=', 'factory.id');
        if($factory_id)
            $results = $results->where('dove_block.factory_id', $factory_id);
        if($block_type)
            $results = $results->where('dove_block.block_type', $block_type);
        if($block_id)
            $results = $results->where('dove_block.id', $block_id);
        $results = $results->where('firm_id', $firm_id)
            ->get();
        return  $results;
    }

    /**
     * @param $block_name
     * @param $block_type
     * @param $type_name
     * @param $factory_id
     * 新增仓号
     * @return mixed
     */
    public function addBlock($block_name, $block_type, $type_name, $factory_id)
    {
        $exists = $this->existsBlock($block_name, $factory_id);
        if ($exists) {
            DB::rollBack();
            return  ['code' => 40000, 'msg' => '仓号名字已经存在', 'data' => []];
        }else{
            try {
                $insertArray = [
                    'name' => $block_name,
                    'block_type' => $block_type,
                    'type_name' => $type_name,
                    'factory_id' => $factory_id,
                    'created_time' => time(),
                    'updated_time' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                $return = ['code' => 20000, 'msg' => '新增成功', 'block_id' => $id];
            } catch (\Exception $e) {
                DB::rollBack();
                $return = ['code' => 40000, 'msg' => '新增失败', 'data' => [$e->getMessage()]];
            }
            return $return;
        }
    }

    /**
     * @param $block_id
     * @param $dung
     * @param $waste
     * 更新仓号鸽粪废料
     * @return mixed
     */
    public function editData($block_id, $dung, $waste)
    {
        DB::beginTransaction();
        try{
            $updateArray = [
                'waste' =>$waste,
                'dung' =>$dung,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $block_id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'数据录入失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 通过仓号ID查询仓号信息
     * @param $block_id
     * @return mixed
     */
    public function getBlockInfo($block_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id, name, block_type, type_name, factory_id, waste, dung'))
            ->where('id', $block_id)
            ->first();
    }

    /**
     * 查询可销售数据
     * @param $firm_id
     * @param $type_id
     * @param $factory_id
     * @return mixed
     */
    public function getSumWaste($firm_id, $type_id, $factory_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('sum(dove_block.waste) as sum_waste, sum(dove_block.dung) as sum_dung'))
            ->leftJoin('dove_factory as factory', 'dove_block.factory_id', '=', 'factory.id')
            ->where('factory.firm_id', $firm_id);
        if($type_id)
            $results = $results->where('dove_block.block_type', $type_id);
        if($factory_id)
            $results = $results->where('dove_block.factory_id', $factory_id);
        $results = $results->first();

        return $results;
    }


    /**
     * @param $block_name
     * @param $factory_id
     * 查询厂区名字存不存在
     * @return mixed
     */
    public function existsBlock($block_name, $factory_id)
    {
        return DB::table($this->table)
            ->where('name', $block_name)
            ->where('factory_id', $factory_id)
            ->exists();

    }
}
