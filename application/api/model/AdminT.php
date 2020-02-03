<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/5/27
 * Time: 下午4:06
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class AdminT extends Model
{

    public function canteen()
    {
        return $this->hasMany('AdminCanteenV', 'admin_id', 'id');

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
                $query->field('id,canteen_id,admin_id,canteen_name');
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
            ->where('state', '<', 3)
            ->field('id,c_id as company_id,company,phone,role,account,remark,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function rolesWithIds($page, $size, $state, $key, $c_name,$company_ids)
    {
        $list = self::with([
            'canteen' => function ($query) {
                $query->field('id,canteen_id,admin_id,canteen_name');
            }
        ])
            ->whereIn('c_id',$company_ids)
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
            ->where('state', '<', 3)
            ->field('id,c_id as company_id,company,phone,role,account,remark,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }


    public static function admin($id)
    {
        $role = self::where('id', $id)
            ->field('id,role,remark,c_id')
            ->find();
        return $role;
    }

    public static function check($c_id, $account)
    {
        $count = self::where('c_id', $c_id)
            ->where('account', $account)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        return $count;

    }

}