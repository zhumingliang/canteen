<?php


namespace app\api\service;


use app\lib\exception\ParameterException;

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
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $info = $excel->move($path);
        $file_name = $info->getPathname();
        //  $file_name = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/template/批量现金充值模板.xlsx';
        $result_excel = $this->importExcel($file_name);
        return $result_excel;

    }


    public function importExcel($file)
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


    public function makeExcel($columName, $list, $fileName, $excel2007 = false)
    {
        if (empty($fileName)) $fileName = time();

        if (empty($columName) || empty($list)) {
            throw new ParameterException(['msg' => '导出数据为空']);
        }

        //实例化PHPExcel类
        $PHPExcel = new \PHPExcel();
        //设置保存版本格式
        if ($excel2007) {
            $PHPWriter = new \PHPExcel_Writer_Excel2007($PHPExcel);
            $fileName = $fileName . date('_YmdHis') . '.xlsx';
        } else {
            $PHPWriter = new \PHPExcel_Writer_Excel5($PHPExcel);
            $fileName = $fileName . date('_YmdHis') . '.xls';
        }

        //获得当前sheet对象
        $PHPSheet = $PHPExcel->getActiveSheet();
        //定义sheet名称
        $PHPSheet->setTitle('Sheet1');

        //excel的列 这么多够用了吧？不够自个加 AA AB AC ……
        $letter = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];
        //把列名写入第1行 A1 B1 C1 ...
        for ($i = 0; $i < count($list[0]); $i++) {
            //$letter[$i]1 = A1 B1 C1  $letter[$i] = 列1 列2 列3
            $PHPSheet->setCellValue("$letter[$i]1", "$columName[$i]");
        }
        //内容第2行开始
        foreach ($list as $key => $val) {
            //array_values 把一维数组的键转为0 1 2 3 ..
            foreach (array_values($val) as $key2 => $val2) {
                //$letter[$key2].($key+2) = A2 B2 C2 ……
                $PHPSheet->setCellValue($letter[$key2] . ($key + 2), $val2);
            }
        }
        $savePath = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/download/' . $fileName;
        $PHPWriter->save($savePath);
        return '/static/excel/download/' . $fileName;
    }


    public function makeExcelMerge($columName, $list, $fileName, $merge, $excel2007 = false)
    {
        if (empty($fileName)) $fileName = time();
        if (empty($columName) || empty($list)) {
            throw new ParameterException(['msg' => '导出数据为空']);
        }
        //实例化PHPExcel类
        $PHPExcel = new \PHPExcel();
        //设置保存版本格式
        if ($excel2007) {
            $PHPWriter = new \PHPExcel_Writer_Excel2007($PHPExcel);
            $fileName = $fileName . date('_YmdHis') . '.xlsx';
        } else {
            $PHPWriter = new \PHPExcel_Writer_Excel5($PHPExcel);
            $fileName = $fileName . date('_YmdHis') . '.xls';
        }

        //获得当前sheet对象
        $PHPSheet = $PHPExcel->getActiveSheet();
        //定义sheet名称
        $PHPSheet->setTitle('Sheet1');

        //excel的列 这么多够用了吧？不够自个加 AA AB AC ……
        $letter = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];
        //把列名写入第1行 A1 B1 C1 ...
        for ($i = 0; $i < count($list[0]); $i++) {
            //$letter[$i]1 = A1 B1 C1  $letter[$i] = 列1 列2 列3
            if (!empty($columName[$i])) {
                $PHPSheet->setCellValue("$letter[$i]1", "$columName[$i]");
            }
        }
        //内容第2行开始
        foreach ($list as $key => $val) {
            //array_values 把一维数组的键转为0 1 2 3 ..
            foreach (array_values($val) as $key2 => $val2) {
                //$letter[$key2].($key+2) = A2 B2 C2 ……
                if ($key2 >= count($columName)) {
                    break;
                }
                $PHPSheet->setCellValue($letter[$key2] . ($key + 2), $val2);
                if ($val['merge'] == 1 && $key2 < $merge)//这里表示合并单元格
                {
                    $PHPSheet->mergeCells($letter[$key2] . $val['start'] . ':' . $letter[$key2] . $val['end']);
                    $PHPSheet->getStyle($letter[$key2] . $val['start'] . ':' . $letter[$key2] . $val['end'])
                        ->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                }
            }
        }
        $savePath = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/download/' . $fileName;
        $PHPWriter->save($savePath);
        return '/static/excel/download/' . $fileName;
    }

}