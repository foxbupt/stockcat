<?php

/**
 * @desc 数据接口请求
 * @author: fox
 * @date: 2014/04/16
 */
class ApiController extends CController
{
	/**
	 * @desc 获取股票池列表
	 * @param $_GET['day'] 选择日期, 可选
	 * @param $_GET['cont_day'] 连续天数
	 * @param $_GET['wave'] 所处波段可选, 取值: 1 下跌  3 上升
	 * // @param $_GET['order'] 排序字段可选, 取值有: price_amount/price_portion/volume_portion, 缺省取值为price_portion
	 * @return json
	 */
	public function actionPoollist()
	{
		$this->layout = false;
		
		if (!isset($_GET['cont_day']))
		{
			echo OutputUtil::json(array(), -1, "invalid params");
			return;
		}
		
		$data = array();
		
		$contDay = intval($_GET['cont_day']);
		if (isset($_GET['day']))
		{
			$day = intval($_GET['day']);
		}
		else 
		{
			$currentDay = date('Ymd');
			$day = (date('H') <= 19)? CommonUtil::getPastOpenDay($currentDay, 1) : $currentDay;
		}
		$wave = isset($_GET['wave'])? intval($_GET['wave']) : CommonUtil::DIRECTTION_UP;
		
		$recordList = StockCont::model()->findAll(array(
				'condition' => "day = :day and cont_days >= :cont_days and wave = :wave and status = 'Y'",
				'params' => array(
					'day' => $day,
					'cont_days' => $contDay,
					'wave' => $wave,
				),
				'order' => 'sum_price_vary_portion desc',
		));
		
		foreach ($recordList as $record)
		{
			$itemInfo['item'] = $record->getAttributes();
			$itemInfo['stock'] = StockUtil::getStockInfo($record->sid);
			$data[] = $itemInfo;
		}
		
		echo OutputUtil::json($data);
	}
	
	/**
	 * @desc 获取股票池列表
	 * @param $_GET['day'] 
	 * @param $_GET['type'] 高低点类型, 取值为high/low/all, all时忽略threshold的值
	 * @param $_GET['threshold'] 突破类型, 可选, 取值: 0-4, 缺省为0 表示获取全部
	 * @return json
	 */
	public function actionPrice()
	{
		$this->layout = false;
		
		if (!isset($_GET['day'], $_GET['type']))
		{
			echo OutputUtil::json(array(), -1, "invalid params");
			return;
		}
		
		$data = array();
		$day = intval($_GET['day']);
		$type = isset($_GET['type'])? $_GET['type'] : "all";
		$threshold = isset($_GET['threshold'])? intval($_GET['threshold']) : 0;
		
		$condition = "day = {$day} and status = 'Y'";
		if (("high" == $type) || ("low_type" == $type))
		{
			$fieldName = ("high" == $type)? "high_type" : "low_type";
			if ($threshold > 0)
			{
				$condition .= " and {$fieldName} = {$threshold}";
			}
			else 
			{
				$condition .= " and {$fieldName} > 0";
			}
		} 
		
		$recordList = StockPriceThreshold::model()->findAll(array('condition' => $condition, 'order' => 'high_type, low_type asc'));
		foreach ($recordList as $record)
		{
			$itemInfo['threshold'] = $record->getAttributes();;
			$itemInfo['stock'] = StockUtil::getStockInfo($record->sid);
			$data[] = $itemInfo;
		}
		
		echo OutputUtil::json($data);
	}
}
?>