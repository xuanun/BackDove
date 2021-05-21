<?php


namespace App\Http\Middleware;
use App\Models\RolePermissions;
use App\Models\RoleUsers;
use App\Models\Permissions;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Cookie;
use Illuminate\Support\Facades\Redis;
use Redirect;

class AuthPermissions extends Middleware
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
        //获取参数
        $input = $request->all();
        $token = $request->header('token');
        $firm_id = $request->header('firmId');
        if(empty($token) || empty($firm_id))  return response()->json(['code' => 60000, 'msg' => '缺少参数', 'data' => []]);
        $url_path = $request->path();

        //判断路由是否设置路由
        $model_permissions = new Permissions();
        $exits_path = $model_permissions->exitsUrlPath($url_path, $firm_id);
        if ($exits_path)
        {
            $redis = Redis::connection('default');
            $cacheKey = "dove_user_login_".$token;
            $cacheValue = $redis->get($cacheKey);
            if(!empty($cacheValue))
                $data = json_decode($cacheValue, true);
            else
                return response()->json(['code' => 50000, 'msg' => '登录信息已失效', 'data' => []]);
            //获取角色ID
            $model_role_users = new RoleUsers();
            $role_id = $model_role_users->getRoleIdByUserId($data['id']);
            if (empty($role_id)) return response()->json(['code' => 30000, 'msg' => '没有权限访问', 'data' => ['角色ID不存在']]);

            //获取角色权限ID
            $model_role_permissions = new RolePermissions();
            $per_ids = $model_role_permissions->getPerIdByRoleId($role_id);
            if (empty($per_ids)) return response()->json(['code' => 30000, 'msg' => '没有权限访问', 'data' => ['权限ID不存在']]);

            //获取权限路由
            $model_permissions = new Permissions();
            $per_paths = $model_permissions->getPermissions($per_ids, $firm_id);
            $per_array = array();
            foreach ($per_paths as $v)
            {
                $per_array[] = $v->url_path;
            }
            if (!in_array($url_path, $per_array)) return response()->json(['code' => 30000, 'msg' => '没有权限访问', 'data' => ['你没有权限访问']]);
            return $next($request);
        }
        return $next($request);
    }
}
