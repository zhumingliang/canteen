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
Route::rule('api/:version/token/official', 'api/:version.Token/getOfficialToken')->middleware(\Naixiaoxin\ThinkWechat\Middleware\OauthMiddleware::class);

Route::post('api/:version/module/system/save', 'api/:version.Module/saveSystem');
Route::post('api/:version/module/system/canteen/save', 'api/:version.Module/saveSystemCanteen');
Route::post('api/:version/module/system/shop/save', 'api/:version.Module/saveSystemShop');
Route::post('api/:version/module/system/handel', 'api/:version.Module/handelSystem');
Route::post('api/:version/module/update', 'api/:version.Module/updateModule');
Route::post('api/:version/module/company/update', 'api/:version.Module/updateCompanyModule');
Route::get('api/:version/modules', 'api/:version.Module/systemModules');
Route::get('api/:version/modules/canteen/withSystem', 'api/:version.Module/canteenModulesWithSystem');
Route::get('api/:version/modules/shop/withSystem', 'api/:version.Module/shopModulesWithSystem');
Route::get('api/:version/modules/canteen/withoutSystem', 'api/:version.Module/canteenModulesWithoutSystem');
Route::get('api/:version/modules/user', 'api/:version.Module/userMobileModules');

Route::post('api/:version/company/save', 'api/:version.Company/save');
Route::get('api/:version/companies', 'api/:version.Company/companies');
Route::get('api/:version/manager/companies', 'api/:version.Company/managerCompanies');
Route::get('api/:version/user/companies', 'api/:version.Company/userCompanies');

Route::post('api/:version/canteen/save', 'api/:version.Canteen/save');
Route::post('api/:version/canteen/configuration/save', 'api/:version.Canteen/saveConfiguration');
Route::post('api/:version/canteen/configuration/update', 'api/:version.Canteen/updateConfiguration');
Route::post('api/:version/canteen/consumptionStrategy/save', 'api/:version.Canteen/saveConsumptionStrategy');
Route::post('api/:version/canteen/consumptionStrategy/update', 'api/:version.Canteen/updateConsumptionStrategy');
Route::post('api/:version/canteen/model/category', 'api/:version.Canteen/canteenModuleCategoryHandel');
Route::post('api/:version/canteen/saveComment', 'api/:version.Canteen/saveComment');
Route::get('api/:version/canteen/consumptionStrategy', 'api/:version.Canteen/consumptionStrategy');
Route::get('api/:version/canteen/configuration', 'api/:version.Canteen/configuration');
Route::get('api/:version/canteens/role', 'api/:version.Canteen/roleCanteens');
Route::get('api/:version/canteen/dinners/user', 'api/:version.Canteen/currentCanteenDinners');

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

Route::rule('api/:version/image/upload', 'api/:version.Image/upload');

Route::post('api/:version/menu/save', 'api/:version.Menu/save');
Route::get('api/:version/menus/company', 'api/:version.Menu/companyMenus');
Route::get('api/:version/menus/canteen', 'api/:version.Menu/canteenMenus');

Route::post('api/:version/food/save', 'api/:version.Food/save');
Route::post('api/:version/food/update', 'api/:version.Food/update');
Route::post('api/:version/food/handel', 'api/:version.Food/handel');
Route::post('api/:version/food/day/handel', 'api/:version.Food/handelFoodsDayStatus');
Route::get('api/:version/foods', 'api/:version.Food/foods');
Route::get('api/:version/foods/officialManager', 'api/:version.Food/foodsForOfficialManager');
Route::get('api/:version/foods/personChoice', 'api/:version.Food/foodsForOfficialPersonChoice');
Route::get('api/:version/foods/menu', 'api/:version.Food/foodsForOfficialMenu');
Route::get('api/:version/food', 'api/:version.Food/food');
Route::get('api/:version/food/info/comment', 'api/:version.Food/infoToComment');

Route::post('api/:version/material/save', 'api/:version.Material/save');
Route::post('api/:version/material/update', 'api/:version.Material/update');
Route::post('api/:version/material/handel', 'api/:version.Material/handel');
Route::post('api/:version/material/upload', 'api/:version.Material/uploadMaterials');
Route::get('api/:version/material/export', 'api/:version.Material/export');
Route::get('api/:version/materials', 'api/:version.Material/materials');
Route::get('api/:version/materials/food', 'api/:version.Material/foodMaterials');

Route::rule('api/:version/weixin/server', 'api/:version.WeiXin/server');
Route::rule('api/:version/weixin/menu/save', 'api/:version.WeiXin/createMenu');

Route::post('api/:version/sms/send', 'api/:version.SendSMS/sendCode');

Route::post('api/:version/user/bindPhone', 'api/:version.User/bindPhone');
Route::post('api/:version/user/bindCanteen', 'api/:version.User/bindCompany');
Route::get('api/:version/user/canteenMenus', 'api/:version.User/userCanteenMenus');
Route::get('api/:version/user/canteens', 'api/:version.User/userCanteens');
Route::get('api/:version/user/card', 'api/:version.User/mealCard');

Route::post('api/:version/order/personChoice/save', 'api/:version.Order/personChoice');
Route::post('api/:version/order/online/save', 'api/:version.Order/orderingOnline');
Route::get('api/:version/order/userOrdering', 'api/:version.Order/userOrdering');
Route::get('api/:version/order/online/info', 'api/:version.Order/infoForOnline');
Route::get('api/:version/order/personalChoice/info', 'api/:version.Order/personalChoiceInfo');
Route::post('api/:version/order/cancel', 'api/:version.Order/orderCancel');
Route::post('api/:version/order/changeCount', 'api/:version.Order/changeOrderCount');
Route::post('api/:version/order/changeFoods', 'api/:version.Order/changeOrderFoods');

Route::post('api/:version/address/save', 'api/:version.Address/save');
Route::post('api/:version/address/update', 'api/:version.Address/update');
Route::post('api/:version/address/handel', 'api/:version.Address/handel');
Route::get('api/:version/addresses', 'api/:version.Address/addresses');

Route::post('api/:version/notice/save', 'api/:version.Notice/save');
Route::post('api/:version/notice/delete', 'api/:version.Notice/deleteNotice');
Route::get('api/:version/notice', 'api/:version.Notice/notice');
Route::get('api/:version/notices/admin', 'api/:version.Notice/adminNotices');
Route::get('api/:version/notices/user', 'api/:version.Notice/userNotices');

Route::post('api/:version/supplier/save', 'api/:version.Supplier/save');
Route::post('api/:version/supplier/update', 'api/:version.Supplier/update');
Route::post('api/:version/supplier/delete', 'api/:version.Supplier/delete');
Route::get('api/:version/suppliers', 'api/:version.Supplier/suppliers');

Route::post('api/:version/category/save', 'api/:version.Category/save');
Route::post('api/:version/category/update', 'api/:version.Category/update');
Route::post('api/:version/category/delete', 'api/:version.Category/delete');
Route::get('api/:version/categories', 'api/:version.Category/categories');

Route::post('api/:version/shop/product/save', 'api/:version.Shop/saveProduct');
Route::post('api/:version/shop/product/update', 'api/:version.Shop/updateProduct');
Route::post('api/:version/shop/product/handel', 'api/:version.Shop/handel');
Route::post('api/:version/shop/stock/save', 'api/:version.Shop/saveProductStock');
Route::get('api/:version/shop/product', 'api/:version.Shop/product');
Route::get('api/:version/shop/products', 'api/:version.Shop/products');
