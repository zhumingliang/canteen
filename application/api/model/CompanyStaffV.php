<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Db;
use think\Model;

class CompanyStaffV extends BaseModel
{
    public function getUrlAttr($value, $data)
    {
        return $this->prefixImgUrlSSL($value, $data);
    }

    public function canteens()
    {
        return $this->hasMany('StaffCanteenT', 'staff_id', 'id');
    }

    public function card()
    {
        return $this->hasOne('StaffCardT', 'staff_id', 'id');
    }

    public static function companyStaffs($page, $size, $c_id, $d_id)
    {
        $list = self::where('company_id', '=', $c_id)
            ->where(function ($query) use ($d_id) {
                if ($d_id) {
                    $query->where('d_id', '=', $d_id);
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                },
                'card' => function ($query) {
                    $query->field('id,staff_id,card_code,state');
                }
            ])
            ->order('id desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function exportStaffs($company_id, $department_id)
    {
        $list = self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', $department_id);
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                },
                'card' => function ($query) {
                    $query->field('id,staff_id,card_code,state')
                        ->where('state', "<", CommonEnum::STATE_IS_DELETE);
                }
            ])
            ->field('id,company,department,state,type,code,username,phone,birthday,face_code')
            ->order('create_time desc')
            ->select()->toArray();
        return $list;

    }

    public static function userCanteens($company_id, $phone)
    {
        $canteens = self::where('phone', $phone)->where('company_id', $company_id)
            ->field('c_id as canteen_id,canteen')
            ->select();
        return $canteens;
    }

    public static function staffsForRecharge($page, $size, $department_id, $key, $company_id)
    {
        $list = self::where('company_id', '=', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', '=', $department_id);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('username|phone|code', 'like', "%" . $key . "%");
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }

            ])
            ->field('id,company,d_id,department,code,card_num,username,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function staffsForRecharge2($page, $size, $department_id, $key, $company_id, $canteen_id)
    {

        $list = Db::table('canteen_staff_canteen_t')->alias('a')
            ->leftJoin('canteen_company_staff_t b ', 'a.staff_id=b.id')
            ->leftJoin('canteen_company_department_t c', 'b.d_id=c.id')
            ->leftJoin('canteen_company_t d', 'b.company_id=d.id')
            ->field('a.staff_id as id,d.name as company,b.d_id,c.name as department,b.code,b.card_num,b.username,b.phone')
            ->where('b.company_id', $company_id)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('b.state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($canteen_id) {
                if (!empty($canteen_id)) {
                    if (strpos($canteen_id, ',') !== false) {
                        $query->whereIn('a.canteen_id', $canteen_id);
                    } else {
                        $query->where('a.canteen_id', $canteen_id);
                    }
                }
            })
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('b.d_id', '=', $department_id);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('b.username|b.phone|b.code', 'like', "%" . $key . "%");
                }
            })
            ->group('b.id')
            ->order('b.create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

        $list = self::where('company_id', '=', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', '=', $department_id);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('username|phone|code', 'like', "%" . $key . "%");
                }
            })
            ->with([
                'canteens' => function ($query) use ($canteen_id) {
                    $query->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }

            ])
            ->field('id,company,d_id,department,code,card_num,username,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }


    public static function searchStaffs($page, $size, $company_id, $department_id, $key)
    {
        $list = self::where('company_id', '=', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', '=', $department_id);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->whereLike('username|phone', "%$key%");
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }, 'card' => function ($query) {
                    $query->field('id,staff_id,card_code,state');
                }

            ])
            ->order('id desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

}