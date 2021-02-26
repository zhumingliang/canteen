<?php


namespace app\api\model;


use think\Model;

class AutomaticFoodT extends Model
{
    public function food()
    {
        return $this->belongsTo('FoodT', 'food_id', 'id');
    }

}