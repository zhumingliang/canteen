<?php


namespace app\api\model;


use think\Model;

class OutConfigV extends Model
{
    public static function outsiders($page, $size, $company_id)
    {
        $list = self::where(function ($query) use ($company_id) {
            if (strpos($company_id, ',') !== false) {
                $query->whereIn('company_id', $company_id);
            } else {
                $query->where('company_id', $company_id);
            }
        })
            ->field('company_id,company,group_concat(canteen separator "，") as canteen')
            ->order('create_time desc')
            ->group('company_id')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function canteens($companyIds)
    {
        $canteens = self::whereIn('company_id', $companyIds)
            ->field('company_id,company,canteen_id,canteen')
            ->select();
        return $canteens;
    }
}