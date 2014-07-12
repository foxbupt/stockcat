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
        $riseFactorList = Yii::app()->redis->getInstance()->zRevRange("rf-" . $day, 0, -1, true);
        // var_dump($riseFactorList);

        $datamap = array();
        $curTime = date('H:i:s');
        $allTagList = CommonUtil::getTagListByCategory(CommonUtil::TAG_CATEGORY_INDUSTRY);

        foreach ($riseFactorList as $sid => $riseFactor)
        {
            $itemdata = array();

            $dailyValue = Yii::app()->redis->get("daily-" . $sid . "-" . $day);
            $itemdata['daily'] = json_decode($dailyValue, true);
            if (!empty($itemdata['daily']))
            {
                $curTime = $itemdata['daily']['time'];
            }

            $itemdata['daily_policy'] = Yii::app()->redis->getInstance()->hGetAll("daily-policy-" . $sid . "-" . $day);

            $tags = StockUtil::getStockTagList($sid);
            $itemdata['tags'] = array();
            foreach ($tags as $tid)
            {
                if (isset($allTagList[$tid]))
                {
                    $itemdata['tags'][] = $allTagList[$tid];
                }
            }

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
        $hqData = array('sid' => $sid);

        $stockInfo = StockUtil::getStockInfo($sid);
        $hqData['stock'] = $stockInfo;

        $dailyKey = "daily-" . $sid . "-" . $day;
        $cacheValue = Yii::app()->redis->get($dailyKey);
        // var_dump($cacheValue);
        $curTime = intval(date('Hi'));

        if ($cacheValue)
        {
            $dailyData = json_decode($cacheValue, true);
            // var_dump($dailyData);
            $hqData = array_merge($hqData, $dailyData);
            $hqData['open_vary_portion'] = ($hqData['last_close_price'] > 0.0)? round(($hqData['open_price'] - $hqData['last_close_price']) / $hqData['last_close_price'] * 100, 2) : 0.0;

            // var_dump($hqData);
            $hqData['policy'] = Yii::app()->redis->getInstance()->hGetAll("daily-policy-" . $sid . "-" . $day);
        }

        $hqData['cur_time'] = sprintf("%02d:%02d", $curTime/100, $curTime%100);
        return $hqData;
    }
    
    /**
     * @desc 展现快速拉升/下降的阶段
     * @param $_GET['rise'] 1 上升 0 下降
     * @param $_GET['day'] 可选
     */
    public function actionRapid()
    {
        $day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $rise = intval($_GET['rise']);
        $keyPrefix = (1 == $rise)? "ts-rr-" : "ts-rf-";

        $rapidList = $stockMap = array();
        $cacheMap = Yii::app()->redis->getInstance()->hGetAll($keyPrefix . $day);
        
        foreach ($cacheMap as $sid => $rapidValue)
        {
            $stockRapidList = json_decode($rapidValue, true);
            foreach ($stockRapidList as &$rapidInfo)
            {
                $rapidInfo['sid'] = $sid;
                $rapidList[] = $rapidInfo;
            }

            $dailyKey = "daily-" . $sid . "-" . $day;
            $cacheValue = Yii::app()->redis->get($dailyKey);
            if ($cacheValue)
            {
                $stockMap[$sid] = json_decode($cacheValue, true);
            }
        }

		uasort($rapidList, array($this, "cmpRapidFunc"));
        $this->render('rapid', array(
                    'rise' => $rise,
                    'rapidList' => $rapidList,
                    'stockMap' => $stockMap
               ));

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

    /**
     * @desc 对拉升数据排序
     *
     * @param array $rapidInfo1
     * @param array $rapidInfo2
     * @return int
     */
    public function cmpRapidFunc($rapidInfo1, $rapidInfo2)
    {
		if ($rapidInfo1["now_time"] == $rapidInfo["now_time"])
		{
			return ($rapidInfo1["vary_portion"] < $rapidInfo2["vary_portion"])? 1 : -1;
		}
		
        // 按照时间的大小逆序排列
		return ($rapidInfo1["now_time"] < $rapidInfo2["now_time"])? 1 : -1;
    }
}
?>
