<?php

Yii::import('member.models.Account');

/**
 * @desc 封装账号相关操作
 * @author fox
 * @date 2013/09/23
 * @copyright bencaimao
 *
 */
class AccountUtil 
{
	// 账号注册方式: 1 email, 2 mobile, 3 open
	const ACCOUNT_TYPE_EMAIL = 1;
	const ACCOUNT_TYPE_MOBILE = 2;
	const ACCOUNT_TYPE_OPEN = 3;
	
	const MAGIC_SIGN = "bencaimao.#678";
	
	const ERROR_ACCOUNT_OK = 0;
	// 账号未激活
	const ERROR_ACCOUNT_INACTIVATE = 101;
	// 账号被禁用
	const ERROR_ACCOUNT_BANNED = 102;
	// 邮箱已经存在
	const ERROR_ACCOUNT_EMAIL_EXIST = 103;
	// 数据库错误
	const ERROR_ACCOUNT_DBOPER = 104;
	// 激活码错误
	const ERROR_ACCOUNT_CODE_INVALID = 105;
	// 用户记录不存在
	const ERROR_ACCONT_RECORD_NONEXIST = 106;
	
	// 错误信息
	public static $errorMessage = array(
								CBaseUserIdentity::ERROR_USERNAME_INVALID => '用户名或密码错误',
								CBaseUserIdentity::ERROR_PASSWORD_INVALID => '用户名或密码错误',
								CBaseUserIdentity::ERROR_UNKNOWN_IDENTITY => '未知错误',
								self::ERROR_ACCOUNT_INACTIVATE  	  		  => '账号还没有激活，请到注册邮箱激活吧！',
								self::ERROR_ACCOUNT_BANNED			 		  => '账号已经被禁用，如有疑问，请联系客服人员！',	
								self::ERROR_ACCOUNT_EMAIL_EXIST				  => '邮箱已存在，请直接使用此邮箱登录，或换一个邮箱注册',
								self::ERROR_ACCONT_RECORD_NONEXIST		  => '邮箱错误',
								self::ERROR_ACCOUNT_CODE_INVALID			  => '激活码错误',
								self::ERROR_ACCOUNT_DBOPER						  => '系统错误',
								self::ERROR_MAIL_SEND					  => '邮件发送失败',								
							); 
								
	const CACHE_KEY_USERINFO = "account:info-";
	
	/*
	 * @desc 摘自discuz里global.func.php中的random函数
	 * @param length int
	 * @param numeric int 0 字母或数字 1 数字
	 * @return string
	 */
	public static function random($length, $numeric = 0) 
	{
		mt_srand();
		$seed = base_convert(md5(print_r($_SESSION, 1) . microtime()), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
		
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) 
		{
			$hash .= $seed[mt_rand(0, $max)];
		}
		
		return $hash;
	}
	
	public static function genActiveCode($email, $curTime)
	{
		return md5(strval($curTime) . self::MAGIC_SIGN . $email);	
	}
	
