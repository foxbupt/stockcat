<?php

/**
 * @desc 导入股票历史数据
 */

Yii::import('application.modules.stock.models.*');

class ImportStockHistCommand extends CConsoleCommand
{
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "Usage: php -c /etc/php.ini importstockhist <filename>\n";
			exit(1);
		}
		
		$filelist = $args;
		while (count($filelist) > 0)
		{
			$filename = array_shift($filelist);
			$lines = file($filename);
			
			foreach ($lines as $line)
			{
				$data = json_decode($line, true);	
				// print_r($data);			
				
				$result = self::addRecord($data);
				echo "op=add_stock_hist_data result=" . $result . " sid=" . $data['sid'] . ' code=' . $data['code'] . ' day=' . $data['day'] . "\n";
			}	

			echo "op=import_file_succ filename=" . $filename . "\n";
		}
		
		echo "finish\n";
	}
	
	// 添加一行历史数据
	public static function addRecord($data)
	{
		$record = new StockData();
		unset($data['code']);
		
		foreach ($data as $key => $value)
		{
			$record->$key = $value;
		}
		
		$record->status = 'Y';
		$record->create_time = time();
		
		// return $record->save()? 1 : 0;
		if ($record->save())
		{
			return 1;
		}
		else
		{
			var_dump($record->getErrors());
			return 0;
		}
	}
}

?>