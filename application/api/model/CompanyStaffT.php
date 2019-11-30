<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyStaffT extends Model
{
    public function qrcode()
    {
        return $this->hasOne('StaffQrcodeT', 's_id', 'id');
    }

    public function canteen()
    {
        return $this->belongsTo('CanteenT', 'c_id', 'id');

    }

    public function canteens()
    {
        return $this->hasMany('StaffCanteenT', 'staff_id', 'id');

    }

    public function company()
    {
        return $this->belongsTo('CompanyT', 'company_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo('CompanyDepartmentT', 'd_id', 'id');
    }

    public static function staff($phone, $company_id = '')
    {
        return self::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query)use($company_id) {
              if (!empty($company_id)){
                  $query->where('company_id',$company_id);
              }
            })
            ->with('qrcode')
            ->find();
    }

    public static function staffName($phone, $company_id )
    {
        return self::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('company_id',$company_id)
            ->find();
    }

    public static function staffWithDepartment($staff_id)
    {
        $staff = self::where('id', $staff_id)
            ->with('department')
            ->find();
        return $staff;
    }

    public static function departmentStaffs($d_ids)
    {
        $staffs = self::where(function ($query) use ($d_ids) {
            if (strpos($d_ids, ',') !== false) {
                $query->whereIn('d_id', $d_ids);
            } else {
                $query->where('d_id', $d_ids);
            }
        })->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,username')->select()->toArray();
        return $staffs;
    }

    public static function getStaffWithPhone($phone)
    {
        return self::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }

    public static function getCompanyStaffCounts($company_id)
    {
        return self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
    }

    public static function getStaffCanteens($phone)
    {
        return self::where('phone', $phone)
            ->with([
                'company' => function ($query) {
                    $query->field('id,name');
                },
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }
            ])
            ->field('id,company_id')
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select();
    }

}