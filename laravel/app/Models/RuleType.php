<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RuleType extends Model
{
    const INVALID = 0;
    const NORMAL = 1;
    protected $table = "dove_user_rule_type";

    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $user_name
     * @param $start_status
     * @param $end_status
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $user_name, $start_status, $end_status)
    {
        $results =  DB::table('dove_user_rule_type as rule_type')
            ->select(DB::raw('rule_type.uid as uid, rule_type.punch_id, rule_type.morn_start, rule_type.noon_start, rule_type.morn_end, rule_type.noon_end, rule_type.morn_start_type,  rule_type.noon_start_type, factory.name as factory_name, user.user_name, rule_type.date, roles.name as role_name'))
            ->leftJoin('dove_user_factory as user_factory', 'user_factory.user_id', '=', 'rule_type.uid')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'user_factory.factory_id')
            ->leftJoin('dove_role_users as user_roles', 'user_roles.user_id', '=', 'rule_type.uid')
            ->leftJoin('dove_roles as roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('dove_user as user', 'user.id', '=', 'rule_type.uid');

        if($start_time && $end_time){
            $results = $results->whereBetween('rule_type.date', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->whereBetween('rule_type.date', [$start_time, $end_time]);
        }elseif (empty($start_time) && $end_time)
        {
            $start_time = $end_time;
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->where('rule_type.date', '=',[$start_time, $end_time]);
        }
        if($factory_id)
            $results = $results->where('factory.id', $factory_id);
        if($user_name)
            $results = $results->where('user.user_name', 'like','%'.$user_name."%");

        if($start_status)
        {
            $status = '';
            if($start_status == 2)
                $status = '迟到';
            if($start_status == 3)
                $status = '正常';
            $results = $results->where(function ($query) use ($status){
                $query->where('rule_type.morn_start_type', $status)->orWhere('rule_type.noon_start_type', $status);
            });
            //$results = $results->where('rule_type.morn_start_type', $status)->orWhere('rule_type.noon_start_type', $status);
        }
        if($end_status)
        {
            $status = '';
            if($end_status == 2)
                $status = '早退';
            if($end_status == 3)
                $status = '正常';
            $results = $results->where(function ($query) use ($status){
                $query->where('rule_type.morn_end_type', $status)->orWhere('rule_type.noon_end_type', $status);
            });
            //$results = $results->where('rule_type.morn_end_type', $status)->orWhere('rule_type.noon_end_type', $status);
        }
        $results = $results
            ->groupBy('punch_id')
            ->orderBy('rule_type.punch_id','desc')
            ->get();

        return  $results;
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $user_name
     * @param $page_size
     * @param $start_status
     * @param $end_status
     * 查询列表-分页
     * @return mixed
     */
    public function getUserList($start_time, $end_time, $factory_id, $user_name, $page_size, $start_status, $end_status)
    {
        $results =  DB::table('dove_user_rule_type as rule_type')
            ->select(DB::raw('rule_type.uid as uid, rule_type.punch_id, rule_type.morn_start, rule_type.morn_start_type, rule_type.morn_end, morn_end_type, rule_type.noon_start, noon_start_type,  rule_type.noon_end, noon_end_type,  factory.name as factory_name, user.user_name, rule_type.date, roles.name as role_name'))
            ->leftJoin('dove_user_factory as user_factory', 'user_factory.user_id', '=', 'rule_type.uid')
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'user_factory.factory_id')
            ->leftJoin('dove_role_users as user_roles', 'user_roles.user_id', '=', 'rule_type.uid')
            ->leftJoin('dove_roles as roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('dove_user as user', 'user.id', '=', 'rule_type.uid');
        if($start_time && $end_time){
            $results = $results->whereBetween('rule_type.date', [$start_time, $end_time]);
        }elseif($start_time && empty($end_time))
        {
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->whereBetween('rule_type.date', [$start_time, $end_time]);
        }elseif (empty($start_time) && $end_time)
        {
            $start_time = $end_time;
            $end_time = date('Y-m-d', strtotime('+1 day', strtotime($start_time)));
            $results = $results->where('rule_type.date', '=',[$start_time, $end_time]);
        }
        if($factory_id)
            $results = $results->where('factory.id', $factory_id);
        if($user_name)
            $results = $results->where('user.user_name', 'like','%'.$user_name."%");
        if($start_status)
        {
            $status = '';
            if($start_status == 2)
                $status = '迟到';
            if($start_status == 3)
                $status = '正常';
            $results = $results->where(function ($query) use ($status){
                $query->where('rule_type.morn_start_type', $status)->orWhere('rule_type.noon_start_type', $status);
            });
            //$results = $results->where('rule_type.morn_start_type', $status)->orWhere('rule_type.noon_start_type', $status);
        }
        if($end_status)
        {
            $status = '';
            if($end_status == 2)
                $status = '早退';
            if($end_status == 3)
                $status = '正常';
            $results = $results->where(function ($query) use ($status){
                $query->where('rule_type.morn_end_type', $status)->orWhere('rule_type.noon_end_type', $status);
            });
            //$results = $results->where('rule_type.morn_end_type', $status)->orWhere('rule_type.noon_end_type', $status);
        }
        $results = $results
            ->groupBy('punch_id')
            ->orderBy('rule_type.punch_id','desc');
        if($page_size)
        {
            $results = $results->paginate($page_size);
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
        }else{
            $results = $results->get();
            $data['list'] = [];
            foreach($results as $v){
                $data['list'][] = $v;
            }
            return  $data;
        }
    }

    /**
     * @param $firm_id
     * @param $data_time
     * 查询企业下人员总数
     * @return mixed
     */
    public function getAmount($firm_id, $data_time)
    {
        return $result = DB::table('dove_user_rule_type as rule_type')
            ->select(DB::raw('count(rule_type.punch_id) as amount'))
            ->leftJoin('dove_user as user', 'user.id', '=', 'rule_type.uid')
            ->where("user.firm_id",$firm_id)
            ->where('user.data_status', self::NORMAL)
            ->where('user.is_del', self::INVALID)
            ->where('rule_type.date', $data_time)
            ->first();
    }

}
