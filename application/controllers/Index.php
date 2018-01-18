
<?php
/**
 * 自动生成mod的代码
 * @author tingting42  <tingting42@staff.sina.com.cn>
 * @copyright 2016-04-11 11:09:56
 */
class IndexController extends Yaf_Controller_Abstract {//Yaf_Controller_Abstract
    public function indexAction()
    {
        echo "Hello 12 World.";
    }


    public function ErrorAction( $code = 404)
    {
         $this->getView()->display('error/404.phtml');
    }

}

?>
