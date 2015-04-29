<?php

/**
 * @desc 短线操作实时分析
 * @author fox
 * @date 2015/04/28
 */

Yii::import('application.components.StatLogUtil');
Yii::import('application.components.StockUtil');
Yii::import('application.components.CommonUtil');
Yii::import('application.modules.stock.models.*');

class ShortTermCommand extends CConsoleCommand
{	
	public $inited = false;
	
	public function run($args)
	{
		$location = isset($args[0])? intval($args[0]) : CommonUtil::LOCATION_CHINA;
		$day = isset($args[1])? intval($args[1]) : intval(date('Ymd'));
		if (!CommonUtil::isMarketOpen($day, $location))
		{
			echo "err=market_not_day location=${location} day=${day}";
			return;
		}
		
		$lastOpenDay = CommonUtil::getPastOpenDay($day, 1, $location);
		while (true)
		{
			$marketState = CommonUtil::getMarketState($location);
			if ((CommonUtil::MSTATE_NOT_OPEN == $marketState) || (CommonUtil::MSTATE_PAUSED == $marketState))
			{
				if ((CommonUtil::MSTATE_NOT_OPEN == $marketState) && !$this->inited)
				{
					$this->initialize($location, $day, $lastOpenDay);	
				}
				sleep(60);
			}
			else if (CommonUtil::MSTATE_CLOSED == $marketState)
			{
				return;
			}

			
		}
	}
	
	/**
	 * @desc 初始化加载短线股票池
	 *
	 * @param int $location
	 * @param int $day
	 * @param int $lastOpenDay
	 * @return bool
	 */
	public function initialize($location, $day, $lastOpenDay)
	{
		
	}
	
	public function core($location, $day)
	{
		
	}
}
?>