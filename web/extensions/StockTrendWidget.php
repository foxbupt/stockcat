<?php

/**
 * @desc 股票趋势图组件
 * @date 2014/09/13
 */
class StockTrendWidget extends CWidget 
{
	public $sid;
	public $trendType = CommonUtil::TREND_FIELD_PRICE;
	public $startDay;
	public $endDay;
	// 趋势图高度, 缺省为600
	public $height = 600;
	
	public function run()
	{
		$data = $this->getTrendData();
		$stockInfo = StockUtil::getStockInfo($this->sid);
		
		$this->render('trend', array(
				'days' => $data['days'],
				'values' => $data['values'],
				'minValue' => floor(min($data['values'])),
				'maxValue' => ceil(max($data['values'])),
				'stockInfo' => $stockInfo,
				'startDay' => $this->startDay,
				'endDay' => $this->endDay,
				'trendList' => $data['trendList'],
				'height' => $this->height,
		));
	}
	
	public function getTrendData()
	{
		$days = $values = $highPoints = array();
		$trendList = array();
		$recordList = StockTrend::model()->findAll(array(
						'condition' => "sid = :sid and type = :type and start_day >= :start_day and end_day <= :end_day and status = 'Y'",
						'params' => array(
							'sid' => $this->sid,
							'type' => $this->trendType,
							'start_day' => $this->startDay,
							'end_day' => $this->endDay
						),
						'order' => 'start_day asc',
					));
					
		foreach ($recordList as $record)
		{	
			$highBeforeLow = ($record->high_day <= $record->low_day);
			if (!in_array($record->start_day, $days))
			{								
				$days[] = $record->start_day;
				$values[] = (float)($record->start_value);
			}
			
			if ($highBeforeLow)
			{
				if (!in_array($record->high_day, $days))
				{
					$days[] = $record->high_day;
					$values[] = (float)($record->high);
					$highPoints[] = array('day' => $record->high_day, 'value' => $record->high);
				}
				
				if (!in_array($record->low_day, $days))
				{
					$days[] = $record->low_day;
					$values[] = (float)($record->low);
					$highPoints[] = array('day' => $record->low_day, 'value' => $record->low);
				}
			}
			else 
			{			
				if (!in_array($record->low_day, $days))
				{
					$days[] = $record->low_day;
					$values[] = (float)($record->low);
					$highPoints[] = array('day' => $record->low_day, 'value' => $record->low);
				}
				
				if (!in_array($record->high_day, $days))
				{
					$days[] = $record->high_day;
					$values[] = (float)($record->high);
					$highPoints[] = array('day' => $record->high_day, 'value' => $record->high);
				}				
			}
			
			if (!in_array($record->end_day, $days))
			{								
				$days[] = $record->end_day;
				$values[] = (float)($record->end_value);
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
		
		return array(
				'days' => $days,
				'values' => $values,
				'trendList' => $trendList,
			);
	}
}
?>