<?php


namespace App\Http\Controllers\power;


use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Permissions;
use App\Models\RolePermissions;
use App\Models\Roles;
use App\Models\RoleUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DoveRolesController extends Controller
{
    /**
     * 权限管理角色列表
     * @param Request $request
     * @return mixed
     */
    public function roleList(Request $request)
    {
        $input = $request->all();
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $firm_id = $request->header('firmId');
        $page = isset($input['page']) ? $input['page'] : 1;
        if(!$firm_id) return response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少企业ID']]);

        $model_Roles = new Roles();
        $data = $model_Roles->getRoleUserList($page_size, $firm_id);
        $list = array();
        if(isset($data))
        {
            $list['code'] = 20000;
            $list['msg'] = '';
            $list['data'] = $data;
        }else
        {
            $list['code'] = 22000;
            $list['msg'] = '';
            $list['data'] = [];
        }
        return  response()->json($list);
    }

    /**
     * 管理员角色列表删除
     * @param Request $request
     * @return mixed
     */
    public function delRole(Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $role_id = isset($input['role_id']) ? $input['role_id'] : 0 ;
        if(empty($role_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);

        //查询是否存在该角色
        $model_Roles = new Roles();
        $exist_role = $model_Roles->existByRoleId($role_id);
        if(empty($exist_role)) return response()->json(['code'=>40000,'msg'=>'该角色不存在', 'data'=>[]]);

        //查询该角色下是否有用户
        $model_role_users = new RoleUsers();
        $exist_user = $model_role_users->existUserByRoleId($role_id);
        if($exist_user)
            return  response()->json(['code'=>40400,'msg'=>'当前角色用户数不为0，删除失败', 'data'=>[]]);

        //满足条件软删除该角色
        $data = $model_Roles->delRole($role_id);
        return  response()->json($data);
    }

    /**
     * 角色列表新增角色
     * @param Request $request
     * @return mixed
     */
    public function addRole(Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $name = isset($input['name']) ? $input['name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id))
        {
            $firm_id = $request->header('firmId');
        }
        $role_desc = isset($input['role_desc']) ? $input['role_desc'] : '';
        if(empty($name)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色名称不能为空', 'data'=>[]]);
        if(empty($role_desc)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色描述不能为空', 'data'=>[]]);

        //查询是否存在该角色名
        $model_Roles = new Roles();
        $exist_role = $model_Roles->existByRoleName($name, $firm_id);
        if($exist_role) return response()->json(['code'=>40000,'msg'=>'角色已经存在', 'data'=>[]]);

        //满足条件添加角色
        $data = $model_Roles->addRole($name, $role_desc, $firm_id);
        return  response()->json($data);
    }

    /**
     * 角色列表修改角色
     * @param Request $request
     * @return mixed
     */
    public function editRole(Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $name = isset($input['name']) ? $input['name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id))
        {
            $firm_id = $request->header('firmId');
        }
        $role_desc = isset($input['role_desc']) ? $input['role_desc'] : '';
        if(empty($name)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色名称不能为空', 'data'=>[]]);
        if(empty($role_desc)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色描述不能为空', 'data'=>[]]);

        //修改角色信息
        $model_Roles = new Roles();
        $data = $model_Roles->editRole($role_id, $name, $role_desc, $firm_id);
        return  response()->json($data);
    }

    /**
     * 角色权限管理-权限菜单列表
     * @param Request $request
     * @return mixed
     */
    public function rolePerMenu( Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $token = $request->header('token');
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        $user_info = json_decode($cacheValue, true);
        $user_id = $user_info['id'];
        $model_role_permissions = new RolePermissions();
        $model_role_user = new RoleUsers();
        $model_permissions = new Permissions();
        if($user_info['firm_id'] != 0)
        {
            //获取角色ID
            $role_id = $model_role_user->getRoleIdByUserId($user_id);
            if (empty($role_id)) return response()->json(['code' => 30000, 'msg' => '没有权限访问', 'data' => ['角色ID不存在']]);
            //获取角色权限
            $per_ids = $model_role_permissions->getPerIdByRoleId($role_id);
            if (empty($per_ids)) return response()->json(['code' => 30000, 'msg' => '没有权限访问', 'data' => ['权限ID不存在']]);
            //获取权限菜单
            $results_data = $model_permissions->getPermissionsInfo($per_ids);
            $results_data = $model_permissions->getAllPer();
        }else{
            $results_data = $model_permissions->getAllPer();
        }
        $p_id_array = array();
        foreach ($results_data as $v)
        {
            $p_id_array[] = $v->p_id;
        }
        $p_id_array = array_values(array_unique($p_id_array));

        $data = array();
        for($i=0; $i<count($p_id_array); $i++)
        {
            $value_id = $p_id_array[$i];
            foreach ($results_data as $v)
            {
                $arr['id'] = $v->id;
                $arr['p_id'] = $v->p_id;
                $arr['name'] = $v->name;
                if($arr['p_id'] == $value_id) {
                    $data[$v->p_id]['list'][] = $arr;
                }
            }
        }
        $array = array();
        if(isset($data[0]['list']))
            $array = $data[0]['list'];
        else
            return response()->json(['code' => 40000, 'msg' => '权限菜单为空', 'data' => []]);
        for($i=0; $i<count($array); $i++)
        {
            //return $data[$array[$i]['id']];
             $array[$i]['list'] = isset($data[$array[$i]['id']]['list']) ? $data[$array[$i]['id']]['list'] : [];
        }
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$array];
        return  response()->json($return_data);
//        foreach ($results_data as $value)
//        {
//            $id = $value->id;
//            $p_id = $value->p_id;
//            if(isset($return_data['data'][$p_id]))
//            {
//                $return_data['data'][$p_id]['list'][] = $value;
//            }else
//            {
//                $return_data['data'][$id]['id'] = $value->id;
//                $return_data['data'][$id]['p_id'] = $value->p_id;
//                $return_data['data'][$id]['name'] = $value->name;
//                $return_data['data'][$id]['list'] = [];
//            }
//
//            if(isset($return_data['data'][$id]) && isset($return_data['data'][$p_id]))
//            {
//                $return_data['data'][$id]['list'][] = $value;
//            }else
//            {
//                $return_data['data'][$id]['id'] = $value->id;
//                $return_data['data'][$id]['p_id'] = $value->p_id;
//                $return_data['data'][$id]['name'] = $value->name;
//                $return_data['data'][$id]['list'] = [];
//            }
//
//        }
    }

    /**
     * 角色权限管理
     * @param Request $request
     * @return mixed
     */
    public function rolePermissions(Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $per_array = isset($input['per_array']) ? $input['per_array'] : [];
        if(empty($role_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色ID不能为空', 'data'=>[]]);
        if(empty($per_array)) return  response()->json(['code'=>60000,'msg'=>'参数错误, 角色权限不能为空', 'data'=>[]]);
        if(!isset($per_array[0])) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);

        //查询是否存在该角色名
        $model_Roles = new Roles();
        $exist_role = $model_Roles->existByRoleId($role_id);
        if(empty($exist_role)) return response()->json(['code'=>40000,'msg'=>'角色ID不存在', 'data'=>[]]);

        //满足条件添加角色权限
        $model_role_permissions = new RolePermissions();
        //开启事务
        DB::beginTransaction();

        $del_data = $model_role_permissions->delRolePermissions($role_id);
        if($del_data['code'] != 20000){
            return  response()->json($del_data);
        }
        for($i=0; $i<count($per_array); $i++)
        {
            $permission_id = $per_array[$i];
            $data = $model_role_permissions->addRolePermissions($role_id, $permission_id);
            if(empty($data) || $data['code'] ==40000 )
            {
                //$return_data = ['code'=>40000,'msg'=>'参数错误, 角色权限添加失败', 'data'=>[]];
                return  response()->json($data);
            }
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'角色权限添加成功', 'data'=>[]];
        return  response()->json($return_data);
    }

    /**
     * 添加权限菜单
     * @param Request $request
     * @return mixed
     */
    public function addPermissionsMenu(Request $request)
    {
        //接收并校验参数
        $input = $request->all();
        $p_id = isset($input['p_id']) ? $input['p_id'] : 0;
        $name = isset($input['name']) ? $input['name'] : '';
        $url_path = isset($input['url_path']) ? $input['url_path'] : 0;
        $firm_id = $request->header('firm_id');

        $model_permissions = new Permissions();
        $exits_path = $model_permissions->exitsUrlPath($url_path);
        if($exits_path) return response()->json(['code'=>40000,'msg'=>'菜单权限已经存在, 添加失败', 'data'=>[]]);
        $return_data = $model_permissions->addPermission($p_id, $name, $url_path);
        return  response()->json($return_data);
    }

    /**
     * 删除权限菜单
     * @param Request $request
     * @return mixed
     */
    public function delPermissionsMenu(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : 0;

        $model_permissions = new Permissions();
        $exits_id = $model_permissions->exitsId($id);
        if(empty($exits_id)) return response()->json(['code'=>40000,'msg'=>'菜单权限不存在存在, 删除失败', 'data'=>[]]);
        $return_data = $model_permissions->delPermission($id);
        return  response()->json($return_data);
    }

    /**
     * 修改权限菜单
     * @param Request $request
     * @return mixed
     */
    public function editPermissionsMenu(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : 0;
        $p_id = isset($input['p_id']) ? $input['p_id'] : 0;
        $name = isset($input['name']) ? $input['name'] : '';
        $url_path = isset($input['url_path']) ? $input['url_path'] : 0;

        $model_permissions = new Permissions();
        $exits_id = $model_permissions->exitsId($id);
        if(empty($exits_id)) return response()->json(['code'=>40000,'msg'=>'菜单权限不存在存在, 修改失败', 'data'=>[]]);

        $return_data = $model_permissions->editPermission($id, $p_id, $name, $url_path);
        return  response()->json($return_data);
    }

    /**
     * 角色权限菜单
     * @param Request $request
     * @return mixed
     */
    public function rolePermissionsMenu(Request $request)
    {
        $input = $request->all();
        $role_id= isset($input['role_id']) ? $input['role_id'] : '';
        if(empty($role_id))
            return  response()->json(['code'=>60000,'msg'=>'参数错误, 缺少角色ID', 'data'=>[]]);

        $model_role_permissions = new RolePermissions();
        $per_data = $model_role_permissions->getPerIdByRoleId($role_id);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$per_data];
        return  response()->json($return_data);
    }
}
