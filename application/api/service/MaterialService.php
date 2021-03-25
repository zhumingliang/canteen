<?php


namespace app\api\service;


use app\api\model\MaterialPriceT;
use app\api\model\MaterialPriceV;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class MaterialService extends BaseService
{
    public function save($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $material = MaterialPriceT::create($params);
        if (!$material) {
            throw  new SaveException();
        }
    }

    public function uploadMaterials($canteen_id, $materials_excel)
    {
        $date = (new ExcelService())->saveExcel($materials_excel);
        $this->prefixMaterials($canteen_id, $date);
    }


    public function prefixMaterials($canteen_id, $data)
    {

        $materials = [];
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            $materials[] = [
                'name' => $v[0],
                'price' => $v[1],
                'unit' => $v[2],
                'state' => CommonEnum::STATE_IS_OK,
                'admin_id' => Token::getCurrentUid(),
                'c_id' => $canteen_id,
            ];
        }
        if (empty($materials)) {
            throw new SaveException(['msg' => '上传文件为空']);
        }
        $res = (new MaterialPriceT())->saveAll($materials);
        if (!$res) {
            throw  new SaveException();
        }
    }


    public function materials($page, $size, $key, $params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $materials = MaterialPriceV::materials($page, $size, $key, $selectField['field'], $selectField['value']);
        return $materials;

    }

    public function exportMaterials($key, $params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $materials = MaterialPriceV::exportMaterials($key, $selectField['field'], $selectField['value']);
        $header = ['序号', '企业名称', '饭堂名称', '材料名称', '单位', '金额-元'];
        $url = (new ExcelService())->makeExcel($header, $materials, "材料价格明细");
        return config('setting.domain').$url;
    }
}