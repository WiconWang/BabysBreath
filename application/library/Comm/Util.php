<?php
class Comm_Util {
	
	/**
	 * 判断php宿主环境是否是64bit
	 * 
	 * ps: 在64bit下，php有诸多行为与32bit不一致，诸如mod、integer、json_encode/decode等，具体请自行google。
	 * 
	 * @return bool
	 */
	public static function is_64bit(){
        return PHP_INT_SIZE == 8;
	}
	
	/**
	 * 修正过的ip2long
	 * 
	 * 可去除ip地址中的前导0。32位php兼容，若超出127.255.255.255，则会返回一个float
	 * 
	 * for example: 02.168.010.010 => 2.168.10.10
	 * 
	 * 处理方法有很多种，目前先采用这种分段取绝对值取整的方法吧……
	 * @param string $ip
	 * @return float 使用unsigned int表示的ip。如果ip地址转换失败，则会返回0
	 */
	public static function ip2long($ip){
		$ip_chunks = explode('.', $ip, 4);
		foreach ($ip_chunks as $i => $v){
			$ip_chunks[$i] = abs(intval($v)); 
		}
		return sprintf('%u', ip2long(implode('.', $ip_chunks)));
	}
	
	/**
	 * 判断是否是内网ip
	 * @param string $ip
	 * @return boolean 
	 */
	public static function is_private_ip($ip){
		$ip_value = self::ip2long($ip);
		return ($ip_value & 0xFF000000) === 0x0A000000 //10.0.0.0-10.255.255.255
				|| ($ip_value & 0xFFF00000) === 0xAC100000 //172.16.0.0-172.31.255.255
				|| ($ip_value & 0xFFFF0000) === 0xC0A80000 //192.168.0.0-192.168.255.255
		;
	}
		
	public static function conf($key) {
		return Comm_Config::get ( $key );
	}

    /**
     * 使json_decode能处理32bit机器上溢出的数值类型
     * 
     * @param string $response
     * @param string $field_name
     * @param boolean $assoc
     * @return array|object
     */
    public static function json_decode($value, $assoc = true) {
        //PHP5.3以下版本不支持
        //TODO 获取机器CPU位数
        if (version_compare(PHP_VERSION, '5.3.0', '>') && defined('JSON_BIGINT_AS_STRING')) {
            return json_decode($value, $assoc, 512, JSON_BIGINT_AS_STRING);
        } else {
            $value = preg_replace("/\"(\w+)\":(\d+[\.\d+[e\+\d+]*]*)/", "\"\$1\":\"\$2\"", $value);
            return json_decode($value, $assoc);
        }
    }
    
   /**
     * To get ip belonged region according to ip
     * @param <string> $ip ip address, heard that can be ip strings, split by "," ,but i found it not used
     * @param <int> $type 地域名及ISP的显示格式  0 默认文本格式；
                                                 1 regions.xml中的id；
                                                 2 regions.xml中的code，即ISO-3166的地区代码；
                                                 3 regions.xml中的fips，即FIPS的地区代码。
     * @param <string> $encoding  编码类, gbk或utf-8, 默认为gbk
     * @return <int or array>
     */
    static function get_ip_source($ip, $type = 1, $encoding = 'utf-8') {
        if (!function_exists('lookup_ip_source'))
            return 0;
        $code = lookup_ip_source($ip, $type, $encoding);
        switch ($code) {
            case "-1" :
                return 0;
                break;
            case "-2" :
                return 0;
                break;
            case "-3" :
                return 0;
                break;
            default :
                return $code;
                break;
        }
    
    }
    
