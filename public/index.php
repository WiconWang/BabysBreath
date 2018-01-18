<?php

//定义是否启用调试模式
if (isset($_SERVER['SERVER_ENV']) && $_SERVER['SERVER_ENV']=='development') {
    ini_set("display_errors", "On");
    error_reporting(E_ALL^E_NOTICE);
    define('DEVELOPMENT', 'development');
    define('DEBUG', true);
} else {
    ini_set("display_errors", "Off");
    error_reporting(0);
    define('DEVELOPMENT', $_SERVER['SERVER_ENV']);
    define('DEBUG', false);
}

//set_time_limit(0);
session_start();
date_default_timezone_set('Asia/Shanghai');
if(Extension_Loaded("zlib")){
    Ob_Start('ob_gzhandler'); //开启gzip压缩模式
}
//定义程序主目录
define("APPLICATION_PATH",  dirname(dirname(__FILE__)));
//smarty.class.php所在目录
define('SMARTY_DIR', APPLICATION_PATH . '/thirdpart/Smarty/');
//定义缓存前缀
define('CACHE_KEY_PREFIX',$_SERVER['CACHE_KEY_PREFIX']);
//定义主域名
define('SERVER_NAME','http://' . $_SERVER['HTTP_HOST']);
//定义静态资源目录
define('STATIC_PATH',SERVER_NAME . '/static');

if (!extension_loaded("yaf")) {
    //如果没有安装Yaf扩展，使用原生PHP实现替代
    include(APPLICATION_PATH . '/framework/loader.php');
}
//初始化及路由
$app  = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");
$app->bootstrap()->run();
if(Extension_Loaded("zlib")){
    Ob_End_Flush();
}
