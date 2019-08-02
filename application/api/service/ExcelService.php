<?php


namespace app\api\service;


class ExcelService
{
    /**
     * 保存excel并获取excel中数据
     * @param $skus
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function saveExcel($excel)
    {
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $info = $excel->move($path);
        $file_name = $info->getPathname();
         $file_name = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/template/上传部门员工信息模板.xlsx';
        $result_excel = $this->import_excel($file_name);
        return $result_excel;

    }

    /**
     * @param $file
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function import_excel($file)
    {
        // 判断文件是什么格式
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        ini_set('max_execution_time', '0');

        // 判断使用哪种格式
        if ($extension == 'xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader->load($file);
        } else if ($extension == 'xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader->load($file);
        } else if ($extension == 'csv') {
            $PHPReader = new \PHPExcel_Reader_CSV();

            //默认输入字符集
            $PHPReader->setInputEncoding('GBK');

            //默认的分隔符
            $PHPReader->setDelimiter(',');

            //载入文件
            $objPHPExcel = $PHPReader->load($file);
        }
        $sheet = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
        //循环读取excel文件,读取一条,插入一条
        $data = array();
        //从第一行开始读取数据
        for ($j = 1; $j <= $highestRow; $j++) {
            //从A列读取数据
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                // 读取单元格
                $data[$j][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }
        return $data;
    }


}