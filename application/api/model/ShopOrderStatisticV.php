<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopOrderStatisticV extends Model
{
    public static function consumptionStatisticGroupByCategoryID($page, $size, $category_id, $product_id,
                                                                 $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                /* if ($status == 1) {
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
                 }*/
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->field('category as statistic,create_time,used_time,"/" as username,"/" as department,category,unit,"/" as product,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('category_id')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $statistic;
    }

    public static function consumptionStatisticGroupByProductID($page, $size, $category_id, $product_id,


                                                                $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                /*  if ($status == 1) {
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
                  }*/
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->field('product as statistic,create_time,used_time,"/" as username,"/" as department,category, product,unit,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('product_id')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $statistic;
    }

    public static function consumptionStatisticGroupByStatus($page, $size, $category_id, $product_id,
                                                             $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->field('status as statistic,create_time,used_time,"/" as username,"/" as department,category, product,unit,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('status')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $statistic;
    }

    public static function statisticCount($category_id, $product_id,
                                          $status, $time_begin, $time_end, $supplier_id, $field, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $count = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status) {
                    $query->where('status', $status);
                }
            })->group($field)
            ->count($field);
        return $count;

    }


    public static function statisticMoney($category_id, $product_id,
                                          $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $money = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->sum('order_money');
        return $money;

    }


    public static function consumptionStatisticGroupByDepartmentID($page, $size, $category_id, $product_id,

                                                                   $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                /*  if ($status == 1) {
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
                  }*/
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->field('department as statistic,create_time,used_time,"/" as username,"/" as department,"/" as category, "/" as product,"/" as unit,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('department_id')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $statistic;
    }

    public static function consumptionStatisticGroupByUsername($page, $size, $category_id, $product_id,

                                                               $status, $time_begin, $time_end, $supplier_id, $department_id, $username)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id, $department_id, $username) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                /*  if ($status == 1) {
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
                  }*/
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->field('username as statistic,create_time,used_time, username,department,"/" as category, "/" as product,"/" as unit,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('staff_id')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $statistic;
    }


}