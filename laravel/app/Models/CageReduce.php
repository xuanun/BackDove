<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CageReduce extends Model
{
    protected $table = "dove_cage_reduce";
    /**
     * @param $user_id
     * @param $sick_num
     * @param $cull_num
     * @param $die_num
     * @param $sell_num
     * @param $sick_sell_num
     * 乳鸽减少
     * @return mixed
     */
    public function reduceSquab($user_id, $sick_num, $cull_num, $die_num,
                                $sell_num, $sick_sell_num)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'disease' => $sick_num,
                'massacre' => $cull_num,
                'death' => $die_num,
                'sell' => $sell_num,
                'disease_sell' => $sick_sell_num,
                'getout' => 0,
                'shift_to' => 0,
                'dead_eggs' => 0,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'reduce_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $sick_num
     * @param $cull_num
     * @param $die_num
     * @param $sell_num
     * @param $sick_sell_num
     * @param $useless_num
     * 种鸽减少
     * @return mixed
     */
    public function reducePigeon($user_id, $sick_num, $cull_num, $die_num,
                                $sell_num, $sick_sell_num, $useless_num)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'disease' => $sick_num,
                'massacre' => $cull_num,
                'death' => $die_num,
                'sell' => $sell_num,
                'disease_sell' => $sick_sell_num,
                'getout' => 0,
                'shift_to' => 0,
                'dead_eggs' => 0,
                'useless' => $useless_num,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'reduce_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $sick_num
     * @param $cull_num
     * @param $die_num
     * @param $sell_num
     * @param $sick_sell_num
     * @param $brood_num
     * 种鸽减少
     * @return mixed
     */
    public function reduceChild($user_id, $sick_num, $cull_num, $die_num,
                                 $sell_num, $sick_sell_num, $brood_num)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'disease' => $sick_num,
                'massacre' => $cull_num,
                'death' => $die_num,
                'sell' => $sell_num,
                'disease_sell' => $sick_sell_num,
                'shift_to' => $brood_num,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'reduce_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $damaged_num
     * @param $bad_num
     * @param $to_num
     * @param $imperfect_num
     * @param $clear_egg
     * @param $sick_num
     * @param $sell_num
     * 鸽蛋减少
     * @return mixed
     */
    public function reduceEgg($user_id, $damaged_num, $bad_num, $to_num,
                              $imperfect_num, $clear_egg, $sick_num, $sell_num)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'disease' => $imperfect_num,
                'massacre' => $damaged_num,
                'death' => $bad_num,
                'sell' => $sell_num,
                'disease_sell' => $clear_egg,
                'dead_eggs' => $sick_num,
                'shift_to' => $to_num,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'reduce_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $sick_num
     * @param $cull_num
     * @param $die_num
     * @param $sell_num
     * @param $yield_num
     * 青年鸽减少
     * @return mixed
     */
    public function reduceYouth($user_id, $sick_num, $cull_num, $die_num,
                                $sell_num, $yield_num)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'disease' => $sick_num,
                'massacre' => $cull_num,
                'death' => $die_num,
                'sell' => $sell_num,
                'shift_to' => $yield_num,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'reduce_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
    /**
     * @param $user_id
     * @param $disease
     * @param $massacre
     * @param $death
     * @param $sell
     * @param $disease_sell
     * @param $getout
     * @param $shift_to
     * @param $dead_eggs
     * @param $useless
     * 新增数据
     * @return mixed
     */
    public function addData($user_id, $disease, $massacre, $death, $sell, $disease_sell, $getout, $shift_to, $dead_eggs, $useless)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'disease' => $disease,
                'massacre' => $massacre,
                'death' => $death,
                'sell' => $sell,
                'disease_sell' => $disease_sell,
                'getout' => $getout,
                'shift_to' => $shift_to,
                'dead_eggs' => $dead_eggs,
                'useless' => $useless,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>['reduce_id'=>$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $reduce_id
     * @param $user_id
     * @param $disease
     * @param $massacre
     * @param $death
     * @param $sell
     * @param $disease_sell
     * @param $getout
     * @param $shift_to
     * @param $dead_eggs
     * @param $useless
     * 修改数据
     * @return mixed
     */
    public function editData($reduce_id, $user_id, $disease, $massacre, $death, $sell, $disease_sell, $getout, $shift_to, $dead_eggs, $useless)
    {
        try{
            $updateArray = [
                'uid' => $user_id,
                'disease' => $disease,
                'massacre' => $massacre,
                'death' => $death,
                'sell' => $sell,
                'disease_sell' => $disease_sell,
                'getout' => $getout,
                'shift_to' => $shift_to,
                'dead_eggs' => $dead_eggs,
                'useless' => $useless,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            DB::table($this->table)->where('reduce_id',$reduce_id)->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>['reduce_id'=>$reduce_id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
}
