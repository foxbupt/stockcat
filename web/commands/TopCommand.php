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

		// TODO: 对于运行出来的推荐列表, 当前处于停牌且超过一周的忽略之.
		$sidList = StockUtil::getStockList($location);
		if (("trend" == $type) || ($type == "all"))
		{
			$trendRecommendList = self::recommendTrend($sidList, $day, $location);
			foreach ($trendRecommendList as $recommendItem)
			{
				$sid = $recommendItem['sid'];
				$result = self::addStockPivot($sid, $day, $recommendItem);
				if ($result)
				{
					// 添加到股票池
                	$poolResult = StockUtil::addStockPool($sid, $day, CommonUtil::SOURCE_UP_RESIST, array('trend' => $recommendItem['near']['trend']));
                	$logInfo = array(
                					'result' => $poolResult? 1 : 0,
                					'sid' => $sid,
                					'day' => $day, 
                					'close_price' => $recommendItem['current_price'],
                					'pivot' => $recommendItem['pivot'],
                					'near' => $recommendItem['near'],
                					'total' => $recommendItem['total'],
                				);
                				
                	echo "op=add_pivot_pool_succ ". StatLogUtil::array2log($logInfo) . "\n";
				}
			}
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
		$pastDay = intval(date('Ymd', strtotime("-4 months", strtotime($day))));
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
			if (0 == count($stockDataList))
			{
				$pastCount = CommonUtil::getOpenDayCount($latestTrendRecord->end_day, $day, $location);
				if ($pastCount >= 5)
				{
					echo "op=ignore_trend_stop sid=$sid day=$day past_count=$pastCount end_day=" . $latestTrendRecord->end_day . "\n";
					continue;
				}
			}
			
			$closePrice = (1 == count($stockDataList))? $stockDataList[0]['close_price'] : $latestTrendRecord->end_value;
			$stockInfo = StockUtil::getStockInfo($sid);
			$checkResult = self::checkOutCapital($closePrice, $stockInfo, (CommonUtil::LOCATION_CHINA == $location)? 10 : 15);
			if (!$checkResult)
			{
				echo "op=low_out_capitalisation sid=$sid day=$day close_price=$closePrice out_capital=" . $stockInfo['out_capital'] . "\n";
				continue;
			}
			
			$pivotInfo = TrendHelper::getPivot($sid, $closePrice, $trendList);	
			$supportVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['support']) * 100;
			$resistVaryPortion = CommonUtil::calcPortion($closePrice, $pivotInfo['resist']) * 100;		         
			// var_dump($pivotInfo, $supportVaryPortion, $resistVaryPortion);
			
			$trendData['pivot'] = $pivotInfo;
			$trendData['support_vary_portion'] = $supportVaryPortion;
			$trendData['resist_vary_portion'] = $resistVaryPortion;
			$trendData['trend'] = $latestTrendRecord->trend;
			$trendData['current_price'] = $closePrice;
				
			// 上升趋势判断突破阻力位
			if ((CommonUtil::DIRECTION_UP == $trendData['trend']) && ($resistVaryPortion >= -1.0))
			{								
				echo "op=recommend_trend_above_resist " . StatLogUtil::array2log($trendData) . "\n";	
				$recommendList[] = $trendData;
			}
			// 下降趋势判断突破支撑位
			else if ((CommonUtil::DIRECTION_DOWN == $trendData['trend']) && ($supportVaryPortion <= -1.0))
			{								
				echo "op=recommend_trend_below_support " . StatLogUtil::array2log($trendData) . "\n";	
				$recommendList[] = $trendData;
			}
			else 
			{
				echo "op=ignore_trend_non_match " . StatLogUtil::array2log($trendData) . "\n";	
			}
		}
		
		return $recommendList;
	}
	
	/**
	 * @desc 添加股票超越趋势记录
	 *
	 * @param int $sid
	 * @param int $day
	 * @param array $trendItem
	 * @return bool
	 */
	public static function addStockPivot($sid, $day, $trendItem)
	{
		$lastDay = date("Ymd", strtotime("-2 week", strtotime($day)));
		$recordList = StockPivot::model()->findAll(array(
										'condition' => "sid = ${sid} and day > ${lastDay} and status = 'Y'",
										'order' => 'day desc'											
									));
		foreach ($recordList as $record)
		{
			// 两周内存在重复超越相同阻力位, 表明一直在上涨, 不插入记录
			if (((CommonUtil::DIRECTION_UP == $record->trend) && ($record->resist == $trendItem['pivot']['resist']))
				|| ((CommonUtil::DIRECTION_DOWN == $record->trend) && ($record->support == $trendItem['pivot']['support'])))
			{
				$logInfo = array(
						'lastday' => $record->day,
						'last_price' => $record->close_price,
						'resist' => $record->resist,
						'support' => $record->support,
						'close_price' => $trendItem['current_price'],
					);
				echo "op=ignore_duplicate_record " . StatLogUtil::array2log($logInfo) . "\n";	
				return false;
			}	
		}
		
		$record = new StockPivot();
		
		$record->sid = $sid;
		$record->day = $day;
		$record->trend = $trendItem['trend'];
		$record->close_price = $trendItem['current_price'];
		$record->resist = $trendItem['pivot']['resist'];
		$record->resist_vary_portion = CommonUtil::formatNumber($trendItem['resist_vary_portion']);
		$record->support = $trendItem['pivot']['support'];
		$record->support_vary_portion = CommonUtil::formatNumber($trendItem['support_vary_portion']);
		$record->create_time = time();
		$record->status = 'Y';
		
		if ($record->save())
		{
			return true;
		}
		else
		{
			echo "err=add_pivot_record_failed " . StatLogUtil::array2log($record->getErrors()) . "\n";	
			return false;
		}
	}
	
	/**
	 * @desc 检查流通市值是否超过指定数值
	 *
	 * @param double $closePrice
	 * @param array $stockInfo
	 * @param double $capitalLimit
	 * @return boolean
	 */
	public static function checkOutCapital($closePrice, $stockInfo, $capitalLimit = 10)
	{
		if ($closePrice < 3.00)
		{
			return false;
		}
		
		$outCapital = $closePrice * floatval($stockInfo['out_capital']); 
        if (CommonUtil::LOCATION_US == $stockInfo['location'])	// 美股股本单位为万股, 需要转换为亿
        {
        	$outCapital = $outCapital / 10000;
        }
        
        // A股>10亿, 美股 >= 15亿刀(部分股票获取不到市值, 取不到股本的直接忽略)
        if ($outCapital <= $capitalLimit)
        {
        	return false;
        }
        
        return true;
	}
}
?>
