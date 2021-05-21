<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Permissions extends Model
{
    protected $table = "dove_permissions";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 查询权限路由里有没有 $url_path
     * @param  $url_path
     * @param  $firm_id
     * @return mixed
     */
    public function exitsUrlPath($url_path, $firm_id)
    {
        return DB::table($this->table)
            ->where('url_path', $url_path)
            ->where('firm_id', $firm_id)
            ->where('data_status',self::NORMAL)
            ->exists();
    }

    /**
     * 通过权限ID查询路由
     * @param  $per_ids
     * @param  $firm_id
     * @return mixed
     */
    public function getPermissions($per_ids, $firm_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('url_path'))
            ->whereIn('id', $per_ids)
            ->where('firm_id', $firm_id)
            ->get();
        return !empty($results) ?  $results : [];
    }

    /**
     * 查询全部权限列表
     * @return mixed
     */
    public function getPermissionList()
    {
        return DB::table($this->table)
            ->select(DB::raw('id, , p_id, name'))
            ->where('data_status', self::NORMAL)
            ->get();
    }

    /**
     * 添加权限菜单
     * @param $p_id
     * @param $name
     * @param $url_path
     * @return mixed
     */
    public function addPermission($p_id, $name, $url_path)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $insertArray = [
                'p_id' => $p_id,
                'name' => $name,
                'url_path' => $url_path,
                'data_status' => self::NORMAL,
                'updated_at' => time(),
                'created_at' =>time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if(!$id){
                DB::rollBack();
                $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 删除权限菜单
     * @param $id
     * @return mixed
     */
    public function delPermission($id)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $del_id = DB::table($this->table)
                ->where('id', $id)
                ->where('data_status',self::NORMAL)
                ->delete();
            if($del_id){
                DB::rollBack();
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 修改权限菜单
     * @param $id
     * @param $p_id
     * @param $name
     * @param $url_path
     * @return mixed
     */
    public function editPermission($id, $p_id, $name, $url_path)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $updateArray = [
                'p_id' => $p_id,
                'name' => $name,
                'url_path' => $url_path,
                'updated_at' => time(),
            ];
            $exit_id = DB::table($this->table)->where('id', $id)->update($updateArray);
            if($exit_id){
                DB::rollBack();
                $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[]];
        }
        DB::commit();
        return $return;

    }

    /**
     * 查询权限路由里有没有ID
     * @param  $id
     * @return mixed
     */
    public function exitsId($id)
    {
        return DB::table($this->table)->where('id', $id)->exists();
    }

    /**
     * 通过权限ID查询路由
     * @param  $per_ids
     * @return mixed
     */
    public function getPermissionsInfo($per_ids)
    {
        return DB::table($this->table)
            ->select(DB::raw("id, p_id, name"))
            ->whereIn('id', $per_ids)
            ->get();
    }
    /**
     * 获取全部权限
     * @return mixed
     */
    public function getAllPer()
    {
        return DB::table($this->table)
            ->select(DB::raw("id, p_id, name"))
            ->get();
    }
}
