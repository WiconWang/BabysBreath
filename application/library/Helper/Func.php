<?php
/**
 * 公共函数类
**/
class Helper_Func{
	public static function sortByKey($field,$arr,$direction="SORT_ASC"){
        if(empty($arr))return array();
        $arrSort = array();  
        foreach($arr AS $uniqid => $row){  
            foreach($row AS $key=>$value){  
                $arrSort[$key][$uniqid] = $value;  
            }  
        }  
        if($direction){  
            array_multisort($arrSort[$field], constant($direction), $arr);  
        }

        return $arr;
    }

    public static function covertCode($data){
        if(is_string($data)){
            return iconv('utf-8', 'gbk',$data);
        }
        $new_data = array();
        foreach ($data as $key => &$val) {
            if(is_array($val)){
                $new_data[$key] = self::covertCode($val);
            }else{
                $new_data[$key] = iconv('utf-8', 'gbk',$val);
            }
        }

        return $new_data;
    }
}