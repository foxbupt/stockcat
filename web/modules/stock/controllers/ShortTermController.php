<?php

/**
 * @desc 短线操作股票池
 *
 */
class ShortTermController extends Controllerion 
{
	/**
	 * @desc 
	 * @param $_GET['location'] 可选
	 */
	public function actionIndex()
	{
		$location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
		$day = CommonUtil::getParamDay(date("Ymd"));
		$cacheKey = "shortpool-" . $location . "-" . $day;
		$shortList = Yii::app()->redis->getInstance()->hGetAll($cacheKey);
		
		$this->render('index', array(
				'day' => $day,
				'location' => $location,
				'shortList' => $shortList,
			));
	}
}
?>