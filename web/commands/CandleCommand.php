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
            echo "Usage: php -c /etc/php.ini console_entry.php candle <location> <day> [sid] \n";
            exit(1);
        }

        $location = intval($args[0]);
        $day = intval($args[1]);
        
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
           	$candleType = CandleParser::parseSingle($stockData);
        	if ($candleType != CandleParser::CANDLE_NONE)
            {
                // self::addStockCont($day, $stockInfo, $result);
                $stockInfo = StockUtil::getStockInfo($sid);
                echo "op=stock_candle day=$day sid=$sid code=" . $stockInfo['code'] . " name=" . $stockInfo['name'] . " candle=${candleType}\n";
				
                // 添加到股票池
                // $poolResult = StockUtil::addStockPool($sid, $day, CommonUtil::SOURCE_CANDLE, array());
                $poolResult = 1;
                echo "op=add_pool_succ result=" . ($poolResult? 1:0) . " day=$day sid=$sid name=" . $stockInfo['name'] . "\n";
            }
        }
    }
}
?>        
