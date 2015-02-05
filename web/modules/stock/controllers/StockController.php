<?php

/**
 * @desc 股票信息展示
 * @author fox
 * @date 2013/10/11
 *
 */
class StockController extends Controller 
{
	/**
	 * @desc 从参数中解析sid
	 *
	 * @param array $params
	 * @param int $location 可选
	 * @return int
	 */
	public static function getParamSid($params, $location = CommonUtil::LOCATION_ALL)
	{
        if (isset($params['sid']))
        {
            $sid = intval($params['sid']);
        }
        else
        {
            $stockMap = StockUtil::getStockMap($location);
            $sid = $stockMap[trim($params['code'])];
        }

        return $sid;
	}
	
	/**
	 * @desc 展示股票的详细信息
	 * @param $_GET['sid']/$_GET['code']
	 * @param $_GET['day'] 可选, 缺省为当前日期
	 *
	 */
	public function actionIndex()
	{
        if (!isset($_GET['sid']) && !isset($_GET['code']))
        {
            throw new CHttpException(404);
        }

        $sid = self::getParamSid($_GET);
        if (empty($sid)) 
        {
        	throw new CHttpException(404);
        }

        $day = isset($_GET['day'])? intval($_GET['day']) : intval(date('Ymd'));
        $marketOpen = CommonUtil::isMarketOpen($day);
        $openDay = CommonUtil::getParamDay($day);
        var_dump($day, $marketOpen, $openDay);
        $curTime = intval(date('His'));

        $stockInfo = StockUtil::getStockInfo($sid);
        $hqData = DataModel::getHQData($sid, $openDay);
        var_dump($hqData);
		$prefix = "";
		if ($hqData['daily']['vary_price'] > 0.00)
		{
			$prefix = "+";
		}
		else if ($hqData['daily']['vary_price'] < 0.00)
		{
			$prefix = "-";
		}
		
        /**
         * 页面展示内容包括:
         * 1、股票基本信息: 昨收/今开/当前价格/涨跌幅/换手率/成交量/量比/上涨因子/市值
         * 2、近1月内股票池记录列表: 连续上涨/价格突破/趋势突破 (TODO: 优先以表格展示)
         * 3、多tab曲线图: 当日分时K线/趋势图 /日K线图
         * 4、
         */
		$startDay = intval(date('Ymd', strtotime("1 month ago", strtotime($day))));
		$poolInfoMap = array();
		$poolRecordList = StockPool::model()->findAll(array(
							'condition' => "sid = :sid and day >= :start and day <= :end and status = 'Y'",
							'params' => array(
								'sid' => $sid,
								'start' => $startDay,
								'end' => $day,
							),
							'order' => 'day desc'	
						));
		foreach ($poolRecordList as $record)
		{
			$poolInfoMap[$record->day] = DataModel::getPoolInfo($sid, $record->day, $record->source);					
		}
		
        $this->render('index', array(       
                    'day' => $day,
        			'curTime' => $curTime,
        			'marketOpen' => $marketOpen,
                    'openDay' => $openDay,
                    'stockInfo' => $stockInfo,
                    'hqData' => $hqData,
        			'prefix' => $prefix,
        			'poolList' => $poolRecordList,
        			'poolMap' => $poolInfoMap,
                ));
	}
	
	/**
	 * @desc 描绘价格曲线
	 * @param $_GET['scode'] 股票编码
	 *
	 */
	public function actionDraw()
	{
		$this->render('draw');
	}
	
	/**
	 * @desc 描绘股票近段趋势图
	 * @param $_GET['sid']/$_GET['code'] 股票id/股票代码
	 * @param $_GET['type']	趋势类型, 可选缺省为1, 取值: 1 价格 2 成交量
	 * @param $_GET['start_day'] 起始日期
	 * @param $_GET['end_day']	结束日期, 缺省为当前日期
	 */
	public function actionTrend()
	{
		if (!isset($_GET['start_day']) || (!isset($_GET['sid']) && !isset($_GET['code'])))
		{
			throw new CHttpException(404);
		}
		
        $sid = self::getParamSid($_GET);
        if (empty($sid))
        {
            throw new CHttpException(404);
        }

		$type = isset($_GET['type'])? intval($_GET['type']) : CommonUtil::TREND_FIELD_PRICE;
		$startDay = intval($_GET['start_day']);
		$endDay = isset($_GET['end_day'])? intval($_GET['end_day']) : intval(date('Ymd'));
		
		$this->render('trend', array(
				'sid' => $sid,
				'type' => $type,
				'startDay' => $startDay,
				'endDay' => $endDay,
			));
	}
}
?>
