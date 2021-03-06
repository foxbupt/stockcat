<?php
Yii::import('application.components.BevaUtil');

/**
 * @desc 数据层抽象: 提供访问数据的接口
 * @author fox
 * @date 2014/08/16
 */
class DataModel
{
	/**
	 * @desc 拉取盘中变动较大的股票列表
	 *
	 * @param int $day
	 * @param bool $isRise 缺省true 获取上涨, false 获取下跌
	 * @return array('rapid_list', 'stock_map')
	 */
	public static function getRapidList($day, $isRise = True)
	{
		$prefix = $isRise? "ts-rr" : "ts-rf";
		$key = BevaUtil::genCacheKey($prefix, $day);
		$rapidList = $stockMap = array();
		
		$cacheMap = Yii::app()->redis->getInstance()->hGetAll($key);
		foreach ($cacheMap as $sid => $rapidValue)
		{
			$stockRapidList = json_decode($rapidValue, true);
            // print_r($stockRapidList);
			foreach ($stockRapidList as &$rapidInfo)
			{
				$rapidInfo['sid'] = $sid;
 				$rapidList[] = $rapidInfo;
			}
 			$stockMap[$sid] = StockUtil::getStockInfo($sid);
		}

		return array(
					'rapid_list' => $rapidList, 
					'stock_map' => $stockMap
				);
	}
	
	/**
      * @desc 实时获取上涨列表
      * @param int $day
      * @return array('realtime', 'time')
      */
	public function getRealtimeList($day)
	{
		$datamap = array();
        $riseFactorList = Yii::app()->redis->getInstance()->zRevRange("rf-" . $day, 0, -1, true);
        $allTagList = CommonUtil::getTagListByCategory(CommonUtil::TAG_CATEGORY_INDUSTRY);

        foreach ($riseFactorList as $sid => $riseFactor)
        {
            $dataItem = self::getHQData($sid, $day); 
            $dataItem['rf'] = $riseFactor;         	                   
            $tags = StockUtil::getStockTagList($sid);
            
            $dataItem['tags'] = array();
            foreach ($tags as $tid)
	        {
                 if (isset($allTagList[$tid]))
                 {
                     $dataItem['tags'][] = $allTagList[$tid];
                 }
             }                                                                                                                      
 
             $datamap[$sid] = $dataItem;
	    }
	    
	    return $datamap;
    }

	/**
     * @desc 获取连续上涨的股票列表
     *
     * @param int $lastDay 昨天
     * @param int $day 当天
     * @param int $contDays 连续上涨天数
     * @return array('cont_map', 'datamap')
     */
    public static function getContList($lastDay, $day, $contDays = 3)
    {
        $datamap = $contMap = array();
        
        $contList = StockCont::model()->findAll(array(
                                'condition' => "day = ${lastDay} and cont_days >= ${contDays} and status = 'Y'",
                                'order' => 'sum_price_vary_portion desc, max_volume_vary_portion desc, cont_days desc',
                             ));
        foreach ($contList as $record)
        {
        	$sid = $record->sid;
            $contMap[$sid] = $record->getAttributes();
         	$datamap[$sid] = self::getHQData($sid, $day);
        }

        return array(
        			'contmap' => $contMap,
        			'datamap' => $datamap,
        		);
    }
    
    /**
     * @desc 获取价格突破列表
     *
     * @param int $lastDay 昨天
     * @param int $day 今天
     * @param array $highTypes 价格高位突破类型
     * @param array $lowTypes 价格低位突破类型
     * @return array
     */
    public static function getThresholdList($lastDay, $day, $highTypes, $lowTypes)
    {
        $datamap = $thresholdMap = array();
        $needHigh = !empty($highTypes);
        $needLow = !empty($lowTypes);

        $condition = "day = ${lastDay} ";
        if ($needHigh || $needLow)
        {
            $condition .= " and (";
            if ($needHigh)
            {
                $condition .= " high_type in (" . implode(",", $highTypes) . ")";
                if ($needLow)
                {
                    $condition .= " or ";
                }
            }
            if ($needLow)
            {
                $condition .= " low_type in (" . implode(",", $lowTypes) . ") ";
            }
            $condition .= " ) ";
        }
        $condition .= " and status = 'Y'";
        // var_dump($condition);
        $priceList = StockPriceThreshold::model()->findAll(array(                                                                  
                                            'condition' => $condition
                                        ));
        foreach ($priceList as $record)
        {
            $sid = $record->sid;
            $thresholdMap[$sid] = $record->getAttributes();
         	$datamap[$sid] = self::getHQData($sid, $day);
        }

        return array(
        			'threshold_map' => $thresholdMap,
        			'datamap' => $datamap,
        		);
    }
    
