<?php

class Comm_Page{

    const PAGE_SIZE = 20;
    const PAGE      = 1;

    public static function getQueryUrl($baseUrl,$query){
        $get = array();
        if(isset($query['page']))unset($query['page']);
        if(!empty($query)){
            foreach ($query as $key => $val) {
                array_push($get,$key . '=' . $val);
            }
        }
        return !empty($get) ? $baseUrl . '?' . implode('&', $get) : $baseUrl;
    }
}
