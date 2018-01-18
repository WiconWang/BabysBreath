<?php

/**
 * 描述：基础依赖第三方接口类
 * 
 */
class Base_Api{

	private static $imgApiUrl = 'http://photo.auto.sina.com.cn/interface/v2/general/get_photo_by_cid.php?carid=';
	private static $newImgApiUrl = 'http://db.auto.sina.com.cn/api/winner/car/getCarPicList.json?carids=';
	private static $articleUrl = 'http://sinanews.sina.cn/auto/sinago_auto_article.d.json?towap=1&id=';
	private static $koubeiUrl = 'http://data.auto.sina.com.cn/api/koubeiapi/getAllBySubid/';
	private static $newsUrl = 'http://interface.sina.cn/auto/inner/getArticleBySubid.d.json?';

	/**
	 * 描述：根据车型ID获取图片信息
	 * @param car_ids array
	 * return array
	 */
	public static function getImgsByCarIds($car_ids){
		if(empty($car_ids) || !is_array($car_ids)){
			return array();
		}

		$result = Comm_Tools::curlRequest(self::$newImgApiUrl . implode(',', $car_ids));
		$result = !empty($result) ? json_decode($result,true) : array();

		return isset($result['status']) && $result['status'] == 0 ? $result['data'] : array();
	}

	/**
	 * 描述：根据文章ID获取文章信息
	 * @param $id
	 * return array
	 **/

	public static function getArticle($id){
		$ret = Comm_Tools::curlRequest(self::$articleUrl.$id);
		$ret = json_decode($ret,true);

		if($ret['result']['status']['code'] !== 0){
			return array();
		}
		
		$article = array();
		$article['shortSummary'] = $ret['result']['data']['items'][0]['shortSummary'];
		$article['URL'] = $ret['result']['data']['items'][0]['URL'];
		$article['pic'] = $ret['result']['data']['items'][0]['mainPic'];
		$article['pub_time'] = $ret['result']['data']['items'][0]['cTime'];
		$article['stitle'] = $ret['result']['data']['items'][0]['stitle'];
		$article['source'] = $ret['result']['data']['items'][0]['outlook'];
		return $article;

	}

	public static function getKoubei($sub_id,$page,$size,$start_time=0,$end_time=0){
		$url = self::$koubeiUrl . $sub_id . '/' . 0;
		if($page > 0){
			$url .= '/' . $page;
		}

		if($size > 0){
			$url .= '/' . $size;
		}

		if($start_time > 0){
			$url .= '/' . $start_time;
		}
		if($start_time > 0 && $end_time -$start_time >=0){
			$url .= '/' . $end_time;
		}
echo $url . '|';
		$cache = array('method'=>__METHOD__,'sub_id'=>$sub_id,'page'=>$page,'size'=>$size,'start_time'=>$start_time,'end_time'=>$end_time);
		$ret = Comm_Cache::useMem($cache);
		// if(!empty($ret))return $ret;

		$ret = Comm_Tools::curlRequest($url);
		
		if(!$ret){
			var_dump($ret);
		}
		$ret = !empty($ret) ? json_decode($ret,true) : array();
		Comm_Cache::useMem($ret,24*60*60);
		return $ret;
	}

	public static function getArticleData($sub_id,$type,$page=1,$size=20){

		if(!$sub_id || !$type){
			return array();
		}

		$param = array('subid'=>$sub_id,'cid'=>$type,'page'=>$page,'limit'=>$size);
		$url = self::$newsUrl . http_build_query($param);
		$cache = array_merge(array('method'=>__METHOD__),$param);
		// $ret = Comm_Cache::useMem($cache);
		if(!empty($ret)) return $ret;
		// echo $url . '|';
		$ret = Comm_Tools::curlRequest($url);
		if(empty($ret)){
			echo $url . '|';
			var_dump($ret);
		}

		return !empty($ret) ? json_decode($ret,true) : false;
	}

}