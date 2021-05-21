<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RuleType extends Model
{
    protected $table = "dove_user_rule_type";

    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $user_name
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $user_name)
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
     * 查询列表-分页
     * @return mixed
     */
    public function getUserList($start_time, $end_time, $factory_id, $user_name, $page_size)
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

        $results = $results
            ->groupBy('punch_id')
            ->orderBy('rule_type.punch_id','desc')
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
}
