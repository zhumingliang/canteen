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
Route::post('api/:version/canteen/consumptionStrategy/save', 'api/:version.Canteen/saveConsumptionStrategy');
Route::post('api/:version/canteen/consumptionStrategy/update', 'api/:version.Canteen/updateConsumptionStrategy');
Route::get('api/:version/canteen/consumptionStrategy', 'api/:version.Canteen/consumptionStrategy');
Route::get('api/:version/canteen/configuration', 'api/:version.Canteen/configuration');

Route::get('api/:version/roles', 'api/:version.Role/roles');
Route::post('api/:version/role/save', 'api/:version.Role/save');
Route::post('api/:version/role/update', 'api/:version.Role/update');
Route::post('api/:version/role/passwd/update', 'api/:version.Role/updatePasswd');
Route::post('api/:version/role/handel', 'api/:version.Role/handel');
Route::get('api/:version/role/types', 'api/:version.Role/roleTypes');
Route::post('api/:version/role/type/save', 'api/:version.Role/saveRoleType');
Route::post('api/:version/role/type/update', 'api/:version.Role/updateRoleType');
Route::post('api/:version/role/handel/type', 'api/:version.Role/handelType');


Route::post('api/:version/department/save', 'api/:version.Department/save');
Route::post('api/:version/department/update', 'api/:version.Department/update');
Route::get('api/:version/departments', 'api/:version.Department/departments');
Route::post('api/:version/department/staff/save', 'api/:version.Department/addStaff');
Route::post('api/:version/department/staff/update', 'api/:version.Department/updateStaff');
Route::post('api/:version/department/staff/delete', 'api/:version.Department/deleteStaff');
Route::post('api/:version/department/staff/upload', 'api/:version.Department/uploadStaffs');
Route::post('api/:version/department/staff/move', 'api/:version.Department/moveStaffDepartment');
Route::get('api/:version/staffs', 'api/:version.Department/staffs');
Route::post('api/:version/staff/qrcode/save', 'api/:version.Department/createStaffQrcode');

Route::rule('api/:version/consumption/staff', 'api/:version.Consumption/staff');
