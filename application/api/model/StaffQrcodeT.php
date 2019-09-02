<?php


namespace app\api\model;



class StaffQrcodeT extends BaseModel
{
    public function getUrlAttr($value){
        return $this->prefixImgUrl($value);
    }


}