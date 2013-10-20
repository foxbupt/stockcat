<?php

Yii::import('application.extensions.captchaExtended.CaptchaExtendedValidator');

class RegisterForm extends CFormModel
{
	public $uid;
	
	// 注册邮箱
	public $username;
	
	// 输入密码
	public $password;
	// 确认密码
	public $confirmPassword;
	// 用户昵称
	public $nickname;
	
	// 验证码 
	public $verifyCode;
	
	public $errorUnknown;
	
	public function rules()
	{
		return array(
			// username and password are required
			array('username, password, confirmPassword, nickname, verifyCode', 'required', 'message' => '{attribute}不能为空'),
			array('username', 'email', 'message' => '邮箱格式错误，请重新输入'),
			array('username', 'checkEmailExist'),
			array('nickname', 'checkValidNickName'),
			array('password', 'length', 'max' => 24, 'min' => 6),
			array('confirmPassword', 'compare', 'compareAttribute' => 'password', 'message' => '两次输入的密码必须一致'),
			array('nickname', 'length', 'max' => 16, 'min' => 2, 'encoding' => 'UTF-8', 
				'tooLong' => '您输入的昵称太长，要求少于8个汉字或者16个英文数字组合',
				'tooShort' => '您输入的昵称太短，要求多于2个汉字或者4个英文数字组合',
			),
			
			// array('verifyCode', 'captcha', 'message' => '您的验证码有误'),
			array('verifyCode', 'CaptchaExtendedValidator', 'allowEmpty' => !CCaptcha::checkRequirements(), 'message' => '您的验证码有误'),
		);
	}
	
	public function attributeLabels()
	{
		return array(
			'username' => '邮箱',
			'password' => '密码',
			'confirmPassword' => '确认密码',
			'nickname' => '昵称',
			'verifyCode' => '验证码',
		);
	}
	
	/*
	 * @desc 检查email是否已经被注册过
	 */
	public function checkEmailExist($attribute, $params)
	{
		if(Account::checkEmailExist($this->username))
		{
			$this->addError('username','邮箱已经注册，登录或者使用其他邮箱');
		}
	}
	
	/*
	 * @desc 检查昵称是否合法
	 */
	public function checkValidNickName($attribute, $params)
	{
		$this->nickname = htmlspecialchars(trim($this->nickname), ENT_QUOTES);
		
		/*
		$retCode = AccountFacade::checkValidNickName($this->nickname);
		if (AccountFacade::ERROR_BAN_WORD == $retCode)
		{
			$this->addError('nickname','昵称不合法， 请重新输入');
		}*/
	}	
	
	public function register($clientIp)
	{
		$uid = 0;		
		$fields = array('gender' => 'M', 'register_ip' => ip2long($clientIp));
		
		$retcode = AccountUtil::register($this->username, $this->password, $this->nickname, $fields, $uid);
		$this->uid = $uid;
		
		if (AccountUtil::ERROR_ACCOUNT_OK !== $retcode)
		{
			$this->addError('errorUnknow', AccountUtil::$errorMessage[$retcode]);
			return false;
		}
	
		StatLogUtil::log("register_succ", array(
						'username' => $this->username,
						'nickname' => $this->nickname,
						'uid' => $uid			
					));
					
		return true;
	}
}
?>