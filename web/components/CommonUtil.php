<?php

Yii::import('application.models.Tag');
class CommonUtil
{	
	const CACHE_KEY_COMMON_CONFIG = "config:all";
	const CACHE_KEY_COMMON_TAG_CATEGORY = "tag:category-";
	
	// 标签分类: 1 行业 2 地域  3 概念
	const TAG_CATEGORY_INDUSTRY = 1;
	const TAG_CATEGORY_LOCATION = 2;
	const TAG_CATEGORY_CONCEPT = 3;
	
	// 评级: 1 中性  2 谨慎/审慎推荐 3 推荐/增持/买入 4 强烈推荐
	const RANK_NEUTRAL = 1;
	const RANK_CARE_RECOMMEND = 2;
	const RANK_RECOMMEND = 3;
	const RANK_STRONG_RECOMMEND = 4;
	
	// 趋势/波段方向: 1 下跌 2 震荡 3 上涨
	const DIRECTION_DOWN = 1;
	const DIRECTION_SHAVE = 2;
	const DIRECTION_UP = 3;

    // 趋势分析字段: 1 价格 2 成交量
    const TREND_FIELD_PRICE = 1;
    const TREND_FIELD_VOLUME = 2;
    
    // 趋势临近点: 1 靠近最低点 2 靠近最高点
    const TREND_NEAR_LOW = 1;
    const TREND_NEAR_HIGH = 2;
    
    // 操作字段: 1 卖出  2 待定 3 买入
    const OP_SELL = 1;
    const OP_PEND = 2;
    const OP_BUY = 3;
    
    // 格式化类型: 1 价格, 2 百分比, 3 千分位数值
    const FORMAT_TYPE_PRICE = 1;
    const FORMAT_TYPE_PORTION = 2;
    const FORMAT_TYPE_NUMBER = 3;
	
    // 股票池来源(source): 1 连续上涨, 2 价格突破, 4 趋势突破阻力位, 8 蜡烛形态
    const SOURCE_BITMAP = 4;
    const SOURCE_CONT = 1;
    const SOURCE_PRICE_THRESHOLD = 2;
    const SOURCE_UP_RESIST = 4;
    const SOURCE_CANDLE = 8;
    static $sourceMaps = array(
    		self::SOURCE_CONT => '连续上涨',
    		self::SOURCE_PRICE_THRESHOLD => '价格突破',
    		self::SOURCE_UP_RESIST => '趋势突破',
    		self::SOURCE_CANDLE => '蜡烛形态',
    	);
    
    // 股票所属国家(location): 0 全部 1 中国 2 香港(hk) 3 美国(us)
    const LOCATION_ALL = 0;
    const LOCATION_CHINA = 1;
    const LOCATION_HK = 2;
    const LOCATION_US = 3;
	
    // 交易所(ecode): 1 上海(sh) 2 深圳(sz) 3 香港(hk) 4 纳斯达克(nasdaq) 5 纽交所(nyse)
    const ECODE_SH = 1;
    const ECODE_SZ = 2;
    const ECODE_HK = 3;
    const ECODE_NASDAQ = 4;
    const ECODE_NYSE = 5;

    // 交易所映射: ecode => ecode_name
    static $ecodeMaps = array(
    		self::ECODE_SH => "sh",
    		self::ECODE_SZ => "sz",
    		self::ECODE_HK => "hk",
    		self::ECODE_NASDAQ => "NASDAQ",
    		self::ECODE_NYSE => "NYSE",
    	);
    	
	// 全年节假日配置
	static $holidays = array( 
			self::LOCATION_CHINA =>	array(
                array('start' => 20150101, 'end' => 20150102),
                array('start' => 20150218, 'end' => 20150224),
                20150406,
                20150501,
                20150622,
				array('start' => 20151001, 'end' => 20151007),
				array('start' => 20160207, 'end' => 20160213),
				20160404,
				20160502,				
				array('start' => 20160609, 'end' => 20160610),
				array('start' => 20160915, 'end' => 20160916),
				array('start' => 20161001, 'end' => 20161007),
			),
			self::LOCATION_US => array(
				20150101,
				20150216,
				20150403,
				20150525,
				20150704,
				20150901,
				20151126,
				20151225,
				20160101,
				20160119,
				20160216,
				20160403,
				20160525,
				20160703,
				20160907,
				20161126,
				20161225
			));

