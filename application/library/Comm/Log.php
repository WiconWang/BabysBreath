<?php

class Comm_Log {

    const LOG_LEVEL_FATAL = 1;
    const LOG_LEVEL_WARNING = 2;
    const LOG_LEVEL_NOTICE = 3;
    const LOG_LEVEL_TRACE = 4;
    const LOG_LEVEL_DEBUG = 5;

    public static $arrLogLevels = array(
        self::LOG_LEVEL_FATAL => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE => 'NOTICE',
        self::LOG_LEVEL_TRACE => 'TRACE',
        self::LOG_LEVEL_DEBUG => 'DEBUG',
    );
    private static $dbInstance = null;

    /**
     * 操作日志
     * @param [type]  $admin_name 操作人名称
     * @param integer $row_id   记录的id号
     * @param integer $log_type   操作类型  1为管理员登录，2为后台操作
     * @param string  $operation  操作细节
     */
    public static function adminLog($admin_name,$row_id = 0, $log_type=1, $operation='') {
        //write log
        $info['log_type'] = $log_type;
        $info['admin_name'] = empty($admin_name)?'未知':$admin_name;
        $info['row_id'] = $row_id;
        $info['path'] = $_SERVER['QUERY_STRING'];
        $info['ip'] = Comm_Ip::getClientIp();
        $info['operation'] = $operation;
        $info['created_at'] = date('Y-m-d H:i:s',time());
        return (new Bussiness_Admin_Operation_LogModel())->insert($info);
    }
    /**
     * 文件日志
     * @param $error
     * @param int $errno
     * @return int
     */
    public static function fileLog($content,$category = '', $ext = 0) {

        // 检测是否已经是array
        if (is_array($content)) {
            $content = json_encode($content);
        }
        $categoryPath=empty($category)?'':$category.'/';

        $prefix = self::getLogPrefix();
        if ($_SERVER['SERVER_CACHE_DIR']) {
            $logPath = $_SERVER['SERVER_CACHE_DIR'].'/log/'.$categoryPath.$prefix;
        } else {
            $logPath = APPLICATION_PATH.'/log/'.$categoryPath.$prefix;
        }
        if (!is_dir($logPath)) {
            @mkdir($logPath,0755,true);
        }
        $logPath .= '/'.$prefix.'_log_'.date('Ymd').'.log';
        $ip = Comm_Ip::getClientIp();
        $logInfo = "st[%s] ip[%s] msg[%s] ext[%s] \r\n";
        $logInfo  = sprintf($logInfo,date('Y-m-d H:i:s'), $ip, $content, $ext);

        return file_put_contents($logPath, $logInfo, FILE_APPEND);
    }

    /**
     * 获取日志文件前缀
     * @return string
     */
    public static function getLogPrefix(){
        if(defined('MODULE')){
            return strtolower(MODULE);
        }else{
            return 'cli';
        }
    }

    /**
     * 接口运行日志
     * @param string $url
     * @param string $msg
     */
    public static function apiLog($retCode, $url='', $msg='') {
        if (empty($url)) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        //write log
        $info['ip'] = Comm_Ip::getClientIp();
        $info['url'] = $url;
        $info['param'] = $msg?$msg:http_build_query($_POST);
        $info['ret_code'] = $retCode;
        $info['time'] = time();

        $logApiModel = Mod_LogApiModel::getInstance();
        return $logApiModel->insert($info);
    }

}

?>
