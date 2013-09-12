<?php

/**
 * @desc 继承自CActiveRecord, 实现分库
 * @author fox
 * @date 2013/08/15
 */
class ShardAR extends CActiveRecord
{
	/**
	 * @desc 根据key来选择
	 *
	 * @param string/int $key
	 * @param string $managerName 连接管理器名称, 用于支持多个连接管理器
	 * @return ShardAR
	 */
	public function select($key, $managerName = "dbConnectionManager")
	{
		$dbConnection = Yii::app()->getComponent($managerName)->select($key);
		$dbConnection->setActive(true);
		
		self::$db = $dbConnection;
		return $this;
	}
}
?>