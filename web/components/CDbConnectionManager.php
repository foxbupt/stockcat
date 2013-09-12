<?php

/**
 * @desc 多个数据库的连接管理, 配置如下:
 * 		'dbConnectionManager' => array(
 * 			'class' => 'application.components.CDbConnectionManager',
 *   		'type' => 'mod/range/discrete',	// 指定分库方式: 取模/范围/离散
 * 			'factor' => 3,	// 指定取模的因子
 * 			// 分库详细规则: dbname => int/string(a-b)/array(a,b,c)
 *   		'shard' => array(
 * 				'db0' => 0, 	// dbname => remainder
 * 				'db1' => 1,
 * 				'db2' => 2,
 * 			),
 * 		),
 * 		a) db1 => array('type' => '')
 * @author fox
 * @date 2013/08/15
 *
 */
class CDbConnectionManager extends CApplicationComponent
{
	public $type = "mod";
	public $factor = 10;
	
	// 分库的规则 
	public $shard = array();
	
	// 转化分析后的规则
	public $rules = array();
	
	public function init()
	{
		parent::init();
		$this->processRules();
	}

	protected function processRules()
	{
		if (("mod" == $this->type) || ("discrete" == $this->type))
		{
			$this->rules = $this->shard();
			return;
		}
		
		foreach ($this->shard as $dbname => $rangestr)
		{
			$rangeList = explode("-", trim($rangestr));
			if (2 == count($rangeList))
			{
				$this->rules[$dbname][] = array('start' => int($rangeList[0]), 'end' => int($rangeList[1]));
			}
			else 
			{
				$this->rules[$dbname][] = array('start' => int($rangeList[0]), 'end' => -1);
			}
		}
		
		return;
	}
	
	/**
	 * @desc 根据分库的字段值选择对应的数据库连接
	 *
	 * @param int $key
	 * @return CDbConnection
	 */
	public function select($key)
	{
		$dbname = "";
		foreach ($this->rules as $name => $rule)
		{
			if ("range" == $this->type)
			{
				if (($rule['start'] >= $key) && (($rule['end'] == -1) || ($key < $rule['end'])))
				{
					$dbname = $name;
					break;
				}
			}
			else 
			{
				if ((is_array($rule) && in_array($key, $rule)) || ($key == $rule))
				{
					$dbname = $name;
					break;
				}
			}
		}
		
		if (empty($dbname))
		{
			return null;
		}
		
		return $this->createDbConnection($dbname);
	}
	
	/**
	 * @desc 根据db实例名称创建对应的DB连接
	 *
	 * @param string $dbname
	 * @return CDbConnection
	 */
	public function createDbConnection($dbname)
	{
		return Yii::app()->getComponent($dbname);
	}
}
?>