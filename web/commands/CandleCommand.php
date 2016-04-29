<?php

Yii::import('application.components.StatLogUtil');
Yii::import('application.components.StockUtil');
Yii::import('application.components.CommonUtil');
Yii::import('application.components.CandleParser');
Yii::import('application.modules.stock.models.*');

class CandleCommand extends CConsoleCommand
{
    public function run($args)
    {
        if (count($args) < 2)
        {
            echo "Usage: php -c /etc/php.ini console_entry.php candle <location> [day] [sid] \n";
            exit(1);
        }

        $location = intval($args[0]);
        $day = (count($args) >= 2)? intval($args[1]) : intval(date("Ymd"));
        
        $stockList = array();
        if (count($args) >= 3)
        {
        	$stockList[] = intval($args[2]);
        }
        else 
        {
    		$stockMap = StockUtil::getStockMap($location);
    		$stockList = array_values($stockMap);
        }
        // var_dump($location, $day, $stockList);
                
        $dailyDataList = StockData::model()->findAll(array(
                        'condition' => "day = $day and status = 'Y'",                       
                    ));
        
        foreach ($dailyDataList as $record)
        {
            $stockData = $record->getAttributes();
           	$sid = $stockData['sid'];   
           	if (!in_array($sid, $stockList))
           	{
           		continue;
            } 

            // var_dump($sid, $stockData);
           	$candleResult = CandleParser::parseSingle($stockData);
        	if (($candleResult['candle'] != CandleParser::CANDLE_NONE) && ($stockData['close_price'] >= 3.00))
            {
                $stockInfo = StockUtil::getStockInfo($sid);
            	$result = self::addStockCandle($day, $stockInfo, $candleResult);
                echo "op=stock_candle day=$day sid=$sid result=${result} code=" . $stockInfo['code'] . " name=" . $stockInfo['name'] . " " . StatLogUtil::array2log($candleResult) . "\n";
				
                // 添加到股票池
                $poolResult = StockUtil::addStockPool($sid, $day, CommonUtil::SOURCE_CANDLE, array());
                // $poolResult = 1;
                echo "op=add_pool_succ result=" . ($poolResult? 1:0) . " day=$day sid=$sid name=" . $stockInfo['name'] . "\n";
            }
        }
    }
    
    public static function addStockCandle($day, $stockInfo, $candleData)
    {
        $record = new StockCandle();

        $record->sid = $stockInfo['id'];
        $record->day = $day;
        $record->candle_type = $candleData['candle'];
        $record->strength = round($candleData['strength'], 2);
        $record->create_time = time();
        $record->status = 'Y';

        $result = $record->save();
        // var_dump($record->getErrors());
        return $result? 1 : 0;
    }
}
?>        
