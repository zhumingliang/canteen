<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CanteenModuleT extends Model
{
    public function module()
    {
        return $this->belongsTo('SystemCanteenModuleT', 'm_id', 'id');
    }

    public static function companyModules($company_id)
    {
        $modules = self::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time','update_time','c_id','state'])
            ->select();
        return $modules;

    }
}