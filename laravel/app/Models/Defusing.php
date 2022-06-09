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
                'type'=> 1,
                'death_number' => $death_amount,
                'handle_number'=> $handle_number,
                'submit_remarks'=> $submit_remarks,
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('defusing_id', $id)
                ->where('type', '!=', 2)
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
            ->select(DB::raw('defusing_id as id, type.alias as death, dove_defusing.factory_id, factory.name as factory_name, dove_defusing.type_id, death_number, handle_number, record_time, submit_uid, submit_remarks, type, examine_uid, examine_remarks, examine_time'))
            ->leftJoin('dove_defusing_type as type', 'type.type_id', '=', 'dove_defusing.type_id')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_defusing.factory_id')
            ->where('factory.firm_id', $firm_id);
        if($factory_id)
            $results = $results->where('dove_defusing.factory_id', $factory_id);
        if($date_time)
            $results = $results->where('record_time',$date_time);
        $results = $results
            //->orderBy('record_time','desc')
            ->orderBy('defusing_id','desc');
        if($page_size)
        {
            $results = $results->paginate($page_size);
            $data = [
                'total'=> ceil($results->total() / 5),
                'currentPage'=>$results->currentPage(),
                'pageSize'=>$page_size / 5,
                'list'=>[]
            ];

            foreach($results as $v){
                $data['list'][] = $v;
            }

            $return_data = array();
            $j = 0;
            $array = array();
            for($i=0; $i<count($data['list']); $i++) {
               //return $data['list'][$i]->id;
                $array['factory_id'] = $data['list'][$i]->factory_id;//厂区ID
                $array['factory_name'] = $data['list'][$i]->factory_name;//厂区名字
                $array['record_time'] = $data['list'][$i]->record_time;//提交日期
                $array['submit_uid'] = $data['list'][$i]->submit_uid;//提交用户ID
                $array['type'] = $data['list'][$i]->type;//审核状态  1 ：提交审核中 2 ：通过， 3：拒绝
                $array['examine_uid'] = $data['list'][$i]->examine_uid;//审核人ID
                $array['examine_remarks'] = $data['list'][$i]->examine_remarks;//审核意见
                $array['examine_time'] = $data['list'][$i]->examine_time;//审核时间
                if ($data['list'][$i]->type_id == 1)
                {
                    $array ['p_id'] = $data['list'][$i]->id;//ID
                    $array ['p_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['p_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['p_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['p_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['p_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 2)
                {
                    $array ['e_id'] = $data['list'][$i]->id;//ID
                    $array ['e_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['e_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['e_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['e_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['e_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 3)
                {
                    $array ['s_id'] = $data['list'][$i]->id;//ID
                    $array ['s_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['s_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['s_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['s_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['s_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 4)
                {
                    $array ['c_id'] = $data['list'][$i]->id;//ID
                    $array ['c_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['c_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['c_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['c_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['c_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 5)
                {
                    $array ['y_id'] = $data['list'][$i]->id;//ID
                    $array ['y_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['y_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['y_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['y_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['y_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if( $i !=0 && ($i+1) % 5 == 0)
                {
                    $return_data[] = $array;
                    $array = array();
                    $j = 0;
                }
                else
                    $j++;
            }
            $data['list'] = $return_data;
            return  $data;
        }else{
            $results = $results->get();
            $data['list'] = [];
            foreach($results as $v){
                $data['list'][] = $v;
            }
            $return_data = array();
            $j = 0;
            $array = array();
            for($i=0; $i<count($data['list']); $i++) {
                //return $data['list'][$i]->id;
                $array['factory_id'] = $data['list'][$i]->factory_id;//厂区ID
                $array['factory_name'] = $data['list'][$i]->factory_name;//厂区名字
                $array['record_time'] = $data['list'][$i]->record_time;//提交日期
                $array['submit_uid'] = $data['list'][$i]->submit_uid;//提交用户ID
                $array['type'] = $data['list'][$i]->type;//审核状态  1 ：提交审核中 2 ：通过， 3：拒绝
                $array['examine_uid'] = $data['list'][$i]->examine_uid;//审核人ID
                $array['examine_remarks'] = $data['list'][$i]->examine_remarks;//审核意见
                $array['examine_time'] = $data['list'][$i]->examine_time;//审核时间
                if ($data['list'][$i]->type_id == 1)
                {
                    $array ['p_id'] = $data['list'][$i]->id;//ID
                    $array ['p_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['p_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['p_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['p_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['p_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 2)
                {
                    $array ['e_id'] = $data['list'][$i]->id;//ID
                    $array ['e_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['e_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['e_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['e_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['e_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 3)
                {
                    $array ['s_id'] = $data['list'][$i]->id;//ID
                    $array ['s_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['s_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['s_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['s_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['s_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 4)
                {
                    $array ['c_id'] = $data['list'][$i]->id;//ID
                    $array ['c_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['c_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['c_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['c_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['c_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if ($data['list'][$i]->type_id == 5)
                {
                    $array ['y_id'] = $data['list'][$i]->id;//ID
                    $array ['y_death'] = $data['list'][$i]->death;//鸽子类型名字
                    $array ['y_type_id'] = $data['list'][$i]->type_id;//类型ID
                    $array ['y_death_number'] = $data['list'][$i]->death_number;//死亡数量
                    $array ['y_handle_number'] = $data['list'][$i]->handle_number;//处理数量
                    $array ['y_submit_remarks'] = $data['list'][$i]->submit_remarks;//备注
                }
                if( $i !=0 && ($i+1) % 5 == 0)
                {
                    $return_data[] = $array;
                    $array = array();
                    $j = 0;
                }
                else
                    $j++;
            }
            $data['list'] = $return_data;
            return  $data;
        }
    }
    /**
     * @param $ids
     * 删除数据
     * @return mixed
     */
    public function delData($ids)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->whereIn('defusing_id', $ids)
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
