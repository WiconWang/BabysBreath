<?php

 if (!defined('QRCODE_ROOT')) {
    define('QRCODE_ROOT', dirname(__FILE__) . '/');
    require(QRCODE_ROOT . 'QRcode/phpqrcode.php');
}


class PHPQrcode
{

//    private $errorCorrectionLevel = 'L'; //容错级别
//    private $matrixPointSize = 6; //生成图片大小

    public function MakeQRcode($content)
    {
        return QRcode::png($content);
    }


}
