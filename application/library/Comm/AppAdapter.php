<?php

/**
 * Description of Application
 *
 * @author pc
 */
class Comm_AppAdapter {
    //put your code here
    public static function getModules(){
        //检测域名首词是否为模块名，如果是则设置为主模块
        // $host = Comm_Tools::getHttpHost();
        // $config = Comm_Config::getConf('application');

        $config = Comm_Config::getConfig('config');
        $default = $config['default'];
        $modules = false;
        // foreach($config['modules'] as $k=>$v){
        //     if($v==$host){
        //         $modules = ucfirst(strtolower($k));
        //         break;
        //     }
        // }
        // 557 By: WangWeiqiang <weiqiang6@staff.sina.com.cn> At:2016-05-06 15:28:54
        //当域名前缘不为模块时，检测路径首词是否为模块，如是则设置为主模块
        // if (empty($modules)) {
            $ifmodules = ucfirst(strtolower(Comm_Tools::getHttpModule()));
            $modules_config = explode(',',Yaf_Application::app()->getConfig()->application->modules);
            if (in_array($ifmodules, $modules_config)) {
                $modules = $ifmodules;
            }
        // }
        // 以上均不是时，以正常无模块对待

            $http_host = $_SERVER['HTTP_HOST'];
            foreach ($config['domain'] as $key => $val) {
                if(in_array($http_host, $val)){
                    $default = $config[$key]['default'];
                    break;
                    // $default_module = ucfirst(strtolower($key));
                }
            }


            $route = array();
            $route['module'] =  $modules ? $modules : $default['modules'];
            $route['controller'] = Comm_Tools::getHttpController() ? Comm_Tools::getHttpController() : $default['controller'];
            $route['action'] = Comm_Tools::getHttpAction() ? Comm_Tools::getHttpAction() : $default['action'];

        return $route;
    }
}
