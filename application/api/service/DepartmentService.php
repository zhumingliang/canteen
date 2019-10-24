<?php


namespace app\api\service;


use app\api\model\CanteenT;
use app\api\model\CompanyDepartmentT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyStaffV;
use app\api\model\DepartmentV;
use app\api\model\StaffCanteenT;
use app\api\model\StaffQrcodeT;
use app\api\model\StaffV;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use http\Params;
use think\Db;
use think\Exception;
use think\Request;
use function GuzzleHttp\Psr7\str;

class DepartmentService
{
    public function save($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $department = CompanyDepartmentT::create($params);
        if (!$department) {
            throw new SaveException();
        }
        return $department->id;
    }

    public function deleteDepartment($id)
    {
        if ($this->checkDepartmentCanDelete($id)) {
            throw new DeleteException(['msg' => '删除操作失败，该部门有子部门或者有员工']);
        }
        $res = CompanyDepartmentT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$res) {
            throw new DeleteException();
        }
    }

    private function checkDepartmentCanDelete($id)
    {
        $staff = CompanyStaffT::where('d_id', $id)
            ->count('id');
        if ($staff) {
            return true;
        }
        $son = CompanyDepartmentT::where('parent_id', $id)->count('id');
        if ($son) {
            return true;
        }
        return false;

    }

    public function departments($c_id)
    {
        $departments = DepartmentV::departments($c_id);
        return getTree($departments);
    }

    public function addStaff($params)
    {
        try {
            Db::startTrans();
            $params['state'] = CommonEnum::STATE_IS_OK;
            $staff = CompanyStaffT::create($params);
            if (!$staff) {
                throw new SaveException();
            }
            //保存用户饭堂绑定关系
            $this->saveStaffCanteen($staff->id, $params['canteens']);
            //保存二维码
            $this->saveQrcode($staff->id);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function updateStaff($params)
    {
        try {
            Db::startTrans();
            if (key_exists('expiry_date', $params)) {
                $qrcode = StaffQrcodeT::update(['expiry_date' => $params['expiry_date']], ['s_id' => $params['id']]);
                if (!$qrcode) {
                    throw new UpdateException(['msg' => '更新二维码有效期失败']);
                }
            }
            $staff = CompanyStaffT::update($params);
            if (!$staff) {
                throw new UpdateException();
            }
            //更新用户饭堂绑定关系
            $canteens = empty($params['canteens']) ? [] : json_decode($params['canteens'], true);
            $cancel_canteens = empty($params['cancel_canteens']) ? [] : json_decode($params['cancel_canteens'], true);
            $this->updateStaffCanteen($staff->id, $canteens, $cancel_canteens);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function saveStaffCanteen($staff_id, $canteens)
    {
        $canteens = json_decode($canteens, true);
        if (empty($canteens)) {
            throw new ParameterException(['msg' => '字段饭堂id，参数格式错误']);
        }
        $data_list = [];
        foreach ($canteens as $k => $v) {
            $data_list[] = [
                'staff_id' => $staff_id,
                'canteen_id' => $v['canteen_id'],
                'state' => CommonEnum::STATE_IS_OK
            ];
        }
        $res = (new StaffCanteenT())->saveAll($data_list);
        if (!$res) {
            throw new SaveException(['msg' => '添加饭堂用户关系失败']);
        }

    }

    private function updateStaffCanteen($staff_id, $canteens, $cancel_canteens)
    {
        $data_list = [];
        if (!empty($canteens)) {
            foreach ($canteens as $k => $v) {
                $data_list[] = [
                    'staff_id' => $staff_id,
                    'canteen_id' => $v,
                    'state' => CommonEnum::STATE_IS_OK
                ];
            }
        }
        if (!empty($cancel_canteens)) {
            foreach ($cancel_canteens as $k => $v) {
                $data_list[] = [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ];
            }
        }
        $res = (new StaffCanteenT())->saveAll($data_list);
        if (!$res) {
            throw new SaveException(['msg' => '更新饭堂用户关系失败']);
        }

    }

    public function uploadStaffs($company_id, $staffs_excel)
    {
        $date = (new ExcelService())->saveExcel($staffs_excel);
        $res = $this->prefixStaffs($company_id, $date);
        return $res;
    }

    private function prefixStaffs($company_id, $data)
    {
        $types = (new AdminService())->allTypes();
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $departments = $this->companyDepartments($company_id);
        $fail = array();
        $success = array();
        $param_key = array();
        if (count($data) < 2) {
            return [];
        }
        foreach ($data as $k => $v) {
            if ($k == 1) {
                $param_key = $data[$k];
            } else if ($k > 1) {
                $check = $this->validateParams($company_id, $param_key, $data[$k], $types, $canteens, $departments);
                if (!$check['res']) {
                    $fail[] = $check['info'];
                    continue;
                }
                $success[] = $check['info'];
            }

        }

        if (count($success)) {
            $all = (new CompanyStaffT())->saveAll($success);
            if (!$all) {
                throw  new SaveException();
            }

            $info = $this->getUploadStaffQrcodeAndCanteenInfo($all);
            $qrcodeInfo = $info['qrcode'];
            $canteenInfo = $info['canteen'];
            $qrcods = (new StaffQrcodeT())->saveAll($qrcodeInfo);
            if (!$qrcods) {
                throw  new SaveException();
            }
            $canteens = (new StaffCanteenT())->saveAll($canteenInfo);
            if (!$canteens) {
                throw  new SaveException();
            }

        }

        return [
            'fail' => $fail
        ];


    }

    private function validateParams($company_id, $param_key, $data, $types, $canteens, $departments)
    {
        foreach ($data as $k => $v) {
            if (!strlen($v)) {
                $fail = [
                    'name' => $data[4],
                    'msg' => "参数：$param_key[$k]" . " 为空"
                ];
                return [
                    'res' => false,
                    'info' => $fail
                ];
                break;
            }
        }
        $canteen = $data[0];
        $department = $data[1];
        $staffType = $data[2];
        $code = $data[3];
        $name = $data[4];
        $phone = $data[5];
        $card_num = $data[6];
        $state = $data[7];
        $canteen_ids = [];
        //判断人员类型是否存在
        $t_id = $this->checkParamExits($types, $staffType);
        if (!$t_id) {
            $fail = [
                'name' => $name,
                'msg' => '系统中不存在该人员类型：' . $staffType
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }
        //判断饭堂是否存在
        $canteen_arr = explode('|', $canteen);
        if (empty($canteen_arr)) {
            $fail = [
                'name' => $name,
                'msg' => '饭堂字段为空'
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }

        foreach ($canteen_arr as $k => $v) {
            $c_id = $this->checkParamExits($canteens, $v);
            if (!$c_id) {
                $fail = [
                    'name' => $name,
                    'msg' => '企业中不存在该饭堂：' . $v
                ];
                return [
                    'res' => false,
                    'info' => $fail
                ];
                break;
            }
            array_push($canteen_ids, $c_id);
        }

        //检测部门是否存在
        $d_id = $this->checkParamExits($departments, $department);
        if (!$d_id) {
            $fail = [
                'name' => $name,
                'msg' => '企业中不存在该部门：' . $department
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }

        return [
            'res' => true,
            'info' => [
                'd_id' => $d_id,
                't_id' => $t_id,
                'code' => $code,
                'username' => $name,
                'phone' => $phone,
                'card_num' => $card_num,
                'company_id' => $company_id,
                'canteen_ids' => implode(',', $canteen_ids),
                'state' => $state
            ]
        ];
    }

    private function checkParamExits($list, $current_data)
    {
        if (!count($list)) {
            return 0;
        }
        foreach ($list as $k => $v) {
            if ($v['name'] == $current_data) {
                return $v['id'];
            }
        }
        return 0;

    }

    private function companyDepartments($company_id)
    {
        $departs = CompanyDepartmentT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select()->toArray();
        return $departs;
    }

    private function getUploadStaffQrcodeAndCanteenInfo($staffs)
    {
        $list = array();
        $staff_canteen_list = array();
        foreach ($staffs as $k => $v) {
            $code = getRandChar(12);
            $url = sprintf(config("setting.qrcode_url"), $code);
            $qrcode_url = (new QrcodeService())->qr_code($url);
            $list[] = [
                'code' => $code,
                's_id' => $v->id,
                'expiry_date' => date('Y-m-d H:i:s', strtotime('+' . config("setting.qrcode_expire_in") . 'minute')),
                'url' => $qrcode_url
            ];

            $canteen_ids = $v->canteen_ids;
            $canteen_arr = explode(',', $canteen_ids);
            if (!empty($canteen_arr)) {
                foreach ($canteen_arr as $k2 => $v2) {
                    $staff_canteen_list[] = [
                        'staff_id' => $v->id,
                        'canteen_id' => $v2,
                        'state' => CommonEnum::STATE_IS_OK
                    ];
                }
            }
        }
        return [
            'qrcode' => $list,
            'canteen' => $staff_canteen_list
        ];

    }

    public
    function saveQrcode($s_id)
    {
        $code = getRandChar(12);
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $data = [
            'code' => $code,
            's_id' => $s_id,
            'minute' => config("setting.qrcode_expire_in"),
            'url' => $qrcode_url
        ];
        $qrcode = StaffQrcodeT::create($data);
        if (!$qrcode) {
            throw new SaveException();
        }
        return $qrcode_url;
    }

    public
    function updateQrcode($params)
    {
        $code = getRandChar(12);
        $url = sprintf(config("setting.qrcode_url"), $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $s_id = $params['id'];
        $params['code'] = $code;
        $params['url'] = $qrcode_url;
        $expiry_date = date('Y-m-d H:i:s', time());
        $params['create_time'] = $expiry_date;
        $params['expiry_date'] = $this->prefixQrcodeExpiryDate($expiry_date, $params);
        $qrcode = StaffQrcodeT::update($params, ['s_id' => $s_id]);
        if (!$qrcode) {
            throw new SaveException();
        }
        $staff = CompanyStaffT::get($s_id);
        return [
            'usernmae' => $staff->username,
            'url' => config('setting.domain') . $qrcode->url,
            'create_time' => $qrcode->create_time,
            'expiry_date' => $qrcode->expiry_date
        ];
    }

    public
    function companyStaffs($page, $size, $c_id, $d_id)
    {
        $staffs = CompanyStaffV::companyStaffs($page, $size, $c_id, $d_id);
        return $staffs;

    }

    private
    function prefixQrcodeExpiryDate($expiry_date, $params)
    {
        $type = ['minute', 'hour', 'day', 'month', 'year'];
        $exit = 0;
        foreach ($type as $k => $v) {
            if (key_exists($v, $params)) {
                $exit = 1;
                $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $params[$v] . "$v", strtotime($expiry_date)));
            }
        }
        if (!$exit) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . config("setting.qrcode_expire_in") . "minute", strtotime($expiry_date)));

        }
        return $expiry_date;
    }

    public
    function departmentStaffs($d_ids)
    {
        $staffs = CompanyStaffT::departmentStaffs($d_ids);
        return $staffs;
    }

    public
    function getStaffWithPhone($phone)
    {
        $staff = CompanyStaffT::getStaffWithPhone($phone);
        return $staff;

    }

    public
    function getCompanyStaffCounts($company_id)
    {
        $count = CompanyStaffT::getCompanyStaffCounts($company_id);
        return $count;
    }

    public function adminDepartments()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $departments = CompanyDepartmentT::adminDepartments($company_id);
        return $departments;
    }

}