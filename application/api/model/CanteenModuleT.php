<?php


namespace app\api\model;


use think\Model;

class CanteenModuleT extends Model
{
    public function module()
    {
        return $this->belongsTo('SystemCanteenModuleT', 'm_id', 'id');
    }
}