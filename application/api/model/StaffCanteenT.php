<?php


namespace app\api\model;


use think\Model;

class StaffCanteenT extends Model
{
    public function info()
    {
       return $this->belongsTo('CanteenT','canteen_id','id');
    }
}