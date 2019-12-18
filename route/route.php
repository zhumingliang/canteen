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
//Route::rule('api/:version/token/official', 'api/:version.Token/getOfficialToken')->middleware(\Naixiaoxin\ThinkWechat\Middleware\OauthMiddleware::class);
Route::rule('api/:version/token/official', 'api/:version.Token/getOfficialToken');
Route::rule('api/:version/token/machine', 'api/:version.Token/getMachineToken');
Route::post('api/:version/token/supplier', 'api/:version.Token/getSupplierToken');

Route::post('api/:version/module/system/save', 'api/:version.Module/saveSystem');
Route::post('api/:version/module/system/canteen/save', 'api/:version.Module/saveSystemCanteen');
Route::post('api/:version/module/system/shop/save', 'api/:version.Module/saveSystemShop');
Route::post('api/:version/module/system/handel', 'api/:version.Module/handelSystem');
Route::post('api/:version/module/default/handel', 'api/:version.Module/handelModuleDefaultStatus');
Route::post('api/:version/module/update', 'api/:version.Module/updateModule');
Route::post('api/:version/module/company/update', 'api/:version.Module/updateCompanyModule');
Route::get('api/:version/modules', 'api/:version.Module/systemModules');
Route::get('api/:version/modules/canteen/withSystem', 'api/:version.Module/canteenModulesWithSystem');
Route::get('api/:version/modules/shop/withSystem', 'api/:version.Module/shopModulesWithSystem');
Route::get('api/:version/modules/canteen/withoutSystem', 'api/:version.Module/canteenModulesWithoutSystem');
Route::get('api/:version/modules/user', 'api/:version.Module/userMobileModules');
Route::get('api/:version/modules/admin', 'api/:version.Module/adminModules');

Route::post('api/:version/company/save', 'api/:version.Company/save');
Route::post('api/:version/company/wxConfig/save', 'api/:version.Company/saveCompanyWxConfig');
Route::get('api/:version/companies', 'api/:version.Company/companies');
Route::get('api/:version/company/consumptionLocation', 'api/:version.Company/consumptionLocation');
Route::get('api/:version/manager/companies', 'api/:version.Company/managerCompanies');
Route::get('api/:version/user/companies', 'api/:version.Company/userCompanies');
Route::get('api/:version/admin/companies', 'api/:version.Company/adminCompanies');

Route::post('api/:version/canteen/save', 'api/:version.Canteen/save');
Route::post('api/:version/canteen/dinner/delete', 'api/:version.Canteen/deleteDinner');
Route::post('api/:version/canteen/configuration/save', 'api/:version.Canteen/saveConfiguration');
Route::post('api/:version/canteen/configuration/update', 'api/:version.Canteen/updateConfiguration');
Route::post('api/:version/canteen/consumptionStrategy/save', 'api/:version.Canteen/saveConsumptionStrategy');
Route::post('api/:version/canteen/consumptionStrategy/update', 'api/:version.Canteen/updateConsumptionStrategy');
Route::post('api/:version/canteen/model/category', 'api/:version.Canteen/canteenModuleCategoryHandel');
Route::post('api/:version/canteen/saveComment', 'api/:version.Canteen/saveComment');
Route::post('api/:version/canteen/saveMachine', 'api/:version.Canteen/saveMachine');
Route::post('api/:version/canteen/updateMachine', 'api/:version.Canteen/updateMachine');
Route::post('api/:version/canteen/deleteMachine', 'api/:version.Canteen/deleteMachine');
Route::get('api/:version/canteen/consumptionStrategy', 'api/:version.Canteen/consumptionStrategy');
Route::get('api/:version/canteen/configuration', 'api/:version.Canteen/configuration');
Route::get('api/:version/canteens/role', 'api/:version.Canteen/roleCanteens');
Route::get('api/:version/canteens/company', 'api/:version.Canteen/getCanteensForCompany');
Route::get('api/:version/canteens', 'api/:version.Canteen/canteens');
Route::get('api/:version/managerCanteens', 'api/:version.Canteen/managerCanteens');
Route::get('api/:version/canteen/dinners/user', 'api/:version.Canteen/currentCanteenDinners');
Route::get('api/:version/canteen/dinners', 'api/:version.Canteen/canteenDinners');
Route::get('api/:version/canteen/diningMode', 'api/:version.Canteen/diningMode');
Route::get('api/:version/machines/company', 'api/:version.Canteen/companyMachines');
Route::get('api/:version/machines', 'api/:version.Canteen/machines');

