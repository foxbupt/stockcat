<?php

Yii::import('application.extensions.captchaExtended.CaptchaExtendedAction');

/**
 * @desc 账号管理操作
 * @author fox
 * @date 2013/09/22
 *
 */
class AccountController extends Controller 
{
/**
	 * @return array action filters
	 */
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
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('login', 'logout', 'register', 'validateEmail', 'captcha', 'forget', 'reset', 'activate', 'sendmail'),
				'users'=>array('*'),
			),			
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class' => 'CaptchaExtendedAction',
				'minLength' => 4,
				'maxLength' => 4,
				'density' => 30,
				'testLimit' => 3,
			),
		);
	}
	
	/**
	 * @desc 登录操作, 表单提交
	 * @param $_GET['url'] 可选
	 * @param $_GET['username'] 可选
	 * @param $_POST['username']
	 * @param $_POST['password']
	 * @param $_POST['rememberMe']
	 *
	 */
	public function actionLogin()
	{
		$model = new LoginForm;
		if (isset($_GET['username']))
		{
			$model->username = trim($_GET['username']);	
		}
		
		$msg = '';		
		if (isset($_POST['LoginForm']))
		{
			$returnUrl = isset($_GET['url'])? $_GET['url'] : "/";
			
			$model->attributes = $_POST['LoginForm'];
			if ($model->validate() && $model->login())
			{
				$this->redirect($returnUrl);
			}
			else
			{
				$msg = '用户名或密码错误';
			}				
		}
		
		$this->render('login', array('model' => $model, 'msg' => $msg));
	}
	
	/**
	 * @desc 登出操作
	 * @param $_GET['returnUrl']
	 *
	 */
	public function actionLogout()
	{
		if (!Yii::app()->user->isGuest)
		{
			Yii::app()->user->logout();
			$this->redirect(empty($_GET['returnUrl'])? "/" : $_GET['returnUrl']);
		}
	}
	
	/**
	 * @desc 注册
	 *
	 */
	public function actionRegister()
	{
		$model = new RegisterForm;
		if (isset($_GET['email']))
		{
			$model->email = $_GET['email'];
		}
		
		if(isset($_GET['returnUrl']))
		{
			Yii::app()->user->returnUrl = $_GET['returnUrl'];
		}
		
		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='register-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		
		if (isset($_POST['RegisterForm']))
		{
			$model->attributes = $_POST['RegisterForm'];
            $clientIP = BevaUtil::getRemoteIP();
			
			if ($model->validate() && $model->register($clientIP))
			{
				$this->render('sendmail', array(
                                'msg' => '',
								'email' => $model->username,
								'emailLink' => self::getEmailLink($model->username)
							));				
				Yii::app()->end();
			}
			else 
			{
				$model->addError('errorUnknow', '系统错误');		
			}
		}
		
		$this->render('register', array('model' => $model));
	}
	
	/**
	 * @desc 找回密码
	 * @param $_POST['email']
	 *
	 */
	public function actionForget()
	{
		$firstStep = true;
		$msg = "";
		$email = $emailLink = "";
		
		if (isset($_POST['email']))	// 提交
		{
			$email = htmlentities(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
			$retcode = AccountUtil::forgetPassword($email);
			
			if ($retcode == AccountUtil::ERROR_ACCOUNT_OK)
			{
				$firstStep = false;
				$emailLink = self::getEmailLink($email);
			}
			else 
			{
				$msg = "邮箱尚未注册";
			}			
		}
		
		$this->render('forget', array(
						'isFirstStep' => $firstStep,
						'msg' => $msg,
						'email' => $email,
						'emailLink' => $emailLink,
					));
	}
	
	/**
	 * @desc 重置密码, 成功后自动跳转到登录页
	 * @param $_GET['email']
	 * @param $_GET['code']
	 * @param $_GET['time']
	 * @param $_POST['password']
	 * @param $_POST['confirmPassword']
	 */
	public function actionReset()
	{
		if (!isset($_GET['code'], $_GET['email'], $_GET['time']))
		{
			echo 'params missing';
			return ;
		}
		
		$code = $_GET['code'];
		$email = $_GET['email'];
		$time = $_GET['time'];	

		// 错误提示信息
		$msg = "";
		// 标识是否重置密码成功
		$success = false;
		// 跳转的登录url
		$loginUrl = "";
		
		if (isset($_POST['password'], $_POST['confirmPassword']))
		{
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
			else 
			{
				$retcode = AccountUtil::resetPassword($email, $time, $code, $password);
				if ($retcode != AccountUtil::ERROR_ACCOUNT_OK)
				{
					$msg = AccountUtil::$errorMessage[$retcode];
				}
				else 
				{
					$success = true;
					$loginUrl = $this->createUrl('/member/account/login', array('username' => $email));
				}
			}
		}
		
		$this->render('reset', array(
					'msg' => $msg,
					'success' => $success,
					'email' => $email,
					'loginUrl' => $loginUrl,
				));
	}

	/**
	 * @desc 邮箱激活
	 *
	 */
	public function actionActivate()
	{
		if(!isset($_GET['code'], $_GET['email'], $_GET['time']))
		{
			$this->render('activate', array('success' => false, 'email' => $_GET['email']));
			Yii::app()->end();
		}
		
		$code = $_GET['code'];
		$email = $_GET['email'];
		$time = $_GET['time'];
		
		$success = false;
		$retcode = AccountUtil::activate($email, $time, $code);
		if ($retcode == AccountFacade::ERROR_OK)
		{
			$success = true;
		}
		
		$this->render('activate', array('success' => $success, 'email' => $email));
	}
	
	/**
	 * @desc 发送激活邮件
	 * @param $_GET['username'] 用户邮箱
	 */
	public function actionSendmail()
	{
		$email = $_GET['username'];
		$msg = "";
		
		$record = Account::getRecordByEmail($email);
		if (empty($record))
		{
			$msg = "账号不存在";
		}
		else if ($record->status == 'Y')
		{
			$msg = "账号已激活, 请直接登录";
		}
		else
		{
			// TODO: 发送激活邮件
		}
		
		$this->render('sendmail', array(
					'msg' => $msg,
					'email' => $email,
					'emailLink' => self::getEmailLink($email),
				));
	}
	
	/**
	 * @desc 获取邮箱域名服务商的首页
	 *
	 * @param string $email
	 * @return string
	 */
	public static function getEmailLink($email)
	{
		$parts = explode("@", $email);
		$domain = str_replace("vip.", "", $parts[1]);
		
		$prefix = "http://";
		if (strstr($domain, "mail") === FALSE)
		{
			$prefix .= "mail.";
		}
		
		return $prefix . $domain;
	}
}
?>
