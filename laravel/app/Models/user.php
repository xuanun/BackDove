<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class user extends Model
{
    protected $table = "dove_user";
    /**
     * 获取用户信息
     * @param $user_id
     * @return string
     */
    public function getUserInfoById($user_id)
    {
        $result = DB::table($this->table)->where("user_id",$user_id)->first();
        return $result ? $result : [];
    }
    /**
     * 获取用户信息
     * @param $mobile
     * @return string
     */
    public function getUserInfoByMobile($mobile)
    {
        $result = DB::table($this->table)
            ->where("mobile",$mobile)
            ->first();
        return $result ? $result : [];
    }

    /**
     * @param $user_id
     * @param $token
     * 用户登录. 更新用户信息
     * @return mixed
     */
    public function UserLogin($user_id, $token)
    {
        DB::beginTransaction();
        $exists = $this->existsUserById($user_id);
        if($exists)
        {
            $updateArray = [
                'login_time' => time(),
                'updated_at' =>time(),
            ];
            $user_id = DB::table($this->table)->where('id', $user_id)->update($updateArray);
            if(!$user_id){
                DB::rollBack();
                return  ['code'=>50000,'msg'=>'登录失败', 'data'=>'', 'time'=>time()];
            }
        }
        DB::commit();
        return  ['code'=>200,'msg'=>'登录成功'];
    }
    /**
     * @param $user_id
     * 用户登录. 更新用户信息
     * @return mixed
     */
    public function existsUserById($user_id)
    {
        return DB::table($this->table)
            ->where('id', $user_id)
            ->first();

    }
}
