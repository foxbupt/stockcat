<?php

Yii::import('application.models.Tag');
Yii::import('application.components.*');
Yii::import('application.modules.stock.models.*');

/**
 * @desc 导入股票标签数据
 *
 */
class ImportTagCommand extends CConsoleCommand
{
	public function run($args)
	{
		if (count($args) < 2)
		{
			echo "Usage: php -c /etc/php.ini importtag <category> <filename>\n";
			exit(1);
		}
		
		$category = intval($args[0]);
		$filename = $args[1];
		
		$tagList = array();
		$recordList = Tag::model()->findAllByAttributes(array('category' => $category, 'status' => 'Y'));
		foreach ($recordList as $record)
		{
			$tagList[$record->slug] = $record->id;
		}
		
		$stockMap = StockUtil::getStockMap();
		$tagStockList = array();
		
		$lines = file($filename);
		foreach ($lines as $line)
		{
			$line = trim($line);
			$tagInfo = json_decode($line, true);
			if (!isset($tagList[$tagInfo['slug']]))
			{
				echo "err=invalid_tag slug=" . $tagInfo['slug'] . "\n";
				continue;
			}
			
			$tid = $tagList[$tagInfo['slug']];
			if (!isset($stockMap[$tagInfo['stock_code']]))
			{
				echo "err=non_exist_code code=" . $tagInfo['stock_code'] . " slug=" . $tagInfo['slug'] . "\n";
				continue;
			}
			
			$sid = $stockMap[$tagInfo['stock_code']];
			if (isset($tagStockList[$tid]) && in_array($tagInfo['stock_code'], $tagStockList[$tid]))
			{
				echo "err=duplicate_stock code=" . $tagInfo['stock_code'] . " slug=" . $tagInfo['slug'] . " tid=$tid sid=$sid \n";
				continue;
			}
			
			$record = new StockTag();
			$record->tid = $tid;
			$record->sid = $sid;
			$record->create_time = time();
			$record->status = 'Y';
			
			$result = $record->save();
			echo "op=add_stock_tag result=$result slug=" . $tagInfo['slug'] . " tid=$tid sid=$sid code=" . $tagInfo['stock_code'] . "\n";
			
			if (!isset($tagStockList[$tid]))
			{
				$tagStockList[$tid] = array();
			}
			$tagStockList[$tid][] = $tagInfo['stock_code'];
		}
		
		echo "finish\n";
	}
}
?>
