<?php

Yii::import('application.models.Tag');
class CommonUtil
{
	// 标签分类: 1 行业 2 地域  3 概念
	const TAG_CATEGORY_INDUSTRY = 1;
	const TAG_CATEGORY_LOCATION = 2;
	const TAG_CATEGORY_CONCEPT = 3;
	
	// 评级: 1 中性  2 谨慎/审慎推荐 3 推荐/增持/买入 4 强烈推荐
	const RANK_NEUTRAL = 1;
	const RANK_CARE_RECOMMEND = 2;
	const RANK_RECOMMEND = 3;
	const RANK_STRONG_RECOMMEND = 4;
	
	const CACHE_KEY_COMMON_CONFIG = "config:all";
	const CACHE_KEY_COMMON_TAG_CATEGORY = "tag:category-";
	
	// 全年节假日配置
	static $holidays = array(
				array('start' => 20130919, 'end' => 20130921),
				array('start' => 20131001, 'end' => 20131007),
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
	 * @desc 获取分类下的标签列表
	 *
	 * @param int $category
	 * @return array(tid => name)
	 */
	public static function getTagListByCategory($category)
	{
		$cacheKey = self::CACHE_KEY_COMMON_TAG_CATEGORY . str($category);
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
	 * @return bool
	 */
	public static function isMarketOpen($day)
	{
		// 判断是否为周六或周日
		$dateinfo = getdate(strtotime(str($day)));
		if ((0 == $dateinfo['wday']) || (6 == $dateinfo['wday']))	
		{
			return false;
		}
		
		foreach (self::$holidays as $unit)
		{
			if ((is_array($unit) && ($unit['start'] <= $day) && ($day <= $unit['end'])) || ($unit == $day))
			{
				return false;
			}
		}
		
		return true;
	}
}
	
?>
