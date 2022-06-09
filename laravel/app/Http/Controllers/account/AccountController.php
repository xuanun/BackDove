<?php
namespace App\Http\Controllers\account;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\Permissions;
use App\Models\RolePermissions;
use App\Models\Roles;
use App\Models\RoleUsers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * 用户登录
     * @param Request $request
     * @return json
     */
    public function login(Request $request)
    {
        $input = $request->all();
        $mobile = $input['mobile'] ? $input['mobile'] : '';
        $password = $input['password'] ? $input['password'] : '';

        if(empty($mobile)) return response()->json(['code'=>40000,'msg'=>'手机号不能为空', 'data'=>[]]);
        if(empty($password)) return response()->json(['code'=>40000,'msg'=>'密码不能为空', 'data'=>[]]);
        $token = Str::random (64);
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        $user = new User();
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            $object = $user->getUserInfoByMobile($mobile);
            $data =  json_decode( json_encode($object),true);
        }
        if(empty($data)) return response()->json(['code'=>40000,'msg'=>'账号不存在', 'data'=>[]]);
        $obj_password = $data['password'];
        $obj_password = decrypt($obj_password);
//        return $obj_password;
        if($password != $obj_password) return response()->json(['code'=>40000,'msg'=>'密码不正确',  'data'=>[]]);
        $return = $user->UserLogin($data['id']);
        if($return['code'] == 20000){
            //判断他所在的企业是否为启用状态
            if($data['firm_id'])
            {
                $model_firm = new Firm();
                $firm_exists = $model_firm->existsFirmById($data['firm_id']);
                if(!$firm_exists) return response()->json(['code'=>40000,'msg'=>'账号没有企业或企业被关闭',  'data'=>[]]);
            }
            $return['data']['user']['id'] = $data['id'];
            $return['data']['user']['token'] = $token;
            $return['data']['user']['user_name'] = $data['user_name'];
            $return['data']['user']['nick_name'] = $data['nick_name'];
            $return['data']['user']['mobile'] = $data['mobile'];
            $return['data']['user']['avatar'] = env('IMAGE_URL').$data['avatar'];
            $return['data']['user']['gander'] = $data['gander'];
            $return['data']['user']['firm_id'] = $data['firm_id'];
            $return['data']['user']['login_time'] = $data['login_time'];
            $return['data']['user']['created_time'] = $data['created_time'];
            $return['data']['user']['updated_time'] = $data['updated_time'];
            $return['time'] = time();
            $user_key = "dove_user".$mobile;
            $old_token = $redis->get($user_key);
            if($old_token)
            {
                $old_cacheKey = "dove_user_login_".$old_token;
                $redis->del($old_cacheKey);
            }
            $redis->set($user_key, $token);
        }
        $redis->set($cacheKey, json_encode($data));
        return response()->json($return);

    }
    /**
     * 用户退出登录
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request)
    {
        $token = $request->header('token');
        if(empty($token)) return response()->json(['code'=>50000,'msg'=>'用户未登录',  'data'=>[]]);
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            return response()->json(['code'=>50000,'msg'=>'你的登录信息已失效',  'data'=>[]]);
        }
        $mobile = $data['mobile'];
        $user_key = "dove_uer".$mobile;
        $redis->del($user_key);
        $redis->del($cacheKey);
        return response()->json(['code'=>20000,'msg'=>'退出登录成功', 'data'=>[]]);
    }

    /**
     * 用户修改密码
     * @param Request $request
     * @return json
     */
    public function editPassword(Request $request)
    {
        $input = $request->all();
        $token = $request->header('token');
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        $model_user = new User();
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }
        else {
            return response()->json(['code'=>40000,'msg'=>'token 已经失效', 'data'=>[]]);
        }
        $old_password = $input['old_password'] ? $input['old_password'] : '';
        $password = $input['password'] ? $input['password'] : '';
        $enterPassword = $input['password1'] ? $input['password1'] : '';
        if(empty($old_password)) return response()->json(['code'=>60000,'msg'=>'原始密码不能为空','data'=>[]]);
        if(empty($password)) return response()->json(['code'=>60000,'msg'=>'新密码不能为空', 'data'=>[]]);
        if(empty($enterPassword)) return response()->json(['code'=>60000,'msg'=>'确认密码不能为空', 'data'=>[]]);
        if($password != $enterPassword) return response()->json(['code'=>40000,'msg'=>'两次密码输入不一致', 'data'=>[]]);
        if($old_password == $password) return response()->json(['code'=>40000,'msg'=>'新密码不能与旧密码一样', 'data'=>[]]);
        if($old_password !=  decrypt($data['password']))
            return response()->json(['code'=>40000,'msg'=>'原密码不正确','data'=>[]]);

        $user_id = $data['id'];
        $e_password = encrypt($password);
        $return_data = $model_user->editUserPassword($user_id, $e_password);
        return response()->json($return_data);
    }


    /**
     * 用户修改头像
     * @param Request $request
     * @return mixed
     */
    public function editAvatar(Request $request)
    {
        $input = $request->all();
        $avatar = isset($input['file_name']) ? $input['file_name'] : '';
        if(empty($avatar)) return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
//       $file_array = explode('.',$avatar);
//        if(count($file_array) != 2) return response()->json(['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[]]);
//        if($file_array[1] == 'jpg' || $file_array[1] == 'jpeg' || $file_array[1] == 'png'|| $file_array[1] == 'gif')
//        {
        $token = $request->header('token');
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        $model_user = new User();
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }
        else {
            return response()->json(['code'=>50000,'msg'=>'token 已经失效', 'data'=>[]]);
        }
        $user_id = $data['id'];
        $return_data = $model_user->editUserAvatar($user_id, $avatar);
        return response()->json($return_data);
