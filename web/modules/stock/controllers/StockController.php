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
	 * @param $_GET['scode']
	 *
	 */
	public function actionIndex()
	{
		$this->render('index');
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
	 * @param $_GET['type']	趋势类型, 可选缺省为1, 取值: 1 价格 2 成交量
	 * @param $_GET['start_day'] 起始日期
	 * @param $_GET['end_day']	结束日期, 缺省为当前日期
	 */
	public function actionTrend()
	{
		if (!isset($_GET['sid'], $_GET['start_day']))
		{
			throw new CHttpException(404);
		}
		
		$sid = intval($_GET['sid']);
		$type = isset($_GET['type'])? intval($_GET['type']) : CommonUtil::TREND_FIELD_PRICE;
		$startDay = intval($_GET['start_day']);
		$endDay = isset($_GET['end_day'])? intval($_GET['end_day']) : intval(date('Ymd'));
		
		$days = $values = $highPoints = array();
		$trendList = array();
		$recordList = StockTrend::model()->findAll(array(
						'condition' => "sid = $sid and type = $type and start_day >= $startDay  and end_day <= $endDay and status = 'Y'",
						'order' => 'start_day asc',
					));
					
		foreach ($recordList as $record)
		{	
			$highBeforeLow = ($record->high_day <= $record->low_day);
			if (!in_array($record->start_day, $days))
			{								
				$days[] = $record->start_day;
				$values[] = $record->start_value;
			}
			
			if ($highBeforeLow)
			{
				if (!in_array($record->high_day, $days))
				{
					$days[] = $record->high_day;
					$values[] = $record->high;
					$highPoints[] = array('day' => $record->high_day, 'value' => $record->high);
				}
				
				if (!in_array($record->low_day, $days))
				{
					$days[] = $record->low_day;
					$values[] = $record->low;
					$highPoints[] = array('day' => $record->low_day, 'value' => $record->low);
				}
			}
			else 
			{			
				if (!in_array($record->low_day, $days))
				{
					$days[] = $record->low_day;
					$values[] = $record->low;
					$highPoints[] = array('day' => $record->low_day, 'value' => $record->low);
				}
				
				if (!in_array($record->high_day, $days))
				{
					$days[] = $record->high_day;
					$values[] = $record->high;
					$highPoints[] = array('day' => $record->high_day, 'value' => $record->high);
				}				
			}
			
			if (!in_array($record->end_day, $days))
			{								
				$days[] = $record->end_day;
				$values[] = $record->end_value;
			}
			
			$directionConfig = CommonUtil::getConfigObject("stock.direction");
			$trendText = (($record->shave && ($record->trend != CommonUtil::DIRECTION_SHAVE))? "震荡" : "") . $directionConfig[$record->trend];
			$trendList[$record->end_day] = array(
										'start_day' => $record->start_day,
										'end_day' => $record->end_day,
										'trend' => $record->trend,
										'shave' => $record->shave,
										'trend_text' => $trendText,
									);
		}
		
		// var_dump($days, $values, $highPoints);		
		$this->render('trend', array(
				'days' => $days,
				'values' => $values,
				'minValue' => floor(min($values)),
				'maxValue' => ceil(max($values)),
				'stockInfo' => StockUtil::getStockInfo($sid),
				'startDay' => $startDay,
				'endDay' => $endDay,
				'trendList' => $trendList,
			));
	}
}
?>
