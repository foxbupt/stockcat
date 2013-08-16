<?php

/**
 * @desc 导入每日研报
 *
 */
Yii::import('application.modules.stock.models.*');

class ImportReportCommand extends CConsoleCommand
{
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "Usage: php -c /etc/php.ini console_entry.php importreport filename \n";
			exit(1);
		}
		
		$filename = $args[0];
		$lines = file($filename);
		foreach ($lines as $line)
		{
			$line = trim($line);
			$reportInfo = json_decode($line, true);
			// print_r($reportInfo);
			
			$record = Stock::model()->findByAttributes(array('name' => $reportInfo['name'], 'status' => 'Y'));
			if (empty($record))
			{
				continue;
			}
			
			$sid = $record['id'];
			$result = self::importReport($sid, $reportInfo);
			echo "op=import_report result=" . $result . " sid=" . $sid . " title=" . $reportInfo['title'] . " day=" . $reportInfo['day'] . "\n";
		}
	}
	
	public static function importReport($sid, $reportInfo)
	{
		$count = StockReport::model()->count(array('condition' => "sid = " . $sid . " and day=" . $reportInfo['day'] . " and title='" . $reportInfo['title'] . "' and status = 'Y'"));
		if ($count > 0)	// 报告已存在
		{
			return 2;
		}
		
		$record = new StockReport();
		$record->sid = $sid;
		// 把rank由文字转换为数值评级
		$reportInfo['rank'] = self::str2Numeric($reportInfo['rank']);
		
		foreach ($reportInfo as $key => $value)
		{
			$record->$key = $value;
		}
		$record->status = 'Y';
		$record->create_time = time();
		
		return $record->save();
	}
	
	// 把评级转化为数值
	public static function str2Numeric($rank)
	{
		if (("无" == $rank) || ("中性" == $rank))
		{
			return CommonUtil::RANK_NEUTRAL;
		}
		elseif (("谨慎推荐" == $rank) || ("审慎推荐" == $rank))
		{
			return CommonUtil::RANK_CARE_RECOMMEND;
		}
		elseif (("推荐" == $rank) || ("买入" == $rank) || ("增持" == $rank))
		{
			return CommonUtil::RANK_RECOMMEND;
		}
		elseif ("强烈推荐" == $rank)
		{
			return CommonUtil::RANK_STRONG_RECOMMEND;
		}
		
		return CommonUtil::RANK_RECOMMEND;
	}
}
?>