<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CanteenT extends Model
{
    public function dinner()
    {
        return $this->hasMany('DinnerT', 'c_id', 'id');

    }

    public function company()
    {
        return $this->belongsTo('CompanyT', 'c_id', 'id');

    }

    public static function canteensMenu($page, $size, $canteen_id)
    {
        $menus = self::whereIn('id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'company' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,name,grade');
                },
                'dinner' => function ($query) {
                    $query->with(['menus' => function ($query2) {
                        $query2->where('state', '=', CommonEnum::STATE_IS_OK)
                            ->field('id,d_id,category,status,count');
                    }])->where('state', '=', CommonEnum::STATE_IS_OK)->field('id,c_id,name');
                },
            ])
            ->field('id,name,c_id')
            ->paginate($size, false, ['page' => $page]);

        return $menus;
    }



}