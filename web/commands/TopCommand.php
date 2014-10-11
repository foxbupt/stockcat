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
	}
	
	/**
	 * @desc 按趋势推荐:
	 * 		1、查找每只股票的趋势列表, 对趋势进行合并, 找出年内最高价/最低价
	 * 		2、找出离年内最高价或最低价在15%上下范围的股票
	 * 		3、若当前价格已经是最高点, 若最后一段趋势上涨幅度 >=20%, 则忽略
	 * 		4a)、找出当前趋势为上涨, 且当前价格超过前一段上涨的高点
	 * 		4b)、当前趋势
	 *
	 * @param array $sidList
	 * @param int $day
	 * @return array
	 */
	public static function recommendTrend($sidList, $day)
	{
		$recommendList = array();
		$pastDay = intval(strtotime("3 months ago", strtotime($day)));
		$startDay = intval(substr($day, 0, 4) . "01");
		if ($startDay >= $pastDay)
		{
			$startDay = $pastDay;
		}
		
		foreach ($sidList as $sid)
		{
			$trendList = TrendHelper::getTrendList($sid, CommonUtil::TREND_FIELD_PRICE, $startDay, $day);
		}
	}
	
	/**
	 * @desc 分析趋势列表
	 *
	 * @param int $sid
	 * @param array $trendList
	 * @param int $startDay
	 * @param int $endDay
	 * @return array
	 */
	public static function anazyleTrendList($sid, $trendList, $startDay, $endDay)
	{
		$result = array();
		$count = count($trendList);
		$openPrice = $highPrice = $lowPrice = $closePrice = 0;
		$highDay = $lowDay = 0;
		
		foreach ($trendList as $index => $trendRecord)
		{
			if (0 == $index)
			{
				$startDay = $trendRecord->start_day;
				$openPrice = $trendRecord->start_value;
			}
			else if ($index == $count-1)
			{
				$endDay = $trendRecord->end_day;
				$closePrice = $trendRecord->end_value;
			}
			
			if ($trendRecord->high >= $highPrice)
			{
				$highPrice = $trendRecord->high;
				$highDay = $trendRecord->high_day;
			}
			
			if ($trendRecord->low <= $lowPrice)
			{
				$lowPrice = $trendRecord->low;
				$lowDay = $trendRecord->low_day;
			}
		}
		
		
	}
}
?>