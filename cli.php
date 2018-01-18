<?php
// error_reporting(E_ALL^E_NOTICE);
// session_start();
error_reporting(0);
date_default_timezone_set('Asia/Shanghai');
//线下环境不需要设置，线上环境需要把SERVER_ENV设置为product， 注意，千万不能把svn上的变成product
define('DEVELOPMENT', 'development');

define("APPLICATION_PATH",  dirname(__FILE__));//定义常量
define('DEBUG',false);
if (!extension_loaded("yaf")) {
    //如果没有安装Yaf扩展，使用原生PHP实现替代
    include(APPLICATION_PATH . '/framework/loader.php');
}
//加载通用函数库
$base = APPLICATION_PATH."/application/library/Common/";
if (version_compare("5.5", PHP_VERSION, ">")) {//php版本小雨5.5的时候引用 如果php函数不存在等情况的兼容
    include $base."/Verson55.php";
}
include $base."/Common.php";

//加载类库
$app  = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");
Yaf_Loader::getInstance()->registerLocalNameSpace(
array('Comm', 'Tool', 'Data', 'Abstract')
);

//获取维护$_SERVER
include APPLICATION_PATH."/application/library/Comm/Config.php";
$_SERVER_CON = Comm_Config::getConf('cli_config.'.DEVELOPMENT);
$_SERVER = array_merge($_SERVER, $_SERVER_CON);

//初始化及路由
list($modules,$controller,$action) = explode('/',$argv[1]);
$param = array();
if(!empty($argv)){
    $param = explode(',',$argv[2]);
}
$request = new Yaf_Request_Simple(null,$modules,$controller,$action,$param);
@$app->getDispatcher()->dispatch($request);

