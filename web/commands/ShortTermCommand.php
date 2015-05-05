<?php

/**
 * @desc 短线操作实时分析
 * @author fox
 * @date 2015/04/28
 */

Yii::import('application.components.StatLogUtil');
Yii::import('application.components.StockUtil');
Yii::import('application.components.CommonUtil');
Yii::import('application.modules.stock.models.*');

class ShortTermCommand extends CConsoleCommand
{	
	public $inited = false;
	// 缺省保留20只股票, count需要根据开盘时间趋于变小
	public $count = 20;
	
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "php -c /etc/php.ini console_entry.php shortterm <location> [count] [day]\n";
			exit(1);
		}
		
		$location = intval($args[0]);
		if (isset($args[1]))
		{			
			$this->count = intval($args[1]);
		}
		$day = isset($args[2])? intval($args[2]) : intval(date('Ymd'));
		if (!CommonUtil::isMarketOpen($day, $location))
		{
			echo "err=market_not_open location=${location} day=${day}";
			return;
		}
		
		$lastOpenDay = CommonUtil::getPastOpenDay($day, 1, $location);
		while (true)
		{
			// $marketState = CommonUtil::getMarketState($location);
			$marketState = CommonUtil::MSTATE_OPENED;
			if ((CommonUtil::MSTATE_NOT_OPEN == $marketState) || (CommonUtil::MSTATE_PAUSED == $marketState))
			{
				if ((CommonUtil::MSTATE_NOT_OPEN == $marketState) && !$this->inited)
				{
					$this->initialize($location, $day, $lastOpenDay);	
				}
			}
			else if (CommonUtil::MSTATE_CLOSED == $marketState)
			{
				return;
			}
			else 
			{
				$this->core($location, $day, $lastOpenDay);
			}
			sleep(60);
		}
	}
	
	/**
	 * @desc 初始化加载短线股票池
	 *
	 * @param int $location
	 * @param int $day
	 * @param int $lastOpenDay
	 * @return bool
	 */
	public function initialize($location, $day, $lastOpenDay)
	{
		$shortPoolList = array();
		$sidList = StockUtil::getStockList($location);
		
		// 添加技术分析股票池
		$recordList = StockPool::model()->findAllByAttributes(array('day' => $lastOpenDay, 'status' => 'Y'));
		foreach ($recordList as $record)
		{
			$sid = $record->sid;
			if (!in_array($sid, $sidList))
			{
				continue;
			}
			$shortPoolList[$sid] = $record->source;
		}
		
		// TODO: 添加热点/资讯选股
		$key = "shortpool-" . $location . "-" . $day;
		Yii::app()->redis->set($key, json_encode($shortPoolList), 86400);
		$this->inited = true;
		
		return true;
	}
	
	public function core($location, $day, $lastOpenDay)
	{
		if (!$this->inited)
		{
			$this->initialize($location, $day, $lastOpenDay);
		}
		
		$cacheKey = "shortpool-" . $location . "-" . $day;
		$cacheValue = Yii::app()->redis->get($cacheKey);
        $cachePoolList = json_decode($cacheValue, true);
		$stockScoreMap = array();
		
		$configInfo = Yii::app()->params['config'];
		foreach ($cachePoolList as $sid => $source)
		{
			$result = $this->filter($sid, $day, $source);
			echo "op=filter_result " . StatLogUtil::array2log(array(
				'sid' => $sid,
				'result' => $result,
				'day' => $day
			)) . "\n";	
			
			if (0 == $result)
			{
				$score = $this->evaluate($sid, $day, $configInfo);
				$stockScoreMap[$sid] = $score;
			}
		}
		
		arsort($stockScoreMap, SORT_NUMERIC);
        $slicePreCount = count($stockScoreMap);
		$newPoolList = array();
		foreach ($stockScoreMap as $sid => $score)
		{
			$newPoolList[$sid] = $cachePoolList[$sid];
		}
		
		$stockScoreMap = array_slice($stockScoreMap, 0, $this->count, true);
		$shortList = array_slice($stockScoreMap, 0, $this->count, true);
		echo "op=core_run time=" . date("His") . " pre_count=${slicePreCount} " . StatLogUtil::array2log($stockScoreMap) . "\n";
		
		// 更新shortpool和shortterm
		Yii::app()->redis->set($cacheKey, json_encode($newPoolList), 86400);
		Yii::app()->redis->set("shortterm-" . $location . "-" . $day, json_encode($shortList), 86400);
		return;
	}
	
	public function filter($sid, $day, $source)
	{
		$hqData = DataModel::getHQData($sid, $day);
		$dailyData = $hqData['daily'];
        var_dump($dailyData);

		// 开盘涨幅在[-3.00, 5.00]之间
		if (($dailyData['open_vary_portion'] < -3.00) || ($dailyData['open_vary_portion'] > 5.00))
		{
			return -1;
		}
		
		// 当日内震荡涨跌幅在[-5.00, 8.00]之间
		if (($dailyData['vary_portion'] < -5.00) || ($dailyData['vary_portion'] > 8.00))
		{
			return -2;	
		}
		
		$timenumber = intval(date("His"));
		$dailyPolicy = $hqData['policy'];
		if ($timenumber < 935)
		{
			return 0;
		}
		// 日内趋势判断
		else if (($dailyPolicy['trend'] == CommonUtil::DIRECTION_DOWN) || 
		($timenumber >= 945 && $dailyPolicy['trend'] == CommonUtil::DIRECTION_SHAVE))
		{
			return -3;
		}		
		
		return 0;
	}
	
	/**
	 * @desc 结合股票的行情数据/技术指标/基本信息/行业热度进行评分
	 *
	 * @param int $sid
	 * @param int $day
	 * @param array $configInfo
	 * @return array('hq', 'item', 'score')
	 */
	public function evaluate($sid, $day, $configInfo)
	{
		$dataItem = array();
		
		// 填充各个维度的数据
		$hqData = DataModel::getHQData($sid, $day);
		$dailyData = $hqData['daily'];
		$dailyPolicy = $hqData['policy'];	
			
		$stockInfo = $hqData['stock'];
		$stockInfo['close_price'] = $dailyData['close_price'];
		$stockInfo['out_capital'] = $dailyData['out_capital'];
		$stockInfo['pe'] = $dailyData['pe'];
		$dataItem['stock'] = $stockInfo;
		
		$poolRecord = StockPool::model()->findByAttributes(array('sid' => $sid, 'day' => $day, 'status' => 'Y'));
		$techinalItem = array();
		if ($poolRecord)
		{
			$techinalItem['volume_ratio'] = $poolRecord->volume_ratio;
			$poolInfo = DataModel::getPoolInfo($sid, $day, $poolRecord->source);
			if (isset($poolInfo['cont']))
			{
				$techinalItem['cont_days'] = $poolInfo['cont']['cont_days'];
				$techinalItem['sum_price_vary_portion']	= $poolInfo['cont']['sum_price_vary_portion'];
			}
			if (isset($poolInfo['threshold']))
			{
				$techinalItem['high_threshold'] = $poolInfo['threshold']['high_type'];	
			}
			if (isset($poolInfo['pivot']))
			{
				$techinalItem['resist_vary_portion'] = $poolInfo['pivot']['resist_vary_portion'];
			}
		}
		$dataItem['techinal'] = $techinalItem;
		
		$dataItem['hq'] = array(
			'open_vary_portion' => $dailyPolicy['open_vary_portion'],
			'day_vary_portion' => $dailyPolicy['day_vary_portion'],
			'vary_portion' => $dailyData['vary_portion'],
			'volume_ratio' => $dailyPolicy['volume_ratio'],
			'trend' => $dailyPolicy['trend'],
		);
		
		$scoreMap = self::score($dataItem, $configInfo);
		$score = array_sum($scoreMap);
		
		$logInfo = $scoreMap;
		$logInfo['sid'] = $sid;
		$logInfo['day'] = $day;
		$logInfo['score'] = $score;
		echo "op=evalute_stock " . StatLogUtil::array2log($logInfo) . "\n";
		
		return $score;
	}

	/**
	 * @desc 对股票每个维度的多个特征值归一化, 赋予权重汇总得到整体的评分
	 *
	 * @param array $dataItem
	 * @param array $configInfo
	 * @return array
	 */
	public static function score($dataItem, $configInfo)
	{
		$scoreMap = array();
		foreach ($configInfo as $dimension => $dimensionConfig)
		{
			if (isset($dataItem[$dimension]) && !empty($dataItem[$dimension]))
			{
				$dimensionData = $dataItem[$dimension];
				$dimensionScore = 0.0;
				foreach ($dimensionConfig['fields'] as $fieldName => $fieldWeight)
				{
					$dimensionScore += $fieldWeight * self::normalize($dimensionData[$fieldName], $dimensionConfig[$fieldName]);
				}
				
				$scoreMap[$dimension] = $dimensionConfig['weight'] * $dimensionScore;
			}
		}
		
		return $scoreMap;
	}
	
	/**
	 * @desc 对数据维度的特征值归一化
	 *
	 * @param double $value
	 * @param array $configItem 格式如下
	 * 		array(array(score, value), array(score, array('start', 'end'))
	 * @return int
	 */
	public static function normalize($value, $configItem)
	{
		foreach ($configItem as $unit)
		{
			$score = $unit['value'];
			$range = $unit['range'];
			if (is_numeric($range) && ($range == $value))
			{
				return $score;
			}
			else if (is_array($range))
			{
				if (isset($range['start']) && isset($range['end']) && ($range['start'] <= $value) && ($value <= $range['end']))
				{
					return $score;
				}
				else if (isset($range['start']) && ($range['start'] <= $value))
				{
					return $score;
				}
				else if (isset($range['end']) && ($value < $range['end']))	
				{
					return $score;
				}
			}
		}
		
		return 0;
	}
}
?>
