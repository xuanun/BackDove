<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Roles extends Model
{
    protected $table = "dove_roles";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 查询管理员角色列表基本信息
     * @param $page_size
     * @param $firm_id
     * @return mixed
     */
    public function getRoleUserList($page_size, $firm_id)
    {
        $results =  DB::table('dove_roles as roles')
            ->select(DB::raw('roles.id as id, roles.name as name, roles.role_desc as role_desc, count(role.user_id) as amount'))
            ->leftJoin('dove_role_users as role', 'role.role_id', '=', 'roles.id')
            ->where('roles.data_status', self::NORMAL)
            ->where('roles.firm_id', $firm_id)
            ->groupBy('roles.id')
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
     * 角色列表-删除角色
     * @param $role_id
     * @return mixed
     */
    public function delRole($role_id)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $updateArray = [
                'data_status' =>self::INVALID,
                'updated_at' => time()
            ];
            $id = DB::table($this->table)->where('id', $role_id)->update($updateArray);
            if($id){
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
            }else
            {
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }
    /**
     * 角色列表-新增角色
     * @param $name
     * @param $role_desc
     * @param $firm_id
     * @return mixed
     */
    public function addRole($name, $role_desc, $firm_id)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $insertArray = [
                'name' =>$name,
                'role_desc' => $role_desc,
                'firm_id' => $firm_id,
                'data_status'=> self::NORMAL,
                'updated_at' => time(),
                'created_at' =>time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if($id){
                $return = ['code'=>20000,'msg'=>'新增成功', 'role_id'=>$id];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 角色列表-新增角色
     * @param $name
     * @param $role_desc
     * @param $role_id
     * @param $firm_id
     * @return mixed
     */
    public function editRole($role_id, $name, $role_desc, $firm_id)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $updateArray = [
                'name' =>$name,
                'role_desc' => $role_desc,
                'firm_id' => $firm_id,
                'updated_at' => time(),

            ];
            $id = DB::table($this->table)->where('id', $role_id)->update($updateArray);
            if($id){
                $return = ['code'=>20000,'msg'=>'修改成功', 'role_id'=>$id];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 通过角色ID查询角色是否存在
     * @param $role_id
     * @return mixed
     */
    public function existByRoleId($role_id)
    {
        return  DB::table($this->table)
            ->where('id', $role_id)
            ->where('data_status', self::NORMAL)
            ->exists();
    }

    /**
     * 通过角色名称查询角色是否存在
     * @param $role_name
     * @param $firm_id
     * @return mixed
     */
    public function existByRoleName($role_name, $firm_id)
    {
        return  DB::table($this->table)
            ->where('name', $role_name)
            ->where('data_status', self::NORMAL)
            ->where('firm_id', $firm_id)
            ->exists();
    }

    /**
     * 获取企业全部角色
     * @param $firm_id
     * @return mixed
     */
    public function getAllRoles( $firm_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('id, name'))
            ->where('firm_id', $firm_id)
            ->where('data_status', self::NORMAL)
            ->get();
    }


    /**
     * 平台设置-新增角色
     * @param $name
     * @param $role_desc
     * @param $firm_id
     * @return mixed
     */
    public function addRoleByFirmID($name, $role_desc, $firm_id)
    {
        try{
            $insertArray = [
                'name' =>$name,
                'role_desc' => $role_desc,
                'firm_id' => $firm_id,
                'data_status'=> self::NORMAL,
                'updated_at' => time(),
                'created_at' =>time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if(!$id){
                DB::rollBack();
                return  ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
            }else{
                return ['code'=>20000,'msg'=>'新增成功', 'role_id'=>$id];
            }
        }catch(\Exception $e){
            DB::rollBack();
            return  ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
        }
    }

    /**
     * 获取角色信息
     * @param $role_id
     * @return mixed
     */
    public function getRoleName( $role_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('id as role_id, name as role_name'))
            ->where('id', $role_id)
            ->first();
    }
}
