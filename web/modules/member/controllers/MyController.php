<?php

/**
 * @desc 个人中心管理操作
 * @author fox
 * @date 2013/09/22
 *
 */
class MyController extends Controller 
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
				'actions'=>array('index', 'profile', 'setting', 'password'),
				'users'=>array('@'),
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}
	
	/**
	 * @desc 个人中心首页
	 *
	 */
	public function actionIndex()
	{
		
	}
	
	/**
	 * @desc 完善个人资料
	 *
	 */
	public function actionProfile()
	{
		$userInfo = AccountUtil::getInfo(Yii::app()->user->getId());		
		$model = new ProfileForm($userInfo);
		$msg = "";
		$field = "";
				
		if (isset($_POST['ProfileForm']))
		{
			$model->setAttributes($_POST['ProfileForm']);
			if ($model->validate() && ($model->modify() == AccountUtil::ERROR_ACCOUNT_OK))
			{
				$msg = "修改成功";	
			}
			else 
			{
				$errorInfo = $this->getModelError($model, "");
				$field = $errorInfo['field'];
				$msg = $errorInfo['msg'];
			}
		}
		
		$this->render('profile', array(
				'model' => $model,
				'msg' => $msg,
				'field' => $field,
		));	
	}
	
	/**
	 * @desc 修改个人设置
	 *
	 */
	public function actionSetting()
	{
		$this->render('setting');	
	}
	
	/**
	 * @desc 修改密码
	 *
	 */
	public function actionPassword()
	{
		$uid = Yii::app()->user->getId();
		$msg = "";		
		$success = false;
		$loginUrl = "";
		
		if (!isset($_POST['oldPassword'], $_POST['password'], $_POST['confirmPassword']))
		{
			$oldPassword = trim($_POST['oldPassword']);
			$password = trim($_POST['password']);
			$confirmPassword = trim($_POST['confirmPassword']);
			
			if ($password != $confirmPassword)
			{
				$msg = "两次密码输入不一致";
			}
			else if (strlen($password) < 8)
			{
				$msg = "密码长度不足8个字符";
			}
			else if ($password == $oldPassword)
			{
				$msg = "新密码与旧密码不能相同";
			}
			else 
			{
				// 判断输入旧密码是否正确
				$record = Account::getRecord($uid);
				if (empty($record))
				{
					$msg = "用户记录不存在";
				}
				else if (md5(md5($password) . $record->salt) != $record->password)
				{
					$msg = "旧密码与当前密码不一致";
				}
				else // 修改密码
				{
					$ret = AccountUtil::modifyPassword($uid, $password);
					if (AccountUtil::ERROR_ACCOUNT_OK == $ret)
					{
						$success = true;
						$loginUrl = $this->createUrl(array('/member/account/login', 'username' => $record->email));
					}
				}
			}
		}
				
		$this->render('password', array(
							'msg' => $msg,
							'success' => $success,
							'loginUrl' => $loginUrl,	
					));	
	}
	
	
}
?>