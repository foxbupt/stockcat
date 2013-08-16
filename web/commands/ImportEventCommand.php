<?php

Yii::import('application.modules.stock.models.*');
Yii::import('application.components.StockUtil');

class ImportEventCommand extends CConsoleCommand
{
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "Usage: php -c /etc/php.ini console_entry.php importevent <file>\n";
			exit(1);
		}
		
		$stockMap = StockUtil::getStockMap();
		
		$lines = file($args[0]);
		foreach ($lines as $line)
		{
			$line = trim($line);
			$eventInfo = json_decode($line, true);
			// print_r($eventInfo);
			$sid = $stockMap[$eventInfo['code']];
			
			$result = self::importEvent($eventInfo, $sid);
			echo "op=import_event result=" . ($result? 1 : 0) . " code=" . $eventInfo['code'] . " sid=" . $sid . " date=" . $eventInfo['event_date'] . "\n";
		}
	}
	
	public static function importEvent($eventInfo, $sid)
	{
		$record = new StockEvent();
		
		$record->sid = $sid;
		$record->event_date = $eventInfo['event_date'];
		$record->title = trim($eventInfo['title']);
		$record->content = isset($eventInfo['content'])? $eventInfo['content'] : "";
		$record->create_time = time();
		$record->status = 'Y';
		
		if (!$record->save())
		{
			var_dump($record->getErrors());
			return false;
		}
		
		return true;
	}
}
?>