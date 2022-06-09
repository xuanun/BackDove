<?php


namespace App\Http\Controllers\external;
use App\Http\Controllers\Controller;
use App\Models\Anomaly;
use App\Models\Cage;
use App\Models\CageLog;
use App\Models\Factory;
use App\Models\GrainReceive;
use App\Models\RuleType;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ExternalController extends Controller
{
    /**
     * 所有数据
     * @param Request $request
     * @return mixed
     */
    public function allData(Request $request)
    {
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $redis = Redis::connection('default');
        $cacheKey = "dove_external_all_data".$firm_id;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            $data_time = date('Y-m-d', time());
            $clock_data =  $this->clockInData($firm_id, $data_time);
            $stock_data =  $this->stockData($firm_id, $data_time);
            $food_data =  $this->useFoodData($firm_id, $data_time);
            $order_data = $this->orderData($firm_id, $data_time);
            $order_rank = $this->orderRank($firm_id, $data_time);
            $death_rank = $this->deathRank($firm_id, $data_time);
            $disease_rank = $this->diseaseRank($firm_id, $data_time);
            $sale_data = $this->saleData($firm_id, $data_time);
            $sale_proportion_data = $this->saleProportion($firm_id, $data_time);
            $sale_day_data =  $this->sumSale($firm_id, $data_time);
            $user_rank =  $this->userRank($firm_id, $data_time);

            $data['clock_data'] = $clock_data;
            $data['stock_data'] = $stock_data;
            $data['food_data'] = $food_data;
            $data['order_data'] = $order_data;
            $data['order_rank'] = $order_rank;
            $data['death_rank'] = $death_rank;
            $data['disease_rank'] = $disease_rank;
            $data['sale_data'] = $sale_data;
            $data['sale_proportion_data'] = $sale_proportion_data;
            $data['sale_day_data'] = $sale_day_data;
            $data['user_rank'] = $user_rank;
            $redis->set($cacheKey, json_encode($data), 600);
        }
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$data]);
    }

    /**
     * 打卡数据
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function clockInData($firm_id, $data_time)
    {
        //员工人数
        $model_user = new User();
        $user_count = $model_user->getUserAmount($firm_id);
        //今日打卡人数
        $model_user_rule_type = new RuleType();
        $clock_count = $model_user_rule_type->getAmount($firm_id, $data_time);
        //今日打卡的员工人数比例
        $clock_ratio = empty($user_count->amount) ? 0 : round($clock_count->amount * 100  / $user_count->amount, 2) .'%';
        $array['user_count'] = $user_count->amount;
        $array['clock_count'] = $clock_count->amount;
        $array['clock_ratio'] = $clock_ratio;
        return $array;
    }

    /**
     * 存栏统计
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function stockData($firm_id, $data_time)
    {
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        //今日存栏 查询cage表
        $model_cage = new Cage();
        $today_data = $model_cage->getFirmSumAmount($firm_id);
        $today_amount = empty($today_data->sum_amount) ? 0 : $today_data->sum_amount;
        $model_cage_log = new CageLog();
        //今日存栏改变量
        $change_data = $model_cage_log->getAmount($firm_id, $data_y, $data_m, $data_d);
        $add_amount = empty($change_data->sum_add) ? 0 : $change_data->sum_add;
        $reduce_amount = empty($change_data->sum_reduce) ? 0 : $change_data->sum_reduce;
        //计算 昨日存栏 今日实时数据减增加的数据 加上减少的数据
        $yesterday_amount = $today_amount + $reduce_amount - $add_amount;
        $status = 0; // 默认
        $change_number = 0;// 默认
        if($yesterday_amount >$today_amount){
            $status = 2;// 减少
            $change_number  = $yesterday_amount - $today_amount;
        }
        if($today_amount >= $yesterday_amount)
        {
            $status = 1;//增长
            $change_number  = $today_amount - $yesterday_amount;
        }
        $array['today_amount'] = $today_amount;
        $array['yesterday_amount'] = $yesterday_amount;
        $array['status'] = $status;
        //存栏数变化比例
        $stock_ratio = empty($yesterday_amount) ? '100%' : round($change_number * 100  / $yesterday_amount, 2) .'%';
        $array['stock_ratio'] = $stock_ratio;
        return $array;
    }

    /**
     * 饲料出库统计
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function useFoodData($firm_id, $data_time)
    {
        //今日饲料消耗
        $yesterday_data_time = date('Y-m-d', strtotime($data_time) - 86400);
        $model_grain_receive = new GrainReceive();
        $today_data = $model_grain_receive->getAmount($firm_id, $data_time);
        $today_amount = empty($today_data->sum_use_number) ? 0 : $today_data->sum_use_number;
        //昨日饲料消耗
        $yesterday_data = $model_grain_receive->getAmount($firm_id, $yesterday_data_time);
        $yesterday_amount = empty($yesterday_data->sum_use_number) ? 0 : $yesterday_data->sum_use_number;

        $status = 0; // 默认
        $change_number = 0;// 默认
        if($yesterday_amount >$today_amount){
            $status = 2;// 减少
            $change_number  = $yesterday_amount - $today_amount;
        }
        if($today_amount >= $yesterday_amount)
        {
            $status = 1;//增长
            $change_number  = $today_amount - $yesterday_amount;
        }
        $array['today_sum_use'] = $today_amount;
        $array['yesterday_sum_use'] = $yesterday_amount;
        $array['status'] = $status;
        //饲料消耗变化比例
        $stock_ratio = empty($yesterday_amount) ? '100%' : round($change_number * 100  / $yesterday_amount, 2) .'%';
        $array['food_ratio'] = $stock_ratio;
        return $array;
    }

    /**
     * 销售订单统计
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function orderData($firm_id, $data_time)
    {
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        //今日销售订单总金额
        $model_sale_type = new SaleType();
        $today_data = $model_sale_type->getAmount($firm_id, $data_y, $data_m, $data_d);
        $today_amount = empty($today_data->sum_number) ? 0 : $today_data->sum_number;
        //昨日销售订单总金额
        $yesterday_data = $model_sale_type->getAmount($firm_id, $yesterday_data_y, $yesterday_data_m, $yesterday_data_d);
        $yesterday_amount = empty($yesterday_data->sum_number) ? 0 : $yesterday_data->sum_number;

        $status = 0; // 默认
        $change_number = 0;// 默认
        if($yesterday_amount >$today_amount){
            $status = 2;// 减少
            $change_number  = $yesterday_amount - $today_amount;
        }
        if($today_amount >= $yesterday_amount)
        {
            $status = 1;//增长
            $change_number  = $today_amount - $yesterday_amount;
        }
        $array['today_sum_number'] = $today_amount;
        $array['yesterday_sum_number'] = $yesterday_amount;
        $array['status'] = $status;
        //饲料消耗变化比例
        $stock_ratio = empty($yesterday_amount) ? '100%' : round($change_number * 100  / $yesterday_amount, 2) .'%';
        $array['order_ratio'] = $stock_ratio;
        return $array;
    }

    /**
     * 不同厂区销售订单排行
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function orderRank($firm_id, $data_time)
    {
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        //今日销售订单总金额
        $model_sale_type = new SaleType();
        return $model_sale_type->getRank($firm_id, $yesterday_data_y, $yesterday_data_m, $yesterday_data_d);
    }

    /**
     * 不同厂区死亡率排名
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function deathRank($firm_id, $data_time)
    {
        //$data_time = "2021-10-01";
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        //return $yesterday_data_m;
        //不同厂区死亡数排名
        $model_cage_log = new CageLog();
        return $model_cage_log->getDeath($firm_id, $yesterday_data_y, $yesterday_data_m, $yesterday_data_d);
    }

    /**
     * 不同厂区病残率排名
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function diseaseRank($firm_id, $data_time)
    {
        //$data_time = "2021-10-01";
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        //return $yesterday_data_m;
        //不同厂区死亡数排名
        $model_cage_log = new CageLog();
        return $model_cage_log->getDisease($firm_id, $yesterday_data_y, $yesterday_data_m, $yesterday_data_d);
    }

    /**
     * 显示所有厂区的年度1-12月销售金额总数曲线
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function saleData($firm_id, $data_time)
    {
        //$data_time = "2021-10-01";
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        //return $yesterday_data_m;
        //不同厂区年度1-12月销售金额总数曲线
        $model_sale_type = new SaleType();
        return $model_sale_type->getSale($firm_id, $data_y);
    }

    /**
     * 显示所有厂区的不同货物品种销售订单的货物数量占比权重
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function saleProportion($firm_id, $data_time)
    {
        //$data_time = "2021-10-04";
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));

        $dove_names = ['种鸽', '鸽蛋', '乳鸽', '童鸽', '青年鸽'];
        //return $yesterday_data_m;
        //同货物品种销售订单的货物数量占比权重
        $model_sale_type = new SaleType();
        return $model_sale_type->getSaleProportion($firm_id, $data_y, $data_m, $data_d,  $dove_names);
    }

    /**
     * 不同货物品种销售订单的销售总额对比
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function sumSale($firm_id, $data_time)
    {
        $data_time = "2021-10-04";
        $data_y = date('Y', strtotime($data_time));
        $data_m = date('m', strtotime($data_time));
        $data_d = date('d', strtotime($data_time));
        $yesterday_data_y = date('Y', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_m = date('m', strtotime("-1 day", strtotime($data_time)));
        $yesterday_data_d = date('d', strtotime("-1 day", strtotime($data_time)));

        $dove_names = ['种鸽', '鸽蛋', '乳鸽', '童鸽', '青年鸽'];
        //return $yesterday_data_m;
        //不同货物品种销售订单的销售总额对比
        $model_sale_type = new SaleType();
        $data['today_sale'] = $model_sale_type->daySale($firm_id, $data_y, $data_m, $data_d,  $dove_names);
        $data['yesterday_sale'] = $model_sale_type->daySale($firm_id, $yesterday_data_y, $yesterday_data_m, $yesterday_data_d,  $dove_names);
        return $data;
    }

    /**
     * 员工管理鸽仓的存栏数排名
     * @param $firm_id
     * @param $data_time
     * @return mixed
     */
    private function userRank($firm_id, $data_time)
    {
        //员工管理鸽仓的存栏数排名
        $model_user = new User();
        return $model_user->userRank($firm_id);
    }

    /**
     * 所有数据
     * @param Request $request
     * @return mixed
     */
    public function reportData(Request $request)
    {
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0;
        $redis = Redis::connection('default');
        $cacheKey = "dove_report_data".$factory_id;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            $data_time = date('Y-m-d', time());
            $statement_data =  $this->dataStatement($firm_id, $data_time, $factory_id);
            $anomaly_data =  $this->anomalyData($firm_id, $data_time, $factory_id);

            $data['statement_data'] = $statement_data;
            $data['stock_data'] = $anomaly_data;
            $redis->set($cacheKey, json_encode($data), 600);
        }
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$data]);
    }

    /**
     * 数据统计--生产报表
     * @param $firm_id
     * @param $data_time
     * @param $factory_id
     * @return mixed
     */
    private function dataStatement($firm_id, $data_time, $factory_id)
    {
        $start_time =  strtotime($data_time);//开始时间
        $end_time = $start_time + 86400;//结束时间
        $block_type = 1 ;//仓号类型
        $block_id = 0;//仓号ID
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_cage_log = new CageLog();
        $data_array = $model_cage_log->getStatementList($start_time, $end_time, $factory_id, $block_type, $block_id, $firm_id);
        $return_data = array();
        foreach ($data_array as  $v)
        {
            //判断日期仓号 保证每天每个仓号一条数据
            if(isset($return_data[$v->log_time.$v->block_id]))
            {
                //如果已经存在了叠加数据
                //判断鸽子类型 组装数据 种鸽
                if($v->type_id == 1)
                {
                    $return_data[$v->log_time.$v->block_id]['p_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['p_disease'] : $v->disease + $return_data[$v->log_time.$v->block_id]['p_disease'];
                    $return_data[$v->log_time.$v->block_id]['p_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['p_death'] : $v->death + $return_data[$v->log_time.$v->block_id]['p_death'];
                    $return_data[$v->log_time.$v->block_id]['p_shift_to'] = empty($v->shift_to) ? $return_data[$v->log_time.$v->block_id]['p_shift_to'] : $v->shift_to +  $return_data[$v->log_time.$v->block_id]['p_shift_to'];
                    $return_data[$v->log_time.$v->block_id]['p_in_add'] = empty($v->in_add) ? $return_data[$v->log_time.$v->block_id]['p_in_add'] : $v->in_add + $return_data[$v->log_time.$v->block_id]['p_in_add'];
                    $return_data[$v->log_time.$v->block_id]['p_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['p_day_survival'] : $v->day_survival +  $return_data[$v->log_time.$v->block_id]['p_day_survival'] ;
                }
                //判断鸽子类型 组装数据 鸽蛋
                if($v->type_id == 2)
                {
                    $return_data[$v->log_time.$v->block_id]['e_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['e_day_survival'] :  $return_data[$v->log_time.$v->block_id]['e_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['e_shift_to'] = empty($v->shift_to) ? $return_data[$v->log_time.$v->block_id]['e_shift_to'] : $return_data[$v->log_time.$v->block_id]['e_shift_to'] + $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['e_massacre'] = empty($v->massacre) ? $return_data[$v->log_time.$v->block_id]['e_massacre'] : $return_data[$v->log_time.$v->block_id]['e_massacre'] + $v->massacre;
                    $return_data[$v->log_time.$v->block_id]['e_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['e_death'] : $return_data[$v->log_time.$v->block_id]['e_death'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['e_getout'] = empty($v->getout) ? $return_data[$v->log_time.$v->block_id]['e_getout'] : $return_data[$v->log_time.$v->block_id]['e_getout'] + $v->getout;
                }
                //判断鸽子类型 组装数据 乳鸽
                if($v->type_id == 3)
                {
                    $return_data[$v->log_time.$v->block_id]['s_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['s_day_survival'] : $return_data[$v->log_time.$v->block_id]['s_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['s_hatch'] = empty($v->hatch) ? $return_data[$v->log_time.$v->block_id]['s_hatch'] : $return_data[$v->log_time.$v->block_id]['s_hatch'] + $v->hatch;
                    $return_data[$v->log_time.$v->block_id]['s_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['s_disease'] : $return_data[$v->log_time.$v->block_id]['s_disease'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['s_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['s_death'] : $return_data[$v->log_time.$v->block_id]['s_death'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['s_disease_sell'] = empty($v->disease_sell) ? $return_data[$v->log_time.$v->block_id]['s_disease_sell'] : $return_data[$v->log_time.$v->block_id]['s_disease_sell'] + $v->disease_sell;
                }
                //判断鸽子类型 组装数据 童鸽
                if($v->type_id == 4)
                {
                    $return_data[$v->log_time.$v->block_id]['c_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['c_day_survival'] : $return_data[$v->log_time.$v->block_id]['c_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['c_shift_to'] = empty($v->shift_to) ? $return_data[$v->log_time.$v->block_id]['c_shift_to'] : $return_data[$v->log_time.$v->block_id]['c_shift_to'] + $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['c_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['c_disease'] : $return_data[$v->log_time.$v->block_id]['c_disease'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['c_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['c_death'] : $return_data[$v->log_time.$v->block_id]['c_death'] + $v->death;
                }
            }
            //判断日期仓号 保证每天每个仓号一条数据 不存在
            else {
                $return_data[$v->log_time.$v->block_id]['log_time'] = $v->log_time;
                $return_data[$v->log_time.$v->block_id]['factory_name'] = $v->factory_name;
                $return_data[$v->log_time.$v->block_id]['block_name'] = $v->block_name;
                $return_data[$v->log_time.$v->block_id]['type_name'] = $v->type_name;
                $return_data[$v->log_time.$v->block_id]['block_id'] = $v->block_id;
                $return_data[$v->log_time.$v->block_id]['factory_id'] = $v->factory_id;
                //$return_data[$v->log_time.$v->block_id]['p_last_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_disease'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_death'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_shift_to'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_in_add'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_day_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['e_day_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['e_shift_to'] = 0;
                $return_data[$v->log_time.$v->block_id]['e_massacre'] = 0;
                $return_data[$v->log_time.$v->block_id]['e_death'] = 0;
                $return_data[$v->log_time.$v->block_id]['e_getout'] = 0;
                $return_data[$v->log_time.$v->block_id]['s_day_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['s_hatch'] = 0;
                $return_data[$v->log_time.$v->block_id]['s_disease'] = 0;
                $return_data[$v->log_time.$v->block_id]['s_death'] = 0;
                $return_data[$v->log_time.$v->block_id]['s_disease_sell'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_day_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_shift_to'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_disease'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_death'] = 0;

                $time =  strtotime(date('Y-m-01',strtotime($v->log_time)));
                $last_time = strtotime(date('Y-m-d',$time));//计算出本月第一天
                $p_last_survival = $model_cage_log->getSurvivalByTime($last_time, $v->block_id, 1);
                $return_data[$v->log_time.$v->block_id]['p_last_survival'] = empty($p_last_survival) ? 0 : $p_last_survival;
                if($v->type_id == 1)
                {
                    $return_data[$v->log_time.$v->block_id]['p_disease'] = empty($v->disease) ? 0 : $v->disease;
                    $return_data[$v->log_time.$v->block_id]['p_death'] = empty($v->death) ? 0 : $v->death;
                    $return_data[$v->log_time.$v->block_id]['p_shift_to'] = empty($v->shift_to) ? 0 : $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['p_in_add'] = empty($v->in_add) ? 0 : $v->in_add;
                    $return_data[$v->log_time.$v->block_id]['p_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                }
                if($v->type_id == 2)
                {
                    $return_data[$v->log_time.$v->block_id]['e_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['e_shift_to'] = empty($v->shift_to) ? 0 : $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['e_massacre'] = empty($v->massacre) ? 0 :$v->massacre;
                    $return_data[$v->log_time.$v->block_id]['e_death'] = empty($v->death) ? 0 :$v->death;
                    $return_data[$v->log_time.$v->block_id]['e_getout'] = empty($v->getout) ? 0 :$v->getout;
                }
                if($v->type_id == 3)
                {
                    $return_data[$v->log_time.$v->block_id]['s_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['s_hatch'] = empty($v->hatch) ? 0 : $v->hatch;
                    $return_data[$v->log_time.$v->block_id]['s_disease'] = empty($v->disease) ? 0 : $v->disease;
                    $return_data[$v->log_time.$v->block_id]['s_death'] = empty($v->death) ? 0 :$v->death;
                    $return_data[$v->log_time.$v->block_id]['s_disease_sell'] = empty($v->disease_sell) ? 0 :$v->disease_sell;
                }
                if($v->type_id == 4)
                {
                    $return_data[$v->log_time.$v->block_id]['c_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['c_shift_to'] = empty($v->shift_to) ? 0 : $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['c_disease'] = empty($v->disease) ? 0 : $v->disease;
                    $return_data[$v->log_time.$v->block_id]['c_death'] = empty($v->death) ? 0 :$v->death;
                }
            }
        }
        $return_data = array_values($return_data);
        return $return_data;
    }

    /**
     * 数据异常列表
     * @param $firm_id
     * @param $data_time
     * @param $factory_id
     * @return mixed
     */
    public function anomalyData($firm_id, $data_time, $factory_id)
    {
        $start_time =  $data_time;//开始时间
        $end_time = $data_time;//结束时间
        $model_anomaly = new Anomaly();
        return $model_anomaly->getAllList($firm_id, $start_time, $end_time, $factory_id);
    }

    /**
     * 查询企业下所有厂区
     * @param Request $request
     * @return mixed
     */
    public function allFactory(Request $request)
    {
        //获取参数 校验参数
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'缺少参数,企业ID', 'data'=>['']]);
        //查询所有厂区
        $model_factory = new Factory();
        $factory_data = $model_factory->getAllFactoryInfo($firm_id);
        $return_data = ['code'=>20000,'msg'=>'', 'data'=>$factory_data];
        return response()->json($return_data);
    }

}