	/*
	 * @desc 验证激活码是否有效
	 * @param email string 
	 * @param paramTime int
	 * @param code string
	 * @param recordCode string
	 * @param valid int
	 * @return bool
	 */
	public static function verifyActiveCode($email, $paramTime, $code, $recordCode, $valid)
	{
		if ($code !== $recordCode)	// 激活码不一致
		{
			return false;
		}
		else if (self::genActiveCode($email, $paramTime) != $code)	// 上传的time/email有误
		{
			return false;
		}
		else if ((time() - $paramTime) >= $valid)	// 激活码超过有效时间
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * @desc 注册账号
	 *
	 * @param string $email
	 * @param string $password
	 * @param string $nickname
	 * @param array $fields array(key => value)
	 * @param int $uid OUT
	 * @return int
	 */
	public static function register($email, $password, $nickname, $fields, &$uid)
	{
		if (Account::checkEmailExist($email))
		{
			return self::ERROR_ACCOUNT_EMAIL_EXIST;
		}
		
		$record = new Account();
		
		$record->email = $email;
		$record->type = self::ACCOUNT_TYPE_EMAIL;
		$record->salt = $salt = self::random(6);
		$record->password = md5(md5($password) . $salt);
		
		$record->nickname = $nickname;
		foreach ($fields as $key => $value)
		{
			$record->$key = $value;		
		}
		$record->update_time = $record->create_time = time();
		$record->active_code = self::genActiveCode($email, time());
		$record->status = 'U';
		
		if (!$record->save())
		{
			return self::ERROR_ACCOUNT_DBOPER;
		}
		
		$uid = $record->getPrimaryKey();
		// TODO: 发送注册的激活邮件
		return self::ERROR_ACCOUNT_OK;
	}
	
	/**
	 * @desc 修改用户个人信息
	 *
	 * @param int $uid
	 * @param array $fields
	 * @return int
	 */
	public static function modifyInfo($uid, $fields)
	{
		$record = Account::getRecord($uid);
		if (empty($record))
		{
			return self::ERROR_ACCONT_RECORD_NONEXIST;
		}
		
		// TODO: 改用saveAttributes
		return ($record->updateByPk($uid, $fields) == 1)? self::ERROR_ACCOUNT_OK : self::ERROR_ACCOUNT_DBOPER;
	}
	
	/**
	 * @desc 获取用户个人信息
	 *
	 * @param int $uid
	 * @return array 
	 */
	public static function getInfo($uid)
	{
		$cacheKey = self::CACHE_KEY_USERINFO . strval($uid);
		$cacheInfo = Yii::app()->redis->get($cacheKey);
		
		if (!$cacheInfo)
		{
			$record = Account::getRecord($uid);
			$userInfo = array();
			
			if ($record)
			{
				$userInfo = $record->getAttributes();
				unset($userInfo['salt'], $userInfo['password']);
				
				Yii::app()->redis->set($cacheKey, json_encode($userInfo), 86400);
			}
			
			return $userInfo;
		}
		
		return json_decode($userInfo, true);
	}
	
	/**
	 * @desc 忘记密码
	 *
	 * @param string $email
	 * @return int
	 */
	public static function forgetPassword($email)
	{
		$record = Account::getRecordByEmail($email);	
		if (empty($record))
		{
			return self::ERROR_ACCONT_RECORD_NONEXIST;
		}

		$now = time();
		$code = self::genActiveCode($email, $now);
		if (self::ERROR_ACCOUNT_OK == self::modifyInfo($record->id, array('active_code' => $code))) // 保存active_code成功
		{
			/*
			Yii::import('common.components.util.MailUtil', true);
			MailUtil::asynSendTemplateMail($email, $info['realname'], '', array(
				'username' => $info['realname'],
				'change-password-url' => Yii::app()->params['accountUrl'] . Yii::app()->createUrl('/newAccount/reset', array(
					'code' => $code, 
					'email' => $email, 
					'time' => $now,
					'from' => 'mail',
				)),
			), 'find-pastword');
			*/
			return self::ERROR_ACCOUNT_OK;
		}
		
		return self::ERROR_ACCOUNT_DBOPER;
	}
	
	/**
	 * @desc 修改用户密码
	 *
	 * @param int $uid
	 * @param string $password
	 * @return int
	 */
	public static function modifyPassword($uid, $password)
	{
		$record = Account::getRecord($uid);
		if (empty($record))
		{
			return self::ERROR_ACCONT_RECORD_NONEXIST;
		}
		
		$encryptPassword = md5(md5($password) . $record->salt);
		if ($encryptPassword == $password)
		{
			return self::ERROR_ACCOUNT_OK;
		}
		
		return (1 == $record->updateByPk($uid, array('password' => $encryptPassword, 'update_time' => time())))? 
				self::ERROR_ACCOUNT_OK : self::ERROR_ACCOUNT_DBOPER;
	}
	
	/**
	 * @desc 根据链接重置密码
	 *
	 * @param string $email
	 * @param int $paramTime
	 * @param string $code
	 * @param string $password
	 * @return int
	 */
	public static function resetPassword($email, $paramTime, $code, $password)
	{
		$record = Account::getRecordByEmail($email);
		// 账号被禁用或不存在
		if (empty($record))
		{
			return self::ERROR_ACCONT_RECORD_NONEXIST;
		}
		// 验证码错误
		else if (!self::verifyActiveCode($email, $paramTime, $code, $record->active_code, 86400))
		{
			return self::ERROR_ACCOUNT_CODE_INVALID;
		}
		
		// 调用修改密码
		return self::modifyPassword($record->id, $password);
	}
	
	public static function activate($email, $paramTime, $code)
	{
		$record = Account::getRecordByEmail($email);
		
		// 账号被禁用或不存在
		if (empty($record) || $record->status != 'U')
		{
			return self::ERROR_ACCONT_RECORD_NONEXIST;
		}
		// 验证码错误
		else if (!self::verifyActiveCode($email, $paramTime, $code, $record->active_code, 86400))
		{
			return self::ERROR_ACCOUNT_CODE_INVALID;
		}
		
		return $record->updateByPk($record->id, array('status' => 'Y'));
	}
}
?>