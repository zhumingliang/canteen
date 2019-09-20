<?php


namespace app\api\model;


use think\Model;

class ShopProductT extends BaseModel
{
    public function getImageAttr($value)
    {
        return $this->prefixImgUrl($value);
    }

}