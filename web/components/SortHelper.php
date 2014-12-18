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
	 * @param array $data 
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
					$unit[] = self::getFieldValue($valueMap, $name);
				}
				$values[$key] = $unit;
			}
			else 
			{
				$values[$key] = $valueMap[$fields];
			}
		}
		
		uasort($values, $asc? "SortHelper::cmpList" : "SortHelper::rcmpList");
		$result = array();

		foreach (array_keys($values) as $key)
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
            return self::cmpValue($unit1, $unit2);
		}
		
		foreach ($unit1 as $index => $value)
		{
			if ($value == $unit2[$index])
			{
				continue;
			}
			else
			{
				return self::cmpValue($value, $unit2[$index]);
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
	    return self::cmpList($unit2, $unit1);	
	}

    /**
     * @desc 对两个值进行比较: 区分数值和字符串 
     * @param value1
     * @param value2
     * @return int
     */
    public static function cmpValue($value1, $value2)
    {
        if (is_numeric($value1))
        {
			if ($value1 == $value2) 
			{
				return 0;
			}
			
			return $value1 < $value2? -1 : 1;
        }

        return strcmp($value1, $value2);
    }
    
    /**
     * @desc 获取数组中指定字段的值, 支持多级字段
     *
     * @param array $map
     * @param string $field 字段名, 支持a.b.c
     * @return string
     */
    public static function getFieldValue($map, $field)
    {
    	if (isset($map[$field]))
    	{
    		return $map[$field];
    	}
    	else if (strstr($field, ".") !== FALSE)
    	{
    		$parts = explode(".", $field);
    		$value = $map;
    		while ($key = array_shift($parts))
    		{
    			if (is_array($value))
    			{
    				$value = $value[$key];
    			}
   				else 
   				{
   					return "";
   				}
    		}
    		
    		return $value;
    	}
    }
}
?>
