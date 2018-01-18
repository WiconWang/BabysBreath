<?php
/**
 * description: 微博相关接口
 * author:
 * createTime: 2016/8/25 12:12
 */

class Comm_Weibo {
    private static $_AKEY = '1';
    private static $_USER = '1@163.com';
    private static $_PASSWORD = '1';
    private static $_AKEY1 = '1'; // 应用：网上4S店的key

    /**
     * 生成短链
     * @param $longUrl
     * @return string
     */
    public static function getShortUrl($longUrl){//生成短链接，司机端点击跳转高德地图
        $url = "http://i2.api.weibo.com/2/short_url/shorten.json?url_long={$longUrl}";
        $data = self::call_method(self::$_AKEY, $url, '', self::$_USER , self::$_PASSWORD);
        if (!empty($data)) {
            if (isset($data['urls'][0]['url_short'])) {
                return $data['urls'][0]['url_short'];
            } else {
                return $longUrl;
            }
        }
        return $longUrl;
    }

    /**
     * http请求
     * @param $akey
     * @param $url
     * @param string $postdata
     * @param string $user
     * @param string $psw
     * @return bool|int|mixed
     */
    private static function call_method($akey, $url, $postdata="", $user = "", $psw="") {
        $cookie = self::getCookie($user, $psw);

        $curl = curl_init();
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $curl , CURLOPT_COOKIE, $cookie);

        $ret = strpos($url, "?");
        $url .= $ret === false ? "?" : "&";

        $url .= "source=$akey";

        curl_setopt($curl , CURLOPT_URL , $url );
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,3);
        curl_setopt($curl,CURLOPT_TIMEOUT,3);  //定义超时3秒钟
        if(is_array($postdata))
        {
            $pdata = "";
            foreach($postdata as $kk=>$vv)
            {
                if($pdata == "")
                    $pdata .= "$kk=" . urlencode($vv);
                else
                    $pdata .= "&$kk=" . urlencode($vv);
            }

            //print "\npdata: $pdata\n";
            curl_setopt( $curl , CURLOPT_POSTFIELDS , $pdata);
        }

        $ret = curl_exec( $curl );
        $ret = preg_replace(array('/"id":(\d+),/'), array("\"id\":\"$1\","), $ret);
        $ret = json_decode($ret , true);
        return $ret;
    }

    //关注用户
    public static function friendships_create2($user_id, $cookie)
    {
        $url = "http://i2.api.weibo.com/2/friendships/create.json";
        $postdata = array('uid'=>$user_id);
        $ret = self::open_get_url1($url, $postdata, $cookie);
        return $ret;
    }


    public static function open_get_url1($url, $postdata="", $cookie="")
    {
        $ret = self::use_weibo_method((defined('UC_AKEY')?UC_AKEY:self::$_AKEY1), $url, $postdata, $cookie);
        return $ret;
    }

    public static function use_weibo_method($akey, $url, $postdata="", $cookie)
    {

        // $cookie = get_cookie($user, $psw);

        $curl = curl_init();
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $curl , CURLOPT_COOKIE, $cookie);

        $ret = strpos($url, "?");
        $url .= $ret === false ? "?" : "&";

        $url .= "source=$akey";

        curl_setopt($curl , CURLOPT_URL , $url );

        if(is_array($postdata))
        {
            $pdata = "";
            foreach($postdata as $kk=>$vv)
            {
                if($pdata == "")
                    $pdata .= "$kk=" . urlencode($vv);
                else
                    $pdata .= "&$kk=" . urlencode($vv);
            }

            //print "\npdata: $pdata\n";
            curl_setopt( $curl , CURLOPT_POSTFIELDS , $pdata);
        }

        $ret = curl_exec( $curl );
        $ret = preg_replace(array('/"id":(\d+),/'), array("\"id\":\"$1\","), $ret);
        $ret = json_decode($ret , true);
        return $ret;
    }

    //

    /**
     * 获取指定cookie
     * @param $cookiestr
     * @param $cname
     * @return string
     */
    private static function getCookie($cookiestr, $cname) {
        $mark = $cname . "=";
        $pos = strpos($cookiestr, $mark);
        if($pos === false) return "";
        $pos += strlen($mark);
        $pos1 = strpos($cookiestr, ";", $pos);
        if($pos1 === false) return "";

        $ret = substr($cookiestr, $pos, $pos1 - $pos);

        return $ret;
    }
}