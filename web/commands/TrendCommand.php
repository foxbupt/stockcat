<?php

/**
 * @desc 股票价格/成交量趋势分析
 * @author fox
 * @date 2014/04/27
 */

Yii::import('application.components.StatLogUtil');
Yii::import('application.components.StockUtil');
Yii::import('application.components.CommonUtil');
Yii::import('application.modules.stock.models.*');

class TrendCommand extends CConsoleCommand
{
    public $trendConfig = array(
            CommonUtil::TREND_FIELD_PRICE => array(
                    // 周期内价格比较相差百分比
                    'vary_portion' => '0.03',
                    // 震荡范围的百分比
                    'shave_portion' => '0.05',
                    // 最小连续天数
                    'min_day' => 3,
                ),

            CommonUtil::TREND_FIELD_VOLUME => array(
                    // 成交量比较相差百分比
                    'vary_portion' => '0.50',
                    // 震荡范围的百分比
                    'shave_portion' => '0.50',
                    // 最小连续天数
                    'min_day' => 3,
                ),
        );

    public function run($args)
    {
        if (count($args) < 2)
        {
            echo "Usage: php -c /etc/php.ini console_entry.php trend <start_day> <end_day> [type] [sid]\n";
            echo "type取值: 1 价格 2 成交量\n";
            exit(1);
        }

        $startDay = intval($args[0]);
        $endDay = intval($args[1]);
        $type = isset($args[2])? intval($args[2]) : CommonUtil::TREND_FIELD_PRICE;
        $sid = isset($args[3])? intval($args[3]) : 0;
        // var_dump($startDay, $endDay, $type, $sid);

        $stockList = array();
        if ($sid > 0)
        {
            $stockList[] = $sid;
        }
        else
        {
            $stockMap = StockUtil::getStockMap();
            $stockList = array_values($stockMap);
        }

        foreach ($stockList as $sid)
        {           
            // 获取最近的一条趋势记录, 从其start_day开始分析
            $latestTrendRecord = StockTrend::model()->find(array(
            			'condition' => "sid = {$sid} and type = {$type} and status = 'Y'",
            			'order' => 'end_day desc',
            			'limit' => 1
            		));
            		
            $latestTrendId = empty($latestTrendRecord)? 0 : $latestTrendRecord->id;
            $newStartDay = empty($latestTrendRecord)? $startDay : max($latestTrendRecord->start_day, $startDay);
            
           	$trendList = $this->analyze($sid, $newStartDay, $endDay, $type, $this->trendConfig[$type]);
            // print_r($trendList);
            if (empty($trendList))
            {
                continue;
            }
            		
            foreach ($trendList as $trendItem)
            {
            	$result = TrendHelper::addTrendRecord($sid, $type, $trendItem, ($newStartDay == $trendItem['start']['day'])? $latestTrendId : 0);
            	echo "op=add_trend result=$result sid=$sid type=$type latest_trend_id=$latestTrendId " . StatLogUtil::array2log($trendItem) . "\n";
            }
        }
    }

    /**
     * @desc 分析股票指定日期内的趋势分析
     * @param sid int
     * @param startDay int 起始日期
     * @param endDay int 结束日期
     * @param type int 分析类型: 价格/成交量
     * @param config array 分析配置
     * @return array('start' => array('day', 'value'), 'end', 'high', 'low', 'trend')
     */
    public function analyze($sid, $startDay, $endDay, $type, $config)
    {
        $stockData = StockUtil::getStockData($sid, $startDay, $endDay);
        if (count($stockData) < 3)
        {
            return false;
        }

        $periods = TrendHelper::partition($startDay, $endDay); 
        // print_r($periods);
        $weekTrends = array();
        $index = 0;

        $fieldName = ($type == CommonUtil::TREND_FIELD_PRICE)? "close_price" : "volume";       
        foreach ($periods as $periodInfo)
        {
            $periodStart = $periodInfo['start'];
            $periodEnd = $periodInfo['end'];
            
           	$periodData = TrendHelper::getFieldRangeData($stockData, $periodStart, $periodEnd, $fieldName, $index);
			$index = $periodData['offset'];
            if (0 == $periodData['count']) // 指定周期内无交易数据, 直接忽略
            {
                continue;
            }
			
            $weekTrends[] = TrendHelper::getPeriodTrend($sid, $periodData, $config);
        }

        // print_r($weekTrends);
        $trendList = TrendHelper::mergeTrend($sid, $type, $weekTrends, $config);
        // print_r($trendList);

        return $trendList;
    }
}

?>
