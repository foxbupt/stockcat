<?php

/**
 * @desc 短线操作股票池
 *
 */
class ShortTermController extends Controller 
{
	/**
	 * @desc 
	 * @param $_GET['location'] 可选
	 */
	public function actionIndex()
	{
		$location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
		$day = CommonUtil::getParamDay(date("Ymd"));
		$cacheKey = "shortlist-" . $location . "-" . $day;
		$cacheValue = Yii::app()->redis->get($cacheKey);
        $shortList = empty($cacheValue)? array() : json_decode($cacheValue, true);
        // print_r($shortList);
		
		$this->render('index', array(
				'day' => $day,
				'location' => $location,
				'shortList' => $shortList,
			));
	}
}
?>