	// 市场交易状态: 0 未开盘 1 开盘交易中  2 午间休盘 3 闭市
	const MSTATE_NOT_OPEN = 0;
	const MSTATE_OPENED = 1;
	const MSTATE_PAUSED = 2;
	const MSTATE_CLOSED = 3;
	
	static $mstateMaps = array(
			self::MSTATE_NOT_OPEN => '未开盘',
			self::MSTATE_OPENED => '交易中',
			self::MSTATE_PAUSED => '午间休盘',
			self::MSTATE_CLOSED => '闭市',
		);
	
	// 市场的开市时间段
	static $timestones = array(
			self::LOCATION_CHINA =>	
                array('start' => 92500, 'end' => 150000),
		);
					
	/**
	 * @desc 添加标签, 存在则返回已有标签id
	 *
	 * @param string $name
	 * @param string $slug
	 * @param int $category
	 * @return int TagID
	 */
	public static function addTag($name, $slug, $category = 1)
	{
		$record = Tag::model()->findByAttributes(array('slug' => $slug, 'status' => 'Y'));
		if ($record)
		{
			return $record->id;
		}
		
		$record = new Tag();
		$record->name = $name;
		$record->slug = $slug;
		$record->category = $category;
		$record->status = 'Y';
		
		return $record->save()? $record->getPrimaryKey() : 0;
	}
	
	/**
	 * @desc 获取指定key的配置项值
	 *
	 * @param string $key
	 * @return mixed array/string
	 */
	public static function getConfig($key = "")
	{
		static $configInfo = array();
		if (!empty($configInfo))
		{
			return empty($key)? $configInfo : $configInfo[$key];
		}
		
		$cacheKey = self::CACHE_KEY_COMMON_CONFIG;
		$configInfo = Yii::app()->redis->getInstance()->hGetAll($cacheKey);
		
		if (empty($configInfo))
		{
			$recordList = Config::model()->findAllByAttributes(array('status' => 'Y'));
			foreach ($recordList as $record)
			{
				$configInfo[$record->key] = $record->value;
			}
			
			Yii::app()->redis->getInstance()->hMSet($cacheKey, $configInfo);
		}
		
		return empty($key)? $configInfo : $configInfo[$key];
	}
	
	/**
	 * @desc 获取指定配置对象的值
	 *
	 * @param string $key
	 * @return array
	 */
	public static function getConfigObject($key)
	{
		$cacheInfo = self::getConfig($key);
		return empty($cacheInfo)? array() : json_decode($cacheInfo, true);	
	}
	
	/**
	 * @desc 获取分类下的标签列表
	 *
	 * @param int $category
	 * @return array(tid => name)
	 */
	public static function getTagListByCategory($category)
	{
		$cacheKey = self::CACHE_KEY_COMMON_TAG_CATEGORY . $category;
		$cacheValue = Yii::app()->redis->get($cacheKey);
		
		if (!$cacheValue)
		{
			$list = array();
			
			$recordList = Tag::model()->findAllByAttributes(array('category' => $category, 'status' => 'Y'));
			foreach ($recordList as $record)
			{
				$list[$record->id] = $record->name;
			}
			
			Yii::app()->redis->set($cacheKey, json_encode($list), 86400);
			return $list;
		}
		
		return json_decode($cacheValue, true);
	}
	
