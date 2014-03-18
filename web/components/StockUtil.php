<?php

/**
 * @desc 股票基本信息的接口封装
 * @author fox
 * @date 2013/07/29
 */
Yii::import('application.models.Tag');
Yii::import('application.modules.stock.models.*');

class StockUtil
{	
	// 类型: 1 股票, 2 指数
	const STOCK_TYPE_STOCK = 1;
	const STOCK_TYPE_INDEXES = 2;
	
	// 所有股票的code => sid映射关系
	const CACHE_KEY_STOCK_MAP = "stock:map";
	// 股票基本信息
	const CACHE_KEY_STOCK_INFO = "stock:info-";
	
	/**
	 * @desc 为股票添加标签
	 * @param int $sid
	 * @param int $tid
	 * @param int $displayOrder
	 * @return bool
	 */
	public static function addStockTag($sid, $tid, $displayOrder = 0)
	{
		$record = new StockTag();
		
		$record->sid = $sid;
		$record->tid = $tid;
		$record->display_order = $displayOrder;
		$record->create_time = time();
		$record->status = 'Y';
		
		return $record->save();
	} 
	
	/**
	 * @desc 获取所有股票code -> id的映射关系
	 * @return array(code => sid)
	 */
	public static function getStockMap()
	{
		$cacheValue = Yii::app()->redis->get(StockUtil::CACHE_KEY_STOCK_MAP);
		if (!$cacheValue)
		{
			$stockMap = array();
			$recordList = Stock::model()->findAllByAttributes(array('status' => 'Y'));
			foreach ($recordList as $record)
			{
				$stockMap[$record->code] = $record->id;
			}
			
			Yii::app()->redis->set(StockUtil::CACHE_KEY_STOCK_MAP, json_encode($stockMap));
			return $stockMap;
		}
		
		return json_decode($cacheValue, true);
	}
	
	/**
	 * @desc 根据股票id获取股票基本信息
	 *
	 * @param int $sid
	 * @return array
	 */
	public static function getStockInfo($sid)
	{
		$cacheKey = StockUtil::CACHE_KEY_STOCK_INFO . strval($sid);
		$cacheValue = Yii::app()->redis->get($cacheKey);
        $cacheValue = false;

		if (!$cacheValue)
		{
			$stockInfo = array();
			$record = Stock::model()->findByPk($sid, "status = 'Y'");
			
			if ($record)
			{
				$stockInfo = $record->getAttributes();
				unset($stockInfo['create_time'], $stockInfo['status']);
			}
			
			Yii::app()->redis->set($cacheKey, json_encode($stockInfo));
			return $stockInfo;
		}
		
		return json_decode($cacheValue, true);
	}
}

?>
