<?php


namespace App\Http\Middleware;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Cookie;
use Illuminate\Support\Facades\Redis;
use Redirect;

class AuthToken extends Middleware
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        // TODO
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //判断是否验证token
        if(env('VERIFY_TOKEN') == false)
        {
            return $next($request);
        }
        // 取得用户的token
        $token = $request->header('token');
        // 如果获取到了token
        if(isset($token)) {
            // 验证token
            $redis = Redis::connection('default');
            $cacheKey = "dove_user_login_".$token;
            $cacheValue = $redis->get($cacheKey);
            if($cacheValue)
            {
                // 往下执行
                return $next($request);
            }
            else{
                return response()->json(['code'=>50000, 'msg'=>'登录信息已经过期，请重新登录']);
            }
        }
        else {
            //如果取不到用户的token，返回错误信息
            return response()->json(['code'=>50000, 'msg'=>'用户未登录，请登录']);
        }
    }

}
