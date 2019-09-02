<?php


namespace app\api\validate;


class Food extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'food_id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'canteen_id' => 'require|isPositiveInteger',
        'f_type' => 'require|in:1,2',
        'food_type' => 'require|in:1,2',
        'status' => 'require|in:1,2,3',
        'menu_id' => 'require|isPositiveInteger',
        'm_d_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'chef' => 'require|isNotEmpty',
        'des' => 'require|isNotEmpty',
        'img_url' => 'require|isNotEmpty',
        'day' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['f_type', 'c_id', 'm_d_id', 'name', 'price', 'chef', 'des', 'img_url'],
        'foods' => ['f_type'],
        'handel' => ['id'],
        'update' => ['id'],
        'food' => ['id'],
        'foodsForOfficialManager' => ['food_type','menu_id','day','canteen_id'],
        'handelFoodsDayStatus' => ['food_id','status','day','canteen_id'],
    ];
}