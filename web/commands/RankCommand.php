<?php

/**
 * @desc 对所有股票池的股票进行评级排序
 * 		rank: 取值最高为100分, 根据规则依次递减
 * 
 * TODO: 考虑对每项source内按照某些字段排序, 取出top前count的最后合并去重
 * 		如连续上涨中, 根据日均涨幅: 涨幅/天数(刨除>=9%和<=2%后)的进行排序
 * @author fox
 * @date 2015/1/29
 *
 */
class RankCommand extends CConsoleCommand
{
	const MAX_RANK = 100;
	
	/**
	 * @Usage: php -c /etc/php.ini console_entry.php rank [day] [location]  
	 * day: 所属日期, 格式为YYYYmmdd, 缺省为当天
	 * location: 所属国家, 1 china 3 us
	 */
	public function run($args)
	{
		$day = isset($args[0])? intval($args[0]) : intval(date('Ymd'));
		$location = isset($args[1])? intval($args[1]) : CommonUtil::LOCATION_CHINA;
		
		$stockList = StockUtil::getStockList($location);
		$poolist = StockPool::model()->findAllByAttributes(array(
							'day' => $day, 
							'status' => 'Y'
					));

		// $contMap = $thresholdMap = $pivotMap = $dataMap = array();
		// $poolItemMap = array();
					
		foreach ($poolist as $record)
		{
			$sid = $record->sid;
			if (!in_array($sid, $stockList))
			{
				continue;
			}
			
			$poolItem = $record->getAttributes();
			$source = intval($record->source);
			$poolItem['bitcount'] = $bitcount = self::getBitmapCount($source);
			if ($bitcount == CommonUtil::SOURCE_BITMAP)
			{
				$rank = self::MAX_RANK;				
			}
			else 
			{
				$rank = self::evalute($sid, $day, $poolItem);
			}
			
            $poolItem['rank'] = $rank;
			// $result = $record->updateByPk($record->id, array('rank' => $rank));
			$result = 1;
			echo "op=add_rank result=${result} rank=${rank} " . StatLogUtil::array2log($poolItem) . "\n";
		}		
		
	}
	
	public static function evalute($sid, $day, $poolItem)
	{
		$initialRank = self::MAX_RANK - (CommonUtil::SOURCE_BITMAP - $poolItem['bitcount']) * 20;
		$poolInfo = self::getStockPoolItem($sid, $day, $poolItem['source']);
		$hqdata = $poolInfo['hq'];
		
		if (isset($poolInfo['pivot']))
		{
			$pivotInfo = $poolInfo['pivot'];
			if (($pivotInfo['resist_vary_portion'] > 15) || ($pivotInfo['resist_vary_portion'] < 5))
			{
				$initialRank -= 2;
			}
		}
		
		if (isset($poolInfo['cont']))
		{
			$contInfo = $poolInfo['cont'];
			$dayPortion = $contInfo['sum_price_vary_portion'] / $contInfo['cont_days'];
			if (($dayPortion >= 9) || ($dayPortion <= 2))
			{
				$initialRank -= 2;
			}	
			if ($contInfo['cont_days'] >= 10)
			{
				$initialRank -= 2;
			}
		}
		
		/*
		if (isset($poolInfo['threshold'])) // TODO: 价格突破时需要比较与之前历史价格的涨幅比例
		{
			
		} */
		
		$volumeRatio = $poolItem['volume_ratio'];
		if (($volumeRatio < 1) || ($volumeRatio > 5))
		{
			$initialRank -= 2;
		}
		
		$dailyData = $hqdata['daily'];
		$policyData = $hqdata['policy'];
		if (($dailyData['exchange_portion'] >= 10) || ($dailyData['exchange_portion'] <= 1))
		{
			$initialRank -= 5;
		}

		// TODO: 判断是否存在十字星/长上影线
		if ($policyData['high_portion'] <= 0.5) 
		{
			$initialRank -= 2;
		}

		if ($policyData['trend'] != CommonUtil::DIRECTION_UP)
		{
			$initialRank -= 2;
		}
		
		if (($dailyData['close_price'] <= 3) || ($dailyData['close_price'] >= 50))
		{
			$initialRank -= 2;
		}
		
		// TODO: 判断最近一段上涨趋势累计涨幅, 趋势为合并后的趋势
		$latestTrendRecord = StockTrend::model()->findByAttributes(array(
											'sid' => $sid, 
											'type' => CommonUtil::TREND_FIELD_PRICE, 
											'trend' => CommonUtil::DIRECTION_UP
									),
									array(
										'order' => 'id desc',
										'limit' => 1,
									));
		if ($latestTrendRecord)
		{
			if (($latestTrendRecord->vary_portion >= 30) || ($latestTrendRecord->vary_portion <= 5))
			{
				$initialRank -= 2;
			}
		}
		
		return $initialRank;
	}
	
	public static function getStockPoolItem($sid, $day, $source)
	{
		$poolItem = array('hq' => DataModel::getHQData($sid, $day));
		
		if (($source & CommonUtil::SOURCE_CONT) == CommonUtil::SOURCE_CONT)
		{
			$contRecord = StockCont::model()->findByAttributes(array(
										'sid' => $sid, 
										'day' => $day, 
										'status' => 'Y'
							));
			if ($contRecord)
			{
				$poolItem['cont'] = $contRecord->getAttributes();
			}
		}	

		if (($source & CommonUtil::SOURCE_PRICE_THRESHOLD) == CommonUtil::SOURCE_PRICE_THRESHOLD)
		{
			$thresholdRecord = StockPriceThreshold::model()->findByAttributes(array(
										'sid' => $sid, 
										'day' => $day, 
										'status' => 'Y'
							));
			if ($thresholdRecord)
			{
				$poolItem['threshold'] = $thresholdRecord->getAttributes();
			}
		}	

		if (($source & CommonUtil::SOURCE_UP_RESIST) == CommonUtil::SOURCE_UP_RESIST)
		{
			$pivotRecord = StockPivot::model()->findByAttributes(array(
										'sid' => $sid, 
										'day' => $day, 
										'status' => 'Y'
							));
			if ($pivotRecord)
			{
				$poolItem['pivot'] = $pivotRecord->getAttributes();
			}
		}

		return $poolItem;
	}
	
	// 根据source获取多个途径满足的个数
	public static function getBitmapCount($source)
	{
		$bitcount = 0;
		if ($source & CommonUtil::SOURCE_CONT)
		{
			$bitcount += 1;
		}
		if ($source & CommonUtil::SOURCE_PRICE_THRESHOLD)
		{
			$bitcount += 1;
		}
		if ($source & CommonUtil::SOURCE_UP_RESIST)
		{
			$bitcount += 1;
		}
		
		return $bitcount;
	}
}
?>
