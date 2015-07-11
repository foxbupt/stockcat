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
	const CACHE_KEY_STOCK_MAP = "stock:map-";
	// 股票基本信息
	const CACHE_KEY_STOCK_INFO = "stock:info-";
    // 股票标签列表
    const CACHE_KEY_STOCK_TAGS = "stock:tags-";
	
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
	 * @param $location int 缺省为1
	 * @return array(code => sid)
	 */
	public static function getStockMap($location = CommonUtil::LOCATION_ALL)
	{
		$cacheValue = Yii::app()->redis->get(StockUtil::CACHE_KEY_STOCK_MAP . strval($location));
		if (!$cacheValue)
		{
			$stockMap = array();
            $attrs = array('type' => 1, 'status' => 'Y');
            if ($location > 0)
            {
                $attrs['location'] =  $location;
            }

			$recordList = Stock::model()->findAllByAttributes($attrs);
			foreach ($recordList as $record)
			{
				$stockMap[$record->code] = $record->id;
			}
			
			Yii::app()->redis->set(StockUtil::CACHE_KEY_STOCK_MAP . strval($location), json_encode($stockMap));
			return $stockMap;
		}
		
		return json_decode($cacheValue, true);
	}
	
	/**
	 * @desc 获取location对应的股票
	 *
	 * @param int $location
	 * @return array(sid, ...)
	 */
	public static function getStockList($location = CommonUtil::LOCATION_CHINA)
	{
		$stockMap = self::getStockMap($location);
		return array_values($stockMap);
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
        // $cacheValue = false;

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

    /**
     * @desc 获取指定日期范围内的股票交易数据
     * @param sid int 
     * @param startDay int 
     * @param endDay int
     * @return array
     */
    public static function getStockData($sid, $startDay, $endDay)
    {
        $data = array();

        $recordList = StockData::model()->findAll(array(
                 'condition' => "sid = $sid and day >= ${startDay} and day <= $endDay and status = 'Y'",
            ));
        foreach ($recordList as $record)
        {
            $data[] = $record->getAttributes();
        }

        return $data;
    }

    /**
     * @desc 获取股票的标签列表
     * @param sid int 
     * @return array
     */
    public static function getStockTagList($sid)
    {
		$cacheKey = StockUtil::CACHE_KEY_STOCK_TAGS. strval($sid);
		$cacheValue = Yii::app()->redis->get($cacheKey);
        // $cacheValue = false;

		if (!$cacheValue)
		{
			$tagList = array();
			$recordList = StockTag::model()->findAllByAttributes(array('sid' => $sid, 'status' => 'Y'));
			
			foreach ($recordList as $record)
			{
                $tagList[] = $record->tid;
			}
			
			Yii::app()->redis->set($cacheKey, json_encode($tagList));
			return $tagList;
		}
		
		return json_decode($cacheValue, true);
    }
    
    /**
     * @desc 把股票加入/更新股票池
     *
     * @param int $sid
     * @param int $day
     * @param int $source
     * @param array $trendInfo array('trend', 'wave')
     * @return bool
     */
    public static function addStockPool($sid, $day, $source, $trendInfo = array())
    {
    	$record = StockPool::model()->findByAttributes(array('sid' => $sid, 'day' => $day, 'status' => 'Y'));
    	if (!empty($record))
    	{
    		$attrs = $trendInfo;
    		$attrs['source'] = intval($record->source) | $source;
    		return $record->updateByPk($record->id, $attrs);
    	}
    	
   		$record = new StockPool();
   		
   		$record->sid = $sid;
   		$record->day = $day;
 		$record->source = $source;
 		if (isset($trendInfo['wave']))
 		{
 			$record->wave = $trendInfo['wave'];
 		}
    	if (isset($trendInfo['trend']))
 		{
 			$record->trend = $trendInfo['trend'];
 		}
 		
 		$hqData = DataModel::getHQData($sid, $day);
 		if (!empty($hqData))
 		{
 			if (!empty($hqData['daily']))
 			{
 				$record->close_price = $hqData['daily']['close_price'];
 			}
 			if (!empty($hqData['policy']))
 			{
 				$record->volume_ratio = round($hqData['policy']['volume_ratio'], 2);
 				$record->rise_factor = round($hqData['policy']['rise_factor'], 2);
 			}
 		}
 		$record->create_time = time();
 		$record->status = 'Y';
 		
        if (!$record->save())
        {
            var_dump($record->getErrors());
            return false;
        }

        return true;
    }
}

?>
