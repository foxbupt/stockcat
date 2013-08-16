<?php
/**
 * @desc 导入股票基本数据
 *
 */
Yii::import('application.components.*');
Yii::import('application.models.*');
Yii::import('application.modules.news.models.*');
Yii::import('application.modules.stock.models.*');

class ImportStockCommand extends CConsoleCommand
{
	public $tagList = array();
	
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "Usage: " . $args[0] . " <filename>\n";
			exit(1);
		}
		
		$lines = file($args[0]);
		
		foreach ($lines as $line)
		{
			$stockInfo = json_decode(trim($line), true);
			// print_r($stockInfo);
			
			$result = $this->importStock($stockInfo);
			echo "op=import_stock code=" . $stockInfo['code'] . " name=" . $stockInfo['name'] . " result=" . ($result? 1 : 0) . "\n";
		}
		
		echo "finish\n";
	}
	
	public function importStock($stockInfo)
	{
		$stockRecord = Stock::model()->findByAttributes(array('code' => $stockInfo['code'], 'status' => 'Y'));
		if (empty($stockRecord))
		{
			echo "op=stock_record_nonexist code=" . $stockInfo['code'] . "\n";
			return false;
		}
		
		// 更新股票信息
		$location = trim(trim($stockInfo['location'], "省"), "市");	
		$stockInfo['capital'] = $stockInfo['captial'];
		$stockInfo['out_capital'] = $stockInfo['out_captial'];
		unset($stockInfo['location'], $stockInfo['code'], $stockInfo['captial'], $stockInfo['captial']);
		
		$result = $stockRecord->updateByPk($stockRecord->id, $stockInfo);
		echo "op=update_stock result=" . $result . " stock_id=" . $stockRecord->id . " code=" . $stockRecord['code'] . " name=" . $stockInfo['name'] . "\n";
		
		$tid = 0;	
		if (isset($this->tagList[$location]))
		{
			$tid = $this->tagList[$location];
		}
		else
		{
			Yii::import('common.components.Pinyinv2');
			
			$slug = Pinyinv2::getPinyin(mb_convert_encoding($location, 'gbk', 'utf-8'));
			$tid = CommonUtil::addTag($location, $slug, CommonUtil::TAG_CATEGORY_LOCATION);
			$this->tagList[$location] = $tid;
			echo "op=add_tag location=" . $location . " slug=" .  $slug . " tid=" . $tid . "\n";
		}
		
		StockUtil::addStockTag($stockRecord->id, $tid);		
		return true;
	}
}
?>