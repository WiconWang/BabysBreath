<?php
/**
 * description: 微信公共号抽象类
 * author: lilong3@staff.sina.com.cn
 * createTime: 2016/5/27 14:45
 */

class Abstract_Wx extends Yaf_Controller_Abstract
{
    const APP_KEY = 'xxx';
    const APP_SECRET = 'xxxxx';

    const DEBUG = true;

    public $_post = array();
    public $_get = array();
    public $_controller = null;
    public $_action = null;
    public $_business = array();
    public $_cityCode = null;
    public $_loc_province = null;
    public $_loc_city = null;
    public $_source = null;
    public $_device_id = null;
    public $_chwm = null;
    public $_time = null;
    public $_apiConfig = array();
    public $_errMsg = array();
    public $_area = 11999;
    public $_province_id = 11;
    public $_city_id = 999;

    public function init()
    {
        $this->_post = $this->getRequest()->getPost();
        $this->_get = $this->getRequest()->getQuery();
        $this->_controller = strtolower($this->getRequest()->getControllerName());
        $this->_action = strtolower($this->getRequest()->getActionName());
        //1 H5  2 android  3 ios
        $this->_source = intval($this->_get['resource']);
        $this->_device_id = htmlspecialchars($this->_get['device_id']);
        $this->_chwm = htmlspecialchars($this->_get['chwm']);
        $this->_time = date('Y-m-d H:i:s',time());
        //在这里验证数据请求的签名
//         $this->check_token();

        $this->_apiConfig = Comm_Config::getConfig('api');
        $shortApiUrl = $this->_controller . '/' . $this->_action;

        //模拟数据
        if (self::DEBUG) {
            $app_data = Comm_Config::getConfig('app_debug_data');
            if (isset($app_data[$this->_controller][$this->_action])) {
                echo $app_data[$this->_controller][$this->_action];
                die();
            }
        }

        $this->_errMsg = $_errMsg = $this->_apiConfig['code'];
        //来源校验
//         if (!in_array($this->_get['resource'], $this->_apiConfig['equipment_type'])) {
//             $this->json_response(array(), 1090, $_errMsg[1090]);
//         }
        //设备号校验
//         if (!$this->_device_id) {
//             $this->json_response('', 1091, $_errMsg[1091]);
//         }
        //校验城市 因为2.6.0 不能验证
//         if (in_array($shortApiUrl, $this->_apiConfig['cityLimitApi'])) {
//             if (!$this->_post['city']) {	
//                 $this->json_response(array(), 1064, $_errMsg[1064]);
//             }
//         }
        //身份校验 需要身份验证的控制器 eg:OrderController
        if (in_array($shortApiUrl, $this->_apiConfig['identityLimitApi'])) {
            //身份验证
            $identity = $this->_post['identity'];
            $mobile = $this->_post['mobile'];
            if (!$identity) {
                $this->json_response(array(), 1062, $_errMsg[1062]);
            } else {
                $userDataModel = new Business_User_DealModel();
                $chkIdentity = $userDataModel->getSessionId($mobile, $this->_device_id);
                if (!$chkIdentity) {
                    $this->json_response(array(), 1062, $_errMsg[1062]);
                } elseif ($chkIdentity == -1) {
                    //说明被其它设备挤下线
                    $userDataModel->updateSessionId('', array('mobile' => $mobile, 'user_type' => 1, 'device_id' => $this->_device_id));
                    $this->json_response(array(), 1091, $_errMsg[1091]);
                } elseif ($identity != $chkIdentity) {
                    $this->json_response(array(), 1062, $_errMsg[1062]);
                }
            }
        }
        
        // 定位城市
        $city = intval($_COOKIE['_cityCode']);//城市id
        $area = Comm_Tools::chunkCity($city);
        
        
        $this->_area = $area['province'].$area['city'];
        $this->_province_id = $area['province'];
        $this->_city_id = $area['city'];
    }

    public function json_response($data, $code = 1000, $msg = '成功') {
        //返回标准的json数据
        header('Content-type:text/json');
        $result = array('code' => $code, 'data' => $data, 'msg' => $msg);
        echo json_encode($result);
        //保存日志
        Comm_Log::apiLog($code);
        die();
    }

    /**
     * 生成SIGN
     * @return string
     */
    private function generateSign() {
        $params = $this->_post;
        $timestamp = $this->_get['timestamp'];
        ksort($params);     //根据键名的字母先后顺序进行数组排序

        $stringToBeSigned = self::APP_KEY;
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
            unset($k, $v);
        }

        $stringToBeSigned .= $timestamp;
        $stringToBeSigned .= self::APP_SECRET;

        return strtoupper(md5($stringToBeSigned));
    }
    
    public function layout($view = '',$data = array()){
    	if (!empty($data)) {
    		foreach($data as $k=>$v){
    			$this->getView()->assign($k, $v);
    		}
    	}
    	$this->getView()->display($view);
    }

    protected function callbackResponse($code = 1000,$msg = '操作成功',$rows = array() ){
    	header('Content-type:text/json');
    	$response = array('status'=>$code,'msg'=>$msg,'rows'=>$rows);
    	$json = json_encode($response);
    	$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
    	if(empty($callback)){
    		echo $json;
    		die();
    	}
    	$ret = $callback . '(' . $json . ')';
    	echo $ret;
    	die();
    }
}