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
	 * @desc 展示股票的详细信息
	 * @param $_GET['code']
	 * @param $_GET['sid']
	 *
	 */
	public function actionIndex()
	{
        if (!isset($_GET['sid']) && !isset($_GET['code']))
        {
            throw new CHttpException(404);
        }

        $sid = isset($_GET['sid'])? intval($_GET['sid']) : 0;
        if ((0 == $sid) && isset($_GET['code']))
        {
            $stockMap = StockUtil::getStockMap(CommonUtil::LOCATION_ALL);
            $sid = $stockMap[trim($_GET['code'])];
        }

        $day = intval(date('Ymd'));
        $openDay = CommonUtil::isMarketOpen($day)? $day : CommonUtil::getPastOpenDay($day, -1);
        $hourmin = intval(date('Hi'));

        $stockInfo = StockUtil::getStockInfo($sid);
        $stockData = $dailyPolicyInfo = array();

        // TODO: 从daily-sid-day里拉取数据
        if (($openDay == $day) && ($hourmin >= 930))
        {
            $key = "daily-" . $sid . "-" . $openDay;
            $cacheValue = Yii::app()->redis->get($key);
            if ($cacheValue)
            {
                $stockData = json_decode($cacheValue, true);
            }

            $dailyPolicyInfo = Yii::app()->redis->getInstance()->hGetAll("daily-policy-" . $sid . "-" . $openDay);
        }
        else // 从t_stock_data查询openDay的交易数据
        {
            $record = StockData::model()->findAllByAttributes(array('sid' => $sid, 'day' => $openDay, 'status' => 'Y'));
            if ($record)
            {
                $stockData = $record->getAttributes();
            }
        }

        $this->render('index', array(
                    'day' => $day,
                    'openDay' => $openDay,
                    'stockInfo' => $stockInfo,
                    'stockData' => $stockData,
                    'dailyPolicyInfo' => $dailyPolicyInfo,
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
	 * @param $_GET['sid'] 股票id
	 * @param $_GET['code'] 股票代码, 可选
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
		
        $sid = 0;
        if (isset($_GET['sid']))
        {
            $sid = intval($_GET['sid']);
        }
        else
        {
            $stockMap = StockUtil::getStockMap(CommonUtil::LOCATION_ALL);
            $sid = $stockMap[trim($_GET['code'])];
        }
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
