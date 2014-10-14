<?php

/**
 * @desc 趋势分析辅助类
 * @author fox
 * @date 2014/04/29
 */

Yii::import('application.modules.stock.models.*');
class TrendHelper
{
	/**
     * desc 按周分割交易日期
     * @param startDay int
     * @param endDay int
     * @param location int 
     * @return array(array('start', 'end', 'count'), ...)
     */
    public static function partition($startDay, $endDay, $location = CommonUtil::LOCATION_CHINA)
    {
        $parts = array();
        $fromWday = 0;

        $rangeStart = $startDay;
        $timestamp = strtotime($rangeStart);
        $rest = false;

        do
        {
            while (!CommonUtil::isMarketOpen($rangeStart, $location))
            {
                $timestamp = strtotime("+1 day", $timestamp);
                $rangeStart = date('Ymd', $timestamp);
            }

            $startDateInfo = getdate($timestamp);
            $fromWday = $startDateInfo['wday'];

            // 起始日期为本周四之后, 直接合并到下周, 本周至少 >= 3个交易日
            if ($fromWday >= 4)
            {
                $offset = 12 - $fromWday;
                $count = 5 + 5 - $fromWday + 1;
            }
            else // 周三以前则直接到本周五
            {
                $offset = 5 - $fromWday;
                $count = 5 - $fromWday + 1;
            }

            $rangeEndTimestamp = strtotime("+{$offset} days", $timestamp);
            $rangeEnd = date('Ymd', $rangeEndTimestamp);
            // var_dump($rangeStart, $rangeEnd);
            if ($rangeEnd > $endDay)
            {
                $rest = true;
                break;
            }

            $parts[] = array('start' => $rangeStart, 'end' => $rangeEnd, 'count' => $count);

            $timestamp = strtotime("+3 day", $rangeEndTimestamp);   
            $rangeStart = date('Ymd', $timestamp);
        } while ($rangeStart <= $endDay);

        if ($rest)
        {
            $dateInfo = getdate(strtotime($endDay));
            $endWeekDay = $dateInfo['wday'];
            $parts[] = array('start' => $rangeStart, 'end' => $endDay, 'count' => $endWeekDay);
        }

        return $parts;
    }
    
    /**
     * @desc 获取指定日期范围内的交易数据
     *
     * @param array $stockData
     * @param int $startDay
     * @param int $endDay
     * @param string $fieldName
     * @param int $offset 起始偏移
     * @return array('offset', 'days', 'values', 'count')
     */
    public static function getFieldRangeData($stockData, $startDay, $endDay, $fieldName = "close_price", $offset = 0)
    {
    	$index = $offset;
        $items = $days = $values = array();
    	
    	while ($index < count($stockData))
        {
            $item = $stockData[$index];
            // 美股中部分数据存在开盘/收盘价格为0的情况, 忽略之
            if (($item['open_price'] == 0.0) || ($item['close_price'] == 0.0))
            {
                $index += 1;
                continue;
            }

            if (($item['day'] >= $startDay) && ($item['day'] <= $endDay))
            {
                $days[] = $item['day'];
                $values[] = $item[$fieldName];
                $items[] = $item;
            }
            else
            {
                break;
            }

            $index += 1;
        }

    	return array(
    		'offset' => $index,
    		'days' => $days,
    		'values' => $values,
            'items' => $items,
    		'count' => count($days),	
    	);
    }
    
