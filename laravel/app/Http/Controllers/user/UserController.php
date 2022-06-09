<?php


namespace App\Http\Controllers\user;


use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Cage;
use App\Models\Factory;
use App\Models\InitialInfo;
use App\Models\Roles;
use App\Models\RoleUsers;
use App\Models\User;
use App\Models\UserFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * 人员管理--人员列表
     * @param Request $request
     * @return mixed
    */
    public function userList(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page =  isset($input['page']) ? $input['page'] : 10;
        //$firm_id = $request->header('firmId');
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误,缺少参数,企业ID', 'data'=>['']]);

        $model_user = new User();
        //$user_list = $model_user->getUserList($page_size, $firm_id);
        $user_list = $model_user->getAllUserList($page_size, $firm_id);
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$user_list];
        return response()->json($return_data);
    }


    /**
     * 人员管理--人员列表-筛选
     * @param Request $request
     * @return mixed
     */
    public function getUsers(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';

        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page =  isset($input['page']) ? $input['page'] : 10;
        //$firm_id = $request->header('firmId');
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误,缺少参数,企业ID', 'data'=>['']]);

        $model_user = new user();
        $user_list = $model_user->getUsers($factory_id, $block_type, $block_id, $role_id, $user_name, $page_size, $firm_id);
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$user_list];
        return response()->json($return_data);
    }

    /**
     * 人员管理--添加人员--获取所有厂区
     * @param Request $request
     * @return mixed
     */
    public function getAllFactory(Request $request)
    {
        //获取参数 校验参数
        //$firm_id = $request->header('firmId');
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数,企业ID']]);
        $model_factory = new Factory();
        $factory_data = $model_factory->getAllFactoryInfo($firm_id);
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$factory_data];
        return response()->json($return_data);
    }

    /**
     * 人员管理--添加人员--获取所有角色
     * @param Request $request
     * @return mixed
     */
    public function getAllRoles(Request $request)
    {
        //获取参数 校验参数
        //$firm_id = $request->header('firmId');
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数,企业ID']]);
        $model_roles = new Roles();
        $roles_data = $model_roles->getAllRoles($firm_id);
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$roles_data];
        return response()->json($return_data);
    }

    /**
     * 人员管理--获取所有仓号类型
     * @param Request $request
     * @return mixed
     */
    public function getBlockType(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        //$firm_id = $request->header('firmId');
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if( empty($factory_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数工厂ID']]);

        //获取未分配的所有仓号
        $model_user_factory = new UserFactory();
        $block_ids = $model_user_factory->getIds($factory_id, $role_id);
        $ids = array();
        foreach ($block_ids as $v){
            $ids[] = $v->block_id;
        }
        $model_block = new Block();
        $roles_data = $model_block->getBlockType($factory_id, $ids);

        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$roles_data];
        return response()->json($return_data);
    }

    /**
     * 人员管理--获取所有仓号
     * @param Request $request
     * @return mixed
     */
    public function getBlockIds(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        //$firm_id = $request->header('firmId');
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if( empty($factory_id) || empty($role_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数工厂ID']]);

        //获取未分配的所有仓号
        $model_user_factory = new UserFactory();
        $block_ids = $model_user_factory->getIds($factory_id, $role_id);
        $ids = array();
        foreach ($block_ids as $v){
            $ids[] = $v->block_id;
        }
        $model_block = new Block();
        $roles_data = $model_block->getBlock($factory_id, $ids);
        $array = array();
        foreach ($roles_data as $v)
        {
            $array[$v->block_type]['type_name'] = $v->type_name;
            $array[$v->block_type]['block_type'] = $v->block_type;
            $block['id'] = $v->id;
            $block['name'] = $v->name;
            $block['list']['count'] = $this->getCage($v->id);;
            $array[$v->block_type][$v->id][] = $block;
        }
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$array];
        return response()->json($return_data);
    }

    /**
     * 人员管理--获取仓号下鸽笼个数
     * @param $block_id
     * @return mixed
     */
    public function getCage($block_id)
    {
        //人员管理--获取仓号下鸽笼个数
        $model_user_factory = new Cage();
        return $model_user_factory->getCageNum($block_id);
    }

    /**
     * 人员管理--获取仓号下全部鸽笼
     * @param Request $request
     * @return mixed
     */
    public function getAllCages(Request $request)
    {
        //获取仓号下所有鸽笼ID
        $input = $request->all();
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';
        $model_user_factory = new Cage();
        $return =  $model_user_factory->getAllCages($block_id);
        return  response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$return]);
    }

    /**
     * 人员管理--获取所有仓号以及全部鸽笼
     * @param Request $request
     * @return mixed
     */
    public function getAllBlocks(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0;
        //if( empty($factory_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数工厂ID']]);

        $model_block = new Block();
        $roles_data = $model_block->getAllBlock($factory_id);
        $model_user_factory = new Cage();
        $array = array();
        foreach ($roles_data as $v)
        {
            $array[$v->block_type]['type_name'] = $v->type_name;
            $array[$v->block_type]['block_type'] = $v->block_type;
            $block['id'] = $v->id;
            $block['name'] = $v->name;
            $block['list'] = $model_user_factory->getAllCages($v->id);
            $array[$v->block_type][$v->id][] = $block;
        }
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$array];
        return response()->json($return_data);
    }

    /**
     * 人员管理--添加人员
     * @param Request $request
     * @return mixed
     */
    public function addUser(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $rsg_time = isset($input['rsg_time']) ? date("Y-m-d H:i:s", strtotime($input['rsg_time']))
            : date("Y-m-d H:i:s", time());
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';
        $mobile = isset($input['mobile']) ? $input['mobile'] : '';
        $block_id = isset($input['block_id']) ? $input['block_id'] : [];
        //$firm_id = $request->header('firmId');
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $data['firm_id'] = $firm_id;
        $data['rsg_time'] = $rsg_time;
        $data['factory_id'] = $factory_id;
        $data['user_name'] = $user_name;
        $data['mobile'] = $mobile;
        if(empty($firm_id) || empty($rsg_time) || empty($role_id) || empty($user_name) || empty($mobile))
            return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>$data]);