//        }else{
//            return ['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[$avatar]];
//        }
    }


    /**
     * 上传头像
     * @param Request $request
     * @return json
     */
    public function uploadAvatar(Request $request)
    {
        if ($request->isMethod('POST')) { //判断文件是否是 POST的方式上传
            $tmp = $request->file('file');
            if(empty($tmp)) return response()->json(['code'=>40000,'msg'=>'文件流不存在', 'data'=>[]]);
            if ($tmp->isValid()) { //判断文件上传是否有效
                $FileType = $tmp->getClientOriginalExtension(); //获取文件后缀

                $FilePath = $tmp->getRealPath(); //获取文件临时存放位置

                $FileName = date('Ymd') . uniqid() . '.' . $FileType; //定义文件名

                Storage::disk('avatar')->put($FileName, file_get_contents($FilePath)); //存储文件
                $IMAGE_URL = env('IMAGE_URL');
                $AVATAR_URL= env('AVATAR_URL');
                $obj['url'] = $IMAGE_URL.$AVATAR_URL. $FileName;
                $data['code'] = 20000;
                $data['data'] = $obj;
                $data['file_name'] = $AVATAR_URL.$FileName;
                $data['msg'] = "";
                $data['time'] = time();
                return response()->json($data);
            }
        }
    }

    /**
     * 通过企业ID查询企业信息  如果 ID==0 则为超级管理员
     * @param Request $request
     * @return json
     */
    public function firmInfo(Request $request)
    {
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $model_firm = new Firm();
        if($firm_id === 0 || $firm_id === '0') {
            $firm_data = $model_firm->getFirmList();
        } else{
            $firm_data = $model_firm->getFirmInfo($firm_id);
        }
        return response()->json(['code'=>20000,'msg'=>"请求成功",  'data'=>$firm_data]);
    }

    /**
     * 判断权限显示那些菜单
     * @param Request $request
     * @return mixed
     */
    public function checkUserMenu(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : []; //用户ID
        if(empty($user_id))  return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        //超级账号
        //if($user_id == 1)  return response()->json(['code'=>20000, 'msg'=>'超级账号', 'data'=>[]]);
        $model_permissions = new Permissions();

        if($user_id == 1)
        {
            $p_menu = $model_permissions->getAllPerById();
            $per_info = $p_menu;
        }else{
            //获取角色ID
            $model_role_users = new RoleUsers();
            $role_id = $model_role_users->getRoleIdByUserId($user_id);

            $model_role = new Roles();
            $role_info = $model_role->getRoleName($role_id);
            //获取主菜单
            $p_id = 0;

            $ids = $model_permissions->getPerIdByPid($p_id);

            $p_menu = $model_permissions->getPermissionsInfo($ids);

            //获取角色权限菜单
            $model_role_permissions = new RolePermissions();
            $per_info = $model_role_permissions->getPerInfo($role_id);
        }
        $return_data = array();
        foreach ($per_info as $v){
            foreach ($p_menu as $value){
                if($v->p_id == $value->id)
                {
                    $return_data[$value->id]['id'] = $value->id;
                    $return_data[$value->id]['name'] = $value->name;
                    $return_data[$value->id]['data'][] = $v;
                }
            }
        }
        $return_data = array_values($return_data);
        return response()->json(['code'=>20000, 'msg'=>'', 'data'=>$return_data]);
    }


    //测试接口
    public function test()
    {
        return response()->json(['code'=>20000,'msg'=>env('VERIFY_TOKEN').'****这是一个测试接口',  'data'=>[]]);
    }
}
