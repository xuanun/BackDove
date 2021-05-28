<?php


namespace App\Http\Controllers;

use App\Models\CageLog;
use App\Models\UserFactory;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * 数据统计--生产报表
     * @param Request $request
     * @return mixed
     */
    public function dataStatement(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? strtotime($input['start_time']) : '';//开始时间
        $end_time = isset($input['end_time']) ? strtotime($input['end_time']) : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_cage_log = new CageLog();
        $data_array = $model_cage_log->getStatementList($start_time, $end_time, $factory_id, $block_type, $block_id);
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
                    $last_time = strtotime("+1 month",strtotime($v->log_time));
                    $p_last_survival = $model_cage_log->getSurvivalByTime($last_time, $v->cage_id, $v->type_id);
                    $return_data[$v->log_time.$v->block_id]['p_last_survival'] = empty($p_last_survival) ? $return_data[$v->log_time.$v->block_id]['p_last_survival'] : $p_last_survival + $return_data[$v->log_time.$v->block_id]['p_last_survival'];
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
                $return_data[$v->log_time.$v->block_id]['block_id'] = $v->block_id;
                $return_data[$v->log_time.$v->block_id]['factory_id'] = $v->factory_id;
                $return_data[$v->log_time.$v->block_id]['p_last_survival'] = 0;
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
                if($v->type_id == 1)
                {
                    $last_time = strtotime("+1 month",strtotime($v->log_time));
                    $p_last_survival = $model_cage_log->getSurvivalByTime($last_time, $v->cage_id, $v->type_id);
                    $return_data[$v->log_time.$v->block_id]['p_last_survival'] = empty($p_last_survival) ? 0 : $p_last_survival;
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
        return response()->json(['code'=>20000, 'msg'=>'请求成功', 'data'=>$return_data]);
    }

    /**
     * 数据统计--育雏仓
     * @param Request $request
     * @return mixed
     */
    public function dataBrood(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? strtotime($input['start_time']) : '';//开始时间
        $end_time = isset($input['end_time']) ? strtotime($input['end_time']) : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_cage_log = new CageLog();
        $data_array = $model_cage_log->getStatementListByType($start_time, $end_time, $factory_id, $block_type, $block_id,4);
        $return_data = array();
        foreach ($data_array as  $v)
        {
            //判断日期仓号 保证每天每个仓号一条数据
            if(isset($return_data[$v->log_time.$v->block_id]))
            {
                //如果已经存在了叠加数据
                //判断鸽子类型 组装数据 童鸽
                if($v->type_id == 4)
                {
                    $return_data[$v->log_time.$v->block_id]['c_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['c_day_survival'] : $return_data[$v->log_time.$v->block_id]['c_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['c_hatch'] = empty($v->hatch) ? $return_data[$v->log_time.$v->block_id]['c_hatch'] : $return_data[$v->log_time.$v->block_id]['c_hatch'] + $v->hatch;
                    $return_data[$v->log_time.$v->block_id]['c_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['c_disease'] :$return_data[$v->log_time.$v->block_id]['c_disease'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['c_massacre'] = empty($v->massacre) ? $return_data[$v->log_time.$v->block_id]['c_massacre'] : $return_data[$v->log_time.$v->block_id]['c_massacre'] + $v->massacre;
                    $return_data[$v->log_time.$v->block_id]['c_shift_to'] = empty($v->shift_to) ? $return_data[$v->log_time.$v->block_id]['c_shift_to'] : $return_data[$v->log_time.$v->block_id]['c_shift_to'] + $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['c_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['c_death'] : $return_data[$v->log_time.$v->block_id]['c_death'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['c_sell'] = empty($v->sell) ?  $return_data[$v->log_time.$v->block_id]['c_sell'] : $return_data[$v->log_time.$v->block_id]['c_sell'] + $v->sell;
                }
            }
            //判断日期仓号 保证每天每个仓号一条数据 不存在
            else {
                $return_data[$v->log_time.$v->block_id]['log_time'] = $v->log_time;
                $return_data[$v->log_time.$v->block_id]['factory_name'] = $v->factory_name;
                $return_data[$v->log_time.$v->block_id]['block_name'] = $v->block_name;
                $return_data[$v->log_time.$v->block_id]['block_id'] = $v->block_id;
                $return_data[$v->log_time.$v->block_id]['factory_id'] = $v->factory_id;
                $return_data[$v->log_time.$v->block_id]['c_day_survival'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_hatch'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_disease'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_massacre'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_shift_to'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_death'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_sell'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_mortality'] = 0;
                $return_data[$v->log_time.$v->block_id]['c_disability'] = 0;
                $breeder_array = $this->getBreeder($v->block_id);
                $return_data[$v->log_time.$v->block_id]['breeder'] = $breeder_array['breeder'];
                $return_data[$v->log_time.$v->block_id]['carer'] = $breeder_array['carer'];

                if($v->type_id == 4)
                {
                    $return_data[$v->log_time.$v->block_id]['c_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['c_hatch'] = empty($v->hatch) ? 0 :$v->hatch;
                    $return_data[$v->log_time.$v->block_id]['c_disease'] = empty($v->disease) ? 0 :$v->disease;
                    $return_data[$v->log_time.$v->block_id]['c_massacre'] = empty($v->massacre) ? 0 :$v->massacre;
                    $return_data[$v->log_time.$v->block_id]['c_shift_to'] = empty($v->shift_to) ? 0 : $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['c_death'] = empty($v->death) ? 0 :$v->death;
                    $return_data[$v->log_time.$v->block_id]['c_sell'] = empty($v->sell) ? 0 :$v->sell;
                }
            }
        }
        $return_data = array_values($return_data);
        $return_array = array();
        foreach ($return_data as $v)
        {
            $v['c_mortality'] = empty($v['c_day_survival']) ? 0 : round($v['c_death'] *100 / $v['c_day_survival'], 2).'%';
            $v['c_disability'] = empty($v['c_day_survival']) ? 0 : round($v['c_disease'] *100 / $v['c_day_survival'], 2).'%';
            $return_array[] = $v;
        }
        return response()->json(['code'=>20000, 'msg'=>'请求成功', 'data'=>$return_array]);
    }

    /**
     * 数据统计--飞棚仓
     * @param Request $request
     * @return mixed
     */
    public function dataYouth(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? strtotime($input['start_time']) : '';//开始时间
        $end_time = isset($input['end_time']) ? strtotime($input['end_time']) : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_cage_log = new CageLog();
        $data_array = $model_cage_log->getStatementListByType($start_time, $end_time, $factory_id, $block_type, $block_id, 0);
        $return_data = array();
        foreach ($data_array as  $v)
        {
            //判断日期仓号 保证每天每个仓号一条数据
            if(isset($return_data[$v->log_time.$v->block_id]))
            {
                //如果已经存在了叠加数据
                //判断鸽子类型 组装数据 童鸽
                if($v->type_id == 5)
                {
                    $return_data[$v->log_time.$v->block_id]['y_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['y_day_survival'] : $return_data[$v->log_time.$v->block_id]['y_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['y_brood'] = empty($v->brood) ? $return_data[$v->log_time.$v->block_id]['y_brood'] : $return_data[$v->log_time.$v->block_id]['y_brood'] + $v->hatch;
                    $return_data[$v->log_time.$v->block_id]['y_conesting'] = empty($v->conesting) ? $return_data[$v->log_time.$v->block_id]['y_conesting'] :$return_data[$v->log_time.$v->block_id]['y_conesting'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['y_hatch'] = empty($v->hatch) ? $return_data[$v->log_time.$v->block_id]['y_hatch'] : $return_data[$v->log_time.$v->block_id]['y_hatch'] + $v->massacre;
                    $return_data[$v->log_time.$v->block_id]['y_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['y_disease'] : $return_data[$v->log_time.$v->block_id]['y_disease'] + $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['y_massacre'] = empty($v->massacre) ? $return_data[$v->log_time.$v->block_id]['y_massacre'] : $return_data[$v->log_time.$v->block_id]['y_massacre'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['y_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['y_death'] : $return_data[$v->log_time.$v->block_id]['y_death'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['y_sell'] = empty($v->sell) ?  $return_data[$v->log_time.$v->block_id]['y_sell'] : $return_data[$v->log_time.$v->block_id]['y_sell'] + $v->sell;
                    $return_data[$v->log_time.$v->block_id]['y_shift_to'] = empty($v->shift_to) ? $return_data[$v->log_time.$v->block_id]['y_shift_to'] : $return_data[$v->log_time.$v->block_id]['y_shift_to'] + $v->death;
                }
            }
            //判断日期仓号 保证每天每个仓号一条数据 不存在
            else {
                $return_data[$v->log_time.$v->block_id]['log_time'] = $v->log_time;
                $return_data[$v->log_time.$v->block_id]['factory_name'] = $v->factory_name;
                $return_data[$v->log_time.$v->block_id]['block_name'] = $v->block_name;
                $return_data[$v->log_time.$v->block_id]['block_id'] = $v->block_id;
                $return_data[$v->log_time.$v->block_id]['factory_id'] = $v->factory_id;
                $return_data[$v->log_time.$v->block_id]['y_day_survival'] = 0;//数量
                $return_data[$v->log_time.$v->block_id]['y_brood'] = 0;//育雏仓转入
                $return_data[$v->log_time.$v->block_id]['y_conesting'] = 0;//转仓
                $return_data[$v->log_time.$v->block_id]['y_hatch'] = 0;//生产仓转入
                $return_data[$v->log_time.$v->block_id]['y_disease'] = 0;//病残淘汰
                $return_data[$v->log_time.$v->block_id]['y_massacre'] = 0;//屠宰
                $return_data[$v->log_time.$v->block_id]['y_death'] = 0;//死亡
                $return_data[$v->log_time.$v->block_id]['y_sell'] = 0;//出售
                $return_data[$v->log_time.$v->block_id]['y_shift_to'] = 0;//补入生产仓
                $return_data[$v->log_time.$v->block_id]['y_mortality'] = 0;
                $return_data[$v->log_time.$v->block_id]['y_disability'] = 0;
                $breeder_array = $this->getBreeder($v->block_id);
                $return_data[$v->log_time.$v->block_id]['breeder'] = $breeder_array['breeder'];
                $return_data[$v->log_time.$v->block_id]['carer'] = $breeder_array['carer'];

                if($v->type_id == 5)
                {
                    $return_data[$v->log_time.$v->block_id]['y_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['y_brood'] = empty($v->brood) ? 0 :$v->brood;
                    $return_data[$v->log_time.$v->block_id]['y_conesting'] = empty($v->conesting) ? 0 :$v->conesting;
                    $return_data[$v->log_time.$v->block_id]['y_hatch'] = empty($v->hatch) ? 0 :$v->hatch;
                    $return_data[$v->log_time.$v->block_id]['y_disease'] = empty($v->disease) ? 0 : $v->disease;
                    $return_data[$v->log_time.$v->block_id]['y_massacre'] = empty($v->massacre) ? 0 : $v->massacre;
                    $return_data[$v->log_time.$v->block_id]['y_death'] = empty($v->death) ? 0 :$v->death;
                    $return_data[$v->log_time.$v->block_id]['y_sell'] = empty($v->sell) ? 0 :$v->sell;
                    $return_data[$v->log_time.$v->block_id]['y_shift_to'] = empty($v->shift_to) ? 0 :$v->shift_to;
                }
            }
        }
        $return_data = array_values($return_data);
        $return_array = array();
        foreach ($return_data as $v)
        {
            $v['y_mortality'] = empty($v['y_day_survival']) ? 0 : round($v['y_death'] *100 / $v['y_day_survival'], 2).'%';
            $v['y_disability'] = empty($v['y_day_survival']) ? 0 : round($v['y_disease'] *100 / $v['y_day_survival'], 2).'%';
            $return_array[] = $v;
        }
        return response()->json(['code'=>20000, 'msg'=>'请求成功', 'data'=>$return_array]);
    }

    /**
     * 数据统计--生产仓
     * @param Request $request
     * @return mixed
     */
    public function dataPigeon(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? strtotime($input['start_time']) : '';//开始时间
        $end_time = isset($input['end_time']) ? strtotime($input['end_time']) : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//鸽子类型
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($firm_id) || empty($type_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_cage_log = new CageLog();
        $data_array = $model_cage_log->getStatementListByType($start_time, $end_time, $factory_id, $block_type, $block_id, $type_id);
        //return  [$data_array];
        $return_data = array();
        foreach ($data_array as  $v)
        {
            //判断日期仓号 保证每天每个仓号一条数据
            if(isset($return_data[$v->log_time.$v->block_id]))
            {
                //如果已经存在了叠加数据
                //判断鸽子类型 组装数据 童鸽
                if($v->type_id == 1)
                {
                    $return_data[$v->log_time.$v->block_id]['p_day_survival'] = 0;//数量
                    $return_data[$v->log_time.$v->block_id]['p_disease'] = 0;//病残淘汰
                    $return_data[$v->log_time.$v->block_id]['p_disease_sell'] = 0;//病残销售
                    $return_data[$v->log_time.$v->block_id]['p_massacre'] = 0;//屠宰
                    $return_data[$v->log_time.$v->block_id]['p_death'] = 0;//死亡
                    $return_data[$v->log_time.$v->block_id]['p_sell'] = 0;//出售
                    $return_data[$v->log_time.$v->block_id]['p_useless'] = 0;//无产能
                    $return_data[$v->log_time.$v->block_id]['p_added_out'] = 0;//外购
                    $return_data[$v->log_time.$v->block_id]['p_added_wit'] = 0;//自繁
                    $return_data[$v->log_time.$v->block_id]['p_replenish'] = 0;//补仓
                    $return_data[$v->log_time.$v->block_id]['p_conesting'] = 0;//并窝

                    $return_data[$v->log_time.$v->block_id]['p_day_survival'] = empty($v->day_survival) ? $return_data[$v->log_time.$v->block_id]['p_day_survival'] : $return_data[$v->log_time.$v->block_id]['p_day_survival'] + $v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['p_disease'] = empty($v->disease) ? $return_data[$v->log_time.$v->block_id]['p_disease'] : $return_data[$v->log_time.$v->block_id]['p_disease'] + $v->hatch;
                    $return_data[$v->log_time.$v->block_id]['p_disease_sell'] = empty($v->disease_sell) ? $return_data[$v->log_time.$v->block_id]['p_disease_sell'] :$return_data[$v->log_time.$v->block_id]['p_disease_sell'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['p_massacre'] = empty($v->massacre) ? $return_data[$v->log_time.$v->block_id]['p_massacre'] : $return_data[$v->log_time.$v->block_id]['p_massacre'] + $v->massacre;
                    $return_data[$v->log_time.$v->block_id]['p_death'] = empty($v->death) ? $return_data[$v->log_time.$v->block_id]['p_death'] : $return_data[$v->log_time.$v->block_id]['p_death'] + $v->shift_to;
                    $return_data[$v->log_time.$v->block_id]['p_sell'] = empty($v->sell) ? $return_data[$v->log_time.$v->block_id]['p_sell'] : $return_data[$v->log_time.$v->block_id]['p_sell'] + $v->disease;
                    $return_data[$v->log_time.$v->block_id]['p_useless'] = empty($v->useless) ? $return_data[$v->log_time.$v->block_id]['p_useless'] : $return_data[$v->log_time.$v->block_id]['p_useless'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['p_added_out'] = empty($v->added_out) ?  $return_data[$v->log_time.$v->block_id]['p_added_out'] : $return_data[$v->log_time.$v->block_id]['p_added_out'] + $v->sell;
                    $return_data[$v->log_time.$v->block_id]['p_added_wit'] = empty($v->added_wit) ? $return_data[$v->log_time.$v->block_id]['p_added_wit'] : $return_data[$v->log_time.$v->block_id]['p_added_wit'] + $v->death;
                    $return_data[$v->log_time.$v->block_id]['p_replenish'] = empty($v->replenish) ?  $return_data[$v->log_time.$v->block_id]['p_replenish'] : $return_data[$v->log_time.$v->block_id]['p_replenish'] + $v->sell;
                    $return_data[$v->log_time.$v->block_id]['p_conesting'] = empty($v->conesting) ? $return_data[$v->log_time.$v->block_id]['p_conesting'] : $return_data[$v->log_time.$v->block_id]['p_conesting'] + $v->death;
                }
            }
            //判断日期仓号 保证每天每个仓号一条数据 不存在
            else {
                $return_data[$v->log_time.$v->block_id]['log_time'] = $v->log_time;
                $return_data[$v->log_time.$v->block_id]['factory_name'] = $v->factory_name;
                $return_data[$v->log_time.$v->block_id]['block_name'] = $v->block_name;
                $return_data[$v->log_time.$v->block_id]['block_id'] = $v->block_id;
                $return_data[$v->log_time.$v->block_id]['factory_id'] = $v->factory_id;
                $return_data[$v->log_time.$v->block_id]['p_day_survival'] = 0;//数量
                $return_data[$v->log_time.$v->block_id]['p_disease'] = 0;//病残淘汰
                $return_data[$v->log_time.$v->block_id]['p_disease_sell'] = 0;//病残销售
                $return_data[$v->log_time.$v->block_id]['p_massacre'] = 0;//屠宰
                $return_data[$v->log_time.$v->block_id]['p_death'] = 0;//死亡
                $return_data[$v->log_time.$v->block_id]['p_sell'] = 0;//出售
                $return_data[$v->log_time.$v->block_id]['p_useless'] = 0;//无产能
                $return_data[$v->log_time.$v->block_id]['p_added_out'] = 0;//外购
                $return_data[$v->log_time.$v->block_id]['p_added_wit'] = 0;//自繁
                $return_data[$v->log_time.$v->block_id]['p_replenish'] = 0;//补仓
                $return_data[$v->log_time.$v->block_id]['p_conesting'] = 0;//并窝
                $return_data[$v->log_time.$v->block_id]['p_mortality'] = 0;
                $return_data[$v->log_time.$v->block_id]['p_disability'] = 0;
                $breeder_array = $this->getBreeder($v->block_id);
                $return_data[$v->log_time.$v->block_id]['breeder'] = $breeder_array['breeder'];
                $return_data[$v->log_time.$v->block_id]['carer'] = $breeder_array['carer'];

                if($v->type_id == 1)
                {
                    $return_data[$v->log_time.$v->block_id]['p_day_survival'] = empty($v->day_survival) ? 0 :$v->day_survival;
                    $return_data[$v->log_time.$v->block_id]['p_disease'] = empty($v->disease) ? 0 :$v->disease;
                    $return_data[$v->log_time.$v->block_id]['p_disease_sell'] = empty($v->disease_sell) ? 0 :$v->disease_sell;
                    $return_data[$v->log_time.$v->block_id]['p_massacre'] = empty($v->massacre) ? 0 :$v->massacre;
                    $return_data[$v->log_time.$v->block_id]['p_death'] = empty($v->death) ? 0 : $v->death;
                    $return_data[$v->log_time.$v->block_id]['p_sell'] = empty($v->sell) ? 0 : $v->sell;
                    $return_data[$v->log_time.$v->block_id]['p_useless'] = empty($v->useless) ? 0 :$v->useless;
                    $return_data[$v->log_time.$v->block_id]['p_added_out'] = empty($v->added_out) ? 0 :$v->added_out;
                    $return_data[$v->log_time.$v->block_id]['p_added_wit'] = empty($v->added_wit) ? 0 :$v->added_wit;
                    $return_data[$v->log_time.$v->block_id]['p_replenish'] = empty($v->replenish) ? 0 :$v->replenish;
                    $return_data[$v->log_time.$v->block_id]['p_conesting'] = empty($v->conesting) ? 0 :$v->conesting;
                }
            }
        }
        $return_data = array_values($return_data);
        $return_array = array();
        foreach ($return_data as $v)
        {
            $v['p_mortality'] = empty($v['p_day_survival']) ? 0 : round($v['p_death'] *100 / $v['p_day_survival'], 2).'%';
            $v['p_disability'] = empty($v['p_day_survival']) ? 0 : round($v['p_disease'] *100 / $v['p_day_survival'], 2).'%';
            $return_array[] = $v;
        }
        return response()->json(['code'=>20000, 'msg'=>'请求成功', 'data'=>$return_array]);
    }

    /**
     * 获取饲养员
     * @param $block_id
     * @return mixed
     */
    public function getBreeder($block_id)
    {
        $model_user_factory = new UserFactory();
        $return_data = $model_user_factory->getUsersInfo($block_id);
        $array  = array();
        foreach ($return_data as $v)
        {
            if($v->role_name == '饲养员')
            {
                $array['breeder'] = $v->user_name;
            }else
                $array['breeder'] = '';
            if($v->role_name == '护工')
            {
                $array['carer'] = $v->user_name;
            }else
                $array['carer'] = '';
        }
        if(empty($array['breeder']) &&  isset($return_data[0])){
            $array['breeder'] = $return_data[0]->user_name;
        }
        if(empty($array['carer']) &&  isset($return_data[1]))
        {
            $array['carer'] = $return_data[1]->user_name;
        }
        return $array;
    }

}
