<?php

/**
 * @desc 策略分析器相关接口
 * @author fox
 * @date 2013/10/09
 *
 */
Yii::import('application.modules.stock.models.*');

class PolicyUtil
{
	/* 表达式操作符(Expression Operator) */
	const EOP_EQ = 1;
	const EOP_GE = 2;
	const EOP_GT = 3;
	const EOP_LT = 4;
	const EOP_LE = 5;
	const EOP_NE = 6;
	// like: 模糊匹配
	const EOP_LIKE = 7;
	const EOP_NOT_LIKE = 8;
	// contain: 包含
	const EOP_CONTAIN = 9;
	const EOP_NOT_CONTAIN = 10;
	
	public $eopMap = array(
			self::EOP_EQ => "=",
			self::EOP_GE => ">=",
			self::EOP_GT => ">",
			self::EOP_LE => "<=",
			self::EOP_LT => "<",
			self::EOP_NE => "<>",
			self::EOP_LIKE => "匹配",
			self::EOP_NOT_LIKE => "不匹配",
			self::EOP_CONTAIN => "包含",
			self::EOP_NOT_CONTAIN => "不包含",
		);  
		
	/* 逻辑操作符(Logic Operator) */
	const LOP_AND = 1;
	const LOP_OR = 2;
	
	public $lopMap = array(
			self::LOP_AND => "and",
			self::LOP_OR => "or",
		);

	// 策略分析器json中字段名称	
	const POLICY_FIELD_LOGIC = "logic";
	const POLICY_FIELD_CONDITION = "condition";
		
	const CACHE_KEY_VAR_LIST = "policy:var";

	/**
	 * @desc 获取策略变量详细信息
	 * @param vid int 变量id, 可选, 为0表示获取系统所有变量
	 * @return array 
	 */
	public static function getVarInfo($vid = 0)
	{
		static $varList = array();
		
		$cacheValue = Yii::app()->redis->get(self::CACHE_KEY_VAR_LIST);
		if ($cacheValue)
		{
			$varList = json_decode($cacheValue, true);
			return (0 == $vid)? $varList : $varList[$vid];
		}
		
		$recordList = PolicyVar::model()->findAllByAttributes(array('status' => 'Y'));
		foreach ($recordList as $record)
		{
			$varList[$record->id] = $record->getAttributes();
		}
		
		Yii::app()->redis->set(self::CACHE_KEY_VAR_LIST, json_encode($varList));
		return (0 == $vid)? $varList : $varList[$vid];
	}
	
	/**
	 * @desc 加载分析器详细信息
	 *
	 * @param int $policyId
	 * @return array
	 */
	public static function loadPolicy($policyId)
	{
		$policyInfo = PolicyInfo::model()->findByPk($policyId, "status = 'Y'");
		if (empty($policyInfo))
		{
			return array();
		}
		
		$policyInfo['expression'] = json_decode($policyInfo['expression'], true);
		$policyInfo['items'] = self::expandPolicyItem($policyInfo['expression']);
		
		return $policyInfo;
	}
	
	/**
	 * @desc 把策略表达式中的条件项展开
	 *
	 * @param array $policyExpressionInfo
	 * @return array
	 */
	public static function expandPolicyItem($policyExpressionInfo)
	{
		$expandInfo = array('logic' => $policyExpressionInfo['logic'], 'items' => array());
		
		foreach ($policyExpressionInfo['conditions'] as $condItem)
		{
			if (is_array($condItem)) // 嵌套的子表达式
			{
				$expandInfo['items'][] = self::expandPolicyItem($condItem);
			}
			else // item_id
			{
				$expandInfo['items'][] = self::loadPolicyItem($condItem);
			}
		}
		
		return $expandInfo;
	}
	
	/**
	 * @desc 加载策略条件项
	 *
	 * @param int $itemId
	 * @return array
	 */
	public static function loadPolicyItem($itemId)
	{
		$record = PolicyItem::model()->findByPk($itemId, "status = 'Y'");
		if (empty($record))
		{
			return array();
		}
		
		$itemInfo = $record->getAttributes();
		unset($itemInfo['create_time'], $itemInfo['status']);
		
		return $itemInfo;
	}
	
}
?>