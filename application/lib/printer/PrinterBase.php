<?php


namespace app\lib\printer;


use app\api\model\PrinterConfigT;
use app\lib\exception\ParameterException;

class PrinterBase
{
    private $USER;
    private $UKEY;
    private $SN;
    private $IP = 'api.feieyun.cn';
    private $PORT = 80;
    private $PATH = '/Api/Open/';

    public function __construct()
    {
        $printerConfig = PrinterConfigT::getPrinterConfig();
        if (!$printerConfig) {
            throw  new ParameterException(['msg' => '打印机配置异常']);
        }
        $this->USER = $printerConfig->user;
        $this->UKEY = $printerConfig->ukey;
    }

    /**
     * [打印订单接口 Open_printMsg]
     * @param  [string] $sn      [打印机编号sn]
     * @param  [string] $content [打印内容]
     * @param  [string] $times   [打印联数]
     * @return [string]          [接口返回值]
     */
    public function printMsg($sn, $content, $times)
    {
        $time = time();         //请求时间
        $msgInfo = array(
            'user' => $this->USER,
            'stime' => $time,
            'sig' => $this->signature($time),
            'apiname' => 'Open_printMsg',
            'sn' => $sn,
            'content' => $content,
            'times' => $times//打印次数
        );
        $client = new HttpClient($this->IP, $this->PORT);
        if (!$client->post($this->PATH, $msgInfo)) {
            echo 'error';
        } else {
            //服务器返回的JSON字符串，建议要当做日志记录起来
            $result = $client->getContent();
            return json_decode($result, true);
        }
    }

    /**
     * [获取某台打印机状态接口 Open_queryPrinterStatus]
     * @param  [string] $sn [打印机编号]
     * @return [string]     [接口返回值]
     */
    public function queryPrinterStatus($sn)
    {
        $time = time();         //请求时间
        $msgInfo = array(
            'user' => $this->USER,
            'stime' => $time,
            'sig' => $this->signature($time),
            'apiname' => 'Open_queryPrinterStatus',
            'sn' => $sn
        );
        $client = new HttpClient($this->IP, $this->PORT);
        if (!$client->post($this->PATH, $msgInfo)) {
            echo 'error';
        } else {
            $result = $client->getContent();
            return json_decode($result, true);
        }
    }


    /**
     * [signature 生成签名]
     * @param  [string] $time [当前UNIX时间戳，10位，精确到秒]
     * @return [string]       [接口返回值]
     */
    public function signature($time)
    {
        return sha1($this->USER . $this->UKEY . $time);//公共参数，请求公钥
    }

}