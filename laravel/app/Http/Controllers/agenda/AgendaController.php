<?php


namespace App\Http\Controllers\agenda;


use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Cage;
use App\Models\CageCategory;
use App\Models\CageLog;
use App\Models\Defusing;
use App\Models\Disinfect;
use App\Models\Drugs;
use App\Models\DrugsLog;
use App\Models\Grain;
use App\Models\GrainLog;
use App\Models\GrainReceive;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\MedRecord;
use App\Models\MedVaccine;
use App\Models\Rule;
use App\Models\RuleRange;
use App\Models\RuleType;
use App\Models\SaleType;
use App\Models\UserFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{

    /**
     * 待办--无害化处理-数据录入-查询数量
     * @param Request $request
     * @return mixed
     */
    public function getAllData(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $date_time = isset($input['date_time']) ? strtotime($input['date_time']) : '';
        if(empty($factory_id) || empty($date_time))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $usage_y = date('Y', $date_time);
        $usage_m = date('m', $date_time);
        $usage_d = date('d', $date_time);

        //获取全部鸽笼编号
        $model_cage = new Cage();
        $cage_data  = $model_cage->getCages($factory_id);
        $cage_ids = array();
        foreach ($cage_data as $v)
        {
            $cage_ids[] = $v->id;
        }
        //获取全部鸽子种类
        $model_category = new CageCategory();
        $category_data = $model_category->getAllCategory();
        $model_cage_log = new CageLog();
        $return_data = array();
        foreach ($category_data as  $v)
        {
            $return_data[$v->category_id]['type_id'] = $v->category_id;
            $return_data[$v->category_id]['type_name'] = $v->category_name;
            $return_data[$v->category_id]['sum_amount'] = $model_cage_log->getData($usage_y, $usage_m, $usage_d, $v->category_id, $cage_ids);
        }
        return response()->json(['code'=>20000, 'msg'=>'',  'data'=>$return_data]);
    }


    /**
     * 待办--无害化处理-数据录入
     * @param Request $request
     * @return mixed
     */
    public function entryHarmless(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : ''; //厂区ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';//记录时间
        //种鸽处理
        $p_category_id = isset($input['p_category_id']) ? $input['p_category_id'] : '';//类型ID
        $p_death_amount = isset($input['p_death_amount']) ? $input['p_death_amount'] : '';//死亡数
        $p_handle_number = isset($input['p_handle_number']) ? $input['p_handle_number'] : '';//处理数
        $p_submit_remarks = isset($input['p_submit_remarks']) ? $input['p_submit_remarks'] : '';//提交备注
        //鸽蛋处理
        $e_category_id = isset($input['e_category_id']) ? $input['e_category_id'] : '';//类型ID
        $e_death_amount = isset($input['e_death_amount']) ? $input['e_death_amount'] : '';//死亡数
        $e_handle_number = isset($input['e_handle_number']) ? $input['e_handle_number'] : '';//处理数
        $e_submit_remarks = isset($input['e_submit_remarks']) ? $input['e_submit_remarks'] : '';//提交备注
        //乳鸽处理
        $s_category_id = isset($input['s_category_id']) ? $input['s_category_id'] : '';//类型ID
        $s_death_amount = isset($input['s_death_amount']) ? $input['s_death_amount'] : '';//死亡数
        $s_handle_number = isset($input['s_handle_number']) ? $input['s_handle_number'] : '';//处理数
        $s_submit_remarks = isset($input['s_submit_remarks']) ? $input['s_submit_remarks'] : '';//提交备注
        //童鸽处理
        $c_category_id = isset($input['c_category_id']) ? $input['c_category_id'] : '';//类型ID
        $c_death_amount = isset($input['c_death_amount']) ? $input['c_death_amount'] : '';//死亡数
        $c_handle_number = isset($input['c_handle_number']) ? $input['c_handle_number'] : '';//处理数
        $c_submit_remarks = isset($input['c_submit_remarks']) ? $input['c_submit_remarks'] : '';//提交备注
        //青年鸽处理
        $y_category_id = isset($input['y_category_id']) ? $input['y_category_id'] : '';//类型ID
        $y_death_amount = isset($input['y_death_amount']) ? $input['y_death_amount'] : '';//死亡数
        $y_handle_number = isset($input['y_handle_number']) ? $input['y_handle_number'] : '';//处理数
        $y_submit_remarks = isset($input['y_submit_remarks']) ? $input['y_submit_remarks'] : '';//提交备注

        DB::beginTransaction();
        //种鸽数据
        $p_data = $this->addHarmlessData($factory_id, $user_id, $date_time, $p_category_id,
            $p_death_amount, $p_handle_number, $p_submit_remarks);
        if($p_data['code'] != 20000){
            DB::rollBack();
            return response()->json($p_data);
        }
        //鸽蛋数据
        $e_data = $this->addHarmlessData($factory_id, $user_id, $date_time, $e_category_id,
            $e_death_amount, $e_handle_number, $e_submit_remarks);
        if($e_data['code'] != 20000){
            DB::rollBack();
            return response()->json($e_data);
        }
        //乳鸽数据
        $s_data = $this->addHarmlessData($factory_id, $user_id, $date_time, $s_category_id,
            $s_death_amount, $s_handle_number, $s_submit_remarks);
        if($s_data['code'] != 20000){
            DB::rollBack();
            return response()->json($s_data);
        }
        //童鸽数据
        $c_data = $this->addHarmlessData($factory_id, $user_id, $date_time, $c_category_id,
            $c_death_amount, $c_handle_number, $c_submit_remarks);
        if($c_data['code'] != 20000){
            DB::rollBack();
            return response()->json($c_data);
        }
        //鸽蛋数据
        $y_data = $this->addHarmlessData($factory_id, $user_id, $date_time, $y_category_id,
            $y_death_amount, $y_handle_number, $y_submit_remarks);
        if($y_data['code'] != 20000){
            DB::rollBack();
            return response()->json($y_data);
        }
        DB::commit();
        return response()->json($y_data);
    }

    /**
     * 待办--无害化处理-数据录入
     * @param $factory_id
     * @param $user_id
     * @param $date_time
     * @param $category_id
     * @param $death_amount
     * @param $handle_number
     * @param $submit_remarks
     * @return mixed
     */
    public function addHarmlessData($factory_id, $user_id, $date_time, $category_id, $death_amount, $handle_number, $submit_remarks)
    {
        $model_defusing = new Defusing();
        $id = $model_defusing->getDataExists($factory_id, $date_time, $category_id);
        if($id)
        {
            $return_data = $model_defusing->updateData($id, $factory_id, $user_id, $date_time, $category_id,
                $death_amount, $handle_number, $submit_remarks);
        }else{
            $return_data = $model_defusing->addData($factory_id, $user_id, $date_time, $category_id,
                $death_amount, $handle_number, $submit_remarks);
        }
        return $return_data;
    }

    /**
     * 待办--无害化处理-列表
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page =  isset($input['page']) ? $input['page'] : 1;
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_defusing = new Defusing();
        $data_list = $model_defusing->getList($page_size, $factory_id, $date_time, $firm_id);
        return response()->json($data_list);
    }

    /**
     * 待办--无害化处理-编辑
     * @param Request $request
     * @return mixed
     */
    public function editData(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';
        //种鸽处理
        $category_id = isset($input['category_id']) ? $input['category_id'] : '';//类型ID
        $death_amount = isset($input['death_amount']) ? $input['death_amount'] : '';//死亡数
        $handle_number = isset($input['handle_number']) ? $input['handle_number'] : '';//处理数
        $submit_remarks = isset($input['submit_remarks']) ? $input['submit_remarks'] : '';//提交备注
        if(empty($factory_id) || empty($date_time) || empty($category_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_defusing = new Defusing();
        $return_data = $model_defusing->updateData($id, $factory_id, $user_id, $date_time, $category_id,
            $death_amount, $handle_number, $submit_remarks);
        return response()->json($return_data);
    }

    /**
     * 待办--无害化处理-删除
     * @param Request $request
     * @return mixed
     */
    public function delData(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        if(empty($id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_defusing = new Defusing();
        $return_data = $model_defusing->delData($id);
        return response()->json($return_data);
    }
    /**
     * 待办--无害化处理-审核
     * @param Request $request
     * @return mixed
     */
    public function checkData(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        $type = isset($input['type']) ? $input['type'] : '';
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//审核人ID
        $examine_remarks = isset($input['examine_remarks']) ? $input['examine_remarks'] : '';
        $model_defusing = new Defusing();
        $return_data = $model_defusing->checkData($id, $type, $user_id, $examine_remarks);
        return response()->json($return_data);
    }

    /**
     * 待办--销售--可销售数量
     * @param Request $request
     * @return mixed
     */
    public function sellData(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//类型ID
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_cage = new Cage();
        $cage_data = $model_cage->getSumData($firm_id, $type_id, $factory_id);
        $model_block = new Block();
        $block_data = $model_block->getSumWaste($firm_id, $type_id, $factory_id);
        if($block_data)
        {
            $cage_data->sum_waste = $block_data->sum_waste;
            $cage_data->sum_dung = $block_data->sum_dung;
        }
        return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$cage_data]);
    }

    /**
     * 待办--销售--录入数据
     * @param Request $request
     * @return mixed
     */
    public function entrySell(Request $request)
    {
        $input = $request->all();
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';//时间
        $user_id = isset($input['user_id']) ? $input['user_id'] : 2;//用户id
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//类型ID
        $goods_name = isset($input['goods_name']) ? $input['goods_name'] : '';//货物名字
        $specs = isset($input['specs']) ? $input['specs'] : ''; //规格
        $unit_price =  isset($input['unit_price']) ? $input['unit_price'] : 0;//单价
        $number =  isset($input['number']) ? $input['number'] : 0;//数量
        $price =  isset($input['price']) ? $input['price'] : 0;//合计金额
        $customer =  isset($input['customer']) ? $input['customer'] : '';//客户
        $pay_method =  isset($input['pay_method']) ? $input['pay_method'] : '';//收款方式
        $remarks =  isset($input['remarks']) ? $input['remarks'] : '';//备注
        if(empty($factory_id)  || empty($type_id) || empty($date_time))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_sale_type = new SaleType();
        $return_data = $model_sale_type->addData($date_time, $user_id,$factory_id,  $type_id,  $goods_name,
            $specs, $unit_price, $number, $price, $customer, $pay_method, $remarks);
        return response()->json($return_data);
    }

    /**
     * 待办--销售--列表
     * @param Request $request
     * @return mixed
     */
    public function sellList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//类型ID
        $goods_name = isset($input['goods_name']) ? $input['goods_name'] : '';//货物名字
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数

        $model_sale_type = new SaleType();
        $return_data = $model_sale_type->getList($start_time, $end_time, $factory_id, $type_id, $goods_name,  $page_size);
        return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$return_data]);
    }

    /**
     * 待办--销售--选择框内容
     * @return mixed
     */
    public function selectInfo()
    {
        $goods_name = [
            '种鸽',
            '鸽蛋',
            '乳鸽',
            '童鸽',
            '青年鸽',
            '鸽子粪',
            '废料'
        ];
        $type_name = [
            '3月龄',
            '4月龄',
            '5月龄',
            '6月龄'
        ];

        $return_data['goods_name'] = $goods_name;
        $return_data['type_name'] = $type_name;
        return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$return_data]);
    }

    /**
     * 待办--销售--编辑
     * @param Request $request
     * @return mixed
     */
    public function editSell(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';//销售ID
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';//时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//类型ID
        //$block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $goods_name = isset($input['goods_name']) ? $input['goods_name'] : '';//货物名字
        $specs = isset($input['specs']) ? $input['specs'] : ''; //规格
        $unit_price =  isset($input['unit_price']) ? $input['unit_price'] : '';//单价
        $number =  isset($input['number']) ? $input['number'] : 0;//数量
        $price =  isset($input['price']) ? $input['price'] : 0;//合计金额
        $customer =  isset($input['customer']) ? $input['customer'] : '';//客户
        $pay_method =  isset($input['pay_method']) ? $input['pay_method'] : '';//收款方式
        $remarks =  isset($input['remarks']) ? $input['remarks'] : '';//备注
        if(empty($id) || empty($factory_id)  || empty($type_id) || empty($date_time))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_sale_type = new SaleType();
        $return_data = $model_sale_type->updateData($id, $date_time, $factory_id,  $type_id,  $goods_name,
            $specs, $unit_price, $number, $price, $customer, $pay_method, $remarks);
        return response()->json($return_data);
    }

    /**
     * 待办--销售-删除
     * @param Request $request
     * @return mixed
     */
    public function delSellData(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        if(empty($id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_sale_type = new SaleType();
        $return_data = $model_sale_type->delData($id);
        return response()->json($return_data);
    }

    /**
     * 待办--粮食储藏
     * @param Request $request
     * @return mixed
     */
    public function foodList(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        //$grain_name = isset($input['grain_name']) ? $input['grain_name'] : '';//物品名字
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品id
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数

        $model_grain = new Grain();
        $return_data = $model_grain->getList($factory_id, $item_id, $page_size);
        return response()->json($return_data);
    }

    /**
     * 待办--粮食储藏--出入库
     * @param Request $request
     * @return mixed
     */
    public function enterFoodList(Request $request)
    {
        $input = $request->all();
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间日期
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        //$grain_name = isset($input['grain_name']) ? $input['grain_name'] : '';//物品名字
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品id
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $reason = isset($input['reason']) ? $input['reason'] : '';//出库原因
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数

        $model_grain = new GrainLog();
        $return_data = $model_grain->getList($record_time, $factory_id, $item_id, $type_id, $reason, $page_size);
        return response()->json($return_data);
    }

    /**
     * 待办--粮食储藏--出入库--编辑
     * @param Request $request
     * @return mixed
     */
    public function editFood(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';//数据ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间日期
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $grain_name = isset($input['grain_name']) ? $input['grain_name'] : '';//物品名字
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品id
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $production = isset($input['production']) ? $input['production'] : '';//厂家
        $number = isset($input['number']) ? $input['number'] : '';//数量
        $unit_price = isset($input['unit_price']) ? $input['unit_price'] : '';//单价
        $price = isset($input['price']) ? $input['price'] : '';//合计金额
        $supplier = isset($input['supplier']) ? $input['supplier'] : '';//供货单位
        $examiner = isset($input['examiner']) ? $input['examiner'] : '';//检验人
        $manager = isset($input['manager']) ? $input['manager'] : '';//经手人
        $reason = isset($input['reason']) ? $input['reason'] : '';//出库原因
        $borrowing = isset($input['borrowing']) ? $input['borrowing'] : '';//借用单位
        $return_time = isset($input['return_time']) ? $input['return_time'] : '';//还回时间
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注

        $model_grain = new GrainLog();
        $return_data = $model_grain->editFood($id, $user_id, $record_time, $factory_id, $item_id, $grain_name, $production, $number, $unit_price, $price, $supplier, $examiner, $manager, $type_id, $reason, $borrowing, $remarks,$return_time);
        return response()->json($return_data);
    }


    /**
     * 待办--粮食储藏--出入库--新增
     * @param Request $request
     * @return mixed
     */
    public function entryFood(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间日期
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $grain_name = isset($input['grain_name']) ? $input['grain_name'] : '';//物品名字
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品id
        $production = isset($input['production']) ? $input['production'] : '';//厂家
        $number = isset($input['number']) ? $input['number'] : '';//数量
        $unit_price = isset($input['unit_price']) ? $input['unit_price'] : '';//单价
        $price = isset($input['price']) ? $input['price'] : '';//合计金额
        $supplier = isset($input['supplier']) ? $input['supplier'] : '';//供货单位
        $examiner = isset($input['examiner']) ? $input['examiner'] : '';//检验人
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $manager = isset($input['manager']) ? $input['manager'] : '';//经手人
        $reason = isset($input['reason']) ? $input['reason'] : '';//出库原因
        $borrowing = isset($input['borrowing']) ? $input['borrowing'] : '';//借用单位
        $return_time = isset($input['return_time']) ? $input['return_time'] : '';//还回时间
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注
        $unit = isset($input['unit']) ? $input['unit'] : '';//单位

        $model_grain = new GrainLog();
        $return_data = $model_grain->addLog( $user_id, $record_time, $factory_id, $item_id, $grain_name, $production, $number, $unit_price, $price, $supplier, $examiner, $type_id, $manager, $reason, $borrowing, $remarks,$return_time, $unit);
        return response()->json($return_data);
    }

    /**
     * 待办--粮食储藏--删除
     * @param Request $request
     * @return mixed
     */
    public function delFoodData(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        if(empty($id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_grain = new GrainLog();
        $return_data = $model_grain->delData($id);
        return response()->json($return_data);
    }

    /**
     * 待办--消杀-列表
     * @param Request $request
     * @return mixed
     */
    public function disinfectList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $mode = isset($input['mode']) ? $input['mode'] : '';//消杀方式
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品ID
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_disinfect = new Disinfect();
        $return_data = $model_disinfect->getList($start_time, $end_time, $factory_id, $block_type, $block_id, $mode, $drugs_id, $firm_id,$page_size);
        return response()->json($return_data);
    }

    /**
     * 待办--消杀-录入数据
     * @param Request $request
     * @return mixed
     */
    public function entryDisinfect(Request $request)
    {
        $input = $request->all();
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        //$block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $mode = isset($input['mode']) ? $input['mode'] : '';//消杀方式
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品ID
        $number = isset($input['number']) ? $input['number'] : '';//用量
        $remarks = isset($input['remarks']) ? $input['remarks'] : ''; //备注
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $batch_number = isset($input['batch_number']) ? $input['batch_number'] : '';//生产批号

        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        DB::beginTransaction();
        $model_disinfect = new Disinfect();
        $disinfect_data = $model_disinfect->addData($record_time,  $factory_id, $block_id, $user_id, $mode, $drugs_id, $number, $remarks, $firm_id);
        $model_drugs = new Drugs();
        if($disinfect_data['code'] != 20000)
            return response()->json($disinfect_data);
            $return_data = $model_drugs->updateData($item_id, $production, $factory_id, $batch_number, $number, 2);
        DB::commit();
        return response()->json($return_data);
    }

    /**
     * 待办--消杀-编辑数据
     * @param Request $request
     * @return mixed
     */
    public function editDisinfect(Request $request)
    {
        $input = $request->all();
        $disinfect_id = isset($input['disinfect_id']) ? $input['disinfect_id'] : '';//数据ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        //$block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $mode = isset($input['mode']) ? $input['mode'] : '';//消杀方式
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品ID
        $number = isset($input['number']) ? $input['number'] : '';//用量
        $remarks = isset($input['remarks']) ? $input['remarks'] : ''; //备注
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_disinfect = new Disinfect();
        $return_data = $model_disinfect->updateData($disinfect_id, $record_time,  $factory_id, $block_id, $user_id, $mode, $drugs_id, $number, $remarks, $firm_id);
        return response()->json($return_data);
    }

    /**
     * 待办--消杀--删除
     * @param Request $request
     * @return mixed
     */
    public function delDisinfect(Request $request)
    {
        $input = $request->all();
        $disinfect_id = isset($input['disinfect_id']) ? $input['disinfect_id'] : '';//数据ID
        if(empty($disinfect_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_disinfect = new Disinfect();
        $return_data = $model_disinfect->delData($disinfect_id);
        return response()->json($return_data);
    }

    /**
     * 待办--获取饲养员
     * @param Request $request
     * @return mixed
     */
    public function getBreeder(Request $request)
    {
        $input = $request->all();
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        if(empty($block_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_user_factory = new UserFactory();
        $return_data = $model_user_factory->getUsersInfo($block_id);
        $array  = array();
        foreach ($return_data as $v)
        {
            if($v->role_name == '饲养员')
            {
                $array['breeder'] = $v;
                return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$array]);
            }else
                $array['breeder'] = [];
            if($v->role_name == '护工')
            {
                $array['carer'] = $v;
                return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$array]);
            }else
                $array['carer'] = [];

        }
        if(empty($array['breeder']) &&  isset($return_data[0])){
            $array['breeder'] = $return_data[0];
            return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$array]);
        }elseif(empty($array['carer']) &&  isset($return_data[1]))
        {
            $array['carer'] = $return_data[1];
            return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$array]);
        }else
            return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$array]);
    }
    /**
     * 待办--饲料消耗-列表
     * @param Request $request
     * @return mixed
     */
    public function fodderList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数

        $model_disinfect = new GrainReceive();
        $return_data = $model_disinfect->getList($start_time, $end_time, $factory_id, $block_type, $block_id, $page_size);
        return response()->json($return_data);
    }

    /**
     * 待办--饲料消耗-录入数据
     * @param Request $request
     * @return mixed
     */
    public function entryFodder(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $data_time = isset($input['data_time']) ? $input['data_time'] : '';//领取时间
        $issuer = isset($input['issuer']) ? $input['issuer'] : '';//饲料发放人
        $grain_id = isset($input['grain_id']) ? $input['grain_id'] : '';//饲料ID
        $number = isset($input['number']) ? $input['number'] : '';//领取数量
        $use_number = isset($input['use_number']) ? $input['use_number'] : '';//使用数量
        $remarks = isset($input['remarks']) ? $input['remarks'] : ''; //备注

        $model_disinfect = new GrainReceive();
        $return_data = $model_disinfect->addData($factory_id, $block_id, $user_id,$data_time, $issuer, $grain_id, $number, $use_number, $remarks);
        return response()->json($return_data);
    }


    /**
     * 待办--消杀-编辑数据
     * @param Request $request
     * @return mixed
     */
    public function editFodder(Request $request)
    {
        $input = $request->all();
        $receive_id = isset($input['receive_id']) ? $input['receive_id'] : '';//数据ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $data_time = isset($input['data_time']) ? $input['data_time'] : '';//领取时间
        $issuer = isset($input['issuer']) ? $input['issuer'] : '';//饲料发放人
        $grain_id = isset($input['grain_id']) ? $input['grain_id'] : '';//饲料ID
        $number = isset($input['number']) ? $input['number'] : '';//领取数量
        $use_number = isset($input['use_number']) ? $input['use_number'] : '';//使用数量
        $remarks = isset($input['remarks']) ? $input['remarks'] : ''; //备注
        if(empty($receive_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_disinfect = new GrainReceive();
        $return_data = $model_disinfect->updateData($receive_id, $factory_id,  $user_id, $block_id, $data_time, $issuer, $grain_id, $number, $use_number, $remarks);
        return response()->json($return_data);
    }


    /**
     * 待办--消杀--删除
     * @param Request $request
     * @return mixed
     */
    public function delFodder(Request $request)
    {
        $input = $request->all();
        $receive_id = isset($input['receive_id']) ? $input['receive_id'] : '';//数据ID
        if(empty($receive_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_disinfect = new GrainReceive();
        $return_data = $model_disinfect->delData($receive_id);
        return response()->json($return_data);
    }

    /**
     * 待办--药品-药品ID
     * @param Request $request
     * @return mixed
     */
    public function getDrugsId(Request $request)
    {
        $input = $request->all();
        $category = isset($input['category']) ? $input['category'] : '';//药品类型  1 : 药品 2 ：疫苗
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $model_drugs = new Drugs();
        $production_data = $model_drugs->getIds($category, $factory_id);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$production_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品-生产厂家
     * @param Request $request
     * @return mixed
     */
    public function getProduction(Request $request)
    {
        $input = $request->all();
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $model_drugs = new Drugs();
        $production_data = $model_drugs->getProduction($item_id, $factory_id);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$production_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品-生产批号
     * @param Request $request
     * @return mixed
     */
    public function getBatch(Request $request)
    {
        $input = $request->all();
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $model_drugs = new Drugs();
        $production_data = $model_drugs->getBatch($item_id, $factory_id, $production);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$production_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品-生产批号
     * @param Request $request
     * @return mixed
     */
    public function getDrugId(Request $request)
    {
        $input = $request->all();
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $batch_number = isset($input['batch_number']) ? $input['batch_number'] : '';//生产批号
        if(empty($item_id) || empty($factory_id) || empty($production) || empty($batch_number))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_drugs = new Drugs();
        $production_data = $model_drugs->getDrugId($item_id, $factory_id, $production, $batch_number);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$production_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品总览--列表
     * @param Request $request
     * @return mixed
     */
    public function drugList(Request $request)
    {
        $input = $request->all();
        $data_time = isset($input['data_time']) ? $input['data_time'] : '';//时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $approved = isset($input['approved']) ? $input['approved'] : '';//批准人
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
//        if(empty($receive_id))
//            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_drugs = new Drugs();
        $drugs_data = $model_drugs->getDataList($data_time, $factory_id, $item_id, $production, $approved, $page_size);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$drugs_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品出入库--列表
     * @param Request $request
     * @return mixed
     */
    public function drugLogList(Request $request)
    {
        $input = $request->all();
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $category_id = isset($input['category_id']) ? $input['category_id'] : '';//物品类别
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $approved = isset($input['approved']) ? $input['approved'] : '';//批准人
        $out_reason = isset($input['out_reason']) ? $input['out_reason'] : ''; //出库原因
        $page_size = isset($input['page_size']) ? $input['page_size'] : 1; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
//        if(empty($receive_id))
//            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_drugs = new DrugsLog();
        $drugs_data = $model_drugs->getDataList($type_id,$record_time, $factory_id, $category_id, $production, $approved, $out_reason, $page_size);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$drugs_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品--录入数据
     * @param Request $request
     * @return mixed
     */
    public function addDrugsLog(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $drugs_name = isset($input['drugs_name']) ? $input['drugs_name'] : '';//药品名称
        $supplier = isset($input['supplier']) ? $input['supplier'] : '';//供货单位
        $producedate = isset($input['producedate']) ? $input['producedate'] : '';//生产日期
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $batch_number = isset($input['batch_number']) ? $input['batch_number'] : '';//生产批号
        $category_id = isset($input['category_id']) ? $input['category_id'] : '';//物品类别ID
        $number = isset($input['number']) ? $input['number'] : '';//药品数量
        $unit = isset($input['unit']) ? $input['unit'] : '';//单位
        $unit_price = isset($input['unit_price']) ? $input['unit_price'] : '';//单价
        $price = isset($input['price']) ? $input['price'] : '';//总金额
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $approved = isset($input['approved']) ? $input['approved'] : '';//批准人
        $receiver = isset($input['receiver']) ? $input['receiver'] : '';//接货人
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注
        $out_reason = isset($input['out_reason']) ? $input['out_reason'] : '';//出库原因

        $model_drugs_log = new DrugsLog();
        $model_drugs = new Drugs();
        DB::beginTransaction();
        $log_data = $model_drugs_log->addLog($user_id,$record_time, $item_id, $drugs_name, $supplier, $producedate, $production, $batch_number, $category_id, $number, $unit, $unit_price, $price, $factory_id, $approved, $receiver, $type_id, $remarks, $out_reason);
        if($log_data['code'] != 20000)
            return response()->json($log_data);
        $exits_status = $model_drugs->existsDrugs($item_id, $production, $factory_id, $batch_number);
        if($exits_status)
            $return_data = $model_drugs->updateData($item_id, $production, $factory_id, $batch_number, $number, $type_id);
        else
            $return_data = $model_drugs->addData($item_id, $drugs_name, $producedate, $production, $batch_number, $category_id, $number,  $unit_price, $factory_id, $approved, $receiver);
        DB::commit();
        return response()->json($return_data);
    }

    /**
     * 待办--药品--编辑
     * @param Request $request
     * @return mixed
     */
    public function editDrugsLog(Request $request)
    {
        $input = $request->all();
        $log_id = isset($input['log_id']) ? $input['log_id'] : '';//数据ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $drugs_name = isset($input['drugs_name']) ? $input['drugs_name'] : '';//药品名称
        $supplier = isset($input['supplier']) ? $input['supplier'] : '';//供货单位
        $producedate = isset($input['producedate']) ? $input['producedate'] : '';//生产日期
        $production = isset($input['production']) ? $input['production'] : '';//生产厂家
        $batch_number = isset($input['batch_number']) ? $input['batch_number'] : '';//生产批号
        $category_id = isset($input['category_id']) ? $input['category_id'] : '';//物品类别
        $number = isset($input['number']) ? $input['number'] : '';//药品数量
        $unit = isset($input['unit']) ? $input['unit'] : '';//单位
        $unit_price = isset($input['unit_price']) ? $input['unit_price'] : '';//单价
        $price = isset($input['price']) ? $input['price'] : '';//总金额
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $approved = isset($input['approved']) ? $input['approved'] : '';//批准人
        $receiver = isset($input['receiver']) ? $input['receiver'] : '';//接货人
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//出入库类型
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注
        $out_reason = isset($input['out_reason']) ? $input['out_reason'] : '';//出库原因

        $model_drugs = new DrugsLog();
        $return_data = $model_drugs->editLog($log_id, $user_id,$record_time, $item_id, $drugs_name, $supplier, $producedate, $production, $batch_number, $category_id, $number, $unit, $unit_price, $price, $factory_id, $approved, $receiver, $type_id, $remarks, $out_reason);
        return response()->json($return_data);
    }

    /**
     * 待办--药品--删除
     * @param Request $request
     * @return mixed
     */
    public function delDrugsLog(Request $request)
    {
        $input = $request->all();
        $log_id = isset($input['log_id']) ? $input['log_id'] : '';//数据ID
        if(empty($log_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_drugs = new DrugsLog();
        $return_data = $model_drugs->delLog($log_id);
        return response()->json($return_data);
    }

    /**
     * 待办--药品使用--列表
     * @param Request $request
     * @return mixed
     */
    public function drugUseList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $page_size = isset($input['page_size']) ? $input['page_size'] : 1; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        $model_med_record = new MedRecord();
        $drugs_data = $model_med_record->getList($start_time, $end_time, $factory_id, $block_type, $block_id, $page_size);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$drugs_data];
        return response()->json($return_data);
    }

    /**
     * 待办--药品使用--录入数据
     * @param Request $request
     * @return mixed
     */
    public function addDrugUse(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $symptom = isset($input['symptom']) ? $input['symptom'] : '';//症状
        $number = isset($input['number']) ? $input['number'] : '';//生病数量
        $usage_time = isset($input['usage_time']) ? $input['usage_time'] : '';//开始使用时间
        $day = isset($input['day']) ? $input['day'] : '';//用药天数
        $dosage = isset($input['dosage']) ? $input['dosage'] : '';//用量
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品id
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $approval = isset($input['approval']) ? $input['approval'] : '';//用药审批人
        $feedback = isset($input['feedback']) ? $input['feedback'] : '';//反馈

        $model_med_record = new MedRecord();
        $return_data = $model_med_record->addData($user_id, $block_id, $record_time, $symptom, $number, $usage_time, $day, $dosage, $drugs_id, $item_id, $approval, $feedback);
        return response()->json($return_data);
    }

    /**
     * 待办--药品使用--编辑
     * @param Request $request
     * @return mixed
     */
    public function editDrugUse(Request $request)
    {
        $input = $request->all();
        $record_id = isset($input['record_id']) ? $input['record_id'] : '';//数据ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $symptom = isset($input['symptom']) ? $input['symptom'] : '';//症状
        $number = isset($input['number']) ? $input['number'] : '';//生病数量
        $usage_time = isset($input['usage_time']) ? $input['usage_time'] : '';//开始使用时间
        $day = isset($input['day']) ? $input['day'] : '';//用药天数
        $dosage = isset($input['dosage']) ? $input['dosage'] : '';//用量
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品id
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $approval = isset($input['approval']) ? $input['approval'] : '';//用药审批人
        $feedback = isset($input['feedback']) ? $input['feedback'] : '';//反馈

        $model_med_record = new MedRecord();
        $return_data = $model_med_record->editData($record_id, $user_id, $block_id, $record_time, $symptom, $number, $usage_time, $day, $dosage, $drugs_id, $item_id, $approval, $feedback);
        return response()->json($return_data);
    }

    /**
     * 待办--药品使用--删除
     * @param Request $request
     * @return mixed
     */
    public function delDrugUse(Request $request)
    {
        $input = $request->all();
        $record_id = isset($input['record_id']) ? $input['record_id'] : '';//数据ID
        if(empty($record_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_med_record = new MedRecord();
        $return_data = $model_med_record->delData($record_id);
        return response()->json($return_data);
    }

    /**
     * 待办--疫苗使用--列表
     * @param Request $request
     * @return mixed
     */
    public function vaccineUseList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $block_type = isset($input['block_type']) ? $input['block_type'] : '';//仓号类型
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $page_size = isset($input['page_size']) ? $input['page_size'] : 1; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        $model_med_vaccine = new MedVaccine();
        $drugs_data = $model_med_vaccine->getList($start_time, $end_time, $factory_id, $block_type, $block_id, $page_size);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$drugs_data];
        return response()->json($return_data);
    }

    /**
     * 待办--疫苗使用--录入数据
     * @param Request $request
     * @return mixed
     */
    public function addVaccineUse(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $symptom = isset($input['symptom']) ? $input['symptom'] : '';//使用原因
        $number = isset($input['number']) ? $input['number'] : '';//接种数量
        $usage_time = isset($input['usage_time']) ? $input['usage_time'] : '';//使用疫苗时间
        $dosage = isset($input['dosage']) ? $input['dosage'] : '';//免疫用量
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品id
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $approval = isset($input['approval']) ? $input['approval'] : '';//免疫负责人
        $charge = isset($input['charge']) ? $input['charge'] : '';//接种负责人
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注
        $personnel = isset($input['personnel']) ? $input['personnel'] : '';//免疫人员
        $breeder = isset($input['breeder']) ? $input['breeder'] : '';//饲养员
        $method = isset($input['method']) ? $input['method'] : '';//免疫方法
        $feedback = isset($input['feedback']) ? $input['feedback'] : '';//反馈

        $model_med_vaccine = new MedVaccine();
        $return_data = $model_med_vaccine->addData($user_id, $block_id, $record_time, $symptom, $number, $usage_time, $dosage, $drugs_id, $item_id, $approval, $charge, $remarks, $personnel, $breeder, $method, $feedback);
        return response()->json($return_data);
    }

    /**
     * 待办--疫苗使用--编辑
     * @param Request $request
     * @return mixed
     */
    public function editVaccineUse(Request $request)
    {
        $input = $request->all();
        $vaccine_id = isset($input['vaccin_id']) ? $input['vaccin_id'] : '';//数据ID
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';//仓号ID
        $record_time = isset($input['record_time']) ? $input['record_time'] : '';//记录时间
        $symptom = isset($input['symptom']) ? $input['symptom'] : '';//症状
        $number = isset($input['number']) ? $input['number'] : '';//生病数量
        $usage_time = isset($input['usage_time']) ? $input['usage_time'] : '';//开始使用时间
        $day = isset($input['day']) ? $input['day'] : '';//用药天数
        $dosage = isset($input['dosage']) ? $input['dosage'] : '';//用量
        $drugs_id = isset($input['drugs_id']) ? $input['drugs_id'] : '';//药品id
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';//物品ID
        $approval = isset($input['approval']) ? $input['approval'] : '';//用药审批人
        $charge = isset($input['charge']) ? $input['charge'] : '';//接种负责人
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';//备注
        $personnel = isset($input['personnel']) ? $input['personnel'] : '';//免疫人员
        $breeder = isset($input['breeder']) ? $input['breeder'] : '';//饲养员
        $method = isset($input['method']) ? $input['method'] : '';//免疫方法
        $feedback = isset($input['feedback']) ? $input['feedback'] : '';//反馈

        $model_med_vaccine = new MedVaccine();
        $return_data = $model_med_vaccine->editData($vaccine_id, $user_id, $block_id, $record_time, $symptom, $number, $usage_time, $dosage, $drugs_id, $item_id, $approval, $charge, $remarks, $personnel, $breeder, $method, $feedback);
        return response()->json($return_data);
    }

    /**
     * 待办--疫苗使用--删除
     * @param Request $request
     * @return mixed
     */
    public function delVaccineUse(Request $request)
    {
        $input = $request->all();
        $vaccine_id = isset($input['vaccin_id']) ? $input['vaccin_id'] : '';//数据ID
        if(empty($vaccine_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_med_vaccine = new MedVaccine();
        $return_data = $model_med_vaccine->delData($vaccine_id);
        return response()->json($return_data);
    }

    /**
     * 待办--请假--列表
     * @param Request $request
     * @return mixed
     */
    public function userLeaveList(Request $request)
    {
        $input = $request->all();
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';//用户名字
        $page_size = isset($input['page_size']) ? $input['page_size'] : 1; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        $model_leave = new Leave();
        $leave_data = $model_leave->getList($start_time, $end_time, $factory_id, $user_name, $page_size);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$leave_data];
        return response()->json($return_data);
    }

    /**
     * 待办--请假--假期类型
     * @param Request $request
     * @return mixed
     */
    public function getLeaveTypes(Request $request)
    {
        $input = $request->all();
        $model_leave_type = new LeaveType();
        $type_data = $model_leave_type->getAllTypes();
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$type_data];
        return response()->json($return_data);
    }
    /**
     * 待办--请假--录入数据
     * @param Request $request
     * @return mixed
     */
    public function addLeave(Request $request)
    {
        $input = $request->all();
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';//用户id
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';//请假类型ID
        $start_time = isset($input['start_time']) ? $input['start_time'] : '';//开始时间
        $end_time = isset($input['end_time']) ? $input['end_time'] : '';//结束时间
        $duration = isset($input['duration']) ? $input['duration'] : '';//请假时长
        $reason = isset($input['reason']) ? $input['reason'] : '';//请假事由

        $model_leave = new Leave();
        $return_data = $model_leave->addData($user_id, $factory_id, $type_id, $start_time, $end_time, $duration, $reason);
        return response()->json($return_data);
    }

    /**
     * 待办--考勤--报表
     * @param Request $request
     * @return mixed
     */
    public function userRuleList(Request $request)
    {
        $input = $request->all();
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';//时间
        $start_time = date('Y-m-d', strtotime($date_time));//开始时间
        $end_time = date('Y-m-d', strtotime("+1 month", strtotime($start_time)));//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';//用户名字
        $model_rule_type = new RuleType();
        $rule_data = $model_rule_type->getList($start_time, $end_time, $factory_id, $user_name);
        $list = array();
        foreach ($rule_data as $v)
        {
            if(isset($list[$v->uid]))
            {
                $list[$v->uid]['amount'] = $list[$v->uid]['amount'] + 1;
                if($v->morn_start_type == "迟到")
                    $list[$v->uid]['late_count'] = $list[$v->uid]['late_count'] + 1;
                if($v->noon_start_type == "迟到")
                    $list[$v->uid]['late_count'] = $list[$v->uid]['late_count'] + 1;
                if(empty($v->morn_start))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->morn_end))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->noon_start))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->noon_end))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
            }else{
                $list[$v->uid]['user_id'] = $v->uid;
                $list[$v->uid]['user_name'] = $v->user_name;
                $list[$v->uid]['data_time'] = date('Y-m', strtotime($v->date));
                $list[$v->uid]['late_count'] = 0;
                $list[$v->uid]['no_clock_count'] = 0;
                $list[$v->uid]['amount'] = 1;
                if($v->morn_start_type == "迟到")
                    $list[$v->uid]['late_count'] = $list[$v->uid]['late_count'] + 1;
                if($v->noon_start_type == "迟到")
                    $list[$v->uid]['late_count'] = $list[$v->uid]['late_count'] + 1;
                if(empty($v->morn_start))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->morn_end))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->noon_start))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
                if(empty($v->noon_end))
                    $list[$v->uid]['no_clock_count'] = $list[$v->uid]['no_clock_count'] + 1;
            }
        }
        //统计请假时间
        $model_leave = new Leave();
        foreach ($list as $v)
        {
            $user_id  = $v['user_id'];
            $leave_amount = $model_leave->getDuration($user_id, $start_time);
            $list[$user_id]['leave_amount'] = 0;
            foreach ($leave_amount as $val)
            {
                $list[$user_id]['leave_amount'] = $list[$user_id]['leave_amount'] + $val->duration;
                if($val->end_time > $end_time)
                {
                    $days = ceil((strtotime($val->end_time) - strtotime($end_time)) / (3600 *24));
                    $list[$user_id]['leave_amount'] = $list[$user_id]['leave_amount'] - $days;
                }
            }
//            return $leave_amount;
        }
        $list = array_values($list);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$list];
        return response()->json($return_data);
    }

    /**
     * 待办--考勤--记录
     * @param Request $request
     * @return mixed
     */
    public function userRuleTypeList(Request $request)
    {
        $input = $request->all();
        $date_time = isset($input['date_time']) ? $input['date_time'] : '';//时间
        $start_time = date('Y-m-d', strtotime($date_time));//开始时间
        $end_time = date('Y-m-d', strtotime("+1 month", strtotime($start_time)));//结束时间
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';//用户名字
        $page_size = isset($input['page_size']) ? $input['page_size'] : 1; //每页条数
        $page =  isset($input['page']) ? $input['page'] : 1;//页数
        $model_rule_type = new RuleType();
        $model_leave = new Leave();
        $rule_data = $model_rule_type->getUserList($start_time, $end_time, $factory_id, $user_name, $page_size);
        foreach ($rule_data['list'] as $v)
        {
            $leave_start_time = date('Y-m-d H:i:s', strtotime($v->date));
            $leave_end_time = date('Y-m-d H:i:s', strtotime("+1 day", strtotime($v->date)));
            $return_data = $model_leave->getDurationState($v->uid, $leave_start_time, $leave_end_time);
            if($return_data)
                $v->leave_status  = 1;
            else
                $v->leave_status  = 0;
        }
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$rule_data];
        return response()->json($return_data);
    }

    /**
     * 待办--考勤--上下班时间列表
     * @param Request $request
     * @return mixed
     */
    public function RuleList(Request $request)
    {
        $input = $request->all();
        $firm_id =  isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        $model_rule = new Rule();
        $rule_data = $model_rule->getList($firm_id);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$rule_data];
        return response()->json($return_data);
    }

    /**
     * 待办--考勤--上下班时间录入
     * @param Request $request
     * @return mixed
     */
    public function entryRule(Request $request)
    {
        $input = $request->all();
        $title = isset($input['title']) ? $input['title'] : '';//标题
        $user_id =  isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $firm_id =  isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $morn_start = isset($input['morn_start']) ? $input['morn_start'] : '';//早上上班时间
        $morn_end = isset($input['morn_end']) ? $input['morn_end'] : '';//早上下班时间
        $noon_start = isset($input['noon_start']) ? $input['noon_start'] : '';//下午上班时间
        $noon_end = isset($input['noon_end']) ? $input['noon_end'] : '';//下午下班时间
        $repeat_day = isset($input['repeat_day']) ? $input['repeat_day'] : '';//每周重复时间
        $state = isset($input['state']) ? $input['state'] : 0;//是否启用  1 ： 启用 0 ：禁用  默认 0 禁用
        $delete = isset($input['delete']) ? $input['delete'] : 1;//是否删除 1：未删除 0：删除 默认 1 未删除

        $model_rule = new Rule();
        $rule_data = $model_rule->addData($title, $user_id, $morn_start, $morn_end, $noon_start, $noon_end,$repeat_day,$state, $delete, $firm_id);
        return response()->json($rule_data);
    }

    /**
     * 待办--考勤--上下班时间编辑
     * @param Request $request
     * @return mixed
     */
    public function editRule(Request $request)
    {
        $input = $request->all();
        $rule_id = isset($input['rule_id']) ? $input['rule_id'] : '';//数据ID
        $title = isset($input['title']) ? $input['title'] : '';//标题
        $user_id =  isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';//厂区ID
        $morn_start = isset($input['morn_start']) ? $input['morn_start'] : '';//早上上班时间
        $morn_end = isset($input['morn_end']) ? $input['morn_end'] : '';//早上下班时间
        $noon_start = isset($input['noon_start']) ? $input['noon_start'] : '';//下午上班时间
        $noon_end = isset($input['noon_end']) ? $input['noon_end'] : '';//下午下班时间
        $repeat_day = isset($input['repeat_day']) ? $input['repeat_day'] : '';//每周重复时间

        $model_rule = new Rule();
        $rule_data = $model_rule->editData($rule_id,  $title, $user_id, $morn_start, $morn_end, $noon_start, $noon_end,$repeat_day);
        return response()->json($rule_data);
    }


    /**
     * 待办--考勤--上下班时间启用与禁用
     * @param Request $request
     * @return mixed
     */
    public function editRuleState(Request $request)
    {
        $input = $request->all();
        $rule_id = isset($input['rule_id']) ? $input['rule_id'] : '';//数据ID
        $state = isset($input['state']) ? $input['state'] : 0;//是否启用  1 ： 启用 0 ：禁用  默认 0 禁用
        $model_rule = new Rule();
        $rule_data = $model_rule->editState($rule_id,  $state);
        return response()->json($rule_data);
    }

    /**
     * 待办--考勤--上下班时间删除
     * @param Request $request
     * @return mixed
     */
    public function delRule(Request $request)
    {
        $input = $request->all();
        $rule_id = isset($input['rule_id']) ? $input['rule_id'] : '';//数据ID
        $model_rule = new Rule();
        $rule_data = $model_rule->delData($rule_id);
        return response()->json($rule_data);
    }

    /**
     * 待办--考勤--打卡地点
     * @param Request $request
     * @return mixed
     */
    public function addRange(Request $request)
    {
        $input = $request->all();
        $user_id =  isset($input['user_id']) ? $input['user_id'] : '';//用户ID
        $firm_id =  isset($input['firm_id']) ? $input['firm_id'] : '';//企业ID
        $longitude = isset($input['longitude']) ? $input['longitude'] : '';//经度
        $latitude = isset($input['latitude']) ? $input['latitude'] : '';//纬度
        $address = isset($input['address']) ? $input['address'] : '';//地址
        $distance = isset($input['distance']) ? $input['distance'] : '';//范围米
        $model_range = new RuleRange();
        $rule_data = $model_range->addData($user_id, $firm_id, $longitude, $latitude, $address, $distance);
        return response()->json($rule_data);
    }

}
