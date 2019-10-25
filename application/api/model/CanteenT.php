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

    /*
        public function modules()

        {
            return $this->hasMany('CanteenModuleT', 'c_id', 'id');
        }*/

    public function modules()
    {
        return $this->hasMany('CanteenModuleV', 'canteen_id', 'id');
    }

    public static function canteensMenu($page, $size, $company_id, $canteen_id)
    {
        $menus = self::where(function ($query) use ($company_id) {
            if (strlen($company_id)) {
                $query->whereIn('c_id', $company_id);
            }
        })->where(function ($query) use ($canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('id', $canteen_id);
            }
        })
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

    public static function canteen($canteen_id)
    {
        $canteen = self::where('id', $canteen_id)
            ->find();
        return $canteen;
    }

    public static function getCanteensForCompany($company_id)
    {
        $canteens = self::where('c_id', $company_id)
            ->with([
                'modules' => function ($query) {
                    $query->field('id,canteen_id,parent_id,type,name')
                        ->order('order');
                }
            ])
            ->field('id,c_id,name')
            ->select()->toArray();
        return $canteens;
    }

}