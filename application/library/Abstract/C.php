<?php
/**
 * 1
 * Controller抽象类
 * @version SVN:$Id: C.php 153132 2016-04-15 10:01:25Z zhengyan1 $
 * 用于提供部分公用方法，方便在Controller中使用
 */
abstract class Abstract_C extends Yaf_Controller_Abstract{

    protected $_business = array();
    protected $_post = array();
    protected $_get = array();
    protected $_errMsg = array();
    /**
     * 共用的JSON提供样式
     * JSON给API提供的时候，不进行中文转义
     */
    protected function jsonCommResponse($data = array() ){
        header('Content-type:text/json');
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        die();
    }

    protected function jsonResponse($code = 1,$msg = '操作成功',$rows = array() ){
        header('Content-type:text/json');
        $response = array('status'=>$code,'msg'=>$msg,'rows'=>$rows);
        echo json_encode($response);
        die();
    }

    protected function callbackResponse($code = 1,$msg = '操作成功',$rows = array(),$callback = 'callback' ){
        header('Content-type:text/json');
        $response = array('status'=>$code,'msg'=>$msg,'rows'=>$rows);
        $json = json_encode($response);
        if(empty($callback)){
            echo $json;
            die();
        }
        $ret = $callback . '(' . $json . ')';
        echo $ret;
        die();
    }

    //公共入口
    public function init(){
        // $this->_business = Comm_Config::getConfig('business');
        $this->_post = $this->getRequest()->getPost();
        $this->_get  = $this->getRequest()->getQuery();

        // foreach ($this->_business as $key => $val) {
        //     $this->getView()->assign($key,$val);
        // }
    }

    /**
     * 获得post参数
     * @param string $name
     * @access public
     * @return void
     */
    public function post($name, $default_value = NULL, $filter='htmlspecialchars') {
        $param = $this->getRequest ()->getPost ( $name, false );
        if ( $param && is_array ( $param ) ) {
            return Comm_Tools::special_chars_with_array($param);
        } else if( $param !== false ) {
            if ($filter=='htmlspecialchars') {
                return htmlspecialchars($param,ENT_QUOTES);
            }
            return $filter($param);
        }

        return $default_value;
    }

    /**
     * 获得get参数
     * @param string $name
     * @access public
     * @return mixed
     */
    function get($name, $default_value = NULL, $filter='htmlspecialchars') {
        $param = $this->getRequest ()->getQuery ( $name, false );

        if( $param === false ) $param = $this->getRequest()->getParam( $name, false );

        if ( $param && is_array ( $param )) {
            return array_map($filter, $param);
        } else if( $param !== false ) {
            if ($filter=='htmlspecialchars') {
                return htmlspecialchars($param,ENT_QUOTES);
            }
            return $filter($param);
        }
        return $default_value;
    }

    /**
     * 对变量进行过滤 (目前只是去空)
     * @param array $param
     * @return array
     */
    private function filterParam($param) {
        $data = array();
        foreach ( $param as $k => $v ) {
            if (is_array($v)) {
                $v = $this->filterParam($v);
            } else {
                $v = trim( $v );
            }
            $k = trim( $k );
            $data [$k] = $v;
        }
        return $data;
    }

    /**
     * 重定向-当一些访问不存或参数找不到对象
     * @param      string $target_url　
     * @access     public
     * @return     void
     */
    public function redirect( $target_url = null ) {
        $target_url = $target_url ? $target_url : "/";
        header("Location:" . $target_url );
        exit;
    }

    /**
     * 错误跳转
     * @param $msg
     * @param string $url
     */
    public function show_error($msg,$url='') {
        $view = $this->getView();
        $view->assign('msg',$msg);
        $view->assign('url',$url);
        $this->getView()->display('inc/error.html');
        exit;
    }

    /**
     * 格式化数据
     * return arry
     */

    public function formatData($data){

        if(empty($data))return $data;

        $result = array();
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                $result[$k][$key] = htmlspecialchars(trim($v));
            }
        }

        return $result;
    }

    public function filterData(&$param,$filter){
        if(empty($param))return $param;
        foreach ($param as $key => $val) {
            if(!in_array($key, $filter))
                unset($param[$key]);
        }
    }

    public function layout($view = '',$data = array()){
        if (!empty($data)) {
            foreach($data as $k=>$v){
                $this->getView()->assign($k, $v);
            }
        }
        $this->getView()->display($view);
    }

    public function _getParams(){
        $allget  = $this->getRequest()->getQuery();
        $return = array();
        foreach($allget as $k=>$v){
            $return[$k] = $this->get($k);
        }
        return $return;
        // $this->_paramsGet = $return;
    }
    public function _postParams(){
        $post = $this->getRequest()->getPost();
        $return = array();
        foreach($post as $k=>$v){
            $return[$k] = $this->post($k);
        }
        return $return;
        // $this->_paramsPost = $return;
    }

}
