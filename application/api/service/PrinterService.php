<?php


namespace app\api\service;


use app\api\model\PrinterT;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Request;

class PrinterService
{
    public function save($params)
    {
        $printer = PrinterT::create($params);
        if (!$printer) {
            throw new SaveException();
        }
    }

    public function delete($id)
    {
        $printer = PrinterT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$printer) {
            throw new DeleteException();
        }
    }

    public function update($params)
    {
        $printer = PrinterT::update($params);
        if (!$printer) {
            throw new UpdateException();
        }
    }

    public function printers($page, $size, $canteenId)
    {
        return PrinterT::printers($page, $size, $canteenId);
    }

}