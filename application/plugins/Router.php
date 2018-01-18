<?php

/**
 * 公共路由插件
 *
 * @package plugin
 * @author  minhao <minhao@staff.sina.com.cn>
 */
class RouterPlugin extends Yaf_Plugin_Abstract {

    protected static $isMobile = false;

    /**
     * 路由开始之前，加载适合的路由规则
     *
     * @param Yaf_Request_Abstract $request
     * @param Yaf_Response_Abstract $response
     */
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        $request->setParam('s_log_time', time());
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {

    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        $route = Comm_AppAdapter::getModules();
        $modules = ucfirst(strtolower($route['module']));
        $controller_name = ucfirst(strtolower($route['controller']));
        $action_name     = ucfirst(strtolower($route['action']));
        defined('MODULE') OR define('MODULE', $modules);


        // 生成系统级缓存
        Base_Funs::getSystemParam();

        if(!empty($modules)){
            $request->setModuleName($modules);
            $request->setControllerName($controller_name);
            $request->setActionName($action_name);
        }

        //对于权限模型按以下规则控制访问
        if ($modules == 'Admin') {
            //默认拦截全部
            $interrupt=true;
            //取得当前URL的 controller和action
            $action=strtolower($request->getControllerName()."/".$request->getActionName());
            $config = Comm_Config::getConfig('config');

            //放行1   配置文件的非需要登录模块中，不进行拦截
            if (in_array($action, $config['nologin'][strtolower($modules)])) {
                $interrupt=false;
                return $interrupt;
                // 但特别注意后台入口模块需要继续后边的cookie检测，防止别人检测后台入口
                if ($action != 'login/login') {
                    return true;
                }
            }

            //检验一次 COOKIE，如果不存在cookie则直接404掉。
            if (!isset($_COOKIE['adminuser']) || $_COOKIE['adminuser'] !='BabysBreath') {
                @header('HTTP/1.1 404 Not Found');
                @header("status: 404 Not Found");
                include("error/404.html");
                exit;
            }

            //放行2   同时存在登陆ID，登陆信息，不进行拦截
            $MANAGE_ID = intval(@Yaf_Session::getInstance()->__get( "MANAGE_ID"));
            $MANAGE_INFO = @Yaf_Session::getInstance()->__get( "MANAGE_INFO");
            if ($interrupt && $MANAGE_ID > 0 && !empty($MANAGE_INFO) ) {
                $interrupt=false;
                if (!in_array($action,$MANAGE_INFO['roles']))
                {
                    echo "<script>alert('此页面无权限');history.go(-1)</script>";exit;
                }
            }

            //如果拦截值为true，则拦截到登录页
            if ($interrupt) {
               $request->setModuleName($modules);
               $request->setControllerName('Login');
               $request->setActionName('login');
               return;
            }
        }




    }


}
