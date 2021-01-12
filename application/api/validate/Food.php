<?php


namespace app\api\validate;


class Food extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'food_id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'canteen_id' => 'require|isPositiveInteger',
        'dinner_id' => 'require|isPositiveInteger',
        'f_type' => 'require|in:1,2',
        'food_type' => 'require|in:1,2',
        'status' => 'require|in:1,2,3',
        'default' => 'require|in:1,2',
        'menu_id' => 'require|isPositiveInteger',
        'm_d_id' => 'require|isPositiveInteger',
        'm_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'chef' => 'require|isNotEmpty',
        'des' => 'require|isNotEmpty',
        'img_url' => 'require|isNotEmpty',
        'day' => 'require|isNotEmpty',
        'taste' => 'require|in:1,2,3,4,5',
        'auto_week' => 'require|in:0,1,2,3,4,5,6',
        'repeat_week' => 'require|in:0,1,2,3,4,5,6',
    ];

    protected $scene = [
        'save' => ['f_type', 'c_id', 'm_id', 'name', 'chef', 'des', 'img_url'],
        'foods' => ['f_type'],
        'handel' => ['id'],
        'update' => ['id'],
        'food' => ['id'],
        'foodsForOfficialManager' => ['day', 'dinner_id', 'canteen_id'],
        'handelFoodsDayStatus' => ['food_id', 'status', 'dinner_id', 'day', 'canteen_id'],
        'foodsForOfficialPersonChoice' => ['dinner_id'],
        'saveComment' => ['food_id', 'taste', 'service'],
        'infoToComment' => ['food_id'],
        'saveAutoConfig' => ['canteen_id', 'dinner_id', 'auto_week', 'repeat_week'],
        'updateAutoConfig' => ['id'],
        'automatic' => ['canteen_id'],
        'upAll' => ['canteen_id','dinner_id','day'],
    ];
}