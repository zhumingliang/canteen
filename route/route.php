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
Route::post('api/:version/module/update', 'api/:version.Module/updateModule');
Route::post('api/:version/module/company/update', 'api/:version.Module/updateCompanyModule');
Route::get('api/:version/modules', 'api/:version.Module/systemModules');
Route::get('api/:version/modules/company', 'api/:version.Module/companyModules');

Route::post('api/:version/company/save', 'api/:version.Company/save');
Route::get('api/:version/companies', 'api/:version.Company/companies');

Route::post('api/:version/canteen/save', 'api/:version.Canteen/save');
Route::post('api/:version/canteen/configuration/save', 'api/:version.Canteen/saveConfiguration');
Route::post('api/:version/canteen/configuration/update', 'api/:version.Canteen/updateConfiguration');
Route::get('api/:version/canteen/configuration', 'api/:version.Canteen/configuration');
