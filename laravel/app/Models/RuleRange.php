<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RuleRange extends Model
{
    protected $table = "dove_user_rule_range";

    /**
     * @param $user_id
     * @param $firm_id
     * @param $longitude
     * @param $latitude
     * @param $address
     * @param $distance
     * 更新数据
     * @return mixed
     */
    public function addData($user_id, $firm_id, $longitude, $latitude, $address, $distance)
    {
        DB::beginTransaction();
        try{
            $range_data = DB::table($this->table)
                ->select(DB::raw('range_id'))
                ->where('firm_id', $firm_id)
                ->where('range_id', '>',0)
                ->first();
            if($range_data){
                $range_id = $range_data->range_id;
                $updateArray = [
                    'firm_id' => $firm_id,
                    'uid' => $user_id,
                    'longitude' => $longitude,
                    'latitude' => $latitude,
                    'address' => $address,
                    'distance' => $distance,
                    'uptime' => time(),
                ];
                DB::table($this->table)
                    ->where('range_id',$range_id)
                    ->update($updateArray);
            }else{
                $insertArray = [
                    'firm_id' => $firm_id,
                    'uid' => $user_id,
                    'longitude' => $longitude,
                    'latitude' => $latitude,
                    'address' => $address,
                    'distance' => $distance,
                    'creatime' => time(),
                    'uptime' => time(),
                ];
                DB::table($this->table)
                ->insert($insertArray);
            }
            $return = ['code'=>20000,'msg'=>'请求成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * 获取考勤打卡地点
     * @param $firm_id
     * @return mixed
     */
    public function getList($firm_id)
    {
        return  DB::table($this->table)
            ->select(DB::raw('range_id, firm_id, longitude, latitude, address, distance, creatime, uptime'))
            ->where('firm_id', $firm_id)
            ->first();
    }
}
