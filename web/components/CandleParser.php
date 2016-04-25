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
	
	/**
	 * @desc 解析单天蜡烛形态
	 *
	 * @param $singleData array('day', 'open_price', 'high_price', 'low_price', 'close_price', 'last_close_price')
	 * return int
	 */
	public static function parseSingle($singleData)
	{
		// 计算实体长度、上影线和下影线长度
		$solidLength = abs($singleData['close_price'] - $singleData['open_price']);
		$upLength = abs($singleData['high_price'] - $singleData['close_price']);
		$bottomLength = abs($singleData['open_price'] - $singleData['low_price']);

		// 最大长度必须占据收盘价的2%以上, 避免长度过小误判
		$maxLength = max(max($solidLength, $upLength), $bottomLength);
		if ($maxLength/$singleData['close_price'] * 100 <= 2)
		{
			return self::CANDLE_NONE;
		}
		
		var_dump($solidLength, $upLength, $bottomLength);
		if ($bottomLength >= 2 * $solidLength) 
		{
			return self::CANDLE_FLIP;
		}
		
		return self::CANDLE_NONE;
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