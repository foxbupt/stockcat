<?php

/**
 * @desc 对数据列表排序的字段
 * @author fox
 * @date 2014/10/01
 */
class SortHelper
{
	/**
	 * @desc 对数组按照给定的字段列表进行排序
	 *
	 * @param array $data OUT
	 * @param mixed $fields string/array
	 * @param bool $asc
	 * @return array
	 */
	public static function sort($data, $fields, $asc = true)
	{
		$values = array();
		foreach ($data as $key => $valueMap)
		{
			if (is_array($fields))
			{
				$unit = array();
				foreach ($fields as $name)
				{
					$unit[] = $valueMap[$name];
				}
				$values[$key] = $unit;
			}
			else 
			{
				$values[$key] = $valueMap[$fields];
			}
		}
		
		uasort($values, $asc? "cmpList" : "rcmpList");
		$result = array();
		foreach ($values as $key)
		{
			$result[] = $data[$key];
		}
		
		return $result;
	}
	
	/**
	 * @desc 公共的升序排序函数, 多个字段值时, 按照顺序依次比较
	 *
	 * @param array $unit1
	 * @param array $unit2
	 * @return int
	 */
	public static function cmpList($unit1, $unit2)
	{
		if (!is_array($unit1))
		{
			if ($unit1 == $unit2) 
			{
				return 0;
			}
			
			return $unit1 < $unit2? -1 : 1;
		}
		
		foreach ($unit1 as $index => $value)
		{
			if ($value == $unit2[$index])
			{
				continue;
			}
			else
			{
				return ($value < $unit2[$index])? -1 : 1;
			}
		}
		
		return 0;
	}
	
/**
	 * @desc 公共的逆序排序函数
	 *
	 * @param array $unit1
	 * @param array $unit2
	 * @return int
	 */
	public static function rcmpList($unit1, $unit2)
	{
		if (!is_array($unit1))
		{
			if ($unit1 == $unit2) 
			{
				return 0;
			}
			
			return $unit1 < $unit2? 1 : -1;
		}
		
		foreach ($unit1 as $index => $value)
		{
			if ($value == $unit2[$index])
			{
				continue;
			}
			else
			{
				return ($value < $unit2[$index])? 1 : -1;
			}
		}
		
		return 0;
	}
}
?>