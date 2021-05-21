<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminUser extends Model
{
    protected $table = "dove_admin_user";
    /**
     * 查询管理员列表基本信息
     * @param $page_limit
     * @return mixed
     */
    public function getAdminUserList($page_limit)
    {
        return DB::table('dove_admin_user as admin')
            ->select(DB::raw('user.id as id, user.avatar as avatar, user.mobile as mobile, roles.name as role_name,
            user.user_name as user_name, admin.data_status as data_status'))
            ->leftJoin('dove_user as user', 'admin.user_id', '=', 'user.id')
            ->leftJoin('dove_role_users as role', 'role.user_id', '=', 'admin.user_id')
            ->leftJoin('dove_roles as roles', 'roles.id', '=', 'role.role_id')
            ->paginate($page_limit);
    }
}
