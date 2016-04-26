<?php

/**
 * @desc 蜡烛形态解析
 * @author fox
 * @date 2016/04/25
 *
 */
class CandleParser
{
	const CANDLE_NONE = 0;
	
	// 反转线
	const CANDLE_FLIP = 1;
	
	public static $candleNameMap = array(
				self::CANDLE_FLIP => '反转线',
			);

	public static function getCandleName($candle)
	{
		return isset(self::$candleNameMap[$candle])? self::$candleNameMap[$candle] : "";	
	}
	
	/**
	 * @desc 解析单天蜡烛形态
	 *
	 * @param $singleData array('day', 'open_price', 'high_price', 'low_price', 'close_price', 'last_close_price')
	 * return array('candle', 'solid', 'up_ratio', 'bottom_ratio')
	 */
	public static function parseSingle($singleData)
	{
		// 计算实体长度、上影线和下影线长度
        $openPrice = $singleData['open_price'];
        $closePrice = $singleData['close_price'];

		$solidLength = abs($closePrice - $openPrice);
		$upLength = abs($singleData['high_price'] - max($openPrice, $closePrice));
		$bottomLength = abs(min($openPrice, $closePrice) - $singleData['low_price']);

		// 最大长度必须占据收盘价的2%以上, 避免长度过小误判
		$maxLength = max(max($solidLength, $upLength), $bottomLength);
		// var_dump($solidLength, $upLength, $bottomLength, $closePrice);
		if (($maxLength/$closePrice * 100 <= 2) || ($solidLength/$closePrice * 100 <= 1))
		{
			return array('type' => self::CANDLE_NONE);
		}
		
        // 返回上下影线与实体长度的比例, 用于判断强弱
        $upRatio = $upLength / $solidLength;
        $bottomRatio = $bottomLength / $solidLength;
        $data = array(
        			'candle' => self::CANDLE_NONE, 
        			'solid' => $solidLength, 
        			'up_ratio' => $upRatio, 
        			'bottom_ratio' => $bottomRatio
        		);
        
        // 下影线超过2倍实体长度, 为反转线, 下影线/实体比例为强度
        if ($bottomRatio >= 2.0) 
		{
			$data['strength'] = $bottomRatio;
			$data['candle'] = self::CANDLE_FLIP;
		}
		
		return $data;
	}
	
	/**
	 * @desc 解析连续多日蜡烛形态
	 *
	 * @param $multiData array(array(), ...)
	 * @return 
	 */
	public static function parseMulti($multiData)
	{
		
	}
}
?>