	/**
	 * @desc 判断指定日期是否开市
	 *
	 * @param int $day
	 * @param int $location
	 * @return bool
	 */
	public static function isMarketOpen($day, $location = self::LOCATION_CHINA)
	{
		// 判断是否为周六或周日
		$dateinfo = getdate(strtotime($day));
		if ((0 == $dateinfo['wday']) || (6 == $dateinfo['wday']))	
		{
			return false;
		}
		
		$locationHolidays = self::$holidays[$location];
		foreach ($locationHolidays as $unit)
		{
			if ((is_array($unit) && ($unit['start'] <= $day) && ($day <= $unit['end'])) || ($unit == $day))
			{
				return false;
			}
		}
		
		return true;
	}

    /**
     * @desc: 获取当前日期最近的第几个交易日
     * @param: int $day
     * @param: int $offset
     * @param: int $location
     * @return int
     */
    public static function getPastOpenDay($day, $offset, $location = CommonUtil::LOCATION_CHINA)
    {
        $step = 1;
        // 含当天
        $openCount = 0;
        $timestamp = strtotime($day);    
    
        while (true)
        {
            $lastTimestamp = strtotime("-${step} days", $timestamp); 
            $lastDay = date('Ymd', $lastTimestamp);
            $step += 1;

            if (self::isMarketOpen($lastDay, $location))
            {
                $openCount += 1;
                if ($openCount == $offset)
                {
                    return $lastDay; 
                }
            }
        }

        return 0;
    }
    
    /**
     * @desc 获取指定日期范围[startDay, endDay] 内的交易天数目
     * @param int $startDay
     * @param int $endDay
     * @param int $location
     * @return int
     */
    public static function getOpenDayCount($startDay, $endDay, $location = CommonUtil::LOCATION_CHINA)
    {
        $count = 0;
        $day = $startDay;

        while ($day <= $endDay)
        {
            if (self::isMarketOpen($day, $location))
            {
                $count += 1;
            }

            $day = date("Ymd", strtotime("+1 day", strtotime($day)));
        }

        return $count;
    }

    /**
     * @desc 检查 当前值与上期值是否超出指定比例
     *
     * @param double $cur
     * @param double $last
     * @param double $portion
     * @return bool
     */
    public static function checkExceed($cur, $last, $portion)
    {
        return (($cur - $last) / $last) >= $portion;
    }
    
    /**
     * @desc 计算两个值的差异比例
     *
     * @param doubel $cur
     * @param double $last
     * @return double
     */
    public static function calcPortion($cur, $last)
    {
    	return ($last != 0.0)? ($cur - $last) / $last : 0.0;	
    }
    
    /**
     * @desc 从后往前搜索值在数组中出现的顺序
     * @param value double
     * @param list array
     * @return mixed index/false
     */
    public static function reverseSearch($value, $list)
    {
        $count = count($list);

        for ($index = $count-1; $index >= 0; $index--)
        {
            if ($list[$index] == $value)
            {
                return $index;
            }
        }

        return false;
    }   

    /**
     * @desc 获取最近开盘的日期
     * TODO: 根据当前时间的市场交易状态(是否闭市), 灵活判断返回日期, 需要返回2个日期:
     * data_day: 离线数据返回昨天, 实时数据返回当天
     * hq_day: 行情数据返回当天
     * 
     * @param int $day
     * @param int $location
     * @return int
     */
    public static function getParamDay($day, $location = CommonUtil::LOCATION_CHINA)
    {
        return CommonUtil::isMarketOpen($day, $location)? $day : CommonUtil::getPastOpenDay($day, 1, $location);
    }
    
    /**
     * @desc 获取美国股市交易日
     *		夏令时是9:30开市, 冬令时是10:30开市, 我们早12个小时
     * @return int
     */
    public static function getUSDay()
    {
    	$hour = date("H");
    	$min = date("i");

    	$day = (($hour < 21) || (($hour == 21) && ($min < 30)))?
    		date('Ymd', strtotime("-1 days", time())) : date('Ymd');
    	return intval($day);
    }
    
