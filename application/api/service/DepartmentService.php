<?php


namespace app\api\service;


use app\api\model\CompanyDepartmentT;
use app\api\model\CompanyStaffT;
use app\api\model\DepartmentV;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\SaveException;

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

    }

}