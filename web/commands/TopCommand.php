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
			
		$sidList = CommonUtil::getStockList($location);
		if (("trend" == $type) || ($type == "all"))
		{
			$trendRecommendList = self::recommendTrend($sidList, $day, $location);
			var_dump($trendRecommendList);
		}
	}
	
	/**
	 * @desc 按趋势推荐:
	 * 		1、查找每只股票的趋势列表, 对趋势进行合并, 找出年内最高价/最低价
	 * 		2、找出离年内最高价或最低价在20%上下范围的股票
	 * 		3、若当前价格已经是最高点, 若最后一段趋势上涨幅度 >=20%, 则忽略
	 * 		4a)、找出当前趋势为上涨, 且当前价格超过前一段上涨的高点 (2%)
	 * 		4b)、当前趋势为下跌, 且最低点 >= 支撑位, 当前价格在支撑位上方(2%)
	 *
	 * @param array $sidList
	 * @param int $day
	 * @param int $location
	 * @return array
	 */
	public static function recommendTrend($sidList, $day, $location)
	{
		$pastDay = intval(strtotime("3 months ago", strtotime($day)));
		$startDay = intval(substr($day, 0, 4) . "01");
		if ($startDay >= $pastDay)
		{
			$startDay = $pastDay;
		}
		
		$recommendList = array();
		foreach ($sidList as $sid)
		{
			$trendList = TrendHelper::getTrendList($sid, CommonUtil::TREND_FIELD_PRICE, $startDay, $day);
			$trendData = TrendHelper::anazyleTrendList($sid, $location, $trendList, $startDay, $day);
			
			if (abs($trendData['near']['vary_portion']) >= 20.0)
			{
				echo "op=ignore_stock_trend sid=$sid " . StatLogUtil::array2log($trendData) . "\n";
				continue; 
			}
			
			$count = count($trendList);
			$latestTrendRecord = $trendList[$count - 1];
			if (($trendData['close_price'] == $trendData['high_price']) && ($latestTrendRecord->trend == CommonUtil::DIRECTION_UP) 
				&& ($latestTrendRecord->vary_portion >= 20.0))
			{
				echo "op=ignore_stock_trend_highrise " . StatLogUtil::array2log($latestTrendRecord->getAttributes()) . "\n";
				continue;
			}
			
			$closePrice = $latestTrendRecord->close_price;
			$pivotInfo = TrendHelper::getPivot($sid, $closePrice, $trendList);	
			$supportVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['support']) * 100;
			$resistVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['resist']) * 100;		
			
			// TODO: 需要判断shave
			if (((CommonUtil::DIRECTION_UP == $latestTrendRecord->trend) && ($resistVaryPortion >= 2.0))
				|| ((CommonUtil::DIRECTION_DOWN == $latestTrendRecord->trend) && ($supportVaryPortion >= 2.0)))				
			{
				$trendData['pivot'] = $pivotInfo;
				$trendData['support_vary_portion'] = $supportVaryPortion;
				$trendData['resist_vary_portion'] = $resistVaryPortion;
				
				$recommendList[] = $trendData;
				echo "op=recommend_stock_trend " . StatLogUtil::array2log($trendData) . "\n";	
			}
		}
		
		return $recommendList;
	}
	
}
?>