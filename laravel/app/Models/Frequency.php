<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Frequency extends Model
{
    protected $table = "dove_frequency";
    const INVALID = 0;
    const NORMAL = 1;

    /**
     * @param $user_id
     * @param $cage_id
     * @param $type_id
     * @param $type_name
     * 新增企业
     * @return mixed
     */
    public function addFrequency($user_id, $cage_id, $type_id, $type_name)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'cage_id' => $cage_id,
                'type_id'=> $type_id,
                'dove_type'=> $type_name,
                'time' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if($id){
                $return = ['code'=>20000,'msg'=>'新增成功', 'firm_id'=>$id];
            }
            else{
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增成功', 'firm_id'=>$id];
            }

        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;

    }
}
