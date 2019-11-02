<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/5/27
 * Time: 下午4:06
 */

namespace app\api\model;


use think\Model;

class AdminT extends Model
{

    public function canteen()
    {
        return $this->hasMany('AdminCanteenT', 'admin_id', 'id');

    }

    public function shop()
    {
        return $this->hasMany('AdminShopT', 'admin_id', 'id');

    }

    public function rule()
    {
        return $this->hasOne('AdminModuleT', 'admin_id', 'id');
    }

    public static function roles($page, $size, $state, $key, $c_name)
    {
        $list = self::with([
            'canteen' => function ($query) {
                $query->field('id,admin_id,role as name');
            }
        ])
            ->where(function ($query) use ($key) {
                if (strlen($key)) {
                    $query->where('role', 'like', '%' . $key . '%');
                }
            })
            ->where(function ($query) use ($state) {
                if ($state != 3) {
                    $query->where('state', $state);
                }
            })
            ->where(function ($query) use ($c_name) {
                if (strlen($c_name) && $c_name != "全部") {
                    $query->where('company', 'like', '%' . $c_name . '%');
                }
            })
            ->field('id,company,phone,role,account,remark,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

}