<?php


namespace App\Http\Controllers\platform;


use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Cage;
use App\Models\EarlyWarning;
use App\Models\Factory;
use App\Models\Firm;
use App\Models\InitialInfo;
use App\Models\Items;
use App\Models\ItemType;
use App\Models\Permissions;
use App\Models\RolePermissions;
use App\Models\Roles;
use App\Models\RoleUsers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class PlatformController extends Controller
{
    /**
     * 平台设置--平台设置
     * @param Request $request
     * @return mixed
     */
    public function  platFormInfo(Request $request)
    {
        $input = $request->all();
        $token = $request->header('token');
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        $model_user = new User();
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }
        else {
            return response()->json(['code'=>40000,'msg'=>'token 已经失效', 'data'=>[]]);
        }
        if($data['firm_id'] != 0 ) return response()->json(['code'=>40000,'msg'=>'你不是超级管理员', 'data'=>[]]);
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $model_firm = new Firm();
        $model_user_role = new RoleUsers();
        $return_data = $model_firm->getAllFirm($page_size);
        $return_array = array();
        $array = array();
        $imgUrl = env('IMAGE_URL');
        foreach ($return_data['list'] as $v)
        {
            $user_data = $model_user->getUserInfoByFirmId($v->id);
            if($user_data){
                $role_data = $model_user_role->getRoleInfoByUserId($user_data->id);
                $v->user_id = $user_data->id;
                $v->user_name = $user_data->user_name;
                $v->mobile = $user_data->mobile;
                $v->avatar = $imgUrl.$user_data->avatar;
                $v->role_id = $role_data->id;
                $v->role_name =   $role_data->name;
                $array[] = $v;
            }else{
                $v->user_id = '';
                $v->user_name = '';
                $v->mobile = '';
                $v->avatar = '';
                $array[] = $v;
            }
        }
        $return_array['list'] = $array;
        $return_array['total'] = $return_data['total'];
        $return_array['currentPage'] = $return_data['currentPage'];
        $return_array['page_size'] = $page_size;
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$return_array]);
    }

    /**
     * 平台设置--新增管理员
     * @param Request $request
     * @return mixed
     */
    public function  addFirmUser(Request $request)
    {
        $input = $request->all();
        $rsg_time = isset($input['rsg_time']) ? $input['rsg_time'] : '';
        $role_name = isset($input['role_name']) ? $input['role_name'] : '超级管理员';
        $role_desc = isset($input['role_desc']) ? $input['role_desc'] : '企业超级管理员';
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $mobile = isset($input['mobile']) ? $input['mobile'] : '';
        $firm_name = isset($input['firm_name']) ? $input['firm_name'] : '';
        $firm_icon = isset($input['firm_icon']) ? $input['firm_icon'] : '';
        if(empty($password) || empty($firm_name) || empty($firm_icon) || empty($role_name) || empty($role_desc) || empty($user_name) || empty($mobile))
            return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数']]);

        if(!$this->is_mobile($mobile))  return  response()->json(['code'=>40000,'msg'=>'手机号不正确', 'data'=>['']]);
        //开始添加数据  新增企业
        //$icon_str = mb_substr($firm_name, 0, 1, 'utf-8'); //截取  第一个汉字
        $firm_model = new firm();
        DB::beginTransaction();
        $firm_data = $firm_model->addFirm($firm_name, $firm_icon);
        if($firm_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($firm_data);
        }
        //新增角色
        $model_Roles = new Roles();
        $role_data = $model_Roles->addRoleByFirmID($role_name, $role_desc, $firm_data['firm_id']);
        $role_id = $role_data['role_id'];
        if($role_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($role_data);
        }
        //新增用户
        $model_user = new User();
        $model_roles = new RoleUsers();
//        $model_initial = new InitialInfo();
//        $str_initial = $model_initial->getInitialInfo(); //'123456';
        $password = encrypt($password);
        $is_firm = 1;
        $user_data = $model_user->addUser($user_name, $mobile, $password, $rsg_time, $firm_data['firm_id'], $is_firm);
        if($user_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($user_data);
        }
        //新增用户角色信息
        $role_data = $model_roles->addUserRole($user_data['user_id'], $role_id);
        if($role_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($role_data);
        }
        //新增用户企业超级管理员角色权限
        $model_role_permissions = new RolePermissions();
        //获取企业超级管理员角色权限
        $model_permissions = new Permissions();
        $per_array = $model_permissions->getFirmPer();
        for($i=0; $i<count($per_array); $i++)
        {
            $permission_id = $per_array[$i];
            $data = $model_role_permissions->addRolePermissions($role_id, $permission_id);
            if(empty($data) || $data['code'] ==40000 )
            {
                return  response()->json($data);
            }
        }
        DB::commit();
        return response()->json(['code'=>20000,'msg'=>'新增成功',  'data'=>[]]);
    }

    /**
     * 平台设置--编辑管理员
     * @param Request $request
     * @return mixed
     */
    public function  editFirmUser(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        $old_user_id = isset($input['old_user_id']) ? $input['old_user_id'] : '';
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';
        $firm_name = isset($input['firm_name']) ? $input['firm_name'] : '';
        $firm_icon = isset($input['firm_icon']) ? $input['firm_icon'] : '';
        if(empty($firm_name) || empty($firm_icon))
            response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数']]);

        //开始添加数据  新增企业
        //$icon_str = mb_substr($firm_name, 0, 1, 'utf-8'); //截取  第一个汉字
        $firm_model = new firm();
        DB::beginTransaction();
        $firm_data = $firm_model->editFirm($id, $firm_name, $firm_icon);
        if($firm_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($firm_data);
        }
        //修改绑定关系
        $model_user = new User();
        $user_data = $model_user->editUserFirm($user_id, $id, $old_user_id);
        if($user_data['code'] != 20000)
        {
            DB::rollBack();
            return response()->json($user_data);
        }
        //新增用户角色信息
        DB::commit();
        return response()->json(['code'=>20000,'msg'=>'修改成功',  'data'=>[]]);
    }
    /**
     * 平台设置--修改显示状态
     * @param Request $request
     * @return mixed
     */
    public function  editFirmStatus(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        $show_status = isset($input['show_status']) ? $input['show_status'] : '';
        $model_initial = new firm();
        $data = $model_initial->editFirmStatus($id, $show_status);
        return response()->json($data);
    }

    /**
     * 平台设置--删除平台设置
     * @param Request $request
     * @return mixed
     */
    public function  delPlatform(Request $request)
    {
        $input = $request->all();
        $id = isset($input['id']) ? $input['id'] : '';
        $model_firm = new firm();
        $data = $model_firm->delFirm($id);
        return response()->json($data);
    }


    /**
     * 平台设置--修改初始密码
     * @param Request $request
     * @return mixed
     */
    public function  editInitialPassword(Request $request)
    {
        $input = $request->all();
        $new_password = isset($input['password']) ? $input['password'] : '';
        $model_initial = new InitialInfo();
        $data = $model_initial->editPassword($new_password); //'123456';
        return response()->json($data);
    }

    /**
     * 平台设置--获取初始密码
     * @param Request $request
     * @return mixed
     */
    public function  getInitialPassword(Request $request)
    {
        $input = $request->all();
        $model_initial = new InitialInfo();
        $data = $model_initial->getInitialInfo(); //'123456';
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>['password'=>$data]]);
    }

    /**
     * 平台设置--物品设置-获取全部类型
     * @param Request $request
     * @return mixed
     */
    public function  getItemType(Request $request)
    {
        $input = $request->all();
        $model_item_type = new ItemType();
        $type_data = $model_item_type->getAllType();
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$type_data]);
    }

    /**
     * 平台设置--物品设置-获取物品名称
     * @param Request $request
     * @return mixed
     */
    public function  getItemName(Request $request)
    {
        $input = $request->all();
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $model_items = new Items();
        $name_data = $model_items->getItemsByType($type_id, $firm_id);
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$name_data]);
    }

    /**
     * 平台设置--物品设置
     * @param Request $request
     * @return mixed
     */
    public function  getItemInfo(Request $request)
    {
        $input = $request->all();
        $time_data = isset($input['time_data']) ? $input['time_data'] : '';
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';
        $item_name = isset($input['item_name']) ? $input['item_name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        if(empty($firm_id)) return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        $model_items = new Items();
        $item_data = $model_items->getNesByType($type_id, $page_size,   $firm_id, $item_name, $time_data);
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$item_data]);
    }

    /**
     * 平台设置--物品设置-录入数据
     * @param Request $request
     * @return mixed
     */
    public function  addItem(Request $request)
    {
        $input = $request->all();
        $time_data = isset($input['time_data']) ? strtotime($input['time_data']) : '';
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';
        $item_name = isset($input['item_name']) ? $input['item_name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $item_img = isset($input['item_img']) ? $input['item_img'] : '';

        if(empty($time_data) || empty($type_id) || empty($item_name) || empty($firm_id) || empty($item_img))
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);

        $model_items = new Items();
        $item_data = $model_items->addItem($time_data, $type_id,$item_name, $firm_id, $item_img);
        return response()->json($item_data);
    }

    /**
     * 平台设置--物品设置-编辑
     * @param Request $request
     * @return mixed
     */
    public function editItem(Request $request)
    {
        $input = $request->all();
        $time_data = isset($input['time_data']) ? strtotime($input['time_data']) : '';
        $item_id = isset($input['item_id']) ? $input['item_id']: '';
        $type_id = isset($input['type_id']) ? $input['type_id'] : '';
        $item_name = isset($input['item_name']) ? $input['item_name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $item_img = isset($input['item_img']) ? $input['item_img'] : '';

        if(empty($time_data) || empty($type_id) || empty($item_name) || empty($firm_id) || empty($item_img) || empty($item_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);

        $model_items = new Items();
        $item_data = $model_items->editItem($item_id, $time_data, $type_id,$item_name, $firm_id, $item_img);
        return response()->json($item_data);
    }

    /**
     * 平台设置--物品设置-删除
     * @param Request $request
     * @return mixed
     */
    public function delItem(Request $request)
    {
        $input = $request->all();
        $item_id = isset($input['item_id']) ? $input['item_id'] : '';
        if(empty($item_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);

        $model_items = new Items();
        $item_data = $model_items->delItem($item_id);
        return response()->json($item_data);
    }


    /**
     * 上传新闻图片
     * @param Request $request
     * @return mixed
     */
    public function uploadItemImg(Request $request)
    {
        if ($request->isMethod('POST')) { //判断文件是否是 POST的方式上传
            $tmp = $request->file('file');
            if ($tmp->isValid()) { //判断文件上传是否有效
                $FileType = $tmp->getClientOriginalExtension(); //获取文件后缀

                $FilePath = $tmp->getRealPath(); //获取文件临时存放位置

                $FileName = date('Ymd') . uniqid() . '.' . $FileType; //定义文件名

                Storage::disk('item')->put($FileName, file_get_contents($FilePath)); //存储文件
                $IMAGE_URL = env('IMAGE_URL');
                $ITEM_URL = env('ITEM_URL');
                $obj['url'] = $IMAGE_URL.$ITEM_URL. $FileName;
                $data['code'] = 20000;
                $data['data'] = $obj;
                $data['file_name'] = $ITEM_URL.$FileName;
                $data['msg'] = "";
                $data['time'] = time();
                return response()->json($data);
            }
        }
    }

    /**
     * 平台设置--预警设置
     * @param Request $request
     * @return mixed
     */
    public function  earlyList(Request $request)
    {
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        $model_early = new EarlyWarning();
        $early_data = $model_early->getList($firm_id);
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$early_data]);
    }

    /**
     * 平台设置--预警设置
     * @param Request $request
     * @return mixed
     */
    public function  editEarly(Request $request)
    {
        $input = $request->all();
        $die_id = isset($input['die_id']) ? $input['die_id'] : '';
        $sick_id = isset($input['sick_id']) ? $input['sick_id'] : '';
        $incompetent_id = isset($input['incompetent_id']) ? $input['incompetent_id'] : '';
        $d_b_pigeon = isset($input['d_b_pigeon']) ? $input['d_b_pigeon'] : 0;//死亡率种鸽
        $d_c_pigeon = isset($input['d_c_pigeon']) ? $input['d_c_pigeon'] : 0;//死亡率童鸽
        $d_s_pigeon = isset($input['d_s_pigeon']) ? $input['d_s_pigeon'] : 0;//死亡率乳鸽
        $d_w_pigeon = isset($input['d_w_pigeon']) ? $input['d_w_pigeon'] : 0;//死亡率青年鸽
        $s_b_pigeon = isset($input['s_b_pigeon']) ? $input['s_b_pigeon'] : 0;//病残率种鸽
        $s_c_pigeon = isset($input['s_c_pigeon']) ? $input['s_c_pigeon'] : 0;//病残率童鸽
        $s_s_pigeon = isset($input['s_s_pigeon']) ? $input['s_s_pigeon'] : 0;//病残率乳鸽
        $s_w_pigeon = isset($input['s_w_pigeon']) ? $input['s_w_pigeon'] : 0;//病残率青年鸽
        $b_egg = isset($input['b_egg']) ? $input['b_egg'] : 0;
        $one_year = isset($input['one_year']) ? $input['one_year'] : 0;
        $two_year = isset($input['two_year']) ? $input['two_year'] : 0;
        $three_year = isset($input['three_year']) ? $input['three_year'] : 0;
        $four_year = isset($input['four_year']) ? $input['four_year'] : 0;
        $five_year = isset($input['five_year']) ? $input['five_year'] : 0;

        if(empty($die_id) || empty($sick_id) || empty($incompetent_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        $model_early = new EarlyWarning();
        $early_data = $model_early->editEarly($die_id, $sick_id, $incompetent_id, $d_b_pigeon,
            $d_c_pigeon, $d_s_pigeon, $d_w_pigeon, $s_b_pigeon, $s_c_pigeon, $s_s_pigeon,
            $s_w_pigeon, $b_egg, $one_year, $two_year, $three_year, $four_year, $five_year);
        return response()->json($early_data);
    }

    /**
     * 仓库设置--仓库管理
     * @param Request $request
     * @return mixed
     */
    public function  warehouseList(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $block_type = isset($input['block_type']) ? $input['block_type'] : 0;
        $block_id = isset($input['block_id']) ? $input['block_id'] : 0;
        $start_cage = isset($input['start_cage']) ? $input['start_cage'] : 0;
        $end_cage = isset($input['end_cage']) ? $input['end_cage'] : 0;
        $start_name = isset($input['start_name']) ? $input['start_name'] : 0;
        $end_name = isset($input['end_name']) ? $input['end_name'] : 0;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $size = 6;

        $model_Cage = new Cage();
        $cage_data = array();
        $return_data = array();
        $cage_ids = $model_Cage->getCage($factory_id, $block_type, $firm_id, $block_id, $start_cage, $end_cage, $start_name, $end_name, $page_size, $size);
        $ids_array = array();
        $ids_array['ids'] = array();
        $ids_array['names'] = array();
        foreach ($cage_ids['list'] as $value)
        {
            $ids_array['ids'][] = $value->id;
            $ids_array['names'][] = $value->name;
            $ids_array['block_ids'][] = $value->block_id;
            $ids_array['factory_names'][] = $value->factory_name;
            $ids_array['block_names'][] = $value->block_name;
            $ids_array['block_types'][] = $value->block_type;
            $ids_array['type_names'][] = $value->type_name;
        }
        $chunk_ids = array_chunk($ids_array['ids'], $size);
        $names = array_chunk($ids_array['names'], $size);
        for($i = 0; $i < count($chunk_ids); $i++)
        {
            $arr = array();
            $arr['id'] = $ids_array['block_ids'][$i];
            $arr['factory_name'] = $ids_array['factory_names'][$i];
            $arr['name'] = $ids_array['block_names'][$i];
            $arr['block_type'] = $ids_array['block_types'][$i];
            $arr['type_name'] = $ids_array['type_names'][$i];
            $arr['cage_ids'] = $chunk_ids[$i];
            $arr['cage_names'] = $names[$i];
            $cage_data[] = $arr;
        }
        $return_data['total'] = $cage_ids['total'];
        $return_data['currentPage'] = $cage_ids['currentPage'];
        $return_data['pageSize'] = $page_size;
        $return_data['list'] = $cage_data;
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$return_data]);
    }

    /**
     * 仓库设置--新建仓库
     * @param Request $request
     * @return mixed
     */
    public function  warehouseAdd(Request $request)
    {
        $input = $request->all();
        $factory_name = isset($input['factory_name']) ? $input['factory_name'] : '';
        $block_type = isset($input['block_type']) ? $input['block_type'] : 0;
        $type_name = isset($input['type_name']) ? $input['type_name'] : '';
        $block_name = isset($input['block_name']) ? $input['block_name'] : '';
        $cage_amount = isset($input['cage_amount']) ? $input['cage_amount'] : 0;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $pigeon = isset($input['pigeon']) ? $input['pigeon'] : 0;
        $egg = isset($input['egg']) ? $input['egg'] : 0;
        $squab = isset($input['squab']) ? $input['squab'] : 0;
        $child = isset($input['child']) ? $input['child'] : 0;
        $youth = isset($input['youth']) ? $input['youth'] : 0;

        if(empty($factory_name) || empty($block_name) || empty($cage_amount) || empty($firm_id))
        {
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        }

        $model_factory = new Factory();
        $model_block = new Block();
        $model_Cage = new Cage();
        DB::beginTransaction();
        $factory_data = $model_factory->addFactory($factory_name, $firm_id);
        if($factory_data['code'] != 20000){
            DB::rollBack();
            return response()->json($factory_data);
        }
        $block_data = $model_block->addBlock($block_name, $block_type, $type_name, $factory_data['factory_id']);
        if($block_data['code'] != 20000){
            DB::rollBack();
            return response()->json($block_data);
        }
        //根据厂区查询最大鸽笼编号
        $max_name = $model_Cage->getMaxName($factory_data['factory_id']);
        $cage_data = ['code'=>20000,'msg'=>'新增失败', 'data'=>[]];
        for($i=0; $i<$cage_amount; $i++)
        {
            //name  鸽笼编号
            $name = $max_name + $i + 1;
            $cage_data = $model_Cage->addCage($block_data['block_id'],  $pigeon, $egg, $squab, $child, $youth, $name);
            if($cage_data['code'] != 20000){
                DB::rollBack();
                return response()->json($block_data);
            }
        }
        Db::commit();
        return response()->json($cage_data);
    }


    /**
     * 仓库设置--创建仓库多条
     * @param Request $request
     * @return mixed
     */
    public function  warehouseAddList(Request $request)
    {
        $input = $request->all();
        $factory_name = isset($input['factory_name']) ? $input['factory_name'] : '';
        $params =  isset($input['params']) ? $input['params'] : [];
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $pigeon = isset($input['pigeon']) ? $input['pigeon'] : 0;
        $egg = isset($input['egg']) ? $input['egg'] : 0;
        $squab = isset($input['squab']) ? $input['squab'] : 0;
        $child = isset($input['child']) ? $input['child'] : 0;
        $youth = isset($input['youth']) ? $input['youth'] : 0;

        if(empty($params))
        {
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        }
//        return response()->json($params);
        $model_factory = new Factory();
        $model_block = new Block();
        $model_Cage = new Cage();
        DB::beginTransaction();
        $factory_data = $model_factory->addFactory($factory_name, $firm_id);
        if($factory_data['code'] != 20000){
            DB::rollBack();
            return response()->json($factory_data);
        }

        //根据厂区查询最大鸽笼编号
        $max_name = $model_Cage->getMaxName($factory_data['factory_id']);
        foreach ($params as $v)
        {
            $block_data = $model_block->addBlock($v['block_name'], $v['block_type'], $v['type_name'], $factory_data['factory_id']);
            if($block_data['code'] != 20000){
                DB::rollBack();
                return response()->json($block_data);
            }
            $cage_data = ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
            for($i=0; $i<$v['cage_amount']; $i++)
            {
                //name  鸽笼编号
                $name = $max_name + $i + 1;
                $cage_data = $model_Cage->addCage($block_data['block_id'], $pigeon, $egg, $squab, $child, $youth, $name);
                if($cage_data['code'] != 20000){
                    DB::rollBack();
                    return response()->json($cage_data);
                }
            }
        }
        Db::commit();
        return response()->json($cage_data);
    }

    /**
     * 仓库设置--新建仓号（多条）
     * @param Request $request
     * @return mixed
     */
    public function  warehouseAddBlocks(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $params =  isset($input['params']) ? $input['params'] : [];
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $pigeon = isset($input['pigeon']) ? $input['pigeon'] : 0;
        $egg = isset($input['egg']) ? $input['egg'] : 0;
        $squab = isset($input['squab']) ? $input['squab'] : 0;
        $child = isset($input['child']) ? $input['child'] : 0;
        $youth = isset($input['youth']) ? $input['youth'] : 0;

        if(empty($params) || empty($factory_id))
        {
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        }

        $model_block = new Block();
        $model_Cage = new Cage();
        DB::beginTransaction();
        //根据厂区查询最大鸽笼编号
        $max_name = $model_Cage->getMaxName($factory_id);
        foreach ($params as $v)
        {
            $block_data = $model_block->addBlock($v['block_name'], $v['block_type'], $v['type_name'], $factory_id);
            if($block_data['code'] != 20000){
                DB::rollBack();
                return response()->json($block_data);
            }
            $cage_data = ['code'=>20000,'msg'=>'新增失败', 'data'=>[]];
            for($i=0; $i<$v['cage_amount']; $i++)
            {
                //name  鸽笼编号
                $name = $max_name + $i + 1;
                $cage_data = $model_Cage->addCage($block_data['block_id'], $pigeon, $egg, $squab, $child, $youth, $name);
                if($cage_data['code'] != 20000){
                    DB::rollBack();
                    return response()->json($cage_data);
                }
            }
        }
        Db::commit();
        return response()->json($cage_data);
    }

    /**
     * 仓库设置--新建鸽笼（多条）
     * @param Request $request
     * @return mixed
     */
    public function  warehouseAddCages(Request $request)
    {
        $input = $request->all();
        $factory_id = isset($input['factory_id']) ? $input['factory_id'] : '';
        $params =  isset($input['params']) ? $input['params'] : [];
//        $block_id = isset($input['block_id']) ? $input['block_id'] : '';
//        $cage_amount =  isset($input['cage_amount']) ? $input['cage_amount'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $pigeon = isset($input['pigeon']) ? $input['pigeon'] : 0;
        $egg = isset($input['egg']) ? $input['egg'] : 0;
        $squab = isset($input['squab']) ? $input['squab'] : 0;
        $child = isset($input['child']) ? $input['child'] : 0;
        $youth = isset($input['youth']) ? $input['youth'] : 0;

        if(empty($params) || empty($factory_id))
        {
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        }
        foreach ($params as $v)
        {
            if(empty($v['block_id']) || empty($v['cage_amount']))
            {
                return response()->json(['code'=>60000,'msg'=>'缺少参数, 没有选择仓号ID',  'data'=>[]]);
            }
        }
        $model_Cage = new Cage();
        DB::beginTransaction();
        //根据厂区查询最大鸽笼编号
        $max_name = $model_Cage->getMaxName($factory_id);
        foreach ($params as $v)
        {
            $cage_data = ['code'=>20000,'msg'=>'新增失败', 'data'=>[]];
            for($i=0; $i<$v['cage_amount']; $i++)
            {
                //name  鸽笼编号
                $name = $max_name + $i + 1;
                $cage_data = $model_Cage->addCage($v['block_id'], $pigeon, $egg, $squab, $child, $youth, $name);
                if($cage_data['code'] != 20000){
                    DB::rollBack();
                    return response()->json($cage_data);
                }
            }
        }
        Db::commit();
        return response()->json($cage_data);
    }

    /**
     * 仓库设置--编辑
     * @param Request $request
     * @return mixed
     */
    public function  editWarehouse(Request $request)
    {
        $input = $request->all();
        $block_id = isset($input['block_id']) ? $input['block_id'] : '';
        $cage_ids =  isset($input['cage_ids']) ? $input['cage_ids'] : '';
        if(empty($block_id) || empty($cage_ids))
        {
            return response()->json(['code'=>60000,'msg'=>'缺少参数',  'data'=>[]]);
        }
        $params = explode(',', $cage_ids);
        $model_Cage = new Cage();
        DB::beginTransaction();
        $cage_data = ['code'=>40000,'msg'=>'修改失败',  'data'=>[]];
        foreach ($params as $v)
        {
            $cage_data = $model_Cage->editCageBlock($v, $block_id);
            if($cage_data['code'] != 20000){
                DB::rollBack();
                return response()->json($cage_data);
            }
        }
        Db::commit();
        return response()->json($cage_data);
    }

    /**
     * 仓库设置--全部仓号类型 默认值
     * @param Request $request
     * @return mixed
     */
    public function  warehouseType(Request $request)
    {
        //仓号类型 1：生产仓 2：育雏仓 3：飞棚仓
        $type_data = [
            [
                'id'=>1,
                'name'=>'生产仓',
            ],
            [
                'id'=>2,
                'name'=>'育雏仓',
            ],
            [
                'id'=>3,
                'name'=>'飞棚仓',
            ]
        ];
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$type_data]);
    }

    /**
     * 仓库设置--全部仓号类型 默认值
     * @param Request $request`
     * @return mixed
     */
    public function  getUsers(Request $request)
    {
        $input = $request->all();
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : '';
        $model_user = new User();
        $return_data = $model_user->getUserByFirmID($firm_id, $role_id);
        return response()->json(['code'=>20000,'msg'=>'请求成功',  'data'=>$return_data]);
    }

    /**
     * 上传企业头像
     * @param Request $request
     * @return mixed
     */
    public function uploadAvatar(Request $request)
    {
        if ($request->isMethod('POST')) { //判断文件是否是 POST的方式上传
            $tmp = $request->file('file');
            if(empty($tmp)) return response()->json(['code'=>40000,'msg'=>'文件流不存在', 'data'=>[]]);
            if ($tmp->isValid()) { //判断文件上传是否有效
                $FileType = $tmp->getClientOriginalExtension(); //获取文件后缀

                $FilePath = $tmp->getRealPath(); //获取文件临时存放位置

                $FileName = date('Ymd') . uniqid() . '.' . $FileType; //定义文件名

                Storage::disk('avatar')->put($FileName, file_get_contents($FilePath)); //存储文件
                $IMAGE_URL = env('IMAGE_URL');
                $AVATAR_URL= env('AVATAR_URL');
                $obj['url'] = $IMAGE_URL.$AVATAR_URL. $FileName;
                $data['code'] = 20000;
                $data['data'] = $obj;
                $data['file_name'] = $AVATAR_URL.$FileName;
                $data['msg'] = "";
                $data['time'] = time();
                return response()->json($data);
            }
        }
    }

    /**
     * 验证输入的手机号码
     * @param  $user_mobile
     * @return bool
     */
    function is_mobile($user_mobile)
    {
        $chars = "/^((\(\d{2,3}\))|(\d{3}\-))?1(3|4|5|7|8|9)\d{9}$/";
        if (preg_match($chars, $user_mobile)){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 验证厂区名字是否已经存在
     * @param Request $request
     * @return mixed
     */
    function checkFactoryName(Request $request)
    {
        $input = $request->all();
        $factory_name = isset($input['factory_name']) ? $input['factory_name'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($factory_name) || empty($firm_id) ) return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_factory = new Factory();
        $exists = $model_factory->existsFactory($factory_name, $firm_id);
        if(empty($exists))
            return response()->json(['code'=>20000,'msg'=>'', 'data'=>[]]);
        else
            return response()->json(['code'=>40000,'msg'=>'已有 '.$factory_name.' 厂区', 'data'=>[]]);
    }
}

