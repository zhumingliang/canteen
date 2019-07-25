<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::rule('/', 'api/v1.Index/index');
Route::rule('api/:version/index', 'api/:version.Index/index');

Route::post('api/:version/token/admin', 'api/:version.Token/getAdminToken');

Route::post('api/:version/module/system/save', 'api/:version.Module/saveSystem');
Route::post('api/:version/module/system/canteen/save', 'api/:version.Module/saveSystemCanteen');
Route::post('api/:version/module/system/shop/save', 'api/:version.Module/saveSystemShop');
Route::post('api/:version/module/system/handel', 'api/:version.Module/handelSystem');
Route::get('api/:version/modules', 'api/:version.Module/systemModules');
