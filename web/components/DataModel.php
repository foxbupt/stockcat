<?php

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

		uasort($rapidList, array(self, "cmpRapidFunc"));      
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
     * @param double $varyPortion 涨幅 
     * @param int $limit 限制条数
     * @return array('uplist', 'stock_map')
     */
    public static function getUpLimitList($lastDay, $day, $varyPortion = 9.00, $limit = 30)
    {
    	$uplist = $dataMap = array();
    	$recordList = StockData::model()->findAll(array(
    									'condition' => "day = ${lastDay} and vary_portion >= ${varyPortion} and status = 'Y'",
    									'order' => 'vary_portion desc, volume asc',
    									'limit' => $limit
    								));
    	foreach ($recordList as $record)
    	{
    		$sid = $record->sid;
    		$uplist[] = $record->getAttributes();
    		$dataMap[$sid] = self::getHQData($sid, $day);
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
    	$dailyKey = BevaUtil::genCacheKey("daily", array($sid, $day));
       	$cacheValue = Yii::app()->redis->get($dailyKey);

       	if ($cacheValue)
       	{
       		$dataItem['daily'] = json_decode($cacheValue, true);	            
       		$dataItem['policy'] = Yii::app()->redis->getInstance()->hGetAll(BevaUtil::genCacheKey("daily-policy", array($sid, $day)));
       	}
       	else 
       	{
       		$dataItem['stock'] = StockUtil::getStockInfo($sid);
       	}
       	
       	return $dataItem;
    }
    
	/** 
      * @desc 对短时间内出现较大变动的股票进行排序
      *
      * @param array $rapidInfo1
      * @param array $rapidInfo2
      * @return int
      */                                                                                                                            
     public static function cmpRapidFunc($rapidInfo1, $rapidInfo2)
     {   
         if ($rapidInfo1["now_time"] == $rapidInfo2["now_time"])
         {   
             return ($rapidInfo1["vary_portion"] < $rapidInfo2["vary_portion"])? 1 : -1; 
         }   
     
         // 涨跌幅相同, 按照时间大小逆序排列
         return ($rapidInfo1["now_time"] < $rapidInfo2["now_time"])? 1 : -1; 
     }   
}
?>
