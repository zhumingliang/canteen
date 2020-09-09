<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Model;
use function GuzzleHttp\Promise\queue;

class OrderTakeoutStatisticV extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'order_id');
    }

    public function getStatusAttr($value, $data)
    {
        if ($data['state'] == CommonEnum::STATE_IS_FAIL) {
            return OrderEnum::STATUS_CANCEL;
        } elseif ($data['state'] == OrderEnum::REFUND) {
            return OrderEnum::STATUS_REFUND;
        } else {
            if ($data['used'] == CommonEnum::STATE_IS_OK) {
                return OrderEnum::STATUS_COMPLETE;
            }

            if ($data['receive'] == CommonEnum::STATE_IS_OK) {
                return OrderEnum::STATUS_RECEIVE;
            }
            return OrderEnum::STATUS_PAID;
        }
    }

    public static function statistic($page, $size,
                                     $ordering_date, $company_ids, $canteen_id, $dinner_id,
                                     $status, $department_id, $user_type)
    {
        $list = self::where('ordering_date', $ordering_date)
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_PAID) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', PayEnum::PAY_SUCCESS)
                        ->where('receive', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_CANCEL) {
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', PayEnum::PAY_SUCCESS)
                        ->where('receive', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == OrderEnum::STATUS_REFUND) {
                    $query->where('state', OrderEnum::REFUND);
                }
            })
            ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('dinner_id', $dinner_id);
                } else {
                    if (!empty($canteen_id)) {
                        $query->where('canteen_id', $canteen_id);
                    } else {
                        if (strpos($company_ids, ',') !== false) {
                            $query->whereIn('company_id', $company_ids);
                        } else {
                            $query->where('company_id', $company_ids);
                        }
                    }
                }
            })
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($user_type) {
                if (!empty($user_type)) {
                    $query->where('outsider', $user_type);
                }
            })
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->hidden(['create_time', 'canteen_id', 'company_id', 'dinner_id', 'state', 'receive', 'used', 'pay'])
            ->order('used DESC')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function exportStatistic($ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type)
    {
        $list = self::where('ordering_date', $ordering_date)
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_PAID) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_CANCEL) {
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == OrderEnum::STATUS_REFUND) {
                    $query->where('state', OrderEnum::REFUND);
                }
            })
            ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('dinner_id', $dinner_id);
                } else {
                    if (!empty($canteen_id)) {
                        $query->where('canteen_id', $canteen_id);
                    } else {
                        if (strpos($company_ids, ',') !== false) {
                            $query->whereIn('company_id', $company_ids);
                        } else {
                            $query->where('company_id', $company_ids);
                        }
                    }
                }
            })
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($user_type) {
                if (!empty($user_type)) {
                    $query->where('outsider', $user_type);
                }
            })
            ->field('order_id,ordering_date,canteen,username,phone,dinner,money,CONCAT(province,city,area,address)  as address,state,used,receive,status')
            ->order('used DESC')
            ->select()->toArray();
        return $list;
    }

    public static function officialStatistic($page, $size,
                                             $ordering_date, $dinner_id, $status, $department_id, $canteen_id)
    {
        $list = self::where('canteen_id', $canteen_id)

            ->where('ordering_date', $ordering_date)
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } else {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_OK);
                }
            })
            ->where(function ($query) use ($dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('dinner_id', $dinner_id);
                }
            })
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('o_id,name,price,count');
                }
            ])
            ->field('order_id,province,city,area,address,address_username as username,address_phone as phone,used,count,money,delivery_fee,canteen_id')
            ->order('used DESC')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

}