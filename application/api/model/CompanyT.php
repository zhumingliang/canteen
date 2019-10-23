<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyT extends Model
{
    public function canteen()
    {
        return $this->hasMany('CanteenT', 'c_id', 'id');

    }

    public function shop()
    {
        return $this->hasOne('ShopT', 'c_id', 'id');

    }

    public static function companies($page, $size, $name, $create_time)
    {

        $list = self::where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'canteen' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                },
                'shop' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                }
            ])
            ->where(function ($query) use ($name) {
                if (strlen($name)) {
                    $query->where('name', 'like', '%' . $name . '%');
                }
            })
            ->where(function ($query) use ($create_time) {
                if (strlen($create_time)) {
                    $query->whereBetweenTime('create_time', $create_time);
                }
            })
            ->field('id,create_time,name,grade,parent_id,parent_name')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function managerCompanies($ids)
    {

        $list = self::whereIn('id', $ids)
            ->with([
                'canteen' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                },
                'shop' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                }
            ])
            ->field('id,name,parent_id')
            ->select()->toArray();
        return $list;

    }

    public static function superManagerCompanies($ids)
    {

        $list = self::whereIn('id', $ids)
            ->with([
                'canteen' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                }
            ])
            ->field('id,name,parent_id')
            ->select()->toArray();
        return $list;

    }

    public static function superManagerCompaniesWithoutCanteen($ids)
    {

        $list = self::whereIn('id', $ids)
            ->field('id,name,parent_id')
            ->select()->toArray();
        return $list;

    }

    public static function getCompanyWithName($name)
    {

        $company = self::where('state', CommonEnum::STATE_IS_OK)
            ->where('name', $name)
            ->field('id,name,parent_id')
            ->find();
        return $company;

    }

    public static function getCompanyWitID($id)
    {

        $company = self::where('id', $id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,parent_id')
            ->find();
        return $company;

    }

    public static function systemManagerGetCompanies($name)
    {

        $list = self::where('state', CommonEnum::STATE_IS_OK)
           /* ->where(function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })*/
            ->field('id,name,parent_id')
            ->select()->toArray();
        return $list;

    }

}