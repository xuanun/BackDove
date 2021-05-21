<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Rule extends Model
{
    protected $table = "dove_user_rule";

    /**
     * 上线班时间列表
     * @param $firm_id
     * @return mixed
     */
    public function getList($firm_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('rule_id, title, morn_start, morn_end, noon_start, noon_end, repeat_day, state'))
            ->where('firm_id', $firm_id)
            ->where('delete',  1)
            ->get();
    }

    /**
     * @param $title
     * @param $user_id
     * @param $morn_start
     * @param $morn_end
     * @param $noon_start
     * @param $noon_end
     * @param $repeat_day
     * @param $state
     * @param $delete
     * @param $firm_id
     * 添加数据
     * @return mixed
     */
    public function addData($title, $user_id, $morn_start, $morn_end, $noon_start, $noon_end, $repeat_day, $state, $delete, $firm_id)
    {
        try {
            $insertArray = [
                'uid' => $user_id,
                'title' => $title,
                'morn_start' => $morn_start,
                'morn_end' => $morn_end,
                'noon_start' => $noon_start,
                'noon_end' => $noon_end,
                'repeat_day' => $repeat_day,
                'state' => $state,
                'delete' => $delete,
                'firm_id' => $firm_id,
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code' => 20000, 'msg' => '新增成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '新增失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $rule_id
     * @param $title
     * @param $user_id
     * @param $morn_start
     * @param $morn_end
     * @param $noon_start
     * @param $noon_end
     * @param $repeat_day
     * 修改数据
     * @return mixed
     */
    public function editData($rule_id, $title, $user_id, $morn_start, $morn_end, $noon_start, $noon_end,$repeat_day)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'uid' => $user_id,
                'title' => $title,
                'morn_start' => $morn_start,
                'morn_end' => $morn_end,
                'noon_start' => $noon_start,
                'noon_end' => $noon_end,
                'repeat_day' => $repeat_day,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('rule_id', $rule_id)
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
     * @param $rule_id
     * @param $state
     * 修改状态
     * @return mixed
     */
    public function editState($rule_id,  $state)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'state' => $state,
                'uptime' => time(),
            ];
            if($state == 0){
                DB::table($this->table)
                    ->where('rule_id', $rule_id)
                    ->update($UpdateArray);
            }
            if($state == 1){
                $stateArray = [
                    'state' => 0,
                    'uptime' => time(),
                ];
                DB::table($this->table)
                    ->where('state', 1)
                    ->update($stateArray);
                DB::table($this->table)
                    ->where('rule_id', $rule_id)
                    ->update($UpdateArray);
            }
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * @param $rule_id
     * 删除数据
     * @return mixed
     */
    public function delData($rule_id)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'delete' => 0,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('rule_id', $rule_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }
}
