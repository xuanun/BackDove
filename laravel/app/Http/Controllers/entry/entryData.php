<?php


namespace App\Http\Controllers\entry;


use App\Http\Controllers\Controller;
use App\Models\Anomaly;
use App\Models\Block;
use App\Models\Cage;
use App\Models\CageAdd;
use App\Models\CageCategory;
use App\Models\CageLog;
use App\Models\CageReduce;
use App\Models\EarlyWarning;
use App\Models\UserFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class entryData extends Controller
{
    /**
     * 数据录入--乳鸽查询鸽笼详情
     * @param Request $request
     * @return mixed
     */
    public function getCageInfo(Request $request)
    {
        $input = $request->all();

        //$category_id = isset($input['category_id']) ? $input['category_id'] : 0; //仓库类型
        $category_str = isset($input['category_str']) ? $input['category_str'] : ''; //squab, pigeon, egg, child, youth
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0;
        if(empty($block_id) || empty($category_str)) return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0;
        $cage_model = new Cage();
        if(empty($cage_id))
            $cage_id = $cage_model->getCageID($block_id);

        $token = $request->header('token');
        if(empty($token)) return response()->json(['code'=>50000,'msg'=>'用户未登录',  'data'=>[]]);
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            return response()->json(['code'=>50000,'msg'=>'你的登录信息已失效',  'data'=>[]]);
        }
        if(empty($block_id) || empty($cage_id))
            return response()->json(['status_code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        //查询鸽笼详情  日龄 存栏数

        $cage_info = $cage_model->getCageInfo($cage_id);
        if(empty($cage_info))
            return response()->json(['status_code'=>40000,'msg'=>'鸽笼ID不存在',  'data'=>[]]);
        //查询饲养员 护工
        $user_factory_model = new UserFactory();
        $user_info = $user_factory_model->getUsersInfo($block_id);
        $exists_user = 0 ;
        $user_id = $data['id'];
        foreach ($user_info as $value){
            if($user_id == $value->user_id ||  $user_id == 1)
                $exists_user = 1;
        }
        if(empty($exists_user))
            return response()->json(['code'=>40000, 'msg'=>'你不是这个仓号的管理员或者护工',  'data'=>[]]);
        if($cage_info->initiate_time)
            $age_day = ceil((time() - $cage_info->initiate_time) / 86400);
        else
            $age_day = 0;
        $return_data['age_day'] = $age_day;
        if($category_str == 'squab')
            $return_data['survival'] = $cage_info->squab;
        if($category_str == 'pigeon')
            $return_data['survival'] = $cage_info->pigeon;
        if($category_str == 'egg')
            $return_data['survival'] = $cage_info->egg;
        if($category_str == 'child')
            $return_data['survival'] = $cage_info->child;
        if($category_str == 'youth')
            $return_data['survival'] = $cage_info->youth;
        $return_data['list'] = $user_info;
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$return_data]);

    }

    /**
     * 数据录入--乳鸽
     * @param Request $request
     * @return mixed
     */
    public function squabData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $survival = isset($input['survival']) ? $input['survival'] : 1; //存栏

        $hatch_add = isset($input['hatch_add']) ? $input['hatch_add'] : 0;//孵化转入
        $squab_add = isset($input['squab_add']) ? $input['squab_add'] : 0;//出雏
        $nest_add = isset($input['nest_add']) ? $input['nest_add'] : 0;//并窝

        $age_day =  isset($input['age_day']) ? $input['age_day'] : 0;//鸽笼日龄
        $hatch_day = isset($input['hatch_day']) ? $input['hatch_day'] : 0;//孵化转入日龄
        $squab_day = isset($input['squab_day']) ? $input['squab_day'] : 0;//出雏日龄
        $nest_day = isset($input['nest_day']) ? $input['nest_day'] : 0;//并窝日龄

        if($age_day != $hatch_day || $age_day != $squab_day || $age_day != $nest_day)
            return response()->json(['code'=>60000, 'msg'=>'日龄与当前存栏日龄不同',  'data'=>[]]);
        $add_number = $hatch_add + $squab_add + $nest_add;

        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//病残淘汰
        $cull_num = isset($input['cull_num']) ? $input['cull_num'] : 0;//屠宰
        $die_num = isset($input['die_num']) ? $input['die_num'] : 0;//死亡
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售
        $sick_sell_num = isset($input['sick_sell_num']) ? $input['sick_sell_num'] : 0;//病残销售
        $reduce_num = $sick_num + $cull_num + $die_num + $sell_num + $sick_sell_num;
        if($reduce_num > $survival)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '乳鸽';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $sick_name = '病残率';
            $sick_data = $early_model->getDataByName($sick_name);
            $sick_s_pigeon = $sick_data->s_pigeon;
            $is_anomaly = 0;
            if($sick_s_pigeon)
            {
                //计算病残率
                $sick_rate = round($sick_num * 100 / $survival, 2) ;
                if($sick_rate > $sick_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'病残率异常,';
                }
            }else
                $sick_rate = 0;
            $die_name = '死亡率';
            $die_data = $early_model->getDataByName($die_name);
            $die_s_pigeon = $die_data->s_pigeon;
            if($die_s_pigeon){
                //计算死亡率
                $die_rate = round($die_num * 100 / $survival, 2) ;
                if($die_rate > $die_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'死亡率异常,';
                }
            }else
                $die_rate = 0;
            $inability_name = '无产能';
            $inability_data = $early_model->getDataByName($inability_name);
            $inability_s_pigeon = $inability_data->s_pigeon;
            $inability_num = 0;
            if($inability_s_pigeon){
                //计算死亡率
                $inability_rate = round($inability_num * 100 / $survival, 2) ;
                if($inability_rate > $inability_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无产能异常';
                }
            }else
                $inability_rate = 0;

            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //乳鸽销售总数
        $sell_total = $sick_sell_num + $sell_num;
        //乳鸽数
        $squab_change_number = $add_number - $reduce_num;
        $sell_total = $sick_sell_num + $sell_num;
        $change_type = 'squab';
        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total, $change_type, $squab_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }
        if($add_number > 0) {
            $add_data = $cage_add_model->addSquab($user_id, $hatch_add, $squab_add, $nest_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            //$reduce_num = 0;
            //$reduce_id = 0;
            $log_data = $cage_log_model->addLog( $user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {
            $reduce_data = $cage_reduce_model->reduceSquab($user_id, $sick_num, $cull_num, $die_num,
                $sell_num, $sick_sell_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }

        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--种鸽
     * @param Request $request
     * @return mixed
     */
    public function pigeonData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $survival = isset($input['survival']) ? $input['survival'] : 0; //存栏

        $age_day =  isset($input['age_day']) ? $input['age_day'] : 0;//鸽笼日龄

        $buy_add = isset($input['buy_add']) ? $input['buy_add'] : 0;//新增（外购）
        $breed_add = isset($input['breed_add']) ? $input['breed_add'] : 0;//新增（自繁）
        $buy_in = isset($input['buy_in']) ? $input['buy_in'] : 0;//补仓
        $nest_add = isset($input['nest_add']) ? $input['nest_add'] : 0;//并窝
        $add_number = $buy_add + $breed_add + $buy_in + $nest_add;

        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//病残淘汰
        $cull_num = isset($input['cull_num']) ? $input['cull_num'] : 0;//屠宰
        $die_num = isset($input['die_num']) ? $input['die_num'] : 0;//死亡
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售
        $sick_sell_num = isset($input['sick_sell_num']) ? $input['sick_sell_num'] : 0;//病残销售
        $useless_num = isset($input['useless_num']) ? $input['useless_num'] : 0;//无产能
        $reduce_num = $sick_num + $cull_num + $die_num + $sell_num + $sick_sell_num + $useless_num;
        if($reduce_num > $survival + $add_number)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '种鸽';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $sick_name = '病残率';
            $sick_data = $early_model->getDataByName($sick_name);
            $sick_s_pigeon = $sick_data->s_pigeon;
            $is_anomaly = 0;
            if($sick_s_pigeon)
            {
                //计算病残率
                $sick_rate = round($sick_num * 100 / $survival, 2) ;
                if($sick_rate > $sick_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'病残率异常,';
                }
            }else
                $sick_rate = 0;
            $die_name = '死亡率';
            $die_data = $early_model->getDataByName($die_name);
            $die_s_pigeon = $die_data->s_pigeon;
            if($die_s_pigeon){
                //计算死亡率
                $die_rate = round($die_num * 100 / $survival, 2) ;
                if($die_rate > $die_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'死亡率异常,';
                }
            }else
                $die_rate = 0;
            $inability_name = '无产能';
            $inability_data = $early_model->getDataByName($inability_name);
            //计算年
            $year = ceil($age_day / 365);
            if($year == 1)
                $inability_s_pigeon = $inability_data->one_year;
            elseif ($year == 2)
                $inability_s_pigeon = $inability_data->two_year;
            elseif ($year == 3)
                $inability_s_pigeon = $inability_data->three_year;
            elseif ($year == 4)
                $inability_s_pigeon = $inability_data->four_year;
            elseif ($year == 5)
                $inability_s_pigeon = $inability_data->five_year;
            else
                $inability_s_pigeon = $inability_data->five_year;
            $inability_num = $useless_num;
            if($inability_s_pigeon){
                //计算死亡率
                $inability_rate = round($inability_num * 100 / $survival, 2) ;
                if($inability_rate > $inability_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无产能异常,';
                }
            }else
                $inability_rate = 0;
            if(($survival + $add_number - $reduce_num) < 2 ){
                //种鸽数量低于2只 异常
                $is_anomaly = 1;
                $cause = $cause.'种鸽需补足,';

            }else
                $inability_rate = 0;
            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //种鸽销售总数
        $sell_total = $sick_sell_num + $sell_num;
        //种鸽改变数
        $pigeon_change_number = $add_number - $reduce_num;
        $change_type = 'pigeon';

        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total,
            $change_type, $pigeon_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }

        if($add_number > 0) {
            $add_data = $cage_add_model->addPigeon($user_id, $buy_add, $breed_add, $buy_in, $nest_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            //$reduce_num = 0;
            //$reduce_id = 0;
            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {
            $reduce_data = $cage_reduce_model->reducePigeon($user_id, $sick_num, $cull_num, $die_num,
                $sell_num, $sick_sell_num, $useless_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }
//            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
//                0, 0, $reduce_num, $reduce_data['reduce_id']);
//            if($log_data['code'] != 20000)
//                return response()->json($log_data);
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--童鸽
     * @param Request $request
     * @return mixed
     */
    public function childData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $survival = isset($input['survival']) ? $input['survival'] : 1; //存栏

        $age_day =  isset($input['age_day']) ? $input['age_day'] : 0;//鸽笼日龄
        $breed_day = isset($input['hatch_day']) ? $input['hatch_day'] : 0;//选育日龄
        if($age_day != $breed_day )
            return response()->json(['code'=>60000, 'msg'=>'选育日龄与当前存栏日龄不同',  'data'=>[]]);

        $breed_add = isset($input['breed_add']) ? $input['breed_add'] : 0;//选育数量
        $add_number = $breed_add;

        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//病残淘汰
        $cull_num = isset($input['cull_num']) ? $input['cull_num'] : 0;//屠宰
        $die_num = isset($input['die_num']) ? $input['die_num'] : 0;//死亡
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售
        $sick_sell_num = isset($input['sick_sell_num']) ? $input['sick_sell_num'] : 0;//病残销售
        $brood_num = isset($input['brood_num']) ? $input['brood_num'] : 0;//转入育雏室
        $reduce_num = $sick_num + $cull_num + $die_num + $sell_num + $sick_sell_num + $brood_num;
        if($reduce_num > $survival + $add_number)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '童鸽';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $sick_name = '病残率';
            $sick_data = $early_model->getDataByName($sick_name);
            $sick_s_pigeon = $sick_data->s_pigeon;
            $is_anomaly = 0;
            if($sick_s_pigeon)
            {
                //计算病残率
                $sick_rate = round($sick_num * 100 / $survival, 2) ;
                if($sick_rate > $sick_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'病残率异常,';
                }
            }else
                $sick_rate = 0;
            $die_name = '死亡率';
            $die_data = $early_model->getDataByName($die_name);
            $die_s_pigeon = $die_data->s_pigeon;
            if($die_s_pigeon){
                //计算死亡率
                $die_rate = round($die_num * 100 / $survival, 2) ;
                if($die_rate > $die_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'死亡率异常,';
                }
            }else
                $die_rate = 0;
            $inability_name = '无产能';
            $inability_data = $early_model->getDataByName($inability_name);
            //计算年
            $year = ceil($age_day / 365);
            if($year == 1)
                $inability_s_pigeon = $inability_data->one_year;
            elseif ($year == 2)
                $inability_s_pigeon = $inability_data->two_year;
            elseif ($year == 3)
                $inability_s_pigeon = $inability_data->three_year;
            elseif ($year == 4)
                $inability_s_pigeon = $inability_data->four_year;
            elseif ($year == 5)
                $inability_s_pigeon = $inability_data->five_year;
            else
                $inability_s_pigeon = $inability_data->five_year;
            $inability_num = 0;
            if($inability_s_pigeon){
                //计算死亡率
                $inability_rate = round($inability_num * 100 / $survival, 2) ;
                if($inability_rate > $inability_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无产能异常,种鸽需补足';
                }
            }else
                $inability_rate = 0;

            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //童鸽销售总数
        $sell_total = $sick_sell_num + $sell_num;
        //童鸽改变数
        $pigeon_change_number = $add_number - $reduce_num;
        $change_type = 'child';

        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total,
            $change_type, $pigeon_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }

        if($add_number > 0) {
            $add_data = $cage_add_model->addChild($user_id, $breed_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            //$reduce_num = 0;
            //$reduce_id = 0;
            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {
            $reduce_data = $cage_reduce_model->reduceChild($user_id, $sick_num, $cull_num, $die_num,
                $sell_num, $sick_sell_num, $brood_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }
//            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
//                0, 0, $reduce_num, $reduce_data['reduce_id']);
//            if($log_data['code'] != 20000)
//                return response()->json($log_data);
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--育雏仓
     * @param Request $request
     * @return mixed
     */
    public function broodData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $survival = isset($input['survival']) ? $input['survival'] : 1; //存栏
        $age_day =  isset($input['age_day']) ? $input['age_day'] : 0;//鸽笼日龄
        $yield_add = isset($input['yield_add']) ? $input['yield_add'] : 0;//生产仓转入
        $add_number = $yield_add;

        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//病残淘汰
        $cull_num = isset($input['cull_num']) ? $input['cull_num'] : 0;//屠宰
        $die_num = isset($input['die_num']) ? $input['die_num'] : 0;//死亡
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售
        $sick_sell_num = isset($input['sick_sell_num']) ? $input['sick_sell_num'] : 0;//病残销售
        $brood_num = isset($input['brood_num']) ? $input['brood_num'] : 0;//转入育雏室
        $reduce_num = $sick_num + $cull_num + $die_num + $sell_num + $sick_sell_num + $brood_num;
        if($reduce_num > $survival + $add_number)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '童鸽';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $sick_name = '病残率';
            $sick_data = $early_model->getDataByName($sick_name);
            $sick_s_pigeon = $sick_data->s_pigeon;
            $is_anomaly = 0;
            if($sick_s_pigeon)
            {
                //计算病残率
                $sick_rate = round($sick_num * 100 / $survival, 2) ;
                if($sick_rate > $sick_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'病残率异常,';
                }
            }else
                $sick_rate = 0;
            $die_name = '死亡率';
            $die_data = $early_model->getDataByName($die_name);
            $die_s_pigeon = $die_data->s_pigeon;
            if($die_s_pigeon){
                //计算死亡率
                $die_rate = round($die_num * 100 / $survival, 2) ;
                if($die_rate > $die_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'死亡率异常,';
                }
            }else
                $die_rate = 0;
            $inability_name = '无产能';
            $inability_data = $early_model->getDataByName($inability_name);
            //计算年
            $year = ceil($age_day / 365);
            if($year == 1)
                $inability_s_pigeon = $inability_data->one_year;
            elseif ($year == 2)
                $inability_s_pigeon = $inability_data->two_year;
            elseif ($year == 3)
                $inability_s_pigeon = $inability_data->three_year;
            elseif ($year == 4)
                $inability_s_pigeon = $inability_data->four_year;
            elseif ($year == 5)
                $inability_s_pigeon = $inability_data->five_year;
            else
                $inability_s_pigeon = $inability_data->five_year;
            $inability_num = 0;
            if($inability_s_pigeon){
                //计算死亡率
                $inability_rate = round($inability_num * 100 / $survival, 2) ;
                if($inability_rate > $inability_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无产能异常,种鸽需补足';
                }
            }else
                $inability_rate = 0;

            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //童鸽销售总数
        $sell_total = $sick_sell_num + $sell_num;
        //童鸽改变数
        $pigeon_change_number = $add_number - $reduce_num;
        $change_type = 'child';

        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total,
            $change_type, $pigeon_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }

        if($add_number > 0) {
            $add_data = $cage_add_model->addChild($user_id, $yield_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            //$reduce_num = 0;
            //$reduce_id = 0;
            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {
            $reduce_data = $cage_reduce_model->reduceChild($user_id, $sick_num, $cull_num, $die_num,
                $sell_num, $sick_sell_num, $brood_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--鸽蛋
     * @param Request $request
     * @return mixed
     */
    public function eggData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $survival = isset($input['survival']) ? $input['survival'] : 0; //存栏

        $egg_add = isset($input['egg_add']) ? $input['egg_add'] : 0;//鸽蛋新增
        $spell_add = isset($input['spell_add']) ? $input['spell_add'] : 0;//拼窝
        $add_number = $egg_add + $spell_add;

        $damaged_num = isset($input['damaged_num']) ? $input['damaged_num'] : 0;//破损
        $bad_num = isset($input['bad_num']) ? $input['bad_num'] : 0;//臭蛋
        $to_num = isset($input['to_num']) ? $input['to_num'] : 0;//转入孵化机
        $imperfect_num = isset($input['imperfect_num']) ? $input['imperfect_num'] : 0;//残次蛋
        $clear_egg = isset($input['clear_egg']) ? $input['clear_egg'] : 0;//无精蛋
        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//死精蛋
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售

        $reduce_num = $damaged_num + $bad_num + $to_num + $imperfect_num + $clear_egg + $sick_num + $sell_num;
        if($reduce_num > $survival + $add_number)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '鸽蛋';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $inability_name = '病残率';
            $inability_data = $early_model->getDataByName($inability_name);
            $inability_b_egg = $inability_data->b_egg;
            $is_anomaly = 0;
            if($inability_b_egg){
                //计算无精蛋率
                $inability_rate = round($clear_egg * 100 / $survival, 2) ;
                if($inability_rate > $inability_b_egg){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无精蛋异常';
                }
            }else
                $inability_rate = 0;

            $die_num = 0;
            $die_rate = 0;
            $sick_rate = 0;
            $inability_num = 0;
            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //童鸽销售总数
        $sell_total = $sell_num;
        //童鸽改变数
        $pigeon_change_number = $add_number - $reduce_num;
        $change_type = 'egg';

        $cage_data = $cage_model->editCage($cage_id, 0, 0, 0, 0,
            $change_type, $pigeon_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }

        if($add_number > 0) {
            $add_data = $cage_add_model->addEgg($user_id, $egg_add, $spell_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {

            $reduce_data = $cage_reduce_model->reduceEgg($user_id, $damaged_num, $bad_num, $to_num,
                $imperfect_num, $clear_egg, $sick_num, $sell_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }

//            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
//                0, 0, $reduce_num, $reduce_data['reduce_id']);
//            if($log_data['code'] != 20000)
//                return response()->json($log_data);
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--飞棚仓-青年鸽
     * @param Request $request
     * @return mixed
     */
    public function youngData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : 0; //用户ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $type_name = isset($input['type_name']) ? $input['type_name'] : 0; //类型名字
        $anomaly_date = date('Y-m-d',time());

        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_model = new Cage();
        $cage_id = $cage_model->getCageID($block_id);//鸽笼ID
        if(empty($cage_id)) return response()->json(['code'=>40000, 'msg'=>'数据异常, 录入失败',  'data'=>[]]);
        $survival = isset($input['survival']) ? $input['survival'] : 1;  //存栏

        $brood_add = isset($input['brood_add']) ? $input['brood_add'] : 0;//育雏仓转入
        $switch_add = isset($input['switch_add']) ? $input['switch_add'] : 0;//转仓
        $yield_add = isset($input['yield_add']) ? $input['yield_add'] : 0;//生产仓转入

        $age_day =  isset($input['age_day']) ? $input['age_day'] : 0;//鸽笼日龄
//        $hatch_day = isset($input['brood_day']) ? $input['brood_day'] : 0;//育雏仓转入日龄
//        $switch_day = isset($input['switch_day']) ? $input['switch_day'] : 0;//转仓日龄
//        $yield_day = isset($input['yield_day']) ? $input['yield_day'] : 0;//生产仓转入日龄
//
//        if($age_day != $hatch_day || $age_day != $switch_day || $age_day != $yield_day)
//            return response()->json(['code'=>60000, 'msg'=>'日龄与当前存栏日龄不同',  'data'=>[]]);
        $add_number = $brood_add + $switch_add + $yield_add;

        $sick_num = isset($input['sick_num']) ? $input['sick_num'] : 0;//病残淘汰
        $cull_num = isset($input['cull_num']) ? $input['cull_num'] : 0;//屠宰
        $die_num = isset($input['die_num']) ? $input['die_num'] : 0;//死亡
        $sell_num = isset($input['sell_num']) ? $input['sell_num'] : 0;//出售
        $yield_num = isset($input['yield_num']) ? $input['yield_num'] : 0;//补入生产仓
        $reduce_num = $sick_num + $cull_num + $die_num + $sell_num + $yield_num;
        if($reduce_num > $survival + $add_number)
            return response()->json(['code'=>60000, 'msg'=>'数据异常，减少数大于存栏数',  'data'=>[]]);

        $category_name = '青年鸽';
        $model_category = new CageCategory();
        $category_id = $model_category->getCageCategoryId($category_name);
        if($survival != 0 )
        {
            $cause = '';
            $early_model = new EarlyWarning();
            $sick_name = '病残率';
            $sick_data = $early_model->getDataByName($sick_name);
            $sick_s_pigeon = $sick_data->s_pigeon;
            $is_anomaly = 0;
            if($sick_s_pigeon)
            {
                //计算病残率
                $sick_rate = round($sick_num * 100 / $survival, 2) ;
                if($sick_rate > $sick_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'病残率异常,';
                }
            }else
                $sick_rate = 0;
            $die_name = '死亡率';
            $die_data = $early_model->getDataByName($die_name);
            $die_s_pigeon = $die_data->s_pigeon;
            if($die_s_pigeon){
                //计算死亡率
                $die_rate = round($die_num * 100 / $survival, 2) ;
                if($die_rate > $die_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'死亡率异常,';
                }
            }else
                $die_rate = 0;
            $inability_name = '无产能';
            $inability_data = $early_model->getDataByName($inability_name);
            //计算年
            $year = ceil($age_day / 365);
            if($year == 1)
                $inability_s_pigeon = $inability_data->one_year;
            elseif ($year == 2)
                $inability_s_pigeon = $inability_data->two_year;
            elseif ($year == 3)
                $inability_s_pigeon = $inability_data->three_year;
            elseif ($year == 4)
                $inability_s_pigeon = $inability_data->four_year;
            elseif ($year == 5)
                $inability_s_pigeon = $inability_data->five_year;
            else
                $inability_s_pigeon = $inability_data->five_year;
            $inability_num = 0;
            if($inability_s_pigeon){
                //计算死亡率
                $inability_rate = round($inability_num * 100 / $survival, 2) ;
                if($inability_rate > $inability_s_pigeon){
                    //死亡率大于 预警设置死亡率
                    $is_anomaly = 1;
                    $cause = $cause.'无产能异常';
                }
            }else
                $inability_rate = 0;

            DB::beginTransaction();
            if($is_anomaly){
                $anomaly_model = new Anomaly();
                $sick_anomaly = $anomaly_model->addData($user_id, $factory_id, $type_name, $type_id,
                    $block_id, $cage_id, $category_id, $category_name,
                    $die_num, $die_rate, $sick_num, $sick_rate,
                    $inability_num, $inability_rate, $cause, $anomaly_date);
            }
        } else{
            DB::beginTransaction();
        }
        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        //修改鸽笼总数据
        //青年鸽销售总数
        $sell_total = $sell_num;
        //青年鸽数
        $squab_change_number = $add_number - $reduce_num;
        $change_type = 'youth';
        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total, $change_type, $squab_change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }
        if($add_number > 0) {
            $add_data = $cage_add_model->addYouth($user_id, $brood_add, $switch_add, $yield_add);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
            //$reduce_num = 0;
            //$reduce_id = 0;
            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                $add_number, $add_data['add_id'], 0, 0);
            if($log_data['code'] != 20000)
                return response()->json($log_data);
        }
        if($reduce_num > 0) {
            $reduce_data = $cage_reduce_model->reduceYouth($user_id, $sick_num, $cull_num, $die_num,
                $sell_num, $yield_num);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
            //$add_number = 0;
            //$add_id = 0;
            if(!isset($log_data['log_id'])){
                $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
                    0, 0, $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }else{
                $log_data = $cage_log_model->editLog($log_data['log_id'], $user_id, $cage_id, $category_id,
                    $add_number, $add_data['add_id'], $reduce_num, $reduce_data['reduce_id']);
                if($log_data['code'] != 20000)
                    return response()->json($log_data);
            }
//            $log_data = $cage_log_model->addLog($user_id, $cage_id, $category_id,
//                0, 0, $reduce_num, $reduce_data['reduce_id']);
//            if($log_data['code'] != 20000)
//                return response()->json($log_data);
        }
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
        return response()->json($return_data);
    }

    /**
     * 数据录入--乳鸽查询鸽笼详情
     * @param Request $request
     * @return mixed
     */
    public function getCageUser(Request $request)
    {
        $input = $request->all();
        //$category_id = isset($input['category_id']) ? $input['category_id'] : 0; //仓库类型
        //$category_str = isset($input['category_str']) ? $input['category_str'] : ''; //squab, pigeon, egg, child, youth
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0;
        if(empty($block_id)) return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);

        $token = $request->header('token');
        if(empty($token)) return response()->json(['code'=>50000,'msg'=>'用户未登录',  'data'=>[]]);
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            return response()->json(['code'=>50000,'msg'=>'你的登录信息已失效',  'data'=>[]]);
        }
        //查询饲养员 护工
        $user_factory_model = new UserFactory();
        $user_info = $user_factory_model->getUsersInfo($block_id);
        $exists_user = 0 ;
        $user_id = $data['id'];
        foreach ($user_info as $value){
            if($user_id == $value->user_id ||  $user_id == 1)
                $exists_user = 1;
        }
        if(empty($exists_user))
            return response()->json(['code'=>40000, 'msg'=>'你不是这个仓号的管理员或者护工',  'data'=>[]]);
        $return_data['list'] = $user_info;
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$return_data]);

    }

    /**
     * 数据录入--其他
     * @param Request $request
     * @return mixed
     */
    public function otherData(Request $request)
    {
        $input = $request->all();
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $dung_add = isset($input['dung_add']) ? $input['dung_add'] : 0;//鸽子粪
        $waste_add = isset($input['waste_add']) ? $input['waste_add'] : 0;//废料
        $model_block = new Block();
        $old_data = $model_block->getBlockInfo($block_id);
        $dung = $dung_add + $old_data->dung;
        $waste = $waste_add + $old_data->waste;
        $block_data = $model_block->editData($block_id, $dung, $waste);
        return response()->json($block_data);
    }

    /**
     * 数据异常列表
     * @param Request $request
     * @return mixed
     */
    public function anomalyData(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : 0; //开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : 0;//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : 0; //厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : 0; //类型ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0; //仓号ID
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : 0; //鸽笼ID
        $category_id = isset($input['category_id']) ? $input['category_id'] : ''; //鸽子种类
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $model_anomaly = new Anomaly();
        $return_data = $model_anomaly->getList($start_time, $end_time, $factory_id, $block_id, $cage_id, $category_id, $type_id, $page_size);
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$return_data]);
    }

    /**
     * 数据异常处理状态修改
     * @param Request $request
     * @return mixed
     */
    public function editAnomaly(Request $request)
    {
        $input = $request->all();
        $anomaly_id = isset($input['anomaly_id']) ? $input['anomaly_id'] : 0; //异常ID
        $anomaly_status = isset($input['anomaly_status']) ? $input['anomaly_status'] : 0; //异常状态
        if(empty($anomaly_status))
            $anomaly_status = 1;
        else
            return response()->json(['code'=>40000, 'msg'=>'已处理不能修改',  'data'=>[]]);
        $model_anomaly = new Anomaly();
        $return_data = $model_anomaly->editAnomaly($anomaly_id, $anomaly_status);
        return response()->json($return_data);
    }

    /**
     * 获取全部鸽子种类
     * @param Request $request
     * @return mixed
     */
    public function AllCageType(Request $request)
    {
        //获取全部鸽子种类
        $model_category = new CageCategory();
        $category_data = $model_category->getAllCategory();
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$category_data]);

    }

    /**
     * 录入数据列表
     * @param Request $request
     * @return mixed
     */
    public function dataLogList(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//鸽子类型
        $page_size = isset($input['page_size']) ? $input['page_size'] : 5; //每页条数
        $data_time = isset($input['data_time']) ? strtotime($input['data_time']) : time();
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        $date_y = date('Y', $data_time);
        $date_m = date('m', $data_time);
        $date_d = date('d', $data_time);
        $cage_log_model = new CageLog();
        $list_data = $cage_log_model->getEntryList($user_id, $type_id, $factory_id, $block_type, $block_id, $date_y, $date_m, $date_d, $page_size);
        $user_factory_model = new UserFactory();
        foreach ($list_data['list'] as $v)
        {
            //return [$v->block_id];
            $user_data = $user_factory_model->getUsersInfo($v->block_id);
            $exits_breeder = 0;
            $exits_carer = 0;
            foreach ($user_data as  $value){
                if($value->role_name == '饲养员')
                {
                    $exits_breeder = 1;
                    $v->breeder_id = $value->user_id;
                    $v->breeder_name = $value->user_name;
                }
                if($value->role_name == '护工')
                {
                    $exits_carer = 1;
                    $v->carer_id = $value->user_id;
                    $v->carer_name = $value->user_name;
                }
            }
            if(empty($exits_breeder)){
                if(isset($user_data[0]))
                {
                    $v->breeder_id = $user_data[0]->user_id;
                    $v->breeder_name = $user_data[0]->user_name;
                }else{
                    $v->breeder_id = '';
                    $v->breeder_name = '';
                }
            } else{
                $v->breeder_id = '';
                $v->breeder_name = '';
            }
            if(empty($exits_carer))
            {
                if(isset($user_data[1]))
                {
                    $v->carer_id = $user_data[1]->user_id;
                    $v->carer_name = $user_data[1]->user_name;
                }else {
                    $v->carer_id = '';
                    $v->carer_name = '';
                }
            }
            else{
                $v->breeder_id = '';
                $v->breeder_name = '';
            }
        }
        $return_data = ['code'=>20000, 'msg'=>'请求成功',  'data'=>$list_data];
        return response()->json($return_data);
    }

    /**
     * 录入数据列表
     * @param Request $request
     * @return mixed
     */
    public function editLogData(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $data_time = isset($input['data_time']) ? $input['data_time'] : date('Y-m-d', time());//时间
        $log_id = isset($input['log_id']) ? $input['log_id'] : '';//日志ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//鸽子类型
        $cage_id = isset($input['cage_id']) ? $input['cage_id'] : '';//鸽笼ID
        $add_id = isset($input['add_id']) ? $input['add_id'] : '';//增加项ID
        $reduce_id = isset($input['reduce_id']) ? $input['reduce_id'] : '';//减少项ID
        //增加
        $hatch = isset($input['hatch']) ? $input['hatch'] : 0;//孵化转入数/生产仓转入
        $brood = isset($input['brood']) ? $input['brood'] : 0;//育雏转入数
        $conesting = isset($input['conesting']) ? $input['conesting'] : 0;//并窝转入数
        $added_out = isset($input['added_out']) ? $input['added_out'] : 0;//新增外购数
        $added_wit = isset($input['added_wit']) ? $input['added_wit'] : 0;//新增自繁数/鸽蛋新增入库
        $breeding = isset($input['breeding']) ? $input['breeding'] : 0;//选育数
        $replenish = isset($input['replenish']) ? $input['replenish'] : 0;//补仓数
        $spell = isset($input['spell']) ? $input['spell'] : 0;//鸽蛋拼窝
        $add_number = $hatch + $brood + $conesting + $added_out + $added_wit + $breeding + $replenish + $spell;

        //减少
        $disease = isset($input['disease']) ? $input['disease'] : 0;//病残数/残次蛋数
        $massacre = isset($input['massacre']) ? $input['massacre'] : 0;//屠宰数/破损死亡数/臭蛋
        $death = isset($input['death']) ? $input['death'] : 0;//死亡数/臭蛋
        $sell = isset($input['sell']) ? $input['sell'] : 0;//出售数
        $disease_sell = isset($input['disease_sell']) ? $input['disease_sell'] : 0;//病残销售数/无精蛋
        $getout = isset($input['getout']) ? $input['getout'] : 0;//转出数
        $shift_to = isset($input['shift_to']) ? $input['shift_to'] : 0;//转入孵化\飞鹏\育雏\种鸽\孵化机
        $dead_eggs = isset($input['dead_eggs']) ? $input['dead_eggs'] : 0;//死精蛋
        $useless = isset($input['useless']) ? $input['useless'] : 0;//无产能,
        $reduce_number = $disease + $massacre + $death + $sell + $disease_sell + $getout + $shift_to + $dead_eggs + $useless;

        //原数据增加
        $old_hatch = isset($input['old_hatch']) ? $input['old_hatch'] : 0;//原数据 孵化转入数/生产仓转入
        $old_brood = isset($input['old_brood']) ? $input['old_brood'] : 0;//原数据 育雏转入数
        $old_conesting = isset($input['old_conesting']) ? $input['old_conesting'] : 0;//原数据 并窝转入数
        $old_added_out = isset($input['old_added_out']) ? $input['old_added_out'] : 0;//原数据 新增外购数
        $old_added_wit = isset($input['old_added_wit']) ? $input['old_added_wit'] : 0;//原数据 新增自繁数/鸽蛋新增入库
        $old_breeding = isset($input['old_breeding']) ? $input['old_breeding'] : 0;//原数据 选育数
        $old_replenish = isset($input['old_replenish']) ? $input['old_replenish'] : 0;//原数据 补仓数
        $old_spell = isset($input['old_spell']) ? $input['old_spell'] : 0;//原数据 鸽蛋拼窝
        $old_add_number = $old_hatch + $old_brood + $old_conesting + $old_added_out + $old_added_wit + $old_breeding + $old_replenish + $old_spell;

        //原数据减少
        $old_disease = isset($input['old_disease']) ? $input['old_disease'] : 0;//原数据 病残数/残次蛋数
        $old_massacre = isset($input['old_massacre']) ? $input['old_massacre'] : 0;//原数据 屠宰数/破损死亡数/臭蛋
        $old_death = isset($input['old_death']) ? $input['old_death'] : 0;//原数据 死亡数/臭蛋
        $old_sell = isset($input['old_sell']) ? $input['old_sell'] : 0;//原数据 出售数
        $old_disease_sell = isset($input['old_disease_sell']) ? $input['old_disease_sell'] : 0;//原数据 病残销售数/无精蛋
        $old_getout = isset($input['old_getout']) ? $input['old_getout'] : 0;//原数据 转出数
        $old_shift_to = isset($input['old_shift_to']) ? $input['old_shift_to'] : 0;//原数据 转入孵化\飞鹏\育雏\种鸽\孵化机
        $old_dead_eggs = isset($input['old_dead_eggs']) ? $input['old_dead_eggs'] : 0;//原数据 死精蛋
        $old_useless = isset($input['old_useless']) ? $input['old_useless'] : 0;//原数据 无产能,
        $old_reduce_number = $old_disease + $old_massacre + $old_death + $old_sell + $old_disease_sell + $old_getout + $old_shift_to + $old_dead_eggs + $old_useless;

        //鸽笼数据
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $waste = isset($input['waste']) ? $input['waste'] : 0;//废料
        $dung = isset($input['dung']) ? $input['dung'] : 0;//鸽子粪

        $old_waste = isset($input['old_waste']) ? $input['old_waste'] : '';//原数据 废料
        $old_dung = isset($input['old_dung']) ? $input['old_dung'] : '';//原数据 鸽子粪

        $cage_model = new Cage();
        $cage_add_model = new CageAdd();
        $cage_log_model = new CageLog();
        $cage_reduce_model = new CageReduce();
        $add_data = array();
        DB::beginTransaction();
        //增加项更新
        if($add_id == 0 && $add_number) {
            $add_data = $cage_add_model->addData($user_id, $hatch, $brood, $conesting, $added_out, $added_wit, $breeding, $replenish, $spell);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
        }
        if($add_id > 0) {
            $add_data = $cage_add_model->editData($add_id, $user_id, $hatch, $brood, $conesting, $added_out, $added_wit, $breeding, $replenish, $spell);
            if($add_data['code'] != 20000)
                return response()->json($add_data);
        }
        $add_id = isset($add_data['data']['add_id']) ? $add_data['data']['add_id'] : 0;
        //减少项更新
        if($reduce_id == 0 && $reduce_number) {
            $reduce_data = $cage_reduce_model->addData($user_id, $disease, $massacre, $death, $sell, $disease_sell, $getout, $shift_to, $dead_eggs, $useless);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
        }
        if($reduce_id > 0) {
            $reduce_data = $cage_reduce_model->editData($reduce_id, $user_id, $disease, $massacre, $death, $sell, $disease_sell, $getout, $shift_to, $dead_eggs, $useless);
            if($reduce_data['code'] != 20000)
                return response()->json($reduce_data);
        }
        $reduce_id = isset($reduce_data['data']['reduce_id']) ? $reduce_data['data']['reduce_id'] : 0;
        //其他数据鸽笼更新
        $change_type = '';
        if($type_id == 1){
            $change_type = 'pigeon';
        }
        if($type_id == 2){
            $change_type = 'egg';
        }
        if($type_id == 3) {
            $change_type = 'squab';
        }
        if($type_id == 4){
            $change_type = 'child';
        }
        if($type_id == 5){
            $change_type = 'youth';
        }

        $sick_num = $disease - $old_disease;
        $cull_num = $massacre - $old_massacre;
        $die_num = $death - $old_death;
        $sell_total = $sell + $disease_sell - $old_sell - $old_disease_sell;
        $change_number = $add_number - $old_add_number - ($reduce_number - $old_reduce_number);
        //判断存栏数量 是否小于减少数量

        $cage_data = $cage_model->editCage($cage_id, $sick_num, $cull_num, $die_num, $sell_total, $change_type, $change_number);
        if($cage_data['code'] != 20000 ){
            return response()->json($cage_data);
        }
        $model_block = new Block();
        $old_data = $model_block->getBlockInfo($block_id);
        $dung_num = $dung - $old_dung + $old_data->dung;
        $waste_num = $waste - $old_waste + $old_data->waste;
        $block_data = $model_block->editData($block_id, $dung_num, $waste_num);
        if($block_data['code'] != 20000 ){
            return response()->json($block_data);
        }

        $log_data = $cage_log_model->editLog($log_id, $user_id, $cage_id, $type_id,
            $add_number, $add_id, $reduce_number, $reduce_id);
        if($log_data['code'] != 20000)
            return response()->json($log_data);
        DB::commit();
        $return_data = ['code'=>20000,'msg'=>'数据更新成功', 'data'=>[]];
        return response()->json($return_data);
    }
}
