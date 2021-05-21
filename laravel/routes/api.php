<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//用户路由
$router->group(['prefix' => 'user'], function () use ($router) {
    $router->group(['middleware'=>['authToken']],function () use ($router) {
        //$router->post("/test",'account\AccountController@test'); //测试接口
        $router->post("/logout", 'account\AccountController@logout');//用户退出登录

    });
    $router->group(['middleware'=>['authToken', 'authPermissions']],function () use ($router) {
        //$router->post("/admin_list",'power\DoveRolesController@adminList'); //管理员列表
        $router->post("/user_list",'user\UserController@userList'); //人员列表
        $router->post("/get_all_users",'user\UserController@getUsers'); //通过条件筛选用户

        $router->post("/all_factory", 'user\UserController@getAllFactory');//所有厂区
        $router->post("/block_type", 'user\UserController@getBlockType');//获取工厂内未分配的仓号类型
        $router->post("/blocks", 'user\UserController@getBlockIds');//获取工厂内未分配的仓号
        $router->post("/avatar", 'account\AccountController@uploadAvatar');//上传头像
        $router->post("/edit_avatar", 'account\AccountController@editAvatar');//修改头像
        $router->post("/all_cages", 'user\UserController@getAllCages');//获取仓号下所有鸽笼ID
        $router->post("/all_blocks", 'user\UserController@getAllBlocks');//获取所有仓号以及全部鸽笼ID

        $router->post("/add_user", 'user\UserController@addUser');//人员管理--添加
        $router->post("/edit_user", 'user\UserController@editUser');//人员管理--编辑
        $router->post("/edit_status", 'user\UserController@editUserStatus');//人员管理--修改状态
        $router->post("/reset_password", 'user\UserController@resetPassword');//人员管理--重置密码
        $router->post("/del_user", 'user\UserController@delUser');//人员管理--删除

        $router->post("/all_roles", 'user\UserController@getAllRoles');//所有角色
        $router->post("/role_list", 'power\DoveRolesController@roleList'); //管理员角色列表
        $router->post("/role_per_list", 'power\DoveRolesController@rolePerMenu'); //权限菜单列表
        $router->post("/del_role", 'power\DoveRolesController@delRole'); //删除角色
        $router->post("/add_role", 'power\DoveRolesController@addRole'); //新增角色
        $router->post("/edit_role", 'power\DoveRolesController@editRole'); //编辑角色
        $router->post("/add_per", 'power\DoveRolesController@rolePermissions'); //角色分配权限

        $router->post("/banner_list", 'IndexController@bannerList');//轮播图列表
        $router->post("/banner_show", 'IndexController@editBannerShow');//轮播图显示与隐藏
        $router->post("/banner_rank", 'IndexController@editBannerRank');//轮播图顺序
        $router->post("/del_banner", 'IndexController@delBanner');//轮播图删除
        $router->post("/upload_b_img", 'IndexController@uploadBannerImage');//上传轮播图图片
        $router->post("/add_banner", 'IndexController@addBanner');//添加轮播图
        $router->post("/edit_banner_info", 'IndexController@editBannerInfo');//编辑轮播图

        $router->post("/all_category", 'IndexController@newsCategory');//获取全部新闻分类
        $router->post("/news_list", 'IndexController@newsList');//获取新闻列表
        $router->post("/upload_n_img", 'IndexController@uploadNewsImg');//上传新闻图片
        $router->post("/add_news", 'IndexController@addNews');//上传新闻图片
        $router->post("/edit_news_info", 'IndexController@editNewsInfo');//编辑新闻
        $router->post("/del_news", 'IndexController@delNews');//删除新闻
        $router->post("/batch_del_news", 'IndexController@batchDelNews');//批量删除新闻
        $router->post("/news_content", 'IndexController@newsContent');//获取新闻内容
        $router->post("/opinion_list", 'IndexController@opinionList');//意见反馈列表
        $router->post("/edit_opinion", 'IndexController@editOpinion');//意见反馈修改读取状态
        $router->post("/batch_del", 'IndexController@batchDelOpinion');//意见反馈批量删除


        $router->post("/edit_password", 'account\AccountController@editPassword');//修改密码
//        $router->post("/test",'account\AccountController@test'); //测试接口
//        $router->post("/test11",'TestController@test'); //测试接口\
        $router->post("/item_type", 'platform\PlatformController@getItemType');//物品全部类型
        $router->post("/item_name", 'platform\PlatformController@getItemName');//某一类全部物品
        $router->post("/item_info", 'platform\PlatformController@getItemInfo');//物品列表
        $router->post("/add_item", 'platform\PlatformController@addItem');//物品录入
        $router->post("/upload_i_img", 'platform\PlatformController@uploadItemImg');//物品录入上传图片
        $router->post("/edit_item", 'platform\PlatformController@editItem');//物品编辑
        $router->post("/del_item", 'platform\PlatformController@delItem');//物品删除
        $router->post("/early_list", 'platform\PlatformController@earlyList');//预警列表
        $router->post("/edit_early", 'platform\PlatformController@editEarly');//预警编辑
        $router->post("/edit_firm_user", 'platform\PlatformController@editFirmUser');//编辑管理员
        $router->post("/del_firm", 'platform\PlatformController@delPlatform');//删除企业
        $router->post("/edit_firm_status", 'platform\PlatformController@editFirmStatus');//修改企业状态
        $router->post("/get_users", 'platform\PlatformController@getUsers');//获取企业下所有人员


        $router->post("/warehouse_list", 'platform\PlatformController@warehouseList');//仓库列表
        $router->post("/warehouse_add", 'platform\PlatformController@warehouseAdd');//新建仓库
        $router->post("/warehouse_add_list", 'platform\PlatformController@warehouseAddList');//新建仓库多条
        $router->post("/warehouse_add_blocks", 'platform\PlatformController@warehouseAddBlocks');//新建仓号多条
        $router->post("/warehouse_add_cages", 'platform\PlatformController@warehouseAddCages');//新建鸽笼多条
        $router->post("/warehouse_type", 'platform\PlatformController@warehouseType');//全部仓号类型
        $router->post("/warehouse_edit", 'platform\PlatformController@editWarehouse');//修改鸽笼绑定仓号

        $router->post("/cage_info", 'entry\entryData@getCageInfo');//查询鸽笼信息
        $router->post("/squab_data", 'entry\entryData@squabData');//录入乳鸽数据
        $router->post("/pigeon_data", 'entry\entryData@pigeonData');//录入种鸽数据
        $router->post("/child_data", 'entry\entryData@childData');//录入童鸽数据
        $router->post("/brood_data", 'entry\entryData@broodData');//录入育雏仓数据
        $router->post("/egg_data", 'entry\entryData@eggData');//录入鸽蛋数据
        $router->post("/young_data", 'entry\entryData@youngData');//录入飞棚仓数据
        $router->post("/other_data", 'entry\entryData@otherData');//录入其他数据
        $router->post("/anomaly_data", 'entry\entryData@anomalyData');//异常数据列表
        $router->post("/edit_anomaly", 'entry\entryData@editAnomaly');//异常数据列表处理状态修改
        $router->post("/all_c_type", 'entry\entryData@AllCageType');//全部鸽子类型
        $router->post("/data_log_list", 'entry\entryData@dataLogList');//录入数据列表
        $router->post("/edit_log_data", 'entry\entryData@editLogData');//录入数据 修改数据

        $router->post("/message_list", 'message\MessageController@messageList');//消息列表
        $router->post("/add_message", 'message\MessageController@addMessage');//新增消息
        $router->post("/del_message", 'message\MessageController@delMessage');//删除消息
        $router->post("/batch_del_message", 'message\MessageController@batchDelMessage');//批量删除消息

        $router->post("/get_data", 'agenda\AgendaController@getAllData');//无害化处理 查询数据
        $router->post("/entry_harmless", 'agenda\AgendaController@entryHarmless');//无害化处理 录入数据
        $router->post("/get_h_list", 'agenda\AgendaController@getList');//无害化处理 列表
        $router->post("/edit_h_data", 'agenda\AgendaController@editData');//无害化处理 编辑
        $router->post("/del_h_data", 'agenda\AgendaController@delData');//无害化处理 删除
        $router->post("/check_h_data", 'agenda\AgendaController@checkData');//无害化处理 审核

        $router->post("/sell_s_data", 'agenda\AgendaController@sellData');//可销售数据
        $router->post("/sell_s_list", 'agenda\AgendaController@sellList');//销售数据列表
        $router->post("/sell_s_info", 'agenda\AgendaController@selectInfo');//销售数据选择框内容
        $router->post("/entry_sell", 'agenda\AgendaController@entrySell');//销售录入数据
        $router->post("/edit_s_sell", 'agenda\AgendaController@editSell');//销售编辑
        $router->post("/del_s_sell", 'agenda\AgendaController@delSellData');//销售删除

        $router->post("/food_list", 'agenda\AgendaController@foodList');//粮食储藏--列表
        $router->post("/food_log", 'agenda\AgendaController@enterFoodList');//粮食储藏--出入库
        $router->post("/edit_food", 'agenda\AgendaController@editFood');//粮食储藏--编辑
        $router->post("/entry_food", 'agenda\AgendaController@entryFood');//粮食储藏--新增
        $router->post("/del_food", 'agenda\AgendaController@delFoodData');//粮食储藏--删除

        $router->post("/get_d_list", 'agenda\AgendaController@disinfectList');//消杀--列表
        $router->post("/entry_disinfect", 'agenda\AgendaController@entryDisinfect');//消杀--录入
        $router->post("/edit_disinfect", 'agenda\AgendaController@editDisinfect');//消杀--编辑
        $router->post("/del_disinfect", 'agenda\AgendaController@delDisinfect');//消杀--删除
        $router->post("/get_breeder", 'agenda\AgendaController@getBreeder');//获取仓号饲养员

        $router->post("/fodder_list", 'agenda\AgendaController@fodderList');//饲料消耗列表
        $router->post("/entry_fodder", 'agenda\AgendaController@entryFodder');//饲料消耗录入
        $router->post("/edit_fodder", 'agenda\AgendaController@editFodder');//饲料消耗编辑
        $router->post("/del_fodder", 'agenda\AgendaController@delFodder');//饲料消耗删除

        //$router->post("/get_drugs_info", 'agenda\AgendaController@getDrugsId');//药品-药品信息
        $router->post("/get_production", 'agenda\AgendaController@getProduction');//查询生产厂家
        $router->post("/get_batch", 'agenda\AgendaController@getBatch');//查询生产批号
        $router->post("/get_drug_id", 'agenda\AgendaController@getDrugId');//查询药品ID
        $router->post("/drug_list", 'agenda\AgendaController@drugList');//药品列表
        $router->post("/drug_log_list", 'agenda\AgendaController@drugLogList');//药品出入库--列表
        $router->post("/add_drugs_log", 'agenda\AgendaController@addDrugsLog');//药品--录入数据
        $router->post("/edit_drugs_log", 'agenda\AgendaController@editDrugsLog');//药品--编辑
        $router->post("/del_drugs_Log", 'agenda\AgendaController@delDrugsLog');//药品--删除

        $router->post("/drug_use_List", 'agenda\AgendaController@drugUseList');//药品使用记录--列表
        $router->post("/add_drug_use", 'agenda\AgendaController@addDrugUse');//药品使用记录--录入数据
        $router->post("/edit_drug_use", 'agenda\AgendaController@editDrugUse');//药品使用记录--编辑
        $router->post("/del_drug_use", 'agenda\AgendaController@delDrugUse');//药品使用记录--删除

        $router->post("/vaccine_use_list", 'agenda\AgendaController@vaccineUseList');//疫苗使用记录--列表
        $router->post("/add_vaccine_use", 'agenda\AgendaController@addVaccineUse');//疫苗使用记录--录入数据
        $router->post("/edit_vaccine_use", 'agenda\AgendaController@editVaccineUse');//疫苗使用记录--编辑
        $router->post("/del_vaccine_use", 'agenda\AgendaController@delVaccineUse');//疫苗使用记录--删除

        $router->post("/user_leave_list", 'agenda\AgendaController@userLeaveList');//请假--列表
        $router->post("/get_leave_types", 'agenda\AgendaController@getLeaveTypes');//请假--假期类型
        $router->post("/add_leave", 'agenda\AgendaController@addLeave');//请假--录入数据

        $router->post("/user_rule_list", 'agenda\AgendaController@userRuleList');//考勤--报表
        $router->post("/rule_type_list", 'agenda\AgendaController@userRuleTypeList');//考勤--记录
        $router->post("/rule_List", 'agenda\AgendaController@RuleList');//考勤--上下班时间列表
        $router->post("/entry_rule", 'agenda\AgendaController@entryRule');//考勤--上下班时间录入
        $router->post("/edit_rule", 'agenda\AgendaController@editRule');//考勤--上下班时间编辑
        $router->post("/edit_state", 'agenda\AgendaController@editRuleState');//考勤--上下班时间启用禁用
        $router->post("/del_rule", 'agenda\AgendaController@delRule');//考勤--上下班时间删除
        $router->post("/add_range", 'agenda\AgendaController@addRange');//考勤--打卡地点
    });
    //uploadFirmImage
    $router->post("/login", 'account\AccountController@login');//用户登录
    $router->post("/platform_info", 'platform\PlatformController@platFormInfo');//平台设置
    $router->post("/add_firm_user", 'platform\PlatformController@addFirmUser');//新增平台管理员
    $router->post("/firm_image", 'platform\PlatformController@uploadFirmImage');//上传管理员头像
    $router->post("/get_initial_password", 'platform\PlatformController@getInitialPassword');//获取初始密码
    $router->post("/edit_initial_password", 'platform\PlatformController@editInitialPassword');//修改初始密码
    $router->post("/firm_info", 'account\AccountController@firmInfo');//获取企业信息
    $router->post("/all_firm", 'IndexController@allFirm');//企业切换-全部企业

});