    /**
     * @desc 分析周期内的趋势
     *
     * @param int $sid
     * @param array $periodData array('count', 'days', 'values', 'count')
     * @param array $trendConfig
     * @return array('start', 'end', 'high', 'low', 'trend', 'shave')
     */
    public static function getPeriodTrend($sid, $periodData, $trendConfig)
    {
    	$days = $periodData['days'];
    	$values = $periodData['values'];
        // $items = $periodData['items'];
    	$count = $periodData['count'];
    	
        if (0 == $count)
        {
            return Null;
        }

        $startValue = $values[0];	// $items[0]['open_price'];
        $endValue = $values[$count-1];
    	$periodTrendInfo['start'] = array('day' => $days[0], 'value' => $startValue);
        $periodTrendInfo['end'] = array('day' => $days[$count-1], 'value' => $endValue);

        $highValue = max($values);
        $periodTrendInfo['high_index'] = $highIndex = CommonUtil::reverseSearch($highValue, $values);
        $periodTrendInfo['high'] = array('day' => $days[$highIndex], 'value' => $highValue);

        $lowValue = min($values);
        $periodTrendInfo['low_index'] = $lowIndex = CommonUtil::reverseSearch($lowValue, $values);
        $periodTrendInfo['low'] = array('day' => $days[$lowIndex], 'value' => $lowValue);

        $periodVaryPortion = CommonUtil::calcPortion($endValue, $startValue);
        $highlowVaryPortion = CommonUtil::calcPortion($highValue, $lowValue);
//        print_r($days);
//        print_r($values);
//        var_dump($periodVaryPortion, $highlowVaryPortion);

        $trend = CommonUtil::DIRECTION_SHAVE;
        if (abs($periodVaryPortion) >= $trendConfig['vary_portion']) // 超过3%的振幅
        {
            $trend = ($periodVaryPortion > 0)? CommonUtil::DIRECTION_UP : CommonUtil::DIRECTION_DOWN;
        }

        $periodTrendInfo['trend'] = $trend;
        $periodTrendInfo['shave'] = (abs($highlowVaryPortion) <= $trendConfig['shave_portion']);
        $periodTrendInfo['count'] = $count;
        $periodTrendInfo['days'] = $days;
        $periodTrendInfo['values'] = $values;
        $periodTrendInfo['items'] = $periodData['items'];
        
        return $periodTrendInfo;
    }
    
/**
     * @desc 合并每段周期的趋势
     * param sid int
     * @param type int
     * @param periodTrendList array
     * @param trendConfig array
     * @return array
     */
    public static function mergeTrend($sid, $type, $periodTrendList, $trendConfig)
    {
        $trendList = array();
        $currentTrend = array();
        
        // $stockInfo = StockUtil::getStockInfo($sid);
        foreach ($periodTrendList as $trendInfo)
        {
            if (empty($currentTrend)) // 标识是新开始的一段趋势
            {
                $currentTrend = $trendInfo;
            }
            else if (($trendInfo['trend'] == $currentTrend['trend']) && ($trendInfo['trend'] != CommonUtil::DIRECTION_SHAVE)) // 本周趋势与当前趋势相同, 判断进行合并
            {
				// echo "desc=merge_trend current_trend=" . $currentTrend['trend'] . " week_trend=" . $trendInfo['trend'] . "\n";     
            	// 上涨且最高价超过之前的最高价、下跌且最低价低于之前的最低价                     
               	if ((($trendInfo['trend'] == CommonUtil::DIRECTION_UP) && ($trendInfo['high']['value'] >= $currentTrend['high']['value']))
               		|| (($trendInfo['trend'] == CommonUtil::DIRECTION_DOWN) && ($trendInfo['low']['value'] <= $currentTrend['low']['value'])))
               	{
                   	$currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "end");
               	}
               	else 
               	{
               		$trendList[] = $currentTrend;
               		$currentTrend = $trendInfo;
               	}
             }
             // 本周和当前趋势都为震荡 或 都在震荡范围内, 进行合并
             else if ((($trendInfo['trend'] == $currentTrend['trend']) && ($trendInfo['trend'] == CommonUtil::DIRECTION_SHAVE)) 
             			|| ($currentTrend['shave'] && $trendInfo['shave']))
             {
                 $currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "end");
                 $varyPortion = CommonUtil::calcPortion($currentTrend['end']['value'], $currentTrend['start']['value']);
                 if (abs($varyPortion) >= $trendConfig['vary_portion'])
                 {
                     $currentTrend['trend'] = ($varyPortion > 0)? CommonUtil::DIRECTION_UP : CommonUtil::DIRECTION_DOWN; 
                 }
                 else
                 {
                     $currentTrend['trend'] = CommonUtil::DIRECTION_SHAVE;
                 }
             }
             else 
             {
                 $isNewHigh = ($trendInfo['high']['value'] >= $currentTrend['high']['value']);
				 $isNewLow = ($trendInfo['low']['value'] <= $currentTrend['low']['value']);
		
                 // 创出新高后下跌, 合并[start, high]到当前趋势, [high, end]作为新趋势
                 if (($currentTrend['trend'] == CommonUtil::DIRECTION_UP) && $isNewHigh)
                 {
					// 本周创出新高后震荡/下跌, 结束价格离最高价更近, 总体为上涨
                 	if (($trendInfo['trend'] == CommonUtil::DIRECTION_SHAVE) 
                 		|| ($trendInfo['shave'] && ($trendInfo['end'] >= $currentTrend['high'])))
                 	{
                     	$currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "end");
						// var_dump($currentTrend);
                 	} 
                 	else
                 	{	
                 		$currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "high");
                     	$trendList[] = $currentTrend;
                     						
                      	$currentTrend = self::divideTrend($sid, $trendInfo, $trendConfig, "high");
                     	// var_dump($currentTrend);
                 	}
                 } 
                 // 创出新低后上涨, 合并[start, low]到当前趋势, [low, end]作为新趋势
                 else if (($currentTrend['trend'] == CommonUtil::DIRECTION_DOWN) && $isNewLow)
                 {
                 	// 本周创出新低后震荡/上涨, 结束价格离最低价更近, 总体为下跌
                 	if (($trendInfo['trend'] == CommonUtil::DIRECTION_SHAVE)
                 		|| ($trendInfo['shave'] && ($trendInfo['end'] <= $currentTrend['low'])))
                 	{
                      	$currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "end");
                      	// var_dump($currentTrend);
                 	}
					else
					{
						$currentTrend = self::extendTrend($currentTrend, $trendInfo, $trendConfig, "low");
	                    $trendList[] = $currentTrend;
	                      
	                    $currentTrend = self::divideTrend($sid, $trendInfo, $trendConfig, "low");
	                    // var_dump($currentTrend); 
					}
                 }
                 else 
                 {
                    $trendList[] = $currentTrend;
					$currentTrend = $trendInfo;
                 }
             }
        }

        $trendList[] = $currentTrend;
		foreach ($trendList as &$itemInfo)
		{
			 unset($itemInfo['high_index'], $itemInfo['low_index'], $itemInfo['days'], $itemInfo['values'], $itemInfo['items']);			
		}
		
        return $trendList;
    }
    
	/**
     * @desc 用本周的值与当前趋势的值比较并刷新
     * @param currentValue array
     * @param trendValue array
     * @param isHigh bool
     * @return bool
     */
    public static function compareValue(&$currentValue, $trendValue, $isHigh)
    {
        $changed = false;

        if (($isHigh && ($trendValue['value'] > $currentValue['value']))
            || (!$isHigh && ($trendValue['value'] < $currentValue['value'])))
        {
            $currentValue = $trendValue;
            $changed = true;
        } 

        return $changed;
    }

    /**
     * @desc 合并延长趋势
     * @param currentTrend array
     * @param trendItem array
     * @param index string 缺省为end, 取值为high/low/end
     * @return array
     */
    public static function extendTrend($currentTrend, $trendItem, $trendConfig, $index = "end")
    {
        $newTrend = $currentTrend;
        $newTrend['end'] = $trendItem[$index];

        if ("end" == $index)
        {
            self::compareValue($newTrend['high'], $trendItem['high'], true);
            self::compareValue($newTrend['low'], $trendItem['low'], false);
            $newTrend['count'] += $trendItem['count'];
        }
        else if ("high" == $index)
        {
            self::compareValue($newTrend['high'], $trendItem['high'], true);
            $newTrend['count'] += $trendItem['high_index'] + 1;
        }
        else if ("low" == $index)
        {
            self::compareValue($newTrend['low'], $trendItem['low'], false);
            $newTrend['count'] += $trendItem['low_index'] + 1;
        }

        $newTrend['shave'] = (abs(CommonUtil::calcPortion($newTrend['high']['value'], $newTrend['low']['value'])) <= $trendConfig['shave_portion']);	        
        return $newTrend;
    }
    
    /**
     * @desc 对当前趋势进行裂变分隔
     *
     * @param int $sid
     * @param array $trendItem
     * @param array $trendConfig
     * @param string $index
     * @return array
     */
    public static function divideTrend($sid, $trendItem, $trendConfig, $index = "high")
    {
    	$periodData = array();
    	$sliceIndex = ($index == "high")? $trendItem['high_index'] : $trendItem['low_index'];
    	
    	if ($sliceIndex == $trendItem['count'] - 1)
    	{
    		return $periodData;
    	}
    	
    	$periodData['days'] = array_slice($trendItem['days'], $sliceIndex);
    	$periodData['values'] = array_slice($trendItem['values'], $sliceIndex);
    	$periodData['items'] = array_slice($trendItem['items'], $sliceIndex);
    	$periodData['count'] = count($periodData['days']);
    	
    	return self::getPeriodTrend($sid, $periodData, $trendConfig);
    }
    
    /**
     * @desc 添加股票趋势记录
     *
     * @param int $sid	
     * @param int $type
     * @param int $trendItem
     * @param int $recordId 记录主键id, 缺省为0, >0 表示修改
     * @return bool
     */
    public static function addTrendRecord($sid, $type, $trendItem, $recordId = 0)
    {
    	$attrs = array();
    	
    	$attrs['trend'] = $trendItem['trend'];
    	$attrs['shave'] = $trendItem['shave']? 1 : 0;
    	$attrs['count'] = $trendItem['count'];
    	
    	$attrs['start_day'] = $trendItem['start']['day'];
    	$attrs['start_value'] = $trendItem['start']['value'];
    	$attrs['end_day'] = $trendItem['end']['day'];
    	$attrs['end_value'] = $trendItem['end']['value'];
    	$attrs['vary_portion'] = round(CommonUtil::calcPortion($trendItem['end']['value'], $trendItem['start']['value']) * 100, 2);
    	$attrs['high'] = $trendItem['high']['value'];
    	$attrs['high_day'] = $trendItem['high']['day'];
    	$attrs['low'] = $trendItem['low']['value'];
		$attrs['low_day'] = $trendItem['low']['day'];
		
    	if ($recordId)
    	{
    		return StockTrend::model()->updateByPk($recordId, $attrs);
    	}
    	else 
    	{
    		$record = new StockTrend();
    		$attrs['sid'] = $sid;
    		$attrs['type'] = $type;
    		$attrs['status'] = 'Y';
    		
    		$record->setAttributes($attrs);
    		$result = $record->save();
            return $result;
    	}
    }
    
    /**
     * @desc 获取指定日期范围的趋势记录列表
     *
     * @param int $sid
     * @param int $type
     * @param int $startDay
     * @param int $endDay
     * @param int trend	趋势类型, 缺省为0获取所有类型的趋势
     * @return array
     */
    public static function getTrendList($sid, $type, $startDay, $endDay, $trend = 0)
    {
    	$partCondition = "sid={$sid} and type={$type} ";
    	if ($trend > 0)
    	{
    		$partCondition .= " and trend={$trend} ";
    	}
    	return StockTrend::model()->findAll(array(
    				'condition' => "{$partCondition} and ((start_day >= :startDay and start_day <= :endDay) or (end_day >= :startDay and end_day <= :endDay)) and status = 'Y'",
    				'params' => array(
    						'startDay' => $startDay,
    						'endDay' => $endDay,
    				),
    				'order' => 'start_day asc'
    			));
    }
    
    /**
     * @desc 利用实时交易数据分析盘中趋势
     *
     * @param double $openPrice	开盘价
     * @param double $curPrice 当前价格
     * @param array $priceList	交易价格列表
     * @param double $shaveVaryPortion 震荡涨跌幅范围
     * @return array('trend', 'op')
     */
    public static function analyzeRealtimeTrend($openPrice, $curPrice, $priceList, $shaveVaryPortion = 0.01)
    {
    	$trendInfo = array();
    	
        $maxPrice = max($priceList);
        $minPrice = min($priceList);        
        $maxVary = $maxPrice - $curPrice;
        $minVary = $curPrice - $minPrice;
        $varyPortion = ($curPrice - $openPrice) / $openPrice;

        if (abs($varyPortion) <= $shaveVaryPortion)		// 涨跌幅在1%以内, 认为是震荡
        {
        	$trendInfo['trend'] = CommonUtil::DIRECTION_SHAVE;
        	$trendInfo['op'] = CommonUtil::OP_PEND;
        }   
        else if ($curPrice > $openPrice)	// 上涨
        {
            $trendInfo['trend'] = (($curPrice == $maxPrice) || ($maxVary < $minVary))? CommonUtil::DIRECTION_UP : CommonUtil::DIRECTION_DOWN;
        }
        else if ($curPrice < $openPrice)	// 下跌
        {
        	$trendInfo['trend'] = (($curPrice == $minPrice) || ($minVary < $maxVary))? CommonUtil::DIRECTION_DOWN : CommonUtil::DIRECTION_UP;
        }

        if (CommonUtil::DIRECTION_DOWN == $trendInfo['trend'])
        {
        	$trendInfo['op'] = CommonUtil::OP_SELL;
        }
        else if (CommonUtil::DIRECTION_UP == $trendInfo['trend'])
        {
        	$trendInfo['op'] = CommonUtil::OP_BUY;
        }

        return $trendInfo;
    }
    
    /**
	 * @desc 分析指定日期范围的趋势列表
	 *
	 * @param int $sid
	 * @param array $trendList
	 * @param int $startDay
	 * @param int $endDay
	 * @param int $location
	 * @return array('open_price', 'open_day', 'high_price', 'high_day', 'low_price', 'low_day',
	 * 			'close_price', 'close_day', 'total' => array(), 'near' => array())
	 */
	public static function analyzeTrendList($sid, $trendList, $startDay, $endDay, $location = CommonUtil::LOCATION_CHINA)
	{
		$data = array();
		$count = count($trendList);		
		// 日期范围内的趋势个数
		$rangeCount = 0;
		
		$openPrice = $highPrice = $lowPrice = $closePrice = 0;
		$openDay = $highDay = $lowDay = 0;
		$lastIndex = $count - 1;
		
		foreach ($trendList as $index => $trendRecord)
		{
			if (($startDay > $trendRecord->end_day) || ($endDay < $trendRecord->start_day))
			{
				continue;
			}
			
			// 存在endDay > 所有趋势的结束日期的情况, 所以lastIndex默认为最后一个趋势
			if ($endDay <= $trendRecord->end_day)
			{
				$lastIndex = min($index, $lastIndex);
			}
			
			if (0 == $openDay)
			{
				$openDay = $trendRecord->start_day;
				$openPrice = $trendRecord->start_value;
			}
			
			if ($trendRecord->high >= $highPrice)
			{
				$highPrice = $trendRecord->high;
				$highDay = $trendRecord->high_day;
			}
			
			if ((0 == $lowPrice) || ($trendRecord->low <= $lowPrice))
			{
				$lowPrice = $trendRecord->low;
				$lowDay = $trendRecord->low_day;
			}
            $rangeCount += 1;
		}
		
		if (0 == $rangeCount) // 表明指定区间内没有趋势记录
		{
			return false;
		}
		
		$data['sid'] = $sid;
		$data['open_price'] = $openPrice;
		$data['open_day'] = $openDay;
		$data['high_price'] = $highPrice;
		$data['high_day'] = $highDay;
		$data['low_price'] = $lowPrice;
		$data['low_day'] = $lowDay;
		$data['close_price'] = $closePrice = $trendList[$lastIndex]['end_value'];
		$data['close_day'] = $trendList[$lastIndex]['end_day'];
		
		// 整体价格的涨跌幅和涨跌比例
		$data['total'] = array(
					'vary_price' => $closePrice - $openPrice, 
					'vary_portion' => CommonUtil::calcPortion($closePrice, $openPrice) * 100
				);
		
		// 获取离当前日期最近的突破点类型, 并计算自突破点以来的趋势
		$data['near']['type'] = ($lowDay >= $highDay)? CommonUtil::TREND_NEAR_LOW : CommonUtil::TREND_NEAR_HIGH;
		if (CommonUtil::TREND_NEAR_LOW == $data['near'])
		{
			$data['near']['offset'] = CommonUtil::getOpenDayCount($lowDay, $endDay, $location);
			$data['near']['vary_portion'] = CommonUtil::calcPortion($closePrice, $lowPrice) * 100;
		}
		else
		{
			$data['near']['offset'] = CommonUtil::getOpenDayCount($highDay, $endDay, $location);
			$data['near']['vary_portion'] = CommonUtil::calcPortion($closePrice, $highPrice) * 100;
		}
		
		if ($data['near']['offset'] >= 2*5) // 离突破点的日期在两周以上, 才计算这段趋势
		{
			$nearTrend = CommonUtil::DIRECTION_SHAVE;
			if (abs($data['near']['vary_portion']) >= 10.0)
			{
				 $nearTrend = ($data['near']['vary_portion'] >= 0.0)? CommonUtil::DIRECTION_UP : CommonUtil::DIRECTION_DOWN;		 
			}
			$data['near']['trend'] = $nearTrend;
		}
		
		return $data;
	}
	
	/**
	 * @desc 输出(open, high, low, close)的趋势
	 *
	 * @param double $openPrice
	 * @param double $highPrice
	 * @param double $lowPrice
	 * @param double $closePrice
	 * @param array $trendConfig ('trend_vary_portion', 'shave_vary_portion')
	 * @return array('trend', 'shave', 'vary_price', 'vary_portion', 'threshold_vary_price', 'threshold_vary_portion')
	 */
	public static function outputTrend($openPrice, $highPrice, $lowPrice, $closePrice, $trendConfig)
	{
		$varyPortion = CommonUtil::calcPortion($closePrice, $openPrice) * 100;
		$thresholdVaryPortion = CommonUtil::calcPortion($highPrice, $lowPrice) * 100;
		
		$trend = CommonUtil::DIRECTION_SHAVE;
		if (abs($varyPortion) >= $trendConfig['trend_vary_portion'])
		{
			$trend = ($varyPortion >= 0.0)? CommonUtil::DIRECTION_UP : CommonUtil::DIRECTION_DOWN;
		}
		
		$shave = isset($trendConfig['shave_vary_portion'])? 
			($thresholdVaryPortion >= $trendConfig['shave_vary_portion']) : ($thresholdVaryPortion >= $trendConfig['trend_vary_portion']);
			
		return array(
					'trend' => $trend, 
					'shave' => $shave,
					'vary_price' => $closePrice - $openPrice,
					'vary_portion' => $varyPortion,
					'threshold_vary_price' => $highPrice - $lowPrice,
					'threshold_vary_portion' => $thresholdVaryPortion,
				);		
	}
	
	/**
	 * @desc 获取趋势支点指标
	 *
	 * @param int $sid
	 * @param double $closePrice
	 * @param array $trendList
	 * @return array('support', 'resist')
	 */
	public static function getPivot($sid, $closePrice, $trendList)
	{
		$count = count($trendList);
		
		$latestIndex = $count - 1;		
		$latestRecord = $trendList[$latestIndex];	
		
		// 上涨趋势时结束价不是最高价 或 其它趋势时, 取当前趋势的最高点
		$lastHigh = ($latestRecord->high != $latestRecord->end_value)? $latestRecord->high : 0.0;
		// 下跌趋势时结束价不是最低价 或 其它趋势时, 取当前趋势的最低点
		$lastLow = ($latestRecord->low != $latestRecord->end_value)? $latestRecord->low : 0.0;
		
		// 取倒数第2段和第3段的趋势(一定有段趋势与当前趋势不同), 取最高点/最低点比较
		$lastHigh = max($lastHigh, max($trendList[$latestIndex-1]->high, $trendList[$latestIndex-2]->high));
		$lastLow = min($lastLow, min($trendList[$latestIndex-1]->low, $trendList[$latestIndex-2]->low));
		
		/*
		// 当前趋势为上涨/下跌, 找到其最近的一段反方向的趋势
		$lastIndex = $latestIndex - 1;
		while ($lastIndex >= 0)
		{
			$record = $trendList[$lastIndex];
			if ((0 == $record->shave) && ($currentTrend != $record->trend))
			{
				break;
			}
			
			$lastIndex -= 1;
		}*/
		
		return array('sid' => $sid, 'support' => $lastLow, 'resist' => $lastHigh);
	}
}
?>