//        if(!is_array($block_id))
//            return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>$data]);
        if(!$this->is_mobile($mobile))  return  response()->json(['code'=>40000,'msg'=>'手机号不正确', 'data'=>['']]);
        //开始添加数据
        $model_user = new User();
        $model_user_factory = new UserFactory();
        $model_roles = new RoleUsers();
        $str_initial = '123456';
        $password = encrypt($str_initial);
        //return decrypt($password);
        DB::beginTransaction();
        //新增用户
        $user_data = $model_user->addUser($user_name, $mobile, $password, $rsg_time, $firm_id, 0);
        if($user_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($user_data);
        }
        //新增用户角色信息
        $role_data = $model_roles->addUserRole($user_data['user_id'], $role_id);
        if($role_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($role_data);
        }
        if(count($block_id) == 0)
        {
            //绑定厂区 没有仓号
            $block_data = $model_user_factory->addUserFactory($user_data['user_id'], $firm_id,$factory_id, 0);
        }
        if(is_array($block_id) && count($block_id) > 0)
        {
            //新增用户管理厂区
            for($i = 0; $i < count($block_id); $i++)
            {
                //判断block_id 存不存在
                if(empty($block_id[$i]))
                {
                    DB::rollBack();
                    return response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>$data]);
                }
                $block_data = $model_user_factory->addUserFactory($user_data['user_id'], $firm_id,$factory_id, $block_id[$i]);
                if($block_data['code'] != 20000)
                {
                    DB::rollBack();
                    return response()->json($block_data);
                }
            }
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'添加成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 人员管理--编辑
     * @param Request $request
     * @return mixed
     */
    public function editUser(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0;
        $rsg_time = isset($input['rsg_time']) ? date("Y-m-d H:i:s", strtotime($input['rsg_time']))
            : date("Y-m-d H:i:s", time());
        $old_factory_id = isset($input['old_factory_id']) ? $input['old_factory_id'] : '';
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $old_block_id = isset($input['old_block_id']) ? $input['old_block_id'] : [];
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';
        $mobile = isset($input['mobile']) ? $input['mobile'] : '';
        $block_id = isset($input['block_id']) ? $input['block_id'] : [];
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
//        $firm_id = $request->header('firmId');
        //if(empty($firm_id) || empty($rsg_time) || empty($factory_id) || empty($role_id) || empty($user_name) ||
//            empty($mobile) || empty($user_id))
//            return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数']]);
        if(!$this->is_mobile($mobile))  return  response()->json(['code'=>40000,'msg'=>'手机号不正确', 'data'=>['']]);
        //开始添加数据
        $model_user = new User();
        $model_user_factory = new UserFactory();
        $model_roles = new RoleUsers();
        DB::beginTransaction();
        //编辑用户信息
        $user_data = $model_user->editUser($user_id, $user_name, $mobile, $rsg_time, $firm_id);
        if($user_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($user_data);
        }
        //编辑用户角色信息
        $role_data = $model_roles->editUserRole($user_data['user_id'], $role_id);
        if($role_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($role_data);
        }
        if($old_factory_id != $factory_id)
        {
            //绑定厂区 没有仓号
            $model_user_factory->delUserFactory($user_id, $old_factory_id, 0);
            $model_user_factory->addUserFactory($user_id, $firm_id,$factory_id, 0);
        }
        if(empty($block_id) &&  ($old_factory_id == $factory_id))
        {
            $model_user_factory->addUserFactory($user_id, $firm_id, $factory_id, 0);
        }
        if($block_id &&  ($old_factory_id == $factory_id))
        {
            $model_user_factory->delUserFactory($user_id, $factory_id, 0);
        }
        //修改仓号信息
        //计算增加的仓号ID
        $add_blocks = array_values(array_diff($block_id, $old_block_id));
        foreach ($add_blocks as $v)
        {
            $block_data = $model_user_factory->addUserFactory($user_id, $firm_id,$factory_id, $v);
            if($block_data['code'] != 20000)
            {
                DB::rollBack();
                return response()->json($block_data);
            }
        }
        //计算减少的仓号ID
        $reduce_blocks = array_values(array_diff($old_block_id, $block_id));
        foreach ($reduce_blocks as $v)
        {
            $block_data = $model_user_factory->delUserFactory($user_id, $factory_id, $v);
            if($block_data['code'] != 20000)
            {
                DB::rollBack();
                return response()->json($block_data);
            }
        }
//        $block_data = $model_user_factory->editUserFactory($user_data['user_id'], $old_factory_id,
//            $old_block_id, $factory_id, $block_id);

        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 人员管理--删除
     * @param Request $request
     * @return mixed
     */
    public function delUser(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0;
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';

        //开始删除数据
        $model_user = new User();
        $model_user_factory = new UserFactory();
        $model_roles = new RoleUsers();
        DB::beginTransaction();
        //编辑用户信息
        $user_data = $model_user->delUserInfo($user_id);
        if($user_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($user_data);
        }
        //编辑用户角色信息
        $role_data = $model_roles->delUserRole($user_id);
        if($role_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($role_data);
        }

        //修改仓号信息
        $block_data = $model_user_factory->delUserFactory($user_id, $factory_id, $block_id);
        if($block_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($block_data);
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'删除成功', 'data'=>['']];
        return response()->json($return_data);
    }

    /**
     * 人员管理--修改状态
     * @param Request $request
     * @return mixed
     */
    public function editUserStatus(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0;
        $status = isset($input['data_status']) ? $input['data_status'] : '';

        //开始修改数据
        $model_user = new User();
        $return_data = $model_user->editUserStatus($user_id, $status);
        return response()->json($return_data);
    }

    /**
     * 人员管理--重置密码
     * @param Request $request
     * @return mixed
     */
    public function resetPassword(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0;
        $model_initial = new InitialInfo();
        $str_initial = $model_initial->getInitialInfo(); //'123456';
        $password = encrypt($str_initial);
        //开始重置密码
        $model_user = new User();
        $user_data = $model_user->resetUserPassword($user_id, $password);
        return response()->json($user_data);
    }

    /**
     * 根据人员获取全部厂区仓号鸽笼
     * @param Request $request
     * @return mixed
     */
    public function UserAllBlocks(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($user_id) || empty($firm_id))  return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);

        $model_user_factory = new UserFactory();
        $user_factory = $model_user_factory->getUserFactory($user_id, $firm_id);
        $factory_data = array();
        $model_cage= new Cage();
        foreach ($user_factory as $v)
        {
            $array = array();
            $factory_id = $v->factory_id;
            $factory_name = $v->factory_name;
            $array['factory_id'] = $factory_id;
            $array['factory_name'] = $factory_name;
            $array['type'] = $model_user_factory->getUserBlockType($user_id);
            $exists_type = $model_user_factory->getFactoryByUserId($user_id, $factory_id, 0);
            if($exists_type)
                $array['type'] = [];
            foreach ($array['type'] as $k)
            {
                $k->blocks = $model_user_factory->getUserBlocks($user_id, $k->block_type, $factory_id, $firm_id);
                foreach ($k->blocks as $value)
                {
                    $value->list = $model_cage->getAllCages($value->block_id);
                    $value->amount = $model_cage->getCageNum($value->block_id);
                }
            }
            $factory_data[] = $array;
        }
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$factory_data];
        return response()->json($return_data);
    }

    /**
     * 验证输入的手机号码
     * @param  $user_mobile
     * @return bool
     */
    function is_mobile($user_mobile)
    {
        $chars = "/^((\(\d{2,3}\))|(\d{3}\-))?1(3|4|5|7|8|9)\d{9}$/";
        if (preg_match($chars, $user_mobile)){
            return true;
        }else{
            return false;
        }

    }

}
