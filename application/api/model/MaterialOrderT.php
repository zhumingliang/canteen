<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Db;
use think\Model;
use think\Request;

class MaterialOrderT extends Model
{

    public function dinner()
    {
        return $this->belongsTo('DinnerT', 'dinner_id', 'id');
    }

    public function canteen()
    {
        return $this->belongsTo('CanteenT', 'canteen_id', 'id');
    }

    public static function getSql()
    {
        $subQuery = Db::table('canteen_material_order_t')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'a.canteen_id=c.id')
            ->field('a.*,b.name as dinner,c.name as canteen')
            ->buildSql();
        return $subQuery;
    }

    public static function checkExits($canteenId, $day, $material)
    {
        return self::where('canteen_id', $canteenId)
            ->where('day', $day)
            ->where('material', $material)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
    }

    public static function orderMaterials($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size)
    {
        $sql = self::getSql();
        return Db::table($sql . ' a')->where('day', '>=', $timeBegin)
            ->where('day', '<=', $timeEnd)
            ->where(function ($query) use ($companyId, $canteenId) {
                if (!$canteenId) {
                    $query->where('canteen_id', $canteenId);
                } else {
                    $query->where('company_id', $companyId);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,DATE_FORMAT(create_time, "%Y-%m-%d %H:%i") as create_time,dinner,canteen,material,order_count,count,price,report')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
    }


    public static function materials($ids)
    {
        return self::whereIn('id', $ids)
            ->selec();
    }


}