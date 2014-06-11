<?php

/**
 * @desc 关注股票池
 * @author fox
 * @date 2014/05/12
 *
 */
class PoolController extends Controller 
{
    /**
     * @desc 股票池列表实时刷新展示
     * @param $_GET['day'] 可选
     */
    public function actionIndex()
    {
        // TODO: 目前查询上一个有效交易日的连续上涨/价格突破历史和年内新高的股票列表, 后续统一查询股票池列表
        $day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $lastDay = CommonUtil::getPastOpenDay($day, 1);
        // var_dump($day, $lastDay);

        $sidList = array();

        $contMap = array();
        $contList = StockCont::model()->findAll(array(
                                'condition' => "day = ${lastDay} and status = 'Y'",
                                'order' => 'sum_price_vary_portion desc, max_volume_vary_portion desc, day asc',
                             ));
        foreach ($contList as $record)
        {
            $sidList[] = $record->sid;
            $contMap[$record->sid] = $record->getAttributes();
        }

        $priceMap = array();
        $priceList = StockPriceThreshold::model()->findAll(array(
                                            'condition' => "day = {$lastDay} and (high_type = 1 or high_type = 2) and status = 'Y'",
                                        ));
        foreach ($priceList as $record)
        {
            $sidList[] = $record->sid;
            $priceMap[$record->sid] = $record->getAttributes();
        }

        $sidList = array_unique($sidList);
        $key = "poollist-" . $day;
        $sidMap = Yii::app()->redis->get($key);
        // var_dump($sidList, $sidMap);

        // 设置到redis缓存中, 用于后台获取开盘价和实时价格
        if (empty($sidMap))
        {
            $sidMap = array();

            foreach ($sidList as $sid)
            {
                $stockInfo = StockUtil::getStockInfo($sid);
                if (!empty($stockInfo))
                {
                    $sidMap[$sid] = strtolower($stockInfo['ecode'] . $stockInfo['code']);
                }
            }

            Yii::app()->redis->set($key, json_encode($sidMap), 86400);
        }

        $hqDataMap = array();
        foreach ($sidList as $sid)
        {
            $hqDataMap[] = self::getPoolHQData($sid, $day, $lastDay);
        }

        $curTime = $hqDataMap[0]['cur_time'];
        $curHour = intval(substr($curTime, 0, 2));
        $curMin = intval(substr($curTime, 3, 2));
        if (($curHour == 9 && $curMin >= 25) || ($curHour > 9 && $curHour < 15) || ($curHour == 15 && $curMin == 0))
        {
		    uasort($hqDataMap, array($this, "cmpHQFunc"));
        }

        $this->render('index', array(
                    // 'sidList' => $sidList,
                    'contMap' => $contMap,
                    'priceMap' => $priceMap,
                    'hqMap' => $hqDataMap,
                    'day' => $day,
                    'lastDay' => $lastDay,
                    'curTime' => $curTime,
        			'trendMap' => CommonUtil::getConfigObject("stock.direction"),
        			'opMap' => CommonUtil::getConfigObject("stock.op"),
                ));
    }

    /**
     * @desc 实时展现股票的上涨因子
     * @param $_GET['day'] 可选
     */
    public function actionRealtime()
    {
        $day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $riseFactorList = Yii::app()->redis->getInstance()->zRevRange("risefactor-" . $day, 0, -1, true);
        // var_dump($riseFactorList);

        $datamap = array();
        $curTime = date('H:i:s');
        $pastdata = Yii::app()->redis->getInstance()->hmGet("pastdata-" . $day, array_keys($riseFactorList));
        // var_dump($pastdata);

        foreach ($riseFactorList as $sid => $riseFactor)
        {
            $itemdata = array();

            $dailyValue = Yii::app()->redis->get("daily-" . $sid . "-" . $day);
            $itemdata['daily'] = json_decode($dailyValue, true);
            if (!empty($itemdata['daily']))
            {
                $curTime = substr($itemdata['daily']['time'], 8);
            }

            $stockPastData = json_decode($pastdata[$sid], true);
            $itemdata['volume_ratio'] = round($itemdata['daily']['predict_volume'] / $stockPastData['avg_volume'], 1);
            $itemdata['stock'] = StockUtil::getStockInfo($sid);

            $datamap[$sid] = $itemdata;
        }

        // var_dump($datamap);
        $this->render('realtime', array(
                    'riseFactorList' => $riseFactorList,
                    'datamap' => $datamap,
                    'curTime' => $curTime
                ));
    }

    // 获取关注股票池中股票的行情数据
    public static function getPoolHQData($sid, $day, $lastDay)
    {
        $hqData = array('sid' => $sid, 'detail' => array());

        $stockInfo = StockUtil::getStockInfo($sid);
        $hqData['stock'] = $stockInfo;
        $hqData['data'] = $stockData = StockData::model()->findByAttributes(array('sid' => $sid, 'day' => $lastDay, 'status' => 'Y'));
        $closePrice = (float)$stockData['close_price'];

        $dailyKey = "realtime-" . $sid . "-" . $day;
        $cacheValue = Yii::app()->redis->get($dailyKey);
        $curTime = intval(date('Hi'));

        if ($cacheValue)
        {
            $dailyData = json_decode($cacheValue, true);
            // var_dump($dailyData);
            $hqData['detail'] = $dailyData;
            $curTime = $dailyData['time'][count($dailyData['time']) - 1];

            $priceList = $dailyData['price'];
            $openPrice = $hqData['open_price'] = (float)$priceList[0];
            $hqData['open_vary_portion'] = round(($openPrice - $closePrice) / $closePrice * 100, 2);
            
            $curPrice = $hqData['cur_price'] = (float)$priceList[count($priceList) - 1];
            $hqData['vary_portion'] = ($openPrice > 0.0)? round(($curPrice - $openPrice) / $openPrice * 100, 2) : 0.00;
            
            if (count($priceList) > 2)
            {
                $hqData['trend'] = TrendHelper::analyzeRealtimeTrend($openPrice, $curPrice, $priceList);
            }
            // var_dump($hqData);
        }

        $hqData['cur_time'] = sprintf("%02d:%02d", $curTime/100, $curTime%100);
        return $hqData;
    }
    
    /**
     * @desc 对行情数据排序
     *
     * @param array $hqData1
     * @param array $hqData2
     * @return int
     */
    public function cmpHQFunc($hqData1, $hqData2)
    {
    	// 9:30后采用当天涨幅排序
		$fieldName = (intval(date('Hi')) >= 930)? "vary_portion" : "open_vary_portion";
		
		if ($hqData1[$fieldName] == $hqData2[$fieldName])
		{
			return 0;
		}
		
        // 按照涨幅的大小逆序排列
		return ($hqData1[$fieldName] < $hqData2[$fieldName])? 1 : -1;
    }
}
?>
