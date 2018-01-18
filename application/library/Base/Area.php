<?php
/**
 * description: 地区类
 * author: lilong3@staff.sina.com.cn
 * createTime: 2016/5/11 12:08
 */

class Base_Area {

    /**
     * 地区数据
     * @var array
     */
    protected static $areas = null;

    /**
     * 获取省份城市数据源
     * @return array
     */
    private static function getAreas() {
        if (is_null(self::$areas)) {
            self::$areas = Comm_Config::getConfig('area_config');
        }
        return self::$areas;
    }

    /**
     * 获取所有省份数据
     * @return array
     */
    public static function getAllProvince() {
        self::getAreas();
        $province = array();
        foreach(self::$areas as $k=>$v) {
            array_push($province, array('id'=>$v['code'],'name'=>$v['name']));
        }

        return $province;
    }
    
    /**
     * 获取指定省份的城市数据
     * @param $pid
     * @return array
     */
    public static function getCitiesByPid($pid) {
        self::getAreas();
        $province = isset(self::$areas[$pid])?self::$areas[$pid]:array('city'=>array());
        $city = array();
        foreach($province['city'] as $k=>$v) {
            array_push($city, array('id'=>$k,'name'=>$v));
        }

        return $city;
    }

    /**
     * 通过省份ID获取对应的省份名称
     * @param $pid
     * @return string
     */
    public static function getPnameByPid($pid) {
        self::getAreas();
        return isset(self::$areas[$pid])?self::$areas[$pid]['name']:'';
    }

    /**
     * 通过省份ID和城市ID获取城市名称
     * @param $pid
     * @param $cid
     * @return string
     */
    public static function getCnameByCid($pid,$cid) {
        self::getAreas();
        if (isset(self::$areas[$pid])) {
            return isset(self::$areas[$pid]['city'][$cid])?self::$areas[$pid]['city'][$cid]:'';
        } else {
            return '';
        }
    }

    /**
     * 获取直辖市ID
     * @return array
     */
    public static function getSpecialId() {
        return array(11,12,31,50);
    }
}