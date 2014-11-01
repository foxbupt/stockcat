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
     * @param $_GET['location'] 可选
     */
    public function actionIndex()
    {
        // TODO: 目前查询上一个有效交易日的连续上涨/价格突破历史和年内新高的股票列表, 后续统一查询股票池列表
        $day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
        
        // $day = CommonUtil::getParamDay($day);
        $lastDay = CommonUtil::getPastOpenDay($day, 1, $location);
        // var_dump($day, $lastDay);

		$contInfo = DataModel::getContList($lastDay, $day);
		$contMap = $contInfo['contmap'];
		
		$thresholdInfo = DataModel::getThresholdList($lastDay, $day, array(1, 2), array());	
        $thresholdMap = $thresholdInfo['threshold_map'];
		// var_dump($contMap, $thresholdMap);
        
        $hqDataMap = array();
        $sidList = StockUtil::getStockList($location);
        // var_dump(count($sidList));
        foreach ($contInfo['datamap'] as $sid => $dataItem)
        {
        	if (!in_array($sid, $sidList))
        	{
        		continue;
        	}
        	
        	$dataItem['cont_days'] = $contMap[$sid]['cont_days'];
        	$dataItem['sum_price_vary_portion'] = $contMap[$sid]['sum_price_vary_portion'];
        	$dataItem['max_volume_vary_portion'] = $contMap[$sid]['max_volume_vary_portion'];
        	$hqDataMap[$sid] = $dataItem;
        }
        
        foreach ($thresholdInfo['datamap'] as $sid => $dataItem)
        {
        	if (!in_array($sid, $sidList))
        	{
        		continue;
        	}
        	
        	if (!isset($hqDataMap[$sid]))
        	{
            	$dataItem['high_type'] = $thresholdMap[$sid]['high_type'];
            	$hqDataMap[$sid] = $dataItem;
        	}
        	else 
        	{
        		$hqDataMap[$sid]['high_type'] = $thresholdMap[$sid]['high_type'];
        	}
        }

        $dataMap = array_values($hqDataMap);
        // var_dump($dataMap);
        $curTime = isset($dataMap[0]['daily'])? $dataMap[0]['daily']['time'] : date('His');
        $curHour = intval(substr($curTime, 0, 2));
        $curMin = intval(substr($curTime, 2, 2));
        if (($curHour == 9 && $curMin >= 25) || ($curHour > 9 && $curHour < 15) || ($curHour == 15 && $curMin == 0))
        {
		    $dataMap = SortHelper::sort($dataMap, array("daily.vary_portion", "daily.open_vary_portion"), false);
        }

        $this->render('index', array(                   
                    'hqMap' => $dataMap,
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
        $day = CommonUtil::getParamDay($day);
        $datamap = DataModel::getRealtimeList($day);
        
        $curTime = date('H:i:s');
    	if (!empty($datamap))
        {
        	$item = current($datamap);
            $curTime = $item['daily']['time'];
        }
        
        // var_dump($datamap);
        $this->render('realtime', array(
        			'day' => $day,
                    'datamap' => $datamap,
                    'curTime' => $curTime,
                ));
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
        $day = CommonUtil::getParamDay($day);
        
        $rapidInfo = DataModel::getRapidList($day, $rise);
        $this->render('rapid', array(
        			'day' => $day,
                    'rise' => $rise,
                    'rapidList' => SortHelper::sort($rapidInfo['rapid_list'], array("now_time", "vary_portion"), false),
                    'stockMap' => $rapidInfo['stock_map']
               ));
    }

    /**
     * @desc 获取昨日涨停的股票当天表现
     * @param $_GET['day'] int
     *
     */
    public function actionUpLimit()
    {
    	$day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
    	$lastDay = CommonUtil::getPastOpenDay($day, 1);
    	
    	$data = DataModel::getUpLimitList($lastDay, $day, $location);
    	$this->render('uplimit', array(
    				'lastDay' => $lastDay,
    				'uplist' => $data['uplist'],
    				'datamap' => $data['datamap'],
    			));
    }
    
    // 获取股票趋势url
    public function getTrendUrl($sid, $type, $day)
    {
    	$startDay = strval(intval(intval($day) / 10000)) . "0101";
    	return $this->createUrl('/stock/stock/trend', array('sid' => $sid, 'type' => $type, 'start_day' => $startDay));
    }
}
?>
