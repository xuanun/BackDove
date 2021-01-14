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
$router->group(['middleware'=>'authToken'],function () use ($router) {
    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->post("/test",'account\AccountController@test'); //用户信息
        $router->post("/login", 'account\AccountController@login');//用户登录
        $router->post("/test11",'TestController@test'); //测试接口
    });
});
