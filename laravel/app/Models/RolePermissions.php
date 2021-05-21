<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RolePermissions extends Model
{
    protected $table = "dove_role_permissions";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 通过角色ID查询所有权限
     * @param  $role_id
     * @return mixed
     */
    public function getPerIdByRoleId($role_id)
    {
        $result = DB::table($this->table)
            ->select(DB::raw('permission_id'))
            ->where('role_id', $role_id)
            ->get();
        $per_ids = array();
        foreach ($result as $v)
        {
            $per_ids[] = $v->permission_id;
        }
        return $per_ids;
    }

    /**
     * 给角色ID添加权限
     * @param  $role_id
     * @param  $permissions_id
     * @return mixed
     */
    public function addRolePermissions($role_id, $permissions_id)
    {
        $exists = $this->existsUserById($role_id, $permissions_id);
        if(empty($exists)){
            try{
                $insertArray = [
                    'role_id' =>$role_id,
                    'permission_id' => $permissions_id,
                    'updated_at' => time(),
                    'created_at' =>time(),
                ];
                DB::table($this->table)->insertGetId($insertArray);
                $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
            return $return;
        }
        return ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
    }

    /**
     * 删除指定角色所有权限
     * @param  $role_id
     * @param  $permissions_id
     * @return mixed
     */
    public function delRolePermissions($role_id)
    {
        try{
            $id = DB::table($this->table)
                ->where('role_id', $role_id)
                ->delete();
            $return = ['code'=>20000,'msg'=>'请求成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
    /**
     * @param $role_id
     * @param $permissions_id
     * 查询角色权限是否存在
     * @return mixed
     */
    public function existsUserById($role_id, $permissions_id)
    {
        return DB::table($this->table)
            ->where('role_id', $role_id)
            ->where('permission_id', $permissions_id)
            ->exists();

    }
}
