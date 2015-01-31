<?php

Yii::import('application.components.StatLogUtil');
Yii::import('application.components.StockUtil');
Yii::import('application.components.CommonUtil');
Yii::import('application.modules.stock.models.*');

class AnalyzeCommand extends CConsoleCommand
{
    public function run($args)
    {
        if (count($args) < 2)
        {
            echo "Usage: php -c /etc/php.ini console_entry.php analyze <location> <day> [interval]\n";
            exit(1);
        }

        $location = intval($args[0]);
        $day = intval($args[1]);
        $interval = (count($args) >= 3)? intval($args[2]) : 10;
        $startDay = CommonUtil::getPastOpenDay($day, $interval, $location);
        var_dump($startDay, $interval, $day);

        $stockMap = StockUtil::getStockMap($location);
        foreach ($stockMap as $scode => $sid)
        {
            $stockInfo = StockUtil::getStockInfo($sid);
            // 忽略指数
            if (StockUtil::STOCK_TYPE_INDEXES == $stockInfo['type'])
            {
                continue;
            }

            $stockDataList = StockData::model()->findAll(array(
                        'condition' => "sid = $sid and day >= ${startDay} and day <= $day and status = 'Y'",
                        // 'order' => 'day',
                        // 'asc' => 'desc',	    
                    ));

            $result = self::filter($sid, $day, $interval, $stockInfo, $stockDataList);
            if ($result)
            {
                self::addStockCont($day, $stockInfo, $result);
                echo "op=stock_match_succ day=$day sid=$sid code=$scode name=" . $stockInfo['name'] . " " . StatLogUtil::array2log($result) . "\n";
				
                // 添加到股票池
                $poolResult = StockUtil::addStockPool($sid, $day, CommonUtil::SOURCE_CONT, array('trend' => CommonUtil::DIRECTION_UP));
                echo "op=add_pool_succ result=" . ($poolResult? 1:0) . " day=$day sid=$sid code=$scode name=" . $stockInfo['name'] . "\n";
            }
        }

    }

