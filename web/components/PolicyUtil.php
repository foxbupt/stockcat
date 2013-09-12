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
	
	public static $eopMap = array(
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
	
	public static $lopMap = array(
			self::LOP_AND => "and",
			self::LOP_OR => "or",
		);

	// 节点类型: 1  父节点 2 叶子节点
	const NODE_TYPE_PARENT = 1;
	const NODE_TYPE_LEAF = 2;
	
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
	 * @param int $pid 分析器id
	 * @return array
	 */
	public static function loadPolicy($pid)
	{
		$record = PolicyInfo::model()->findByPk($pid, "status = 'Y'");
		if (empty($record))
		{
			return array();
		}
		
		$policyInfo = $record->getAttributes();
		$policyInfo['expression'] = ($policyInfo['root_item'] > 0)? self::getPolicyItemList($pid, $policyInfo['root_item']) : array();		
		return $policyInfo;
	}
	
	/**
	 * @desc 获取策略分析器的所有条件项
	 *
	 * @param int $pid
	 * @param int $rootId
	 * @return array
	 */
	public static function getPolicyItemList($pid, $rootId)
	{	
		$nodes = array();
		$itemList = array();
		
		$recordList = PolicyItem::model()->findAll(array(
									'condition' => "pid = $pid and status = 'Y'",
									'order' => "id asc",
							));
		if (empty($recordList))
		{
			return array();	
		}
		
		// 遍历节点列表获取父子节点关系					
		foreach ($recordList as $record)
		{
			$itemList[$record->id] = $record->getAttributes();
			if ($record->parent_id == 0)
			{				
				continue;
			}
			else 
			{
				if (!isset($nodes[$record->parent_id]))
				{
					$nodes[$record->parent_id] = array();
				}
				
				$nodes[$record->parent_id][] = $record->id;
			}
		}
		
		return self::expandItemNode($rootId, $nodes, $itemList);
	}
	
	/**
	 * @desc 递归展开某个节点下所有的条件项
	 *
	 * @param int $nodeId
	 * @param array $nodes
	 * @param array $itemList
	 * @return array
	 */
	public static function expandItemNode($nodeId, $nodes, $itemList)
	{
		$data = $itemList[$nodeId];
		$data['children'] = array();

		if (!empty($nodes[$nodeId]))
		{
			foreach ($nodes[$nodeId] as $childNodeId)
			{
				$childNodeInfo = $itemList[$childNodeId];
				if (self::NODE_TYPE_LEAF == $childNodeInfo['node_type'])
				{
					$data['children'][] = $childNodeInfo;
				}
				else 
				{
					$data['children'][] = self::expandItemNode($childNodeId, $nodes, $itemList);
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * @desc 加载策略条件项
	 *
	 * @param int $itemId
	 * @return array
	 */
	public static function loadItem($itemId)
	{
		$record = PolicyItem::model()->findByPk($itemId, "status = 'Y'");
		if (empty($record))
		{
			return array();
		}
		
		$itemInfo = $record->getAttributes();
		unset($itemInfo['update_time'], $itemInfo['create_time'], $itemInfo['status']);
		
		return $itemInfo;
	}
	
	/**
	 * @desc 格式化条件项显示名称
	 *
	 * @param array $itemInfo
	 * @return string
	 */
	public static function formatItemLabel($itemInfo)
	{
		if ($itemInfo['node_type'] == PolicyUtil::NODE_TYPE_PARENT)
		{
			$logicName = self::$lopMap[$itemInfo['logic']];
			return empty($itemInfo['name'])? $logicName : $itemInfo['name'] . "(" . $logicName . ")";
		}
		else
		{
			$labelFields = array();
			
			$varInfo = PolicyUtil::getVarInfo($itemInfo['vid']);
			$varName = $varInfo['name'];
			$eopName = PolicyUtil::$eopMap[$itemInfo['optor']];			
			if (!empty($itemInfo['param']))
			{
				$labelFields[] = $varName . "(" . $itemInfo['param'] . ")";
			}
			else 
			{
				$labelFields[] = $varName;
			}
			$labelFields[] = $eopName;
			$labelFields[] = $itemInfo['value'];
			
			return implode(" ", $labelFields);
		}
	}
}
?>
