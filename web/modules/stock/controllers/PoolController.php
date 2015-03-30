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
        $dayParam = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
        
        $day = CommonUtil::getParamDay($dayParam, $location);
        $lastDay = ($day == $dayParam)? CommonUtil::getPastOpenDay($day, 1, $location) : $day;
        // var_dump($dayParam, $day, $lastDay);

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
        $marketState = CommonUtil::getMarketState($location);
        if ($marketState >= CommonUtil::MSTATE_OPENED)
        {
		    $dataMap = SortHelper::sort($dataMap, array("daily.vary_portion", "daily.open_vary_portion"), false);
        }

        $this->render('index', array(                   
                    'hqMap' => $dataMap,
                    'day' => $day,
                    'lastDay' => $lastDay,
        			'nextDay' => CommonUtil::nextDay($day),
        			'location' => $location,
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
    	$pastDay = date("Ymd", strtotime("-6 month", strtotime($day)));
    	$startDay = strval(intval(intval($day) / 10000)) . "0101";
    	if ($startDay >= $pastDay) {
    		$startDay = $pastDay;
    	}
    	return $this->createUrl('/stock/stock/trend', array('sid' => $sid, 'type' => $type, 'start_day' => $startDay));
    }
    
    /**
     * @desc 展现价格突破列表
     * @param $_GET['day'] 可选
     * @param $_GET['location'] 可选, 缺省为1
     *
     */
    public function actionThreshold()
    {
     	$dayParam = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;

        $day = CommonUtil::getParamDay($dayParam);
        $lastDay = ($day == $dayParam)? CommonUtil::getPastOpenDay($day, 1, $location) : $day;
        // var_dump($day, $lastDay);

		$thresholdInfo = DataModel::getThresholdList($lastDay, $day, array(3, 4), array(1, 2, 3, 4));	
        $thresholdMap = $thresholdInfo['threshold_map'];
		// var_dump($thresholdMap);
        
        $hqDataMap = array();
        $sidList = StockUtil::getStockList($location);
        foreach ($thresholdMap as $sid => $thresholdItem)
        {
        	if (!in_array($sid, $sidList))
        	{
        		continue;
        	}
        	
        	$dataItem = $thresholdInfo['datamap'][$sid];
        	// print_r($dataItem);
            $dataItem['high_type'] = $thresholdItem['high_type'];
            $dataItem['low_type'] = $thresholdItem['low_type'];
            $hqDataMap[$sid] = $dataItem;
        }
        
        $this->render('threshold', array(                   
                    'hqMap' => $hqDataMap,
                    'day' => $day,
                    'lastDay' => $lastDay,
                ));
    }
    
    /**
     * @desc 展现趋势突破列表
     * @param $_GET['day'] 可选
     * @param $_GET['location'] 可选, 缺省为1
     *
     */
    public function actionUpresist()
    {
    	$dayParam = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;

        $day = CommonUtil::getParamDay($dayParam, $location);
        $lastDay = ($day == $dayParam)? CommonUtil::getPastOpenDay($day, 1, $location) : $day;
        // var_dump($day, $lastDay);

        $recordList = StockPivot::model()->findAll(array(
                    'condition' => "day = $lastDay and status = 'Y'",
                    'order' => 'resist_vary_portion asc'
                ));
        $sidList = StockUtil::getStockList($location);

        $resistMap = $datamap = $orderList = array();
		foreach ($recordList as $record)
        {
            $sid = $record->sid;
        	if (!in_array($sid, $sidList))
        	{
        		continue;
        	}

            $resistMap[$sid] = $record;
        	$datamap[$sid] = DataModel::getHQData($sid, $day);	
        	$orderList[] = $sid;
        }
        
    	if (CommonUtil::getMarketState($location) >= CommonUtil::MSTATE_OPENED)
        {
        	$datamap = SortHelper::sort($datamap, array("daily.vary_portion", "daily.open_vary_portion"), false);
        	$orderList = array();
        	foreach ($datamap as $dataItem)
        	{
        		$datamap[$dataItem['sid']] = $dataItem;
        		$orderList[] = $dataItem['sid'];
        	}
		}
		
        $this->render('upresist', array(                   
                    'resistMap' => $resistMap,
        			'datamap' => $datamap,
        			'orderList' => $orderList,
                    'day' => $day,
                    'lastDay' => $lastDay,
        			'nextDay' => CommonUtil::nextDay($day),
        			'location' => $location,
                ));
    }
    
    /**
     * @desc 展现趋势突破列表
     * @param $_GET['day'] 可选
     * @param $_GET['location'] 可选, 缺省为1
     * @param $_GET['count'] 可选, 缺省为10条
     *
     */
    public function actionRankList()
    {
    	$dayParam = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
		$count = isset($_GET['count'])? intval($_GET['count']) : 20;
        
        $day = CommonUtil::getParamDay($dayParam, $location);
        $lastDay = ($day == $dayParam)? CommonUtil::getPastOpenDay($day, 1, $location) : $day;
                
        $recordList = StockPool::model()->findAll(array(
                    'condition' => "day = $lastDay and status = 'Y'",
                    'order' => 'rank desc',
                ));
        $sidList = StockUtil::getStockList($location);

        $rankmap = $datamap = $orderList = array(); 		
		foreach ($recordList as $record)
        {
            $sid = $record->sid;
        	if (!in_array($sid, $sidList))
        	{
        		continue;
        	}

            $rankInfo = $record->getAttributes();
            $rankmap[$sid] = $rankInfo; 
        	$datamap[$sid] = DataModel::getHQData($sid, $day);	
        	$orderList[] = $sid;
        	
            if (count($rankmap) >= $count)
            {
            	break;
            }
        }
        
        if (CommonUtil::getMarketState($location) >= CommonUtil::MSTATE_OPENED)
        {
        	$datamap = SortHelper::sort($datamap, array("daily.vary_portion", "daily.open_vary_portion"), false);
        	$orderList = array();
        	foreach ($datamap as $dataItem)
        	{
        		$datamap[$dataItem['sid']] = $dataItem;
        		$orderList[] = $dataItem['sid'];
        	}
		}
		
        $this->render('rank', array(                   
                    'rankmap' => $rankmap,
        			'datamap' => $datamap,
        			'orderList' => $orderList,	
                    'day' => $day,
                    'lastDay' => $lastDay,
        			'nextDay' => CommonUtil::nextDay($day),
        			'location' => $location,
                ));
    }
    
}
?>
