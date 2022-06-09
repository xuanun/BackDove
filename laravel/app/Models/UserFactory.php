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
        $exist_data = $this->existsData($user_id, $factory_id, $block_id);
        if(!$exist_data)
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
                }else{
                    $return = ['code'=>40000,'msg'=>'', 'data'=>[]];
                    DB::rollBack();
                }
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
        }else{
            $return = ['code'=>40000,'msg'=>'新增失败, ', 'data'=>[]];
        }

        return $return;
    }

    /**
     * @param $user_id
     * @param $factory_id
     * @param $block_id
     * 查询厂区关联是否存在
     * @return mixed
     */
    public function existsData($user_id, $factory_id, $block_id)
    {
        return DB::table($this->table)
            ->where('user_id', $user_id)
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
     * @param $user_id
     * @param $factory_id
     * @param $block_id
     * 根据人员查询仓号关联是否存在
     * @return mixed
     */
    public function getFactoryByUserId($user_id, $factory_id, $block_id)
    {
        $result =  DB::table($this->table)
            ->select(DB::raw('id'))
            ->where('user_id', $user_id)
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
        if($old_factory_id == $factory_id && $old_block_id == $block_id)
        {
            $return = ['code'=>20000,'msg'=>'数据没有改变', 'data'=>[]];
        }
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
     * @param $block_id
     * @return mixed
     */
    public function delUserFactory($user_id, $factory_id, $block_id)
    {
        $exist_id = $this->getFactoryByUserId($user_id, $factory_id, $block_id);
        $return = ['code'=>40004,'msg'=>'删除失败', 'data'=>['仓号已经存在']];
        if($exist_id)
        {
            try{
//                $updateArray = [
//                    'data_status' => self::INVALID,
//                    'updated_time' => time(),
//                ];
//                $id = DB::table($this->table)
//                    ->where('id', $exist_id)
//                    ->where('data_status', self::NORMAL)
//                    ->update($updateArray);
//                if($id){
//                    $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
//                }else
//                    DB::rollBack();
                DB::table($this->table)->where('id', $exist_id)->delete();
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
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

    /**
     * @param $user_id
     * @param $firm_id
     * 根据用户ID获取所有的厂区信息
     * @return mixed
     */
    public function getUserFactory($user_id, $firm_id)
    {
        return $results =  DB::table('dove_user_factory as user_factory')
            ->select(DB::raw('user_factory.id as id, factory.id as factory_id, factory.name as factory_name'))
            ->leftJoin('dove_factory as factory', 'user_factory.factory_id','=', 'factory.id')
            ->where('user_factory.user_id', $user_id)
            ->where('factory.firm_id', $firm_id)
            ->groupBy('factory.id')
            ->get();
    }

    /**
     * @param $user_id
     * @param $factory_id
     * @param $firm_id
     * 根据用户ID获取所有的仓号类型
     * @return mixed
     */
    public function getUserBlockType($user_id)
    {
        return $results =  DB::table('dove_user_factory as user_factory')
            ->select(DB::raw('block.block_type as block_type, block.type_name as type_name'))
            ->leftJoin('dove_block as block', 'user_factory.block_id','=', 'block.id')
            ->where('user_factory.user_id', $user_id)
            ->groupBy('block.block_type')
            ->get();
    }
    /**
     * @param $user_id
     * @param $type_id
     * @param $factory_id
     * @param $firm_id
     * 根据用户ID获取所有的仓号ID
     * @return mixed
     */
    public function getUserBlocks($user_id, $type_id, $factory_id, $firm_id)
    {
        return $results =  DB::table('dove_user_factory as user_factory')
            ->select(DB::raw('block.id as block_id, block.name as block_name'))
            ->leftJoin('dove_block as block', 'user_factory.block_id','=', 'block.id')
            ->leftJoin('dove_factory as factory', 'user_factory.factory_id','=', 'factory.id')
            ->where('user_factory.user_id', $user_id)
            ->where('block.block_type', $type_id)
            ->where('factory.firm_id', $firm_id)
            ->where('factory.id', $factory_id)
            ->get();
    }

    /**
     * @param $user_id
     * @param $firm_id
     * 链表查询人员关联所有数据
     * @return mixed
     */
    public function getUserData($user_id, $firm_id)
    {
        return $results =  DB::table('dove_user_factory as user_factory')
            ->select(DB::raw('user_factory.id as id, user_id, block.id as block_id, block.name as block_name, block.block_type as block_type, block.type_name as type_name, factory.id as factory_id, factory.name as factory_name'))
            ->leftJoin('dove_block as block', 'user_factory.block_id','=', 'block.id')
            ->leftJoin('dove_factory as factory', 'user_factory.factory_id','=', 'factory.id')
            ->where('user_factory.user_id', $user_id)
            ->where('factory.firm_id', $firm_id)
            ->get();
    }
}
