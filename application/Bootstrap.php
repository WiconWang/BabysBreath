<?php

/**
 * @name Bootstrap
 * @author wangweiqiang
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

    public function _initLoader(Yaf_Dispatcher $dispatcher) {
        header("Content-type: text/html; charset=utf-8");
        ob_start();

        //配置本地类库前缀
        Yaf_Loader::getInstance()->registerLocalNameSpace(
                array('Comm', 'Tool', 'Data', 'Abstract')
        );
        define('NOW', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());
        date_default_timezone_set('PRC');
    }

    public function _initConfig() {
        //把配置保存起来
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);
    }
    /**
     * 本地函数库
     */
    public function _initCommon(){
        $base = APPLICATION_PATH."/application/library/Common/";
        if (version_compare("5.5", PHP_VERSION, ">")) {
            //php版本低于5.5的时候引用 如果php函数不存在等情况的兼容
            Yaf_Loader::import($base."/Verson55.php");
        }
        Yaf_Loader::import($base."/Common.php");
    }
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        //注册一个插件
        $plugin_router = new RouterPlugin();
        $dispatcher->registerPlugin($plugin_router);
    }

    /**
     * 注册自己的路由协议,此处我们不对路由进行特殊设置
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        $router = $dispatcher->getRouter();
        //$router->addRoute('myRoute', new Yaf_Route_Simple('m','c','a'));
        //$router->addRoute('myRoute3', new Yaf_Route_Rewrite("/product/:name/:value",array('controller'=>'Index','action'=>'action')));
    }

    /**
     * 注册自己的view控制器，例如smarty,firekylin
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initView(Yaf_Dispatcher $dispatcher) {
        $dispatcher->disableView();
        if (!$dispatcher->getRequest()->isXmlHttpRequest() && !$dispatcher->getRequest()->isCli()) {
            $smartyConf = Yaf_Registry::get("config")->get("smarty");
            $array = array();
            foreach ($smartyConf as $key => $value) {
                $array[$key] = $value;
            }
            $route = Comm_AppAdapter::getModules() ;

            if( !empty($route)){
                $array['template_dir'] = APPLICATION_PATH."/application/modules/".$route['module'].'/views/';
            }

            $smarty = new Comm_SmartyAdapter(null, $array);
            $dispatcher->setView($smarty);
        }
    }

}