    /**
     * @desc  获取涨幅排行前列的股票列表
     * @param int $lastDay
     * @param int $day
     * @param int $location int
     * @param double $varyPortion 涨幅 
     * @param int $limit 限制条数
     * @return array('uplist', 'stock_map')
     */
    public static function getUpLimitList($lastDay, $day, $location, $varyPortion = 8.00, $limit = 30)
    {
    	$uplist = $dataMap = array();
        $stockList = StockUtil::getStockList($location);
    	$recordList = StockData::model()->findAll(array(
    									'condition' => "day = ${lastDay} and vary_portion >= ${varyPortion} and status = 'Y'",
    									'order' => 'vary_portion desc, volume asc',
    								));

    	$count = 0;							
    	foreach ($recordList as $record)
    	{
    		$sid = $record->sid;
            if (!in_array($sid, $stockList))
            {
                continue;
            }

    		$uplist[] = $record->getAttributes();
    		$dataMap[$sid] = self::getHQData($sid, $day);
    		
    		$count += 1;
    		if (($limit > 0) && ($count >= $limit))
    		{
    			break;
    		}
    	}

    	return array(
    				'uplist' => $uplist, 
    				'datamap' => $dataMap
    			);
    }
    
    /**
     * @desc 获取盘中的行情数据
     *
     * @param int $sid
     * @param int $day
     * @return array('daily', 'policy')
     */
    public static function getHQData($sid, $day)
    {
    	$dataItem = array('sid' => $sid);
       	$dataItem['stock'] = StockUtil::getStockInfo($sid);
       	
    	$dailyKey = BevaUtil::genCacheKey("daily", array($sid, $day));
       	$cacheValue = Yii::app()->redis->get($dailyKey);

       	if ($cacheValue)
       	{
       		$dataItem['daily'] = json_decode($cacheValue, true);	            
       		$dataItem['policy'] = Yii::app()->redis->getInstance()->hGetAll(BevaUtil::genCacheKey("daily-policy", array($sid, $day)));
       	}
       	else // if ($dataItem['stock']['location'] == CommonUtil::LOCATION_US)
       	{
            // $lastday = CommonUtil::getPastOpenDay($day, 1, CommonUtil::LOCATION_US);
       		$datalist = StockUtil::getStockData($sid, $day, $day);
            // var_dump($sid, $day, count($datalist));
       		if (count($datalist) > 0)
       		{
       			$dataItem['daily'] = $datalist[0];
       		}
            else // get last_close_price
            {
                $lastday = CommonUtil::getPastOpenDay($day, 1, $dataItem['stock']['location']);
       		    $datalist = StockUtil::getStockData($sid, $lastday, $day);
                if (count($datalist))
                {
                    $dataItem['daily']['last_close_price'] = $datalist[0]['close_price'];
                }
            }
       	}
       	
       	return $dataItem;
    }
    
    /**
     * @desc 获取股票池的相关信息
     *
     * @param int $sid
     * @param int $day
     * @param int $source
     * @return array('cont', 'threshold', 'pivot')
     */
    public static function getPoolInfo($sid, $day, $source)
    {
    	$poolInfo = array();
    	
    	if ($source & CommonUtil::SOURCE_CONT)	
    	{
    		$contRecord = StockCont::model()->findByAttributes(array(
    							'sid' => $sid, 'day' => $day, 'status' => 'Y')
    					);
    		if ($contRecord)
    		{
    			$poolInfo['cont'] = $contRecord->getAttributes();
    		}
    	}
    	
    	if ($source & CommonUtil::SOURCE_PRICE_THRESHOLD)	
    	{
    		$thresholdRecord = StockPriceThreshold::model()->findByAttributes(array(
    							'sid' => $sid, 'day' => $day, 'status' => 'Y')
    					);
    		if ($thresholdRecord)
    		{
    			$poolInfo['threshold'] = $thresholdRecord->getAttributes();
    		}
    	}
    	
    	if ($source & CommonUtil::SOURCE_UP_RESIST)	
    	{
    		$pivotRecord = StockPivot::model()->findByAttributes(array(
    							'sid' => $sid, 'day' => $day, 'status' => 'Y')
    					);
    		if ($pivotRecord)
    		{
    			$poolInfo['pivot'] = $pivotRecord->getAttributes();
    		}
    	}
    	
    	if ($source & CommonUtil::SOURCE_CANDLE)
    	{
    		$candleList = StockCandle::model()->findAllByAttributes(array(
    						'sid' => $sid, 'day' => $day, 'status' => 'Y'
    					));
    		foreach ($candleList as $candleRecord)
    		{
    			$poolInfo['candles'][] = $candleRecord->getAttributes();
    		}	
    	}
    	return $poolInfo;
    }
    
    /**
     * @desc 获取指定日期的
     *
     * @param int $lastDay
     * @param int $day
     * @param int $source
     * @return array(array('pool', 'hq', 'cont', 'threshold', 'pivot'), ...)
     */
    public static function getPoolList($lastDay, $day, $source = 0)
    {
    	$poolList = array();
    	
    	$condition = "day = ${lastDay} and status = 'Y' ";
    	if ($source)
    	{
    		$condition .= " and (source & ${source}) = ${source}";
    	}
    	
    	$recordList = StockPool::model()->findAll(array(
                                'condition' => $condition,
                                'order' => 'source desc, volume_ratio desc, cont_days desc',
                             ));
        foreach ($recordList as $record)
        {
        	$sid = $record->sid;
        	
        	$dataItem = self::getPoolInfo($sid, $lastDay, $record->source);
        	$dataItem['pool'] = $record->getAttributes();
        	$dataItem['hq'] = self::getHQData($sid, $day);
        	
        	$poolList[] = $dataItem;
        }

        return $poolList;
    }
    
}
?>
