<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopOrderV extends Model
{
    public function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');
    }

    public function getStatusAttr($value, $data)
    {
        if ($data['used'] == CommonEnum::STATE_IS_OK) {
            return 1;
        } else if ($data['state'] == CommonEnum::STATE_IS_FAIL) {
            return 2;
        } elseif ($data['state'] == CommonEnum::STATE_IS_OK && $data['distribution'] == 1 &
            $data['used'] == CommonEnum::STATE_IS_FAIL) {
            return 3;
        } elseif ($data['state'] == CommonEnum::STATE_IS_OK && $data['distribution'] == 2 &
            $data['used'] == CommonEnum::STATE_IS_FAIL) {
            return 4;
        }

    }

    public function getStatusTextAttr($value, $data)
    {
        $status = [1 => '已完成', '2' => '已取消', 3 => '待取货', 4 => '待送货'];
        if ($data['used'] == CommonEnum::STATE_IS_OK) {
            return $status[1];
        } else if ($data['state'] == CommonEnum::STATE_IS_FAIL) {
            return $status[2];
        } elseif ($data['state'] == CommonEnum::STATE_IS_OK && $data['distribution'] == 1 &
            $data['used'] == CommonEnum::STATE_IS_FAIL) {
            return $status[3];
        } elseif ($data['state'] == CommonEnum::STATE_IS_OK && $data['distribution'] == 2 &
            $data['used'] == CommonEnum::STATE_IS_FAIL) {
            return $status[4];
        }

    }

    public function products()
    {
        return $this->hasMany('ShopOrderDetailV', 'order_id', 'id');
    }

    public static function orderStatisticToManager($page, $size, $department_id, $name, $phone, $status, $time_begin, $time_end, $company_id)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->where(function ($query) use ($department_id, $name, $phone) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($name)) {
                    $query->where('name', $name);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status == 1) {
                    //已完成
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == 2) {
                    //已取消
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 3) {
                    //待取货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 1)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 4) {
                    //待送货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 2)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                }

            })->whereBetweenTime('create_time', $time_begin, $time_end)
            ->with([
                'address' => function ($query) {
                    $query->field('id,address');
                }
            ])
            ->field('order_id,create_time,used_time,username,phone,count as order_count,money,address_id,2 as status,used,state,distribution')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $orderings;
    }

    public static function exportOrderStatisticToManager($department_id, $name, $phone, $status, $time_begin, $time_end, $company_id)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->where(function ($query) use ($department_id, $name, $phone) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($name)) {
                    $query->where('name', $name);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status == 1) {
                    //已完成
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == 2) {
                    //已取消
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 3) {
                    //待取货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 1)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 4) {
                    //待送货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 2)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                }

            })->whereBetweenTime('create_time', $time_begin, $time_end)
            ->with([
                'address' => function ($query) {
                    $query->field('id,address');
                },
                'products'
            ])
            ->field('order_id as id,1 as number,create_time,used_time,username,phone,count as order_count,money,address_id,2 as status,2  as status_text,used,state,distribution')
            ->select()
            ->toArray();
        return $orderings;
    }


}