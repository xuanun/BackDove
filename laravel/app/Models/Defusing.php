<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Defusing extends Model
{
    protected $table = "dove_defusing";
    const INVALID = 0;
    const NORMAL = 1;
    /**
     * 查询今日是否已经存在数据
     * @param $factory_id
     * @param $date_time
     * @param $category_id
     * @return mixed
     */
    public function getDataExists($factory_id, $date_time, $category_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('defusing_id'))
            ->where('factory_id', $factory_id)
            ->where('record_time', $date_time)
            ->where('type_id', $category_id)
            ->first();
        return isset($results->defusing_id) ?  $results->defusing_id : 0;
    }


    /**
     * @param $id
     * @param $factory_id
     * @param $user_id
     * @param $date_time
     * @param $category_id
     * @param $death_amount
     * @param $handle_number
     * @param $submit_remarks
     * 记录已经存在 更新记录
     * @return mixed
     */
    public function updateData($id, $factory_id, $user_id, $date_time, $category_id, $death_amount, $handle_number, $submit_remarks)
    {
        try{
            $UpdateArray = [
                'factory_id' =>$factory_id,
                'submit_uid' => $user_id,
                'record_time'=> $date_time,
                'type_id'=> $category_id,
                'death_number' => $death_amount,
                'handle_number'=> $handle_number,
                'submit_remarks'=> $submit_remarks,
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('defusing_id', $id)
                ->where('type', self::NORMAL)
                ->update($UpdateArray);
            if($id)
                $return = ['code'=>20000,'msg'=>'请求成功', 'data'=>[]];
            else
                $return = ['code'=>40400,'msg'=>'数据已经审核', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $factory_id
     * @param $user_id
     * @param $date_time
     * @param $category_id
     * @param $death_amount
     * @param $handle_number
     * @param $submit_remarks
     * 新增数据
     * @return mixed
     */
    public function addData($factory_id, $user_id, $date_time, $category_id, $death_amount, $handle_number, $submit_remarks)
    {
        try{
            $insertArray = [
                'factory_id' => $factory_id,
                'submit_uid' => $user_id,
                'record_time' => $date_time,
                'type_id'=> $category_id,
                'death_number'=> $death_amount,
                'handle_number'=> $handle_number,
                'submit_remarks'=> $submit_remarks,
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if($id){
                $return = ['code'=>20000,'msg'=>'录入成功', 'data'=>[]];
            }
            else
                $return = ['code'=>40400,'msg'=>'新增失败', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
    /**
     * @param $page_size
     * @param $factory_id
     * @param $date_time
     * @param $firm_id
     * 查询列表
     * @return mixed
     */
    public function getList($page_size, $factory_id, $date_time, $firm_id)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('defusing_id as id, type.death, dove_defusing.factory_id, factory.name as factory_name, dove_defusing.type_id, death_number, handle_number, record_time, submit_uid, submit_remarks, type,
            examine_uid, examine_remarks, examine_time'))
            ->leftJoin('dove_defusing_type as type', 'type.type_id', '=', 'dove_defusing.type_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_defusing.factory_id')
            ->where('factory.firm_id', $firm_id);
        if($factory_id)
            $results = $results->where('dove_defusing.factory_id', $factory_id);
        if($date_time)
            $results = $results->where('record_time',$date_time);
        $results = $results
            ->orderBy('record_time','desc')
            ->orderBy('defusing_id','desc')
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        foreach($results as $v){
            $data['list'][] = $v;
        }
        return  $data;

    }
    /**
     * @param $id
     * 删除数据
     * @return mixed
     */
    public function delData($id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('defusing_id', $id)
                ->delete();
            if($id){
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
            }
            else{
                DB::rollBack();
                $return = ['code'=>40400,'msg'=>'删除失败', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }

    /**
     * @param $id
     * @param $type
     * @param $user_id
     * @param $examine_remarks
     * 无害化审核
     * @return mixed
     */
    public function checkData($id, $type, $user_id, $examine_remarks)
    {
        try{
            $UpdateArray = [
                'type' => $type,
                'examine_uid' => $user_id,
                'examine_remarks'=> $examine_remarks,
                'examine_time'=> date('Y-m-d', time()),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('defusing_id', $id)
                ->where('type', self::NORMAL)
                ->update($UpdateArray);
            if($id)
                $return = ['code'=>20000,'msg'=>'请求成功', 'data'=>[]];
            else
                $return = ['code'=>40400,'msg'=>'数据已经审核', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }
}
