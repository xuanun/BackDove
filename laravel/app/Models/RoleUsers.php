<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RoleUsers extends Model
{
    protected $table = "dove_role_users";
    const INVALID = 0;
    const NORMAL = 1;

    /**
     * 通过用户ID查询角色ID
     * @param  $user_id
     * @return mixed
     */
    public function getRoleIdByUserId($user_id)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('role_id'))
            ->where('user_id', $user_id)
            ->where('data_status', self::NORMAL)
            ->first();
        return  empty($results->role_id) ? '' : $results->role_id;
    }

    /**
     * 通过用户ID查询角色信息
     * @param  $user_id
     * @return mixed
     */
    public function getRoleInfoByUserId($user_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('role.id, role.name'))
            ->leftJoin('dove_roles as role', 'dove_role_users.role_id','=', 'role.id')
            ->where('dove_role_users.user_id', $user_id)
            ->where('dove_role_users.data_status', self::NORMAL)
            ->first();
    }

    /**
     * 通过角色ID查询是否存在用户ID
     * @param  $role_id
     * @return mixed
     */
    public function existUserByRoleId($role_id)
    {
        return DB::table($this->table)
            ->where('role_id', $role_id)
            ->where('data_status', self::NORMAL)
            ->exists();
    }

    /**
     * 给用户分配角色
     * @param $user_id,
     * @param $role_id,
     * @return mixed
     */
    public function addUserRole($user_id, $role_id)
    {
        $exists = $this->existUserId($user_id);
        $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>['用户角色已经存在']];
        if(!$exists)
        {
            try{
                $insertArray = [
                    'user_id' =>$user_id,
                    'role_id' => $role_id,
                    'data_status' => self::NORMAL,
                    'updated_at' => time(),
                    'created_at' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
                }
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
            }
        }
        return $return;
    }

    /**
     * 查询用户ID是否存在
     * @param  $user_id
     * @return mixed
     */
    public function existUserId($user_id)
    {
        return DB::table($this->table)
            ->where('user_id', $user_id)
            ->where('data_status', self::NORMAL)
            ->exists();
    }

    /**
     * 编辑用户分角色
     * @param $user_id,
     * @param $role_id,
     * @return mixed
     */
    public function editUserRole($user_id, $role_id)
    {
        $exists = $this->existUserId($user_id);
        $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>['用户角色不存在']];
        if($exists)
        {
            try{
                $updateArray = [
                    'user_id' =>$user_id,
                    'role_id' => $role_id,
                    'updated_at' => time(),
                ];
                DB::table($this->table)
                    ->where('user_id', $user_id)
                    ->where('data_status', self::NORMAL)
                    ->update($updateArray);
                $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[]];
            }
        }
        return $return;
    }

    /**
     * 删除用户关联角色
     * @param $user_id,
     * @return mixed
     */
    public function delUserRole($user_id)
    {
        $exists = $this->existUserId($user_id);
        $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>['用户角色不存在']];
        if($exists)
        {
            try{
                $updateArray = [
                    'data_status' => self::INVALID,
                    'updated_at' => time(),
                ];
                $id = DB::table($this->table)
                    ->where('user_id', $user_id)
                    ->where('data_status', self::NORMAL)
                    ->update($updateArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
                }
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[]];
            }
        }
        return $return;
    }
}
