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
            ->field('order_id,create_time,used_time,username,phone,count as order_count,money,address_id')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $orderings;
    }

}