Route::get('api/:version/roles', 'api/:version.Role/roles');
Route::get('api/:version/role', 'api/:version.Role/role');
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
Route::post('api/:version/department/delete', 'api/:version.Department/delete');
Route::get('api/:version/departments', 'api/:version.Department/departments');
Route::get('api/:version/departments/recharge', 'api/:version.Department/departmentsForRecharge');
Route::get('api/:version/department/staffs', 'api/:version.Department/departmentStaffs');
Route::get('api/:version/department/staffs/recharge', 'api/:version.Department/staffsForRecharge');
Route::get('api/:version/admin/departments', 'api/:version.Department/adminDepartments');
Route::post('api/:version/department/staff/save', 'api/:version.Department/addStaff');
Route::post('api/:version/department/staff/update', 'api/:version.Department/updateStaff');
Route::post('api/:version/department/staff/delete', 'api/:version.Department/deleteStaff');
Route::post('api/:version/department/staff/upload', 'api/:version.Department/uploadStaffs');
Route::post('api/:version/department/staff/move', 'api/:version.Department/moveStaffDepartment');
Route::get('api/:version/staffs', 'api/:version.Department/staffs');
Route::get('api/:version/export/staffs', 'api/:version.Department/exportStaffs');
Route::post('api/:version/staff/qrcode/save', 'api/:version.Department/createStaffQrcode');

Route::rule('api/:version/consumption/staff', 'api/:version.Consumption/staff');

Route::rule('api/:version/image/upload', 'api/:version.Image/upload');

Route::post('api/:version/menu/save', 'api/:version.Menu/save');
Route::get('api/:version/menus/company', 'api/:version.Menu/companyMenus');
Route::get('api/:version/menus/canteen', 'api/:version.Menu/canteenMenus');
Route::get('api/:version/menus/dinner', 'api/:version.Menu/dinnerMenus');

Route::post('api/:version/food/save', 'api/:version.Food/save');
Route::post('api/:version/food/saveComment', 'api/:version.Food/saveComment');
Route::post('api/:version/food/update', 'api/:version.Food/update');
Route::post('api/:version/food/handel', 'api/:version.Food/handel');
Route::post('api/:version/food/day/handel', 'api/:version.Food/handelFoodsDayStatus');
Route::get('api/:version/foods', 'api/:version.Food/foods');
Route::get('api/:version/foods/officialManager', 'api/:version.Food/foodsForOfficialManager');
Route::get('api/:version/foods/personChoice', 'api/:version.Food/foodsForOfficialPersonChoice');
Route::get('api/:version/foods/menu', 'api/:version.Food/foodsForOfficialMenu');
Route::get('api/:version/food', 'api/:version.Food/food');
Route::get('api/:version/food/info/comment', 'api/:version.Food/infoToComment');
Route::post('api/:version/food/material/update', 'api/:version.Food/updateMaterial');

Route::post('api/:version/material/save', 'api/:version.Material/save');
Route::post('api/:version/material/update', 'api/:version.Material/update');
Route::post('api/:version/material/handel', 'api/:version.Material/handel');
Route::post('api/:version/material/upload', 'api/:version.Material/uploadMaterials');
Route::get('api/:version/material/export', 'api/:version.Material/export');
Route::get('api/:version/materials', 'api/:version.Material/materials');
Route::get('api/:version/materials/food', 'api/:version.Material/foodMaterials');
Route::get('api/:version/material/exportFoodMaterials', 'api/:version.Material/exportFoodMaterials');
Route::get('api/:version/material/exportMaterialReports', 'api/:version.Material/exportMaterialReports');
Route::get('api/:version/material/exportOrderMaterials', 'api/:version.Material/exportOrderMaterials');