    /**
     * 获取真实的客户端ip地址
     *
     * This function is copied from login.sina.com.cn/module/libmisc.php/get_ip()
     *
     * @param boolean $to_long	可选。是否返回一个unsigned int表示的ip地址
     * @return string|float		客户端ip。如果to_long为真，则返回一个unsigned int表示的ip地址；否则，返回字符串表示。
     */
    public static function getRealClientIp($to_long = false) {
        $forwarded = self::getServer('HTTP_X_FORWARDED_FOR');
        if ($forwarded) {
            $ip_chains = explode(',', $forwarded);
            $proxied_client_ip = $ip_chains ? trim(array_pop($ip_chains)) : '';
        }
    
        if (Comm_Util::isPrivateIp(self::getServer('REMOTE_ADDR')) && isset($proxied_client_ip)) {
            $real_ip = $proxied_client_ip;
        } else {
            $real_ip = self::getServer('REMOTE_ADDR');
        }
    
        return $to_long ? self::ip2long($real_ip) : $real_ip;
    }
    
    /**
     * 根据实际场景，获取客户端IP
     * @param	boolean		$to_long	是否变为整型IP
     * @return	string
     */
    public static function getClientIp($to_long = false) {
        static $ip = null;
        if ($ip === null) {
            $module = Yaf_Dispatcher::getInstance()->getRequest()->getModuleName();
            switch ($module) {
            	case 'Internal' :
            	    isset($_GET['cip']) && $ip = $_GET['cip'];
            	    break;
            	case 'Openapi' :
            	    $headers = array();
            	    if(function_exists('getallheaders')) {
            	        foreach( getallheaders() as $name => $value ) {
            	            $headers[strtolower($name)] = $value;
            	        }
            	    } else {
            	        foreach($_SERVER as $name => $value) {
            	            if(substr($name, 0, 5) == 'HTTP_') {
            	                $headers[strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($name, 5))))] = $value;
            	            }
            	        }
            	    }
            	    isset($headers['cip']) && $ip = $headers['cip'];
            	    break;
            	case 'Cli' :
            	    $ip = '0.0.0.0';
            	    //					$ip = `/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1`;
            	    break;
            }
            empty($ip) && $ip = self::getRealClientIp();
        }
    
        return $to_long ? self::ip2long($ip) : $ip;
    }
    
    /**
     * 获取当前Referer
     *
     * @return string
     */
    public static function getReferer() {
        return self::getServer('HTTP_REFERER');
    }
    
    /**
     * 获取当前域名
     *
     * @return string
     */
    public static function getDomain() {
        return self::getServer('SERVER_NAME');
    }
    
    /**
     * 得到当前请求的环境变量
     *
     * @param string $name
     * @return string|null 当$name指定的环境变量不存在时，返回null
     */
    public static function getServer($name) {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
    }
    
    /**
     * 返回当前url
     *
     * @param bool $urlencode 是否urlencode后返回，默认true
     */
    public static function getCurrentUrl($urlencode = true) {
        $req_uri = self::getServer('REQUEST_URI');
        if (null === $req_uri) {
            $req_uri = self::getServer('PHP_SELF');
        }
    
        $https = self::getServer('HTTPS');
        $s = null === $https ? '' : ('on' == $https ? 's' : '');
    
        $protocol = self::getServer('SERVER_PROTOCOL');
        $protocol = strtolower(substr($protocol, 0, strpos($protocol, '/'))) . $s;
    
        $port = self::getServer('SERVER_PORT');
        $port = ($port == '80') ? '' : (':' . $port);
    
        $server_name = self::getServer('HTTP_HOST');
        $current_url = $protocol . '://' . $server_name . $port . $req_uri;
    
        return $urlencode ? rawurlencode($current_url) : $current_url;
    }
    
    /*
     * 获得服务器本地ip
    */
    static public function getServerIp() {
        $exec = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
        $fp = @popen($exec, 'r');
        $ip = trim(@fread($fp, 2096));
        @pclose($fp);
        if (preg_match('/^[0-9\.]+$/', $ip)) {
            return $ip;
        } else {
            return '60.28.175.24';
        }
    }
    
    /**
     * 判断是否是内网ip
     * @param string $ip
     * @return boolean
     */
    public static function isPrivateIp($ip) {
        $ip_value = self::ip2long($ip);
        return ($ip_value & 0xFF000000) === 0x0A000000 ||         //10.0.0.0-10.255.255.255
        ($ip_value & 0xFFF00000) === 0xAC100000 ||         //172.16.0.0-172.31.255.255
        ($ip_value & 0xFFFF0000) === 0xC0A80000;        //192.168.0.0-192.168.255.255
    
    }

    /**
     * php获取中文字符拼音首字母
     * @param $str 汉字
     *
     * @return string 首字母
     */
    public static function getFirstCharter($str){
        if(empty($str)){return '';}

        $fchar=ord($str{0});
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
        $s1=iconv('UTF-8','gb2312',$str);
        $s2=iconv('gb2312','UTF-8',$s1);
        $s=$s2==$str?$s1:$str;
        $asc=ord($s{0})*256+ord($s{1})-65536;
        if($asc>=-20319&&$asc<=-20284) return 'A';
        if($asc>=-20283&&$asc<=-19776) return 'B';
        if($asc>=-19775&&$asc<=-19219) return 'C';
        if($asc>=-19218&&$asc<=-18711) return 'D';
        if($asc>=-18710&&$asc<=-18527) return 'E';
        if($asc>=-18526&&$asc<=-18240) return 'F';
        if($asc>=-18239&&$asc<=-17923) return 'G';
        if($asc>=-17922&&$asc<=-17418) return 'H';
        if($asc>=-17417&&$asc<=-16475) return 'J';
        if($asc>=-16474&&$asc<=-16213) return 'K';
        if($asc>=-16212&&$asc<=-15641) return 'L';
        if($asc>=-15640&&$asc<=-15166) return 'M';
        if($asc>=-15165&&$asc<=-14923) return 'N';
        if($asc>=-14922&&$asc<=-14915) return 'O';
        if($asc>=-14914&&$asc<=-14631) return 'P';
        if($asc>=-14630&&$asc<=-14150) return 'Q';
        if($asc>=-14149&&$asc<=-14091) return 'R';
        if($asc>=-14090&&$asc<=-13319) return 'S';
        if($asc>=-13318&&$asc<=-12839) return 'T';
        if($asc>=-12838&&$asc<=-12557) return 'W';
        if($asc>=-12556&&$asc<=-11848) return 'X';
        if($asc>=-11847&&$asc<=-11056) return 'Y';
        if($asc>=-11055&&$asc<=-10247) return 'Z';
        return null;
    }

    public static function GetIP(){ 
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) 
        $ip = getenv("HTTP_CLIENT_IP"); 
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) 
        $ip = getenv("HTTP_X_FORWARDED_FOR"); 
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) 
        $ip = getenv("REMOTE_ADDR"); 
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) 
        $ip = $_SERVER['REMOTE_ADDR']; 
        else 
        $ip = "unknown"; 
        return($ip); 
    } 

    /**
     * 将字符串中的非数字字符过滤
     */
    public static function filterNotNumChar($str) {
        return preg_replace('/[^0-9]/', '', $str);
    }

    /**
     * 获取当前主域名
     */
    public static function getMainDomain() {
        $domainArr =explode('.',$_SERVER['HTTP_HOST']);
        if(count($domainArr)>2){
            unset($domainArr[0]);
        }
        $ret = implode('.',$domainArr);

        return $ret;
    }

    /**
     * 通过省份城市ID获取对应的名称
     * @param $province
     * @param $city
     * @return array
     */
    public static function getProvCityById($province,$city) {
        $proCity = Comm_Config::getConfig('prov_city_pool');
        $province_name = $city_name = '';
        if ($province && isset($proCity[$province])) {
            $province_name = $proCity[$province]['name'];
            if ($city && isset($proCity[$province]['city'][$city])) {
                $city_name = $proCity[$province]['city'][$city];
            }
        }

        return array('pname'=>$province_name,'cname'=>$city_name);
    }
}
