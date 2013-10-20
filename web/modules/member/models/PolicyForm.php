<?php

/**
 * @desc 策略分析器
 * @author fox
 * @date 2013/10/12
 */
class PolicyForm extends CFormModel
{
	public $pid = 0;
	
	public $name;
	public $type;
	public $remark;
	
	public $errMsg;
	
	public function __construct($pid = 0, $policyInfo = null)
	{
		parent::__construct();
		$this->pid = $pid;
		if ($policyInfo)
		{
			$attrs = array_keys((array)$this->attributeLabels());
			foreach ($attrs as $key)
			{
				$this->$key = $policyInfo[$key];
			}
		}
	}
	
	public function rules()
	{
		return array(
			array('name', 'length', 'max' => 128, 'message' => '名称长度不能超过128'),
			array('remark', 'length', 'max' => 1024, 'message' => '描述长度不能超过1024'),
			array('type', 'numerical', 'integerOnly'=>true),		
		);
	}
	
	public function attributeLabels()
	{
		return array(
			'name' => '名称',
			'type' => '类型',
			'remark' => '描述',
		);
	}
	
	/**
	 * @desc 保存分析器
	 *
	 * @param int $uid 当前操作的用户uid
	 * @return bool
	 */
	public function serialize($uid)
	{
		$this->name = htmlspecialchars(trim($this->name), ENT_QUOTES);
		$this->remark = htmlspecialchars(trim($this->remark), ENT_QUOTES);
		
		$policyTypes = CommonUtil::getConfig("policy.type");
		if (!isset($policyTypes[$this->type]))
		{
			$this->addError('errMsg', "分析器类型不是有效类型");
			return false;	
		}
		
		$isAdd = true;
		if (0 == $this->pid)
		{
			$record = new PolicyInfo();
			$record->create_time = $record->update_time = time();			
			$record->status = 'Y';
		}
		else 
		{
			$isAdd = false;
			$record->update_time = time();	
			$record = PolicyInfo::model()->findByPk($this->pid, "status = 'Y'");
			if (empty($record))
			{
				return false;
			}
		}
		
		$record->uid = $uid;
		$attrs = array_keys((array)$this->attributeLabels());
		foreach ($attrs as $key)
		{
			$record->$key = $this->$key;
		}
		
		if (!$record->save())
		{
			return false;
		}
		
		if ($isAdd)
		{
			$this->pid = $record->getPrimaryKey();
			// 创建根节点
			$rootId = PolicyItem::addItem($this->pid, PolicyUtil::LOP_AND, PolicyUtil::NODE_TYPE_PARENT, 0, array('name' => "根节点"));
			if ($rootId)
			{
				$record->updateByPk($record->id, array('root_item' => $rootId));
			}
		}
		return true;
	}
}
?>