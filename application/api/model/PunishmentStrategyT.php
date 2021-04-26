<?php


namespace app\api\model;


use think\Model;
use app\lib\enum\CommonEnum;

class PunishmentStrategyT extends Model
{
    public function detail()
    {
        return $this->hasMany('PunishmentDetailT', 'strategy_id', 'id');
    }

    public static function strategyDetail($page, $size, $company_id, $canteen_id)
    {

        $details = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            }
        })
            ->field('id,company_id,canteen_id,staff_type_id')
            ->with(['detail' => function ($query) {
                $query->field('id,strategy_id,type,count,state');
            }
            ])
            ->paginate($size, false, ['page' => $page]);
        return $details;
    }

}