    /**
     * @desc: 过滤条件
     */
    public static function filter($sid, $day, $interval, $stockInfo, $stockDataList)
    {
		$count = count($stockDataList);
        $logInfo = array(
                    'op' => 'filter_not_match',
                    'sid' => $sid,
                    'code' => $stockInfo['code'],
                    'count' => $count,
                );
		
		// 指定周期内有成交记录的天数 <= 2 
		if ($count <= $interval/2)
		{
            $logInfo['reason'] = "low_count";
            echo StatLogUtil::array2log($logInfo) . "\n";
			return FALSE;
		}
		
        // 当天停牌或下跌
		if ($stockDataList[$count-1]['day'] != $day || $stockDataList[$count-1]['vary_portion'] < 0.0)
		{
            $logInfo['reason'] = 'today_stop_or_fall';
            $logInfo['day'] = $stockDataList[$count-1]['day'];
            $logInfo['vary_portion'] = $stockDataList[$count-1]['vary_portion'];

            echo StatLogUtil::array2log($logInfo) . "\n";
			return FALSE;
		}
		
        // 流通市值 = 流通股(亿股) * close_price > 10 亿元
        $close = floatval($stockDataList[$count-1]['close_price']);
        $out_capitalisation = $close * floatval($stockInfo['out_capital']); 
        if (CommonUtil::LOCATION_US == $stockInfo['location'])	// 美股股本单位为万股, 需要转换为亿
        {
        	$out_capitalisation = $out_capitalisation / 10000;
        }
        // var_dump($close, $out_capitalisation);
        
        // A股>10亿, 美股 > 5亿刀(部分股票获取不到市值)
        if (((CommonUtil::LOCATION_CHINA == $stockInfo['location']) && ($out_capitalisation <= 10))
        	|| ((CommonUtil::LOCATION_US == $stockInfo['location']) && ($out_capitalisation > 0) && ($out_capitalisation <= 5)))
        {
            $logInfo['reason'] = "low_out_capitalisation";
            $logInfo['out_cap'] = $out_capitalisation;
            echo StatLogUtil::array2log($logInfo) . "\n";
        	return FALSE;
        }

		// 成交量/价格变化数组
		$volumes = $volume_vary_portion = $price_vary_portion = array();
		$rise_count = $fall_count = 0;		
				
		foreach ($stockDataList as $index => $stockData)
		{
			$vary_portion = floatval($stockData['vary_portion']);
			$price_vary_portion[] = $vary_portion;
			($vary_portion >= 0.0)? $rise_count++ : $fall_count++;
			
            $curVolume = floatval($stockData['volume']);
            $volumes[] = $curVolume;

			if ($index > 0)
			{
				$lastVolume = floatval($stockDataList[$index - 1]['volume']);
				$volume_vary_portion[] = $curVolume / $lastVolume;
			}
			else
			{
				$volume_vary_portion[] = 0.0;
			}
		}
		
        // 总体上涨天数 小于3天, 直接忽略
		if ($rise_count < 3)
		{
            $logInfo['reason'] = "rise_count_lt_3";
            $logInfo['rise_count'] = $rise_count;
            echo StatLogUtil::array2log($logInfo) . "\n";
			return FALSE;
		}
		
		$cont_rise_count = $cont_start_index = $cont_end_index = 0;
		$parts = self::getLongestPart($price_vary_portion);

		foreach ($parts as $partInfo)
		{
			if ($partInfo['length'] >= $cont_rise_count)
			{
				$cont_rise_count = $partInfo['length'];
				$cont_start_index = $partInfo['start'];
				$cont_end_index = $partInfo['end'];
			}	
		}
		
		// 连续上涨天数小于2天 
		if ($cont_rise_count < 2)
		{
            $logInfo['reason'] = "cont_rise_count_lt_2";
            $logInfo['cont_rise_count'] = $cont_rise_count;
            echo StatLogUtil::array2log($logInfo) . "\n";
			return FALSE;
		}	

        // 连续上涨结束日期 < 当前指定日期, 表明这条记录已插入
        if ($stockDataList[$cont_end_index - 1]['day'] < $day) 
        {
            $logInfo['reason'] = "cont_rise_end";
            $logInfo['cont_rise_count'] = $cont_rise_count;
            $logInfo['rise_end_day'] = $stockDataList[$cont_end_index - 1]['day'];
            echo StatLogUtil::array2log($logInfo) . "\n";
			return FALSE;
        }
		
		// 价格连续上涨累计幅度和累计比例
		$cont_vary_price = $cont_vary_portion = 0.0;
		foreach (array_slice($stockDataList, $cont_start_index, $cont_rise_count) as $stockData)
		{
			$cont_vary_price += $stockData['vary_price'];
			$cont_vary_portion += $stockData['vary_portion']; 
		}
        $volume_vary_portion_list = array_slice($volume_vary_portion, $cont_start_index, $cont_rise_count);
        $volumes =  array_slice($volumes, $cont_start_index, $cont_rise_count);
        // 成交量放大的最大比例
        // $volume_scale = round(max($volumes) / min($volumes), 1);
        $volume_scale = round(max($volume_vary_portion_list), 1);
		
        // 价格连续上涨幅度 < 4% 或 成交量放大比例 < 1.5, 直接忽略
        if (($cont_vary_portion <= 3) || ($volume_scale < 1.5))
        {
            $logInfo['reason'] = "low_cont_vary_portion_or_volume_scale";
            $logInfo['cont_rise_count'] = $cont_rise_count;
            $logInfo['cont_vary_portion'] = $cont_vary_portion;
            $logInfo['volume_scale'] = $volume_scale;
            echo StatLogUtil::array2log($logInfo) . "\n";

			return FALSE;
        }

		return array(
				'start_day' => $stockDataList[$cont_start_index]['day'],
				'end_day' => $stockDataList[$cont_end_index - 1]['day'],
                'close_price' => $close,
                'cont_rise_count' => $cont_rise_count,
				'cont_vary_price' => $cont_vary_price,
				'cont_vary_portion' => $cont_vary_portion,
                'volume_vary_portion' => $volume_vary_portion_list,
                'volume_scale' => $volume_scale,
			);
    }
    
    // 获取数据列表中满足条件的最长子序列
    public static function getLongestPart($list)
    {
        $count = count($list);
        $parts = array();
        $lastIndex = 0;
        
        foreach ($list as $index => $data)
        {
            if ($data < 0.0)
            {
                if ($index > $lastIndex)
                {
                    $parts[] = array('start' => $lastIndex, 'end' => $index, 'length' => $index - $lastIndex);	    	
                }
                $lastIndex = $index + 1;
            }
        }
        
        if ($lastIndex < $count)
        {
            $parts[] = array('start' => $lastIndex, 'end' => $count, 'length' => $count - $lastIndex);	  	
        }
        
        return $parts;
    }

    // 添加到股票池中
    public static function addStockCont($day, $stockInfo, $filterInfo)
    {
        $record = new StockCont();

        $record->sid = $stockInfo['id'];
        $record->name = $stockInfo['name'];
        $record->day = $day;
        $record->wave = 3;
        $record->start_day = $filterInfo['start_day'];
        $record->cont_days = $filterInfo['cont_rise_count'];
        $record->current_price = $filterInfo['close_price'];
        $record->sum_price_vary_amount = $filterInfo['cont_vary_price'];
        $record->sum_price_vary_portion = $filterInfo['cont_vary_portion'];
        $record->max_volume_vary_portion = sprintf("%.2f", $filterInfo['volume_scale']);
        $record->add_time = time();
        $record->status = 'Y';

        $result = $record->save();
        // var_dump($record->getErrors());
        return $result;
    }
}