Route::rule('api/:version/weixin/server', 'api/:version.WeiXin/server');
Route::rule('api/:version/weixin/menu/save', 'api/:version.WeiXin/createMenu');

Route::post('api/:version/sms/send', 'api/:version.SendSMS/sendCode');

Route::post('api/:version/user/bindPhone', 'api/:version.User/bindPhone');
Route::post('api/:version/user/bindCanteen', 'api/:version.User/bindCanteen');
Route::get('api/:version/user/canteenMenus', 'api/:version.User/userCanteenMenus');
Route::get('api/:version/user/canteens', 'api/:version.User/userCanteens');
Route::get('api/:version/user/card', 'api/:version.User/mealCard');
Route::get('api/:version/user/phone', 'api/:version.User/userPhone');

Route::post('api/:version/order/personChoice/save', 'api/:version.Order/personChoice');
Route::post('api/:version/order/online/save', 'api/:version.Order/orderingOnline');
Route::get('api/:version/order/userOrdering', 'api/:version.Order/userOrdering');
Route::get('api/:version/order/online/info', 'api/:version.Order/infoForOnline');
Route::get('api/:version/order/personChoice/info', 'api/:version.Order/infoForPersonChoiceOnline');
Route::get('api/:version/order/personalChoice/info', 'api/:version.Order/personalChoiceInfo');
Route::post('api/:version/order/cancel', 'api/:version.Order/orderCancel');
Route::post('api/:version/order/changeCount', 'api/:version.Order/changeOrderCount');
Route::post('api/:version/order/changeFoods', 'api/:version.Order/changeOrderFoods');
Route::post('api/:version/order/changeAddress', 'api/:version.Order/changeOrderAddress');
Route::post('api/:version/order/handelOrderedNoMeal', 'api/:version.Order/handelOrderedNoMeal');
Route::get('api/:version/order/userOrderings', 'api/:version.Order/userOrderings');
Route::get('api/:version/order/consumptionRecords', 'api/:version.Order/consumptionRecords');
Route::get('api/:version/order/detail', 'api/:version.Order/orderDetail');
Route::get('api/:version/order/consumptionRecords/detail', 'api/:version.Order/recordsDetail');
Route::get('api/:version/order/managerOrders', 'api/:version.Order/managerOrders');
Route::get('api/:version/order/managerDinnerStatistic', 'api/:version.Order/managerDinnerStatistic');
Route::get('api/:version/order/usersStatistic', 'api/:version.Order/orderUsersStatistic');
Route::get('api/:version/order/foodUsersStatistic', 'api/:version.Order/foodUsersStatistic');
Route::get('api/:version/order/orderStatistic', 'api/:version.Order/orderStatistic');
Route::get('api/:version/order/orderStatistic/detail', 'api/:version.Order/orderStatisticDetail');
Route::get('api/:version/order/orderSettlement', 'api/:version.Order/orderSettlement');
Route::get('api/:version/order/materialsStatistic', 'api/:version.Order/orderMaterialsStatistic');
Route::post('api/:version/order/material/update', 'api/:version.Order/updateOrderMaterial');
Route::get('api/:version/order/material/reports', 'api/:version.Order/materialReports');
Route::get('api/:version/order/material/report', 'api/:version.Order/materialReport');
Route::post('api/:version/order/material/report/delete', 'api/:version.Order/materialReportHandel');
Route::get('api/:version/order/consumptionStatistic', 'api/:version.Order/consumptionStatistic');

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
Route::get('api/:version/company/suppliers', 'api/:version.Supplier/companySuppliers');

Route::post('api/:version/category/save', 'api/:version.Category/save');
Route::post('api/:version/category/update', 'api/:version.Category/update');
Route::post('api/:version/category/delete', 'api/:version.Category/delete');
Route::get('api/:version/categories', 'api/:version.Category/categories');
Route::get('api/:version/company/categories', 'api/:version.Category/companyCategories');

Route::post('api/:version/shop/product/save', 'api/:version.Shop/saveProduct');
Route::post('api/:version/shop/save', 'api/:version.Shop/saveShop');
Route::post('api/:version/shop/update', 'api/:version.Shop/updateShop');
Route::post('api/:version/shop/delete', 'api/:version.Shop/deleteShop');
Route::post('api/:version/shop/order/save', 'api/:version.Shop/saveOrder');
Route::post('api/:version/shop/order/cancel', 'api/:version.Shop/orderCancel');
Route::post('api/:version/shop/product/update', 'api/:version.Shop/updateProduct');
Route::post('api/:version/shop/product/handel', 'api/:version.Shop/handel');
Route::post('api/:version/shop/stock/save', 'api/:version.Shop/saveProductStock');
Route::post('api/:version/shop/product/saveComment', 'api/:version.Shop/saveComment');
Route::get('api/:version/shop/product', 'api/:version.Shop/product');
Route::get('api/:version/shop/products', 'api/:version.Shop/products');
Route::get('api/:version/shop/official/products', 'api/:version.Shop/officialProducts');
Route::get('api/:version/shop/supplier/products', 'api/:version.Shop/supplierProducts');
Route::get('api/:version/shop/cms/products', 'api/:version.Shop/cmsProducts');
Route::get('api/:version/shop/product/comments', 'api/:version.Shop/productComments');
Route::get('api/:version/shop/order/deliveryCode', 'api/:version.Shop/deliveryCode');
Route::get('api/:version/shop/order/statistic/supplier', 'api/:version.Shop/orderDetailStatisticToSupplier');
Route::get('api/:version/shop/order/statistic/manager', 'api/:version.Shop/orderStatisticToManager');
Route::get('api/:version/shop/takingMode', 'api/:version.Shop/takingMode');
Route::get('api/:version/shop/salesReport/supplier', 'api/:version.Shop/salesReportToSupplier');
Route::get('api/:version/shop/salesReport/manager', 'api/:version.Shop/salesReportToManager');
Route::get('api/:version/shop/orderConsumption', 'api/:version.Shop/consumptionStatistic');
Route::get('api/:version/shop/companyProducts/search', 'api/:version.Shop/companyProductsToSearch');
Route::get('api/:version/shop/supplierProducts/search', 'api/:version.Shop/supplierProductsToSearch');
Route::get('api/:version/shop/order/products', 'api/:version.Shop/shopOrderProducts');

Route::get('api/:version/order/takeoutStatistic', 'api/:version.Takeout/statistic');
Route::get('api/:version/order/info/print', 'api/:version.Takeout/infoToPrint');
Route::post('api/:version/order/used', 'api/:version.Takeout/used');

Route::post('api/:version/wallet/recharge/cash', 'api/:version.Wallet/rechargeCash');
Route::post('api/:version/wallet/recharge/upload', 'api/:version.Wallet/rechargeCashUpload');
Route::post('api/:version/wallet/clearBalance', 'api/:version.Wallet/clearBalance');
Route::get('api/:version/wallet/recharge/admins', 'api/:version.Wallet/rechargeAdmins');
Route::get('api/:version/wallet/recharges', 'api/:version.Wallet/rechargeRecords');
Route::get('api/:version/wallet/users/balance', 'api/:version.Wallet/usersBalance');
Route::post('api/:version/wallet/supplement', 'api/:version.Wallet/rechargeSupplement');
Route::post('api/:version/wallet/supplement/upload', 'api/:version.Wallet/rechargeSupplementUpload');
Route::post('api/:version/wallet/pay', 'api/:version.Wallet/saveOrder');
Route::get('api/:version/wallet/pay/getPreOrder', 'api/:version.Wallet/getPreOrder');
Route::rule('api/:version/wallet/WXNotifyUrl', 'api/:version.Wallet/WXNotifyUrl');


Route::rule('api/:version/service/orderStateHandel', 'api/:version.Service/orderStateHandel');
Route::rule('api/:version/service/sendMsgHandel', 'api/:version.Service/sendMsgHandel');
Route::rule('api/:version/service/noticeHandel', 'api/:version.Service/sendNoticeHandel');