    /**
     * @desc 获取当前时刻的市场交易状态(暂时不支持中间休息的状态)
     * @param int $location
     * @param int $timevalue 格式为HHMMSS, 缺省null表示采用当前时间
     * @return int self::MSTATE_xxx
     */
    public static function getMarketState($location = self::LOCATION_CHINA, $timevalue = null)
    {
        $timenumber = empty($timevalue)? intval(date('His', time())) : intval($timevalue);
		if (!isset(self::$timestones[$location])) 
		{
			return self::MSTATE_NOT_OPEN;
		}
		
        $stone = self::$timestones[$location];
		// var_dump($timenumber, $stone);
        if ($timenumber < $stone['start'])
        {
        	return self::MSTATE_NOT_OPEN;
        }
        else if ($timenumber > $stone['end'])
        {
        	return self::MSTATE_CLOSED;
        }
        
        return self::MSTATE_OPENED; 
    }

    /**
     * @desc 格式化价格/涨幅显示
     *
     * @param float $number
     * @param int $type 参见FORMAT_TYPE_xxx
     * @return string
     */
    public static function formatNumber($number, $type = CommonUtil::FORMAT_TYPE_PRICE)
    {
    	switch ($type)
    	{
            case CommonUtil::FORMAT_TYPE_PRICE:
    		default:
    			return sprintf("%.2f", $number);
            case CommonUtil::FORMAT_TYPE_PORTION:
    			return sprintf("%.2f%%", $number);
            case CommonUtil::FORMAT_TYPE_NUMBER:
   			{
   				return number_format($number);
   			}
    	}	
    }
    
    /**
     * @desc 获取qq的行情页面地址
     * @param code string
     * @param $ecode int
     * @param location int
     * @return string
     */ 
    public static function getHQUrl($code, $ecode = CommonUtil::ECODE_SH, $location = CommonUtil::LOCATION_CHINA)
    {
    	switch ($location)
    	{
    		case CommonUtil::LOCATION_CHINA:
    			return "http://gu.qq.com/" . self::$ecodeMaps[$ecode] . $code;
    			// return "http://stockhtm.finance.qq.com/sstock/ggcx/" . $code . ".shtml";	
    		case CommonUtil::LOCATION_HK:
				return "http://gu.qq.com/hk" . $code;
    		case CommonUtil::LOCATION_US:
   			{	
   				$postfix = (CommonUtil::ECODE_NASDAQ == $ecode)? "OQ" : "N";
   				return "http://gu.qq.com/us" . $code . "." . $postfix;
   				// return "http://stockhtm.finance.qq.com/astock/ggcx/" . $code . "." . $postfix . ".htm";
   			}
    	}
    }
    
    /**
     * @desc 返回展示的code
     *
     * @param string $code
     * @param int $ecode
     * @return string
     */
    public static function getShowCode($code, $ecode)
    {
    	if ((CommonUtil::ECODE_SH == $ecode) || (CommonUtil::ECODE_SZ == $ecode)
    		|| (CommonUtil::ECODE_HK == $ecode))	
    	{
    		return self::$ecodeMaps[$ecode] . $code;	
    	}
    	else 
    	{
    		return self::$ecodeMaps[$ecode] . "." . $code;
    	}
    }
    
    /**
     * @desc 返回展示的source字符串
     *
     * @param int $source
     * @param string $delim 分割符
     * @return string
     */
    public static function formatSource($source, $delim = "|")
    {
		$values = array();
		foreach (self::$sourceMaps as $key => $label)
		{
			if ($source & $key)
			{
				$values[] = $label;
			}	
		}
		
		return implode($delim, $values);
    }
    
    /**
	 * @desc 获取下一个有效交易日
	 * @param int $day
	 * @param int $location
	 * @return int
	 */
    public static function nextDay($day, $location = CommonUtil::LOCATION_CHINA)
    {
    	$nextDay = $day;
    	$timestamp = strtotime($day);    
    	$offset = 1;
    	
        while (true)
        {
            $nextTimestamp = strtotime("${offset} days", $timestamp); 
            $nextDay = date('Ymd', $nextTimestamp);
            $offset += 1;

            if (self::isMarketOpen($nextDay, $location))
            {
                return $nextDay;
            }
        }
        
        return $nextDay;
    }
}
	
?>
