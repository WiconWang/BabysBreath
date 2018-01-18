<?php
/**
 * 用法如下：
 *      $cache = Cache::getCacheObj();
 *      $key = Cache::getCacheKey("key");
 *      echo $cache->get($key), PHP_EOL;
 *      $cache->set($key, "This is value", $timeOut);
 *      echo $cache->get($key), PHP_EOL;
 *
 */

 if(!class_exists("Memcached",false)) {
     class Memcached {
         private static $_instance;
         public function set($key, $value, $timeout) {
             return self::$_instance->set($key, $value, false,$timeout);
         }
         public function get($key) {
             return self::$_instance->get($key);
         }
         public function addServer($server, $port, $flag) {
             self::$_instance = memcache_connect($server, $port);
         }
         public function delete($key) {
             return self::$_instance->delete($key);
         }
     }
 }
class Comm_Cache {
    private static $_instance;
    /**
     * 获取一个单例缓存对象（memcached）
     *
     * @return memcached object
     */
    public static final function getCacheObj() {
        if (!self::$_instance) {
            $memcached_servers = explode(' ', $_SERVER['SINASRV_MEMCACHED_SERVERS']);
            $memcache = new Memcached;
            foreach ($memcached_servers as $memcached) {
                list($server, $port) = explode(':', $memcached);
                $memcache->addServer($server, $port, FALSE);
            }

            self::$_instance = $memcache;
        } 
        return self::$_instance;
    }
    
    /**
     * 生成一个memcache key
     *
     * @param string $key
     * @return string key
     */
    public static final function getCacheKey($key) {
        if(trim($key)=="") return "";
        return SINASRV_MEMCACHED_KEY_PREFIX .$key;
    }


    public static function useMem($param,$data=NULL,$maxTime=10,$isMem=true){//return array();
        if(!$isMem) return $data;
        $keys = self::getKeys($param);
        self::$_instance = self::getCacheObj();
        if($data!==NULL){
        	self::$_instance->set($keys,$data,$maxTime);
        	return $data;
        }
        $res = self::$_instance->get($keys);
        
        return empty($res)?$data:$res;
    }

    public static function getKeys($param){
        $key = $_SERVER['SINASRV_MEMCACHED_KEY_PREFIX'] . 'temporary1';
        if(is_array($param)){
            ksort($param);
            $key = $key . http_build_query($param);
        }else{
            $key = md5($key . $param);
        }

        return md5($key);
    }
}
