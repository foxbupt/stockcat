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
                                'order' => 'sum_price_vary_portion desc, day asc',
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
        $curTime = 0;
        foreach ($sidList as $sid)
        {
            $hqDataMap[$sid] = self::getPoolHQData($sid, $day, $lastDay);
            $curTime = $hqDataMap[$sid]['cur_time'];
        }

        $this->render('index', array(
                    'sidList' => $sidList,
                    'contMap' => $contMap,
                    'priceMap' => $priceMap,
                    'hqMap' => $hqDataMap,
                    'day' => $day,
                    'lastDay' => $lastDay,
                    'curTime' => $curTime,
                ));
    }

    // 获取关注股票池中股票的行情数据
    public static function getPoolHQData($sid, $day, $lastDay)
    {
        $hqData = array('detail' => array());

        $stockInfo = StockUtil::getStockInfo($sid);
        $hqData['stock'] = $stockInfo;
        $hqData['data'] = $stockData = StockData::model()->findByAttributes(array('sid' => $sid, 'day' => $lastDay, 'status' => 'Y'));
        $closePrice = (float)$stockData['close_price'];

        $dailyKey = "daily-" . $sid . "-" . $day;
        $cacheValue = Yii::app()->redis->get($dailyKey);
        $curTime = intval(date('Hi'));

        if ($cacheValue)
        {
            $dailyData = json_decode($cacheValue, true);
            // var_dump($dailyData);
            $hqData['detail'] = $dailyData;
            $curTime = $dailyData['time'][count($dailyData['time']) - 1];

            $priceList = $dailyData['price'];
            $hqData['open_price'] = (float)$priceList[0];
            $hqData['cur_price'] = (float)$priceList[count($priceList) - 1];
            
            if (count($priceList) > 2)
            {
                $hqData['trend'] = self::getTrend($hqData['open_price'], $priceList);
            }
            // var_dump($hqData);
        }

        $hqData['cur_time'] = sprintf("%02d:%02d", $curTime/100, $curTime%100);
        return $hqData;
    }

    public static function getTrend($openPrice, $priceList)
    {
        $trendInfo = array();

        $maxPrice = max($priceList);
        $maxIndex = array_search($maxPrice, $priceList);
        $minPrice = min($priceList);
        $minIndex = array_search($minPrice, $priceList);
        $curPrice = $priceList[count($priceList) - 1]; 
        $maxVary = $maxPrice - $curPrice;
        $minVary = $curPrice - $minPrice;

        if ($curPrice > $openPrice) // 上涨
        {
            if ($curPrice == $maxPrice) 
            {
                $trendInfo['trend'] = "上涨";
            }    
            else 
            {
                $varyPortion = ($curPrice - $openPrice) / $openPrice;
                if ($varyPortion < 0.01)
                {
                    $trendInfo['trend'] = "震荡";
                }
                else 
                {
                    $trendInfo['trend'] = ($maxVary >= $minVary)? "下跌" : "上涨";
                }
            }
        }
        else if ($curPrice == $openPrice)
        {
            $trendInfo['trend'] = "震荡";
        }
        else // 下跌
        {
            $varyPortion = abs(($openPrice - $curPrice) / $openPrice);
            $trendInfo['trend'] = ($varyPortion >= 0.01)? "下跌": "震荡下跌";
        }

        if (strstr($trendInfo['trend'], "震荡") !== FALSE)
        {
            $trendInfo['op'] = "待定";
        }
        else if ($trendInfo['trend'] == "上涨")
        {
            $trendInfo['op'] = "买入";
        }
        else
        {
            $trendInfo['op'] = "卖出";
        }

        return $trendInfo;
    }
}
?>
