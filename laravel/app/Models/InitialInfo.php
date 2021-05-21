<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InitialInfo extends Model
{
    protected $table = "dove_initial_info";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 查询初始密码
     * @return mixed
     */
    public function getInitialInfo()
    {
        $results = DB::table($this->table)
            ->select(DB::raw('initial_password'))
            ->where('data_status', self::NORMAL)
            ->first();
        return $results->initial_password;
    }

    /**
     * 修改初始密码
     * @param $new_password
     * @return mixed
     */
    public function editPassword($new_password)
    {
        DB::beginTransaction();
        try{
            $updateArray = [
                'initial_password' =>$new_password,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('data_status', self::NORMAL)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }
}
