<?php
/**
 * 配置类
 *
 * @package Swift
 * @copyright copyright(2011) weibo.com all rights reserved
 * @author weibo.com php team
 */

class Comm_Config {

    /**
     * 加载指定的配置文件
     *
     * @param string 映射configuration文件名
     * @return array
     */
    public static function load($config_file) {
        $file = self::swift_find_file('config', $config_file);
        if (empty($file)) {
            throw new Comm_Exception_Program("config file not exists");
        }
        $config = array();
        $config = Comm_Array::merge($config, self::swift_load($file));
        return $config;
    }

    /**
     * 获取指定的配置项，如果$key不存在将报错
     * 进程内缓存，避免重复加载
     *
     * @param string $key 支持dot path方式获取
     */
    public static function get($key) {
        static $config = array();

        if (strpos($key, '.') !== false) {
            list($file, $path) = explode('.', $key, 2);
        }else{
            $file = $key;
        }
        if (!isset($config[$file])) {
            $config[$file] = self::load($file);
        }

        if (isset($path)) {
            $val = Comm_Array::path($config[$file], $path, "#not_found#");
            if ($val === "#not_found#"){
                throw new Comm_Exception_Program("config key not exists:" . $key);
            }

            return $val;
        }else{
            // 获取整个配置
            return $config[$file];
        }
    }

    public static function swift_find_file($dir, $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file . ".php";
        $found = false;
        $paths = array(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'application');
        foreach ($paths as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $path)) {
                $found = $dir . DIRECTORY_SEPARATOR . $path;
                break;
            }
        }
        return $found;
    }

    public static function swift_load($file) {
        return include $file;
    }

    /**
     * 读取配置信息
     *
     * @param string $path 节点路径，第一个是文件名，使用点号分隔。如:"app","app.product.routes"
     *
     * @return array/string    成功返回数组或string
     */
    static public function getConf($path) {
        $arr = explode('.', $path, 3);
        try {
            $conf = new Yaf_Config_ini(APPLICATION_PATH . '/conf/' . $arr[0] . '.ini');
        } catch (Exception $e) {
        }
        !empty($arr[1]) && !empty($conf) && $conf = $conf->get($arr[1]);
        !empty($arr[2]) && !empty($conf) && $conf = $conf->get($arr[2]);
        if (!isset($conf) || is_null($conf)) {
            throw new Exception("读取的配置信息不存在", 200401);
        }
        return is_object($conf) ? $conf->toArray() : $conf;
    }

    /**
     * 读取配置信息（使用静态数据缓存）
     *
     * @param string $path 节点路径，第一个是文件名，使用点号分隔。如:"app","app.product.routes"
     *
     * @return array/string    成功返回数组或string
     */
    static public function getUseStatic($path) {
        $static_key = 'get_' . $path;

        $result = Comm_Sdata::get(__CLASS__, $static_key);
        if ($result === false) {
            $result = self::getConf($path);
            Comm_Sdata::set(__CLASS__, $static_key, $result);
        }
        return $result;
    }


    public static function getConfig($filename) {
        static $config = array();
        if (!isset($config[$filename])) {
            $config[$filename] = self::configFile($filename);
        }
        return $config[$filename];
    }

    protected static function configFile($filename) {
        $file = APPLICATION_PATH."/conf/{$filename}.php";
        $return = $replace = array();
        if (file_exists($file)) {
            $return = include $file;
        }
        $file2 = APPLICATION_PATH."/conf/".$_SERVER['SINA_ENV']."/{$filename}.php";
        if(file_exists($file2)){
            $replace = include $file2;
        }
        foreach ($replace as $key => $val)
    {
            $return[$key] = $val;
    }
        return $return;
    }


}
