<?php

/**
 * @desc 策略分析器管理
 * 		理想的UI: 添加/编辑/删除采用ajax提交后, 分析器下的条件项列表异步刷新, 不需要刷新整个页面.
 * 		目前先简单实现: 采用表单提交, 提交成功后重定向到分析器的查看页面
 * @author fox
 * @date 2013/10/11
 */

Yii::import('stock.models.PolicyInfo');
Yii::import('stock.models.PolicyItem');

class PolicyController extends Controller 
{
	public $layout = "application.views.layouts.member";
	
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
				'actions'=>array('index', 'add', 'modify', 'view', 'delete', 'addItem', 'modifyItem', 'viewItem', 'deleteItem', 'move', 'treeData'),
				'users'=>array('@'),
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}
	
	/**
	 * @desc 分析器列表
	 *
	 */
	public function actionIndex()
	{
		$uid = Yii::app()->user->getId();
		$policyList = PolicyInfo::getUserPolicyList($uid);
		$policyTypes = CommonUtil::getConfigObject("policy.type");
		
		$this->render('index', array(
						'policyList' => $policyList,
						'policyTypes' => $policyTypes,
					));	
	}
	
	/**
	 * @desc 创建分析器
	 *
	 */
	public function actionAdd()
	{
		$uid = Yii::app()->user->getId();
		$policyTypes = $this->encodeDropdownList(CommonUtil::getConfigObject("policy.type"));
		
		$model = new PolicyForm();
		$pid = 0;
		$msg = '';
		$result = false;
		
		if (isset($_POST['PolicyForm']))
		{
			$model->setAttributes($_POST['PolicyForm']);
			if ($model->validate() && $model->serialize($uid))
			{
				$pid = $model->pid;
				$result = true;
				$msg = '创建成功';
			}
			else 
			{
				$errorInfo = $this->getModelError($model, "创建失败");
				$msg = $errorInfo['msg'];
			}
		}
		
		$this->render('policy_form', array(
					'pid' => $pid,
					'model' => $model,
					'policyTypes' => $policyTypes,
					'msg' => $msg,
					'result' => $result,
					'isAdd' => true,
				));
	}
	
	
	/**
	 * @desc 修改分析器本身字段, 不含分析条件
	 * @param $_GET['pid']
	 */
	public function actionModify()
	{
		$pid = intval($_GET['pid']);
		$uid = Yii::app()->user->getId();
		$policyInfo = PolicyUtil::loadPolicy($pid);
		
		if (empty($policyInfo) || ($policyInfo['uid'] != $uid))
		{
			throw new CHttpException(404);
		}
		
		$policyTypes = $this->encodeDropdownList(CommonUtil::getConfigObject("policy.type"));
		$model = new PolicyForm($pid, $policyInfo);
		$msg = '';
		$result = false;
		
		if (isset($_POST['PolicyForm']))
		{
			$model->setAttributes($_POST['PolicyForm']);
			if ($model->validate() && $model->serialize($uid))
			{
				$result = true;
				$msg = '保存成功';
			}
			else 
			{
				$errorInfo = $this->getModelError($model, "保存失败");
				$msg = $errorInfo['msg'];
			}
		}
		
		$this->render('policy_form', array(
				'pid' => $pid,
				'model' => $model,
				'policyTypes' => $policyTypes,
				'msg' => $msg,
				'result' => $result,
				'isAdd' => false,
			));
	}
	
	/**
	 * @desc 查看分析器
	 * @param $_GET['pid']
	 */
	public function actionView()
	{
		$pid = intval($_GET['pid']);
		$policyInfo = PolicyUtil::loadPolicy($pid);
		
		if (empty($policyInfo))
		{
			throw new CHttpException(404);
		}
		
		$varList = PolicyUtil::getVarInfo();
		$policyTypes = $this->encodeDropdownList(CommonUtil::getConfigObject("policy.type"));
		
		$this->render('view-tree', array(
					'pid' => $pid,
					'varList' => $varList,
					'policyTypes' => $policyTypes,
					'policyInfo' => $policyInfo,					
					'treeDataUrl' => $this->createUrl('/member/policy/treeData', array('pid' => $pid)),
					'addUrl' => $this->createUrl('/member/policy/addItem'),
					'modifyUrl' => $this->createUrl('/member/policy/modifyItem'),
					'deleteUrl' => $this->createUrl('/member/policy/deleteItem'),
					'moveUrl' => $this->createUrl('/member/policy/move'),
				));
	}
	
	/**
	 * @desc 删除分析器
	 * @param $_POST['pid']
	 * @return json
	 */
	public function actionDelete()
	{
		$this->layout = false;
		
		if (Yii::app()->user->isGuest)
		{
			$this->renderText(OutputUtil::json(array(), -1, "用户未登录"));
			return;	
		}
		
		$uid = Yii::app()->user->getId();
		$pid = int($_POST['pid']);
		
		$record = PolicyInfo::model()->findByPk($pid, "status = 'Y'");
		if (empty($record) || ($record->uid != $uid))
		{
			$this->renderText(OutputUtil::json(array(), -2, "分析器不存在"));
			return;
		}
		
		$record->updateByPk($pid, array('status' => 'N'));
		$this->renderText(array(), 0, "删除成功");
	}
	
	/**
	 * @desc 添加分析条件
	 * @param $_POST['pid']
	 * @param $_POST['Item']
	 * @return json
	 */
	public function actionAddItem()
	{
		$this->layout = false;
		if (!isset($_POST['pid'], $_POST['Item']))
		{
			$this->renderText(OutputUtil::json(array(), -1, "提交参数非法"));
			return;
		}
		
		$uid = Yii::app()->user->getId();
		$pid = intval($_POST['pid']);	
				
		$policyInfo = PolicyUtil::loadPolicy($pid);
		if (empty($policyInfo) || ($policyInfo['uid'] != $uid))
		{
			$this->renderText(OutputUtil::json(array(), -2, "用户没有该分析器"));
			return;
		}
		
		$postItemInfo = $_POST['Item'];		
		$model = new PolicyItemForm($pid);		
		$model->setAttributes($postItemInfo);		
		
		$result = -3;
		$itemId = 0;
		if ($model->validate() && $model->serialize())
		{
			$result = 0;
			$itemId = $model->itemId;
		}
		
		$postItemInfo['pid'] = $pid;
		$postItemInfo['item_id'] = $itemId;
		$postItemInfo['result'] = $result;
		StatLogUtil::log("add_policy_item", $postItemInfo);
						
		$this->renderText(OutputUtil::json(array('item_id' => $itemId), $result, (0 == $result)? "创建成功" : $this->getModelError($model, "系统错误")));	
	}
	
	/**
	 * @desc 修改分析条件
	 * @param $_POST['pid']
	 * @param $_POST['item_id']
	 * @param $_POST['Item'] 包含字段vid/optor/param/value
	 * @return json
	 */
	public function actionModifyItem()
	{
		$this->layout = false;
		if (!isset($_POST['pid'], $_POST['item_id'], $_POST['Item']))
		{
			$this->renderText(OutputUtil::json(array(), -1, "提交参数非法"));
			return;
		}
		
		$uid = Yii::app()->user->getId();
		$pid = intval($_POST['pid']);	
		$itemId	= intval($_POST['item_id']);
		
		$policyInfo = PolicyUtil::loadPolicy($pid);
		$itemRecord = PolicyItem::model()->findByPk($itemId, "pid = $pid and status = 'Y'");
		if (empty($policyInfo) || ($policyInfo['uid'] != $uid))
		{
			$this->renderText(OutputUtil::json(array(), -2, "用户没有该分析器"));
			return;
		}
		else if (empty($itemRecord))
		{
			$this->renderText(OutputUtil::json(array(), -3, "分析器不存在此条件项"));
			return;
		}
		
		// TODO: 需要判断$itemRecord->vid == $postItemInfo['vid'], 若不一致的话, 表明更换了变量, 需要判断分析器所有条件项, 避免出现冲突
		$postItemInfo = $_POST['Item'];
		$model = new PolicyItemForm($pid, $itemId);		
		$model->setAttributes($postItemInfo);	
		
		$result = ($model->validate() && $model->serialize());
		$postItemInfo['pid'] = $pid;
		$postItemInfo['item_id'] = $itemId;
		$postItemInfo['result'] = $result? 0 : -4;
		StatLogUtil::log("modify_policy_item", $postItemInfo);
		
		$this->renderText(OutputUtil::json(array(), $result? 0 : -4, $result? "修改成功" : $this->getModelError($model, "系统错误")));								
	}
	
	/**
	 * @desc 删除分析条件项
	 * @param $_POST['pid']
	 * @param $_POST['item_id']
	 * @return json
	 */
	public function actionDeleteItem()
	{
		$this->layout = false;
		
		if (!isset($_POST['pid'], $_POST['item_id']))
		{
			$this->renderText(OutputUtil::json(array(), -1, "提交参数非法"));
			return;
		}
		
		$uid = Yii::app()->user->getId();
		$pid = intval($_POST['pid']);	
		$itemId	= intval($_POST['item_id']);
		
		$policyInfo = PolicyUtil::loadPolicy($pid);
		$itemRecord = PolicyItem::model()->findByPk($itemId, "pid = $pid and status = 'Y'");
		if (empty($policyInfo) || ($policyInfo['uid'] != $uid))
		{
			$this->renderText(OutputUtil::json(array(), -2, "用户没有该分析器"));
			return;
		}
		else if (empty($itemRecord))
		{
			$this->renderText(OutputUtil::json(array(), -3, "分析器不存在此条件项"));
			return;
		}
		else if ($itemRecord->id == $policyInfo['root_item']) 
		{
			$this->renderText(OutputUtil::json(array(), -4, "不允许删除根节点"));
			return;
		}
		
		/*
		 * 若该item为条件项, 直接删除即可.
		 * item为父节点, 除了删除本身, 需要把下面的直属子节点提升一级
		 */ 		
		$result = (1 == $itemRecord->updateByPk($itemId, array('status' => 'N')));
		if ($itemRecord->node_type == PolicyUtil::NODE_TYPE_PARENT)
		{
			PolicyItem::model()->updateAll(array('parent_id' => $itemRecord->parent_id), array(
								"condition" => "parent_id = :parent_id and status = 'Y'",
								"params" => array('parent_id' => $itemRecord->id)
							));
		}
		
		StatLogUtil::log('delete_policy_item', array(
							'result' => $result? 1 : 0,
							'pid' => $pid,
							'item_id' => $itemId,
							'node_type' => $itemRecord->node_type,
							'vid' => $itemRecord->vid,		
						));
		
		$this->renderText(OutputUtil::json(array(), $result? 0 : -5, $result? "删除成功" : "删除失败"));						
	}
	
	/**
	 * @desc 检查策略条件项是否合法:
	 * 		1) 检查条件项本身是否有效: 如value在变量范围内、操作符、参数等
	 * 		2) 检查条件项是否与已有条件项冲突, 需要定位新增条件项所在位置
	 * @param array $policyInfo
	 * @param array $itemInfo
	 * @param int $itemId 条件项id, 为0表示创建, > 0 表示修改已有条件项
	 * @return int
	 */
	public static function checkPolicyItemValid($policyInfo, $itemInfo, $itemId = 0)
	{
		// 获取变量详细信息
		$varInfo = PolicyUtil::getVarInfo($itemInfo['vid']);
		if (empty($varInfo))
		{
			return -1;
		}
		
		// 根据变量配置判断条件项
		
		// 判断与已有条件项是否冲突
		return 0;
	}
	
	/**
	 * @desc 获取条件项树形展示的数据
	 * @param $_GET['pid']
	 * @return json
	 */
	public function actionTreeData()
	{
		$this->layout = false;
		$data = array();
		
		$pid = intval($_GET['pid']);
		$policyInfo = PolicyUtil::loadPolicy($pid);
		
		if ($policyInfo['expression'])
		{
			$data[] = self::formatTreeNode($policyInfo['expression']);
		}
		
		$this->renderText(json_encode($data));
	}
	
	/**
	 * @desc 格式化树形节点
	 *
	 * @param array $nodeInfo
	 * @return array
	 */
	public static function formatTreeNode($nodeInfo)
	{
		$nodeData = $nodeInfo;
		unset($nodeData['children']);
		$nodeData['label'] = PolicyUtil::formatItemLabel($nodeInfo);
		
		if (isset($nodeInfo['children']))
		{
			$nodeData['children'] = array();
			foreach ($nodeInfo['children'] as $childNodeInfo)
			{
				if (PolicyUtil::NODE_TYPE_LEAF == $childNodeInfo['node_type'])
				{
					$childNodeData = $childNodeInfo;
					$childNodeData['label'] = PolicyUtil::formatItemLabel($childNodeInfo);
					
					$nodeData['children'][] = $childNodeData;
				}
				else 
				{
					$nodeData['children'][] = self::formatTreeNode($childNodeInfo);
				}
			}
		}
		
		return $nodeData;
	}
	
	/**
	 * @desc 移动节点
	 * @param $_POST['pid']
	 * @param $_POST['mid'] 移动节点id
	 * @param $_POST['tid'] 目标节点id
	 * @param $_POST['position'] 位置
	 * @return json
	 */
	public function actionMove()
	{
		$this->layout = false;
		
		if (!isset($_POST['pid'], $_POST['mid'], $_POST['tid'], $_POST['position']))
		{
			$this->renderText(OutputUtil::json(array(), -1, "提交参数非法"));
			return;
		}
		
		$uid = Yii::app()->user->getId();
		$pid = intval($_POST['pid']);			
		$policyInfo = PolicyUtil::loadPolicy($pid);
		if (empty($policyInfo) || ($policyInfo['uid'] != $uid))
		{
			$this->renderText(OutputUtil::json(array(), -2, "用户没有该分析器"));
			return;
		}
		
		$mid = intval($_POST['mid']);
		$tid = intval($_POST['tid']);
		$position = trim($_POST['position']);
		
		$mItemRecord = PolicyItem::model()->findByPk($mid, "pid = $pid and status = 'Y'");
		$tItemRecord = PolicyItem::model()->findByPk($tid, "pid = $pid and status = 'Y'");		
		if (empty($mItemRecord) || empty($tItemRecord))
		{
			$this->renderText(OutputUtil::json(array(), -3, "条件项不存在"));
			return;
		}
		else if (($mItemRecord->parent_id == $tItemRecord->parent_id) && (($position == "before") || ($position == "after")))
		{
			$this->renderText(OutputUtil::json(array(), 0, "移动成功"));
			return;
		}
				
		if ("inside" == $position)	// 拖到里面
		{
			$parentId = $tItemRecord->id;
			
			if ($tItemRecord->node_type == PolicyUtil::NODE_TYPE_LEAF) // 移动到一个叶子节点里面, 需要新增一个父类节点
			{
				$parentId = PolicyItem::addItem($pid, PolicyUtil::LOP_AND, PolicyUtil::NODE_TYPE_PARENT, $tItemRecord->parent_id);	
				if (0 == $parentId)
				{
					$this->renderText(OutputUtil::json(array(), -4, "系统错误"));
					return;
				}
				$tItemRecord->updateByPk($tid, array('parent_id' => $parentId));
			}
			
			$mItemRecord->updateByPk($mid, array('parent_id' => $parentId));
		}
		else
		{
			$mItemRecord->updateByPk($mid, array('parent_id' => $tItemRecord->parent_id));
		}
		
		$this->renderText(OutputUtil::json(array(), 0, "移动成功"));
	}
}
?>