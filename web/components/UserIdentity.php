<?php

Yii::import('member.models.Account');

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	public $uid;
	// public $nickname;
	
	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$record = Account::getRecord($this->username);
		if (empty($record))
		{
			$this->errorCode = AccountUtil::ERROR_ACCONT_RECORD_NONEXIST;
			return false;
		}
		else if ($record->status == "U")	// 账号未激活
		{
			$this->errorCode = AccountUtil::ERROR_ACCOUNT_INACTIVATE;
			return false;
		}
		
		$enpwd = md5(md5($this->password) . $record->salt);
		if ($enpwd != $record->password)	// 密码校验错误
		{
			$this->errorCode = UserIdentity::ERROR_PASSWORD_INVALID;
			return false;
		}
		
		$this->uid = $record->id;
		$this->username = $record->nickname;
		
		$this->errorCode = AccountUtil::ERROR_ACCOUNT_OK;
		return true;
	}
	
	public function getId()
	{
		return $this->uid;
	}
}