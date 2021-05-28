<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CageLog extends Model
{
    protected $table = "dove_cage_log";

    /**
     * @param $user_id
     * @param $cage_id
     * @param $category_id
     * @param $add_number
     * @param $add_id
     * @param $reduce_num
     * @param $reduce_id
     * @param $day_survival
     * 鸽笼数据日志
     * @return mixed
     */
    public function addLog($user_id, $cage_id, $category_id, $add_number, $add_id, $reduce_num, $reduce_id, $day_survival)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'cage_id' => $cage_id,
                'type_id' => $category_id,
                'in_add' => $add_number,
                'add_id' => $add_id,
                'in_reduce' => $reduce_num,
                'reduce_id' => $reduce_id,
                'day_survival' => $day_survival,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'log_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $log_id
     * @param $user_id
     * @param $cage_id
     * @param $category_id
     * @param $add_number
     * @param $add_id
     * @param $reduce_num
     * @param $reduce_id
     * @param $day_survival
     * 鸽笼数据修改日志
     * @return mixed
     */
    public function editLog($log_id, $user_id, $cage_id, $category_id,
                           $add_number, $add_id, $reduce_num, $reduce_id, $day_survival)
    {
        try{
            $updateArray = [
                'uid' =>$user_id,
                'cage_id' => $cage_id,
                'type_id' => $category_id,
                'in_add' => $add_number,
                'add_id' => $add_id,
                'in_reduce' => $reduce_num,
                'reduce_id' => $reduce_id,
                'day_survival' => $day_survival,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->where('log_id', $log_id)->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'log_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $usage_y
     * @param $usage_m
     * @param $usage_d
     * 获取今天的数据
     * @return mixed
     */
    public function getDataByTime($usage_y, $usage_m, $usage_d)
    {
        return DB::table($this->table)
            ->select(DB::raw('dove_cage_log.*, add.hatch, add.brood, add.conesting, add.added_out, add.added_wit, add.breeding, add.replenish,
            add.spell, reduce.disease, reduce.massacre, reduce.death, reduce.sell, reduce.shift_to'))
            ->leftJoin('dove_cage_add as add', 'dove_cage_log.add_id', '=', 'add.add_id')
            ->leftJoin('dove_cage_reduce as reduce', 'dove_cage_log.reduce_id', '=', 'reduce.reduce_id')
            ->where('dove_cage_log.usage_y', $usage_y)
            ->where('dove_cage_log.usage_m', $usage_m)
            ->where('dove_cage_log.usage_d', $usage_d)
            ->first();
    }

    /**
     * @param $usage_y
     * @param $usage_m
     * @param $usage_d
     * @param $type_id
     * @param $cage_ids
     * 获取今天的数据
     * @return mixed
     */
    public function getData($usage_y, $usage_m, $usage_d, $type_id, $cage_ids)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('sum(reduce.death) as  sum_death'))
            ->leftJoin('dove_cage_reduce as reduce', 'dove_cage_log.reduce_id', '=', 'reduce.reduce_id')
            ->where('dove_cage_log.usage_y', $usage_y)
            ->where('dove_cage_log.usage_m', $usage_m)
            ->where('dove_cage_log.usage_d', $usage_d)
            ->where('dove_cage_log.type_id', $type_id)
            ->whereIn('dove_cage_log.cage_id', $cage_ids)
            ->first();
        return isset($results->sum_death) ? $results->sum_death : 0;
    }


    /**
     * @param $user_id
     * @param $type_id
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $date_y
     * @param $date_m
     * @param $date_d
     * @param $page_size
     * 获取今天录入的数据列表
     * @return mixed
     */
    public function getEntryList($user_id, $type_id, $factory_id, $block_type, $block_id, $date_y, $date_m, $date_d, $page_size)
    {
        $results = DB::table('dove_cage_log as log')
            ->select(DB::raw('log_id, log.cage_id, log.in_add, log.in_add, log.add_id, log.in_reduce, log.reduce_id, add.hatch, add.brood, add.conesting, add.added_out, add.added_wit, add.breeding, add.replenish,add.spell, reduce.disease, reduce.massacre, reduce.death, reduce.sell, reduce.disease_sell, reduce.getout, reduce.shift_to, reduce.dead_eggs, reduce.useless, block.id as block_id, block.name as block_name, block.block_type as block_type, block.type_name as type_name, cage.pigeon as pigeon, cage.egg as egg, cage.squab as squab, cage.ageday as ageday, cage.child as child, cage.youth as youth, block.waste as waste, block.dung as dung, factory.id as factory_id, factory.name as factory_name'))
            ->leftJoin('dove_cage as cage', 'cage.id', '=', 'log.cage_id')
            ->leftJoin('dove_block as block', 'cage.block_id', '=', 'block.id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'block.factory_id')
            ->leftJoin('dove_cage_add as add', 'log.add_id', '=', 'add.add_id')
            ->leftJoin('dove_cage_reduce as reduce', 'log.reduce_id', '=', 'reduce.reduce_id');
            if($factory_id)
                $results =  $results->where('factory.id', $factory_id);
            if($block_type)
                $results =  $results->where('block.block_type', $block_type);
            if($block_id)
                $results =  $results->where('block.id', $block_id);
            if($type_id)
                $results =  $results->where('log.type_id', $type_id);
        $results =  $results
            ->where('log.uid', $user_id)
            ->where('log.usage_y', $date_y)
            ->where('log.usage_m', $date_m)
            ->where('log.usage_d', $date_d)
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
     * 获取留存
     * @param $log_id
     * @return mixed
     */
    public function getDaySurvival($log_id)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('day_survival'))
            ->where('log_id', $log_id)
            ->first();
        return isset($results->day_survival) ? $results->day_survival : 0;
    }

    /**
     * 获取上月留存留存
     * @param $data_time
     * @param $cage_id
     * @param $type_id
     * @return mixed
     */
    public function getSurvivalByTime($data_time, $cage_id, $type_id)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('day_survival'))
            ->where('cage_id', $cage_id)
            ->where('type_id', $type_id)
            ->where('creatime', '<', $data_time)
            ->orderBy('creatime', 'desc')
            ->first();
        return isset($results->day_survival) ? $results->day_survival : 0;
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * 获取数据列表
     * @return mixed
     */
    public function getStatementList($start_time, $end_time, $factory_id, $block_type, $block_id)
    {
        $results = DB::table('dove_cage_log as log')
            ->select(DB::raw("log_id, log.cage_id, log.type_id, type.alias, log.day_survival, log.in_add, log.in_reduce, cage.block_id, cage.pigeon, cage.egg, cage.squab, cage.child, cage.youth, add.hatch, add.brood, add.conesting, add.added_out, add.added_wit, add.breeding, add.replenish, add.spell, reduce.disease, reduce.massacre, reduce.death, reduce.sell, reduce.disease_sell, reduce.getout, reduce.shift_to, reduce.dead_eggs, reduce.useless, block.id as block_id, block.name as block_name, factory.id as factory_id, factory.name as factory_name, FROM_UNIXTIME(log.creatime, '%Y-%m-%d') as log_time"))
            ->leftJoin('dove_cage as cage', 'cage.id', '=', 'log.cage_id')
            ->leftJoin('dove_block as block', 'cage.block_id', '=', 'block.id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'block.factory_id')
            ->leftJoin('dove_cage_add as add', 'log.add_id', '=', 'add.add_id')
            ->leftJoin('dove_defusing_type as type', 'type.type_id', '=', 'log.type_id')
            ->leftJoin('dove_cage_reduce as reduce', 'log.reduce_id', '=', 'reduce.reduce_id');
        if($start_time && $end_time){
            $results = $results->whereBetween('log.creatime', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $end_time = $start_time + 86400;
            $results = $results->where('log.creatime', [$start_time, $end_time]);
        }elseif (empty($start_time) && $end_time)
        {
            $start_time = $end_time - 86400;
            $results = $results->where('log.creatime', [$start_time, $end_time]);
        }
        if($factory_id)
            $results =  $results->where('factory.id', $factory_id);
        if($block_type)
            $results =  $results->where('block.block_type', $block_type);
        if($block_id)
            $results =  $results->where('block.id', $block_id);
        $results =  $results
            //->orderBy('type_id','desc')
            ->get();
        return  $results;
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $block_type
     * @param $block_id
     * @param $type_id
     * 获取数据列表
     * @return mixed
     */
    public function getStatementListByType($start_time, $end_time, $factory_id, $block_type, $block_id, $type_id)
    {
        $results = DB::table('dove_cage_log as log')
            ->select(DB::raw("log_id, log.cage_id, log.type_id, type.alias, log.day_survival, log.in_add, log.in_reduce, cage.block_id, cage.pigeon, cage.egg, cage.squab, cage.child, cage.youth, add.hatch, add.brood, add.conesting, add.added_out, add.added_wit, add.breeding, add.replenish, add.spell, reduce.disease, reduce.massacre, reduce.death, reduce.sell, reduce.disease_sell, reduce.getout, reduce.shift_to, reduce.dead_eggs, reduce.useless, block.id as block_id, block.name as block_name, factory.id as factory_id, factory.name as factory_name, FROM_UNIXTIME(log.creatime, '%Y-%m-%d') as log_time"))
            ->leftJoin('dove_cage as cage', 'cage.id', '=', 'log.cage_id')
            ->leftJoin('dove_block as block', 'cage.block_id', '=', 'block.id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'block.factory_id')
            ->leftJoin('dove_cage_add as add', 'log.add_id', '=', 'add.add_id')
            ->leftJoin('dove_defusing_type as type', 'type.type_id', '=', 'log.type_id')
            ->leftJoin('dove_cage_reduce as reduce', 'log.reduce_id', '=', 'reduce.reduce_id');
        if($start_time && $end_time){
            $results = $results->whereBetween('log.creatime', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $end_time = $start_time + 86400;
            $results = $results->where('log.creatime', [$start_time, $end_time]);
        }elseif (empty($start_time) && $end_time)
        {
            $start_time = $end_time - 86400;
            $results = $results->where('log.creatime', [$start_time, $end_time]);
        }
        if($factory_id)
            $results =  $results->where('factory.id', $factory_id);
        if($block_type)
            $results =  $results->where('block.block_type', $block_type);
        if($block_id)
            $results =  $results->where('block.id', $block_id);
        if($type_id)
            $results =  $results->where('log.type_id', $type_id);
        $results =  $results
            //->orderBy('type_id','desc')
            ->get();
        return  $results;
    }
}
