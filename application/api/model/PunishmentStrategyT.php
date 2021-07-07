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

    public function staff()
    {
        return $this->hasOne('StaffTypeT','id','staff_type_id');
    }
    public function canteen()
    {
        return $this->hasOne('CanteenT','id','canteen_id');
    }


    public static function strategyDetail($page, $size, $company_id)
    {
        $details = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })
            ->field('id,company_id,staff_type_id')
            ->with(['staff' => function ($query) {
                $query->field('id,name');
            },
                'detail' => function ($query) {
                    $query->field('id,strategy_id,type,count,state');
                }
            ])
            ->paginate($size, false, ['page' => $page]);
        return $details;
    }

    public function getStaffMaxPunishment($company_id, $t_id)
    {
        return self::where('company_id', $company_id)
            ->where('staff_type_id', $t_id)
            ->field('id')
            ->with([
                'detail' => function ($query) {
                    $query->field('strategy_id,type,count');
                }
            ])->select()->toArray();
    }

    public static function strategy($companyId, $staffTypeId)
    {
        return self::where('company_id', $companyId)
            ->where('staff_type_id', $staffTypeId)
            ->with(
                [
                    'detail' => function ($query) {
                        $query->where('state', CommonEnum::STATE_IS_OK)
                            ->field('id,strategy_id,type,count');
                    }
                ]
            )
            ->find();

    }

    public static function companyStrategy($companyId)
    {
        return self::where('company_id', $companyId)
            ->with(
                [
                    'detail' => function ($query) {
                        $query->where('state', CommonEnum::STATE_IS_OK)
                            ->field('id,strategy_id,type,count');
                    }
                ]
            )
            ->select();

    }

}