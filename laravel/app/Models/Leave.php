<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Leave extends Model
{
    protected $table = "dove_leave";
    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $user_name
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $user_name, $page_size)
    {
        $results =  DB::table('dove_leave as leave')
            ->select(DB::raw('factory.name as factory_name, user.user_name, leave.start_time, leave.end_time, type.type_name, leave.reason'))
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'leave.factory_id')
            ->leftJoin('dove_user as user', 'user.id', '=', 'leave.uid')
            ->leftJoin('dove_leave_type as type', 'type.type_id', '=', 'leave.type_id');
        if($start_time && $end_time){
            $results = $results->whereBetween('leave.start_time', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->whereBetween('leave.start_time', [$start_time, $end_time]);
        }elseif (empty($start_time) && $end_time)
        {
            $start_time = $end_time;
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->where('leave.start_time', '=',[$start_time, $end_time]);
        }
        if($factory_id)
            $results = $results->where('leave.factory_id', $factory_id);
        if($user_name)
            $results = $results->where('user.user_name', 'like','%'.$user_name."%");

        $results = $results
            ->orderBy('leave.leave_id','desc')
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
     * @param $user_id
     * @param $factory_id
     * @param $type_id
     * @param $start_time
     * @param $end_time
     * @param $duration
     * @param $reason
     * 药品入库
     * @return mixed
     */
    public function addData($user_id, $factory_id, $type_id, $start_time, $end_time, $duration, $reason)
    {
        try {
            $insertArray = [
                'uid' => $user_id,
                'factory_id' => $factory_id,
                'type_id' => $type_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration' => $duration,
                'reason' => $reason,
                'check_type' => 2,
                'check_time' => '',
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code' => 20000, 'msg' => '新增成功', 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            $return = ['code' => 40000, 'msg' => '新增失败', 'data' => [$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 请假时间
     * @param $user_id
     * @param $start_time
     * @return mixed
     */
    public function getDuration($user_id, $start_time)
    {
        return  DB::table($this->table)
            ->select(DB::raw('leave_id, uid, type_id, start_time, end_time, duration'))
            ->where('uid', $user_id)
            ->where('start_time', '>', $start_time)
            ->get();
    }

    /**
     * 请假时间 查询
     * @param $user_id
     * @param $start_time
     * @return mixed
     */
    public function getDurationState($user_id, $start_time, $end_time)
    {
        return  DB::table($this->table)
            ->select(DB::raw('leave_id, uid, type_id, start_time, end_time, duration'))
            ->where('uid', $user_id)
            ->whereBetween('start_time',  [$start_time, $end_time])
            ->first();
    }
}
