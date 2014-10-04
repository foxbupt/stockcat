<?php

/**
 * @desc 导入美股的历史总览数据
 */

Yii::import('application.modules.stock.models.*');

class ImportUSHistCommand extends CConsoleCommand
{
	public function run($args)
	{
		if (count($args) < 2)
		{
			echo "Usage: php -c /etc/php.ini importushist <sid> <filename>\n";
			exit(1);
		}
		
		$sid = intval($args[0]);
		$filename = $args[1];
		$lastClosePrice = 0.0;
		
		$lines = file($filename);	
		// 按照时间顺序依次导入, 便于获取昨日收盘价格
		while (!empty($lines))	
		{
			$line = array_pop($lines);			
			$line = trim($line);
			$fields = explode(",", $line);
			if ($fields[0] == "Date")
			{
				continue;
			}
			
			$data = array('sid' => $sid, 'amount' => 0, 'exchange_portion' => 0.0);	
			$data['day'] = intval(str_replace("-", "", $fields[0]));
			$data['open_price'] = floatval($fields[1]);
			$data['high_price'] = floatval($fields[2]);
			$data['low_price'] = floatval($fields[3]);
			$data['close_price'] = floatval($fields[4]);
			$data['volume'] = floatval($fields[5]);
			$data['adj_close_price'] = floatval($fields[6]);
			$data['last_close_price'] = $lastClosePrice;
			$lastClosePrice = $data['close_price'];
			
			$data['vary_price'] = (0 == $lastClosePrice)? 0.0 : ($data['close_price'] - $lastClosePrice);
			$data['vary_portion'] = (0 == $lastClosePrice)? 0.0 : $data['vary_price']/$lastClosePrice * 100;
			$data['swing'] = (0 == $lastClosePrice)? 0.0 : (($data['high_price'] - $data['low_price']) / $lastClosePrice * 100);
			
			$result = self::addRecord($data);
			echo "op=import_us_hist_data result=" . $result . " sid=" . $data['sid'] . ' day=' . $data['day'] . "\n";
		}	

		echo "op=import_file_finish sid=" . $sid . " filename=" . $filename . "\n";
	}
	
	// 添加一行历史数据
	public static function addRecord($data)
	{
		$record = new StockData();
		unset($data['adj_close_price']);
		
        $data['vary_price'] = sprintf("%.2f", (float)$data['vary_price']);
        $data['vary_portion'] = sprintf("%.2f", (float)$data['vary_portion']);
        $data['swing'] = sprintf("%.2f", (float)$data['swing']);

		foreach ($data as $key => $value)
		{
			$record->$key = $value;
		}
		
		$record->status = 'Y';
		$record->create_time = time();
		
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
