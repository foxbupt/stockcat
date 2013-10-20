<?php

class PolicyItemForm extends CFormModel
{
	public $pid;
	public $itemId = 0;
	public $parent_id = 0;
	public $logic = PolicyUtil::LOP_AND;
	public $node_type = PolicyUtil::NODE_TYPE_LEAF;
	
	public $vid;
	public $optor;
	public $value;
	public $param;
	
	public $errMsg;
	
	public function __construct($pid, $itemId = 0)
	{
		parent::__construct();
		$this->pid = $pid;
		$this->itemId = $itemId;
	}
	
	public function rules()
	{
		return array(
			array('vid, optor, parent_id', 'numerical', 'integerOnly' => true, 'min' => 1, 'message' => '字段取值无效'),
			array('logic', 'in', 'range' => array_keys(PolicyUtil::$lopMap), 'message' => '逻辑关系无效'),
			array('param, value', 'length', 'max'=>255),
		);
	}
	
	/**
	 * @desc 检查条件项本身是否有效: 如value在变量范围内、操作符、参数等
	 *
	 */
	public function checkItem()
	{
		$varInfo = PolicyUtil::getVarInfo($this->vid);
		if (empty($varInfo))
		{
			$this->addError('vid', '变量名不存在');
			return false;
		}
		
		if (!isset(PolicyUtil::$eopMap[$this->optor]))
		{
			$this->addError('optor', '运算符无效');
			return false;
		}
		
		// TODO: 根据变量配置$varInfo判断value和param是否合法
		
		return true;
	}
	
	/**
	 * @desc 序列化保存条件项
	 *
	 * @return unknown
	 */
	public function serialize()
	{
		if (!$this->checkItem())
		{
			return false;
		}
		
		$parentId = $this->parent_id;
		if ($this->itemId > 0)	// 修改
		{
			$record = PolicyItem::model()->findByPk($this->itemId, "status = 'Y'");	
			$record->update_time = time();
		}
		else // 新增条件项, 需要判断parent_id节点是叶子还是父节点
		{
			$parentNode = PolicyUtil::loadItem($parentId);
			if (empty($parentNode))
			{
				return false;
			}
			
			if (PolicyUtil::NODE_TYPE_LEAF == $parentNode['node_type']) // 父节点为叶子节点, 需要自动添加一个父节点
			{
				$newItemId = PolicyItem::addItem($this->pid, $this->logic, PolicyUtil::NODE_TYPE_PARENT, $parentNode['parent_id']);	
				if (0 == $newItemId)
				{
					return false;
				}
				
				PolicyItem::model()->updateByPk($parentId, array('parent_id' => $newItemId));
				$parentId = $newItemId;
			}
			
			$record = new PolicyItem;
			$record->update_time = $record->create_time = time();
			$record->status = 'Y';
		}
		
		$this->parent_id = $parentId;
		$attrs = $this->attributes;
		unset($attrs['itemId']);
		
		$record->setAttributes($attrs);
		if (!$record->save())
		{
			$this->addError('errMsg', "系统错误");
			return false;
		}
		
		$this->itemId = $record->getPrimaryKey();
		return true;
	}
}
?>