<?php

/**
 * @desc 股票趋势图组件
 * @date 2014/09/13
 */
class StockTrendWidget
{
	public $sid;
	public $trendType;
	public $startDate;
	public $endDate;
	
	public function run()
	{
		$this->render('trend');
	}
}
?>