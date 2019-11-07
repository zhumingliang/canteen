<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:16
 */

namespace app\api\validate;


class Order extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'dinner_id' => 'require|isPositiveInteger',
        'food_id' => 'require|isPositiveInteger',
        'canteen_id' => 'require|isPositiveInteger',
        'count' => 'require|isPositiveInteger',
        'type' => 'require|in:1,2,3',
        'ordering_date' => 'require|isNotEmpty',
        'title' => 'require|isNotEmpty',
        'detail' => 'require|isNotEmpty',
        'time_begin' => 'require|isNotEmpty',
        'time_end' => 'require|isNotEmpty',
        'consumption_time' => 'require|isNotEmpty',
        'consumption_type' => 'require|isNotEmpty',
        'company_ids' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'personChoice' => ['dinner_id', 'detail', 'ordering_date', 'type', 'count'],
        'orderingOnline' => ['detail'],
        'companyMenus' => ['canteen_id'],
        'canteenMenus' => ['canteen_id'],
        'orderingCancel' => ['id'],
        'changeOrderCount' => ['id', 'count'],
        'changeOrderFoods' => ['id', 'detail'],
        'personalChoiceInfo' => ['id'],
        'userOrders' => ['id', 'type'],
        'userOrderings' => ['id', 'type'],
        'userOrdering' => ['consumption_time'],
        'orderDetail' => ['id', 'type'],
        'deliveryCode' => ['id'],
        'consumptionRecords' => ['consumption_time'],
        'managerOrders' => ['canteen_id', 'consumption_time'],
        'managerDinnerStatistic' => ['dinner_id', 'consumption_time'],
        'orderUsersStatistic' => ['dinner_id', 'consumption_time', 'consumption_type'],
        'foodUsersStatistic' => ['dinner_id', 'food_id', 'consumption_time'],
        'handelOrderedNoMeal' => ['dinner_id', 'consumption_time'],
        'orderStatistic' => ['company_ids', 'time_begin', 'time_end'],
        'orderStatisticDetail' => ['company_ids', 'time_begin', 'time_end'],
        'orderMaterialsStatistic' => ['time_begin', 'time_end'],
        'updateOrderMaterial' => ['time_begin', 'time_end', 'canteen_id', 'title', 'detail'],
    ];
}