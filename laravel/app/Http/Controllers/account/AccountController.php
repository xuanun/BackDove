<?php
namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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

        if(empty($mobile)) return response()->json(['code'=>50000,'msg'=>'手机号不能为空']);
        if(empty($password)) return response()->json(['code'=>50000,'msg'=>'密码不能为空']);
        $params = [
            'mobile'=>$mobile,
            'password'=>$password,
        ];
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
        if(empty($data)) return response()->json(['code'=>50000,'msg'=>'账号不存在']);
        $obj_password = $data['password'];
        $obj_password = decrypt($obj_password);
        if($password != $obj_password) return response()->json(['code'=>50000,'msg'=>'密码不正确']);
        $return = $user->UserLogin($data['id'], $token);
        if($return['code'] == 200){
            $return['data']['user']['id'] = $data['id'];
            $return['data']['user']['token'] = $token;
            $return['data']['user']['user_name'] = $data['user_name'];
            $return['data']['user']['nick_name'] = $data['nick_name'];
            $return['data']['user']['mobile'] = $data['mobile'];
            $return['data']['user']['gander'] = $data['gander'];
            $return['data']['user']['login_time'] = $data['login_time'];
            $return['data']['user']['created_at'] = $data['created_at'];
            $return['data']['user']['updated_at'] = $data['updated_at'];
            $return['time'] = time();
            $user_key = "dove_uer".$mobile;
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
    //测试接口
    public function test()
    {
       return response()->json(['status_code'=>2200,'message'=>env('VERIFY_TOKEN')]);
    }
}
