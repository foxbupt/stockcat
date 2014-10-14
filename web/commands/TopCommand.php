<?php

/**
 * @desc 根据综合信息推荐
 * @author fox
 * @date 2014/10/09
 *
 */
class TopCommand extends CConsoleCommand
{
	/**
	 * @Usage: php -c /etc/php.ini console_entry.php recommend [location] [type] [time] 
	 * location: 所属国家, 1 china 3 us
	 * type: 取值all/trend/realtime
	 * time: 当前时刻, 格式为HHmmss
	 */
	public function run($args)
	{
		$location = isset($args[0])? intval($args[0]) : CommonUtil::LOCATION_CHINA;
		$type = isset($args[1])? $args[1] : "all";
		$curTime = isset($args[2])? intval($args[2]) : intval(date('His')); 
		
		$day = (CommonUtil::LOCATION_CHINA == $location)? intval(date('Ymd')) : intval(date('Ymd', strtotime("1 days ago", time())));
		$day = CommonUtil::getParamDay($day, $location);	
			
		$sidList = StockUtil::getStockList($location);
		if (("trend" == $type) || ($type == "all"))
		{
			$trendRecommendList = self::recommendTrend($sidList, $day, $location);
		}
	}
	
	/**
	 * @desc 按趋势推荐:
	 * 		1、查找每只股票的趋势列表, 对趋势进行合并, 找出年内最高价/最低价
	 * 		2、找出离年内最高价或最低价在20%上下范围的股票
	 * 		3、若当前价格已经是最高点, 若最后一段趋势上涨幅度 >=20%, 则忽略
	 * 		4a)、找出当前趋势为上涨, 且当前价格超过前一段上涨的高点 (2%)
	 * 		// 4b)、当前趋势为下跌, 且最低点 >= 支撑位, 当前价格在支撑位上方(2%)
	 * 		5、后续合并趋势, 避免当前趋势为震荡, 整体趋势为上涨的被忽略掉
	 *
	 * @param array $sidList
	 * @param int $day
	 * @param int $location
	 * @return array
	 */
	public static function recommendTrend($sidList, $day, $location)
	{
		$pastDay = intval(date('Ymd', strtotime("-3 months", strtotime($day))));
		$startDay = intval(substr($day, 0, 4) . "0101");
		if ($startDay >= $pastDay)
		{
			$startDay = $pastDay;
		}
		
		$recommendList = array();
		foreach ($sidList as $sid)
		{
			$trendList = TrendHelper::getTrendList($sid, CommonUtil::TREND_FIELD_PRICE, $startDay, $day);
			$trendData = TrendHelper::analyzeTrendList($sid, $trendList, $startDay, $day, $location);
            // var_dump($trendData);
			
			if (abs($trendData['near']['vary_portion']) >= 50.0)
			{
				echo "op=ignore_trend_near_hrise sid=$sid " . StatLogUtil::array2log($trendData) . "\n";
				continue; 
			}
			
			$count = count($trendList);
			$latestTrendRecord = $trendList[$count - 1];
			if (($trendData['close_price'] == $trendData['high_price']) && ($latestTrendRecord->trend == CommonUtil::DIRECTION_UP) 
				&& ($latestTrendRecord->vary_portion >= 20.0))
			{
				echo "op=ignore_trend_latest_hrise " . StatLogUtil::array2log($latestTrendRecord->getAttributes()) . "\n";
				continue;
			}
			
			$stockDataList = StockUtil::getStockData($sid, $day, $day);
			$closePrice = (1 == count($stockDataList))? $stockDataList[0]['close_price'] : $latestTrendRecord->end_value;
			
			$pivotInfo = TrendHelper::getPivot($sid, $closePrice, $trendList);	
			$supportVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['support']) * 100;
			$resistVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['resist']) * 100;		         
			// var_dump($pivotInfo, $supportVaryPortion, $resistVaryPortion);
			
			$trendData['pivot'] = $pivotInfo;
			$trendData['support_vary_portion'] = $supportVaryPortion;
			$trendData['resist_vary_portion'] = $resistVaryPortion;
			$trendData['trend'] = $latestTrendRecord->trend;
			$trendData['current_price'] = $closePrice;
				
			// TODO: 需要判断shave
			if ((CommonUtil::DIRECTION_UP == $trendData['trend']) && ($resistVaryPortion >= 2.0))
			{								
				echo "op=recommend_trend_above_resist " . StatLogUtil::array2log($trendData) . "\n";	
				$recommendList[] = $trendData;
			}
			else 
			{
				echo "op=ignore_trend_non_match " . StatLogUtil::array2log($trendData) . "\n";	
			}
		}
		
		return $recommendList;
	}
	
}
?>
