<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CageAdd extends Model
{
    protected $table = "dove_cage_add";

    /**
     * @param $user_id
     * @param $hatch_add
     * @param $squab_add
     * @param $nest_add
     * 乳鸽新增数据
     * @return mixed
     */
    public function addSquab($user_id, $hatch_add, $squab_add, $nest_add)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'hatch' => $hatch_add,
                'brood' => $squab_add,
                'conesting' => $nest_add,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'add_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $buy_add
     * @param $breed_add
     * @param $buy_in
     * @param $nest_add
     * 种鸽新增数据
     * @return mixed
     */
    public function addPigeon($user_id, $buy_add, $breed_add, $buy_in, $nest_add)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'added_out' => $buy_add,
                'added_wit' => $breed_add,
                'conesting' => $nest_add,
                'replenish' => $buy_in,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'add_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $breed_add
     * 童鸽录入数据
     * @return mixed
     */
    public function addChild($user_id, $breed_add)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'breeding' => $breed_add,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'add_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $egg_add
     * @param $spell_add
     * 鸽蛋录入数据
     * @return mixed
     */
    public function addEgg($user_id, $egg_add, $spell_add)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'added_wit' => $egg_add,
                'spell' => $spell_add,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'add_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $brood_add
     * @param $switch_add
     * @param $yield_add
     * 青年鸽新增数据
     * @return mixed
     */
    public function addYouth($user_id, $brood_add, $switch_add, $yield_add)
    {
        try{
            $insertArray = [
                'uid' =>$user_id,
                'hatch' => $yield_add,
                'brood' => $brood_add,
                'conesting' => $switch_add,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'add_id'=>$id];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $user_id
     * @param $hatch
     * @param $brood
     * @param $conesting
     * @param $added_out
     * @param $added_wit
     * @param $breeding
     * @param $replenish
     * @param $spell
     * 新增数据
     * @return mixed
     */
    public function addData($user_id, $hatch, $brood, $conesting, $added_out, $added_wit, $breeding, $replenish, $spell)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'hatch' => $hatch,
                'brood' => $brood,
                'conesting' => $conesting,
                'added_out' => $added_out,
                'added_wit' => $added_wit,
                'breeding' => $breeding,
                'replenish' => $replenish,
                'spell' => $spell,
                'usage_y' => date('Y', time()),
                'usage_m' => date('m', time()),
                'usage_d' => date('d', time()),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>['add_id'=>$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $add_id
     * @param $user_id
     * @param $hatch
     * @param $brood
     * @param $conesting
     * @param $added_out
     * @param $added_wit
     * @param $breeding
     * @param $replenish
     * @param $spell
     * 修改数据
     * @return mixed
     */
    public function editData($add_id, $user_id, $hatch, $brood, $conesting, $added_out, $added_wit, $breeding, $replenish, $spell)
    {
        try{
            $updateArray = [
                'uid' => $user_id,
                'hatch' => $hatch,
                'brood' => $brood,
                'conesting' => $conesting,
                'added_out' => $added_out,
                'added_wit' => $added_wit,
                'breeding' => $breeding,
                'replenish' => $replenish,
                'spell' => $spell,
                'uptime' => time(),
            ];
            DB::table($this->table)->where('add_id',$add_id)->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>['add_id'=>$add_id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
}
