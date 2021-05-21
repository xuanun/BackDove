<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserFactory extends Model
{
    protected $table = "dove_user_factory";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 通过厂区ID查询已经分配的仓号
     * @param $factory_id
     * @param $role_id
     * @return mixed
     */
    public function getIds($factory_id, $role_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('dove_user_factory.block_id'))
            ->leftJoin('dove_role_users as role_user', 'dove_user_factory.user_id','=', 'role_user.user_id');
        if($role_id)
            $results = $results->where('role_user.role_id',  $role_id);
        $results = $results->where('dove_user_factory.factory_id', $factory_id)
            ->where('dove_user_factory.data_status', self::NORMAL)
            ->get();
        return  $results;
    }

    /**
     * 人员管理--添加数据
     * @param $user_id
     * @param $firm_id
     * @param $factory_id
     * @param $block_id
     * @return mixed
     */
    public function addUserFactory($user_id, $firm_id, $factory_id, $block_id)
    {
        $exists = $this->existsData($factory_id, $block_id);
        $return = ['code'=>40004,'msg'=>'新增失败', 'data'=>['仓号已经存在']];
        if(!$exists)
        {
            try{
                $insertArray = [
                    'user_id' => $user_id,
                    'firm_id' => $firm_id,
                    'factory_id' => $factory_id,
                    'block_id' => $block_id,
                    'data_status' => 1,
                    'updated_time' => time(),
                    'created_time' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
                }else
                    DB::rollBack();
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
        }
        return $return;
    }

    /**
     * @param $factory_id
     * @param $block_id
     * 查询厂区关联是否存在
     * @return mixed
     */
    public function existsData($factory_id, $block_id)
    {
        return DB::table($this->table)
            ->where('factory_id', $factory_id)
            ->where('block_id', $block_id)
            ->where('data_status', self::NORMAL)
            ->exists();
    }
    /**
     * @param $factory_id
     * @param $block_id
     * 查询厂区关联是否存在
     * @return mixed
     */
    public function getUserFactoryId($factory_id, $block_id)
    {
        $result =  DB::table($this->table)
            ->select(DB::raw('id'))
            ->where('factory_id', $factory_id)
            ->where('block_id', $block_id)
            ->where('data_status', self::NORMAL)
            ->first();
        return isset($result->id) ? $result->id : 0;
    }

    /**
     * 人员管理--修改
     * @param $user_id
     * @param $factory_id
     * @param $old_factory_id
     * @param $old_block_id
     * @param $block_id
     * @return mixed
     */
    public function editUserFactory($user_id, $old_factory_id, $old_block_id, $factory_id, $block_id)
    {
        $user_f_id = $this->getUserFactoryId($old_factory_id, $old_block_id);
        $exist_id = $this->getUserFactoryId($factory_id, $block_id);
        $return = ['code'=>40004,'msg'=>'修改失败', 'data'=>['仓号已经存在']];
        if($user_f_id && !$exist_id)
        {
            try{
                $updateArray = [
                    'user_id' =>$user_id,
                    'factory_id' =>$factory_id,
                    'block_id' => $block_id,
                    'updated_time' => time(),
                ];
                $id = DB::table($this->table)
                    ->where('id', $user_f_id)
                    ->where('data_status', self::NORMAL)
                    ->update($updateArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
                }else
                    DB::rollBack();
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
            }
        }
        return $return;
    }
    /**
     * 人员管理--删除
     * @param $user_id
     * @param $factory_id
     * @param $old_factory_id
     * @param $old_role_id
     * @param $block_id
     * @return mixed
     */
    public function delUserFactory($user_id, $factory_id, $block_id)
    {
        $exist_id = $this->getUserFactoryId($factory_id, $block_id);
        $return = ['code'=>40004,'msg'=>'删除失败', 'data'=>['仓号已经存在']];
        if($exist_id)
        {
            try{
                $updateArray = [
                    'data_status' => self::INVALID,
                    'updated_time' => time(),
                ];
                $id = DB::table($this->table)
                    ->where('id', $exist_id)
                    ->where('data_status', self::NORMAL)
                    ->update($updateArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
                }else
                    DB::rollBack();
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
            }
        }
        return $return;
    }

    /**
     * 通过仓号ID查询饲养员 护工
     * @param $block_id
     * @return mixed
     */
    public function getUsersInfo($block_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('dove_user_factory.user_id, user.user_name, role.id as role_id, role.name as role_name'))
            ->leftJoin('dove_role_users as role_user', 'dove_user_factory.user_id','=', 'role_user.user_id')
            ->leftJoin('dove_roles as role', 'role_user.role_id','=', 'role.id')
            ->leftJoin('dove_user as user', 'dove_user_factory.user_id','=', 'user.id')
            ->where('dove_user_factory.block_id', $block_id)
            ->where('dove_user_factory.data_status', self::NORMAL)
            ->get();
    }
}
