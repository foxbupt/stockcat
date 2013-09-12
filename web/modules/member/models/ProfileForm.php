<?php

class ProfileForm extends CFormModel
{
	public $nickname;
	public $gender;
	public $mobile_no;
	public $intro;
	
	public $errMsg;
	
	public function __construct($userInfo = null)
	{
		parent::__construct();
		if ($userInfo !== null)
		{
			$this->nickname = $userInfo['nickname'];
			$this->gender = $userInfo['gender'];
			$this->mobile_no = $userInfo['mobile_no'];
			$this->intro = $userInfo['intro'];	
		}
	}
	
	public function rules()
	{
		return array(
			array('nickname', 'length', 'max' => 16, 'min' => 2, 'encoding' => 'UTF-8',
				'tooShort' => '您的昵称长度太短，昵称需要2-8个汉字或4-16个字母、数字哦',
				'tooLong' => '您的昵称长度太长，昵称需要2-8个汉字或4-16个字母、数字哦',
				'message' => '昵称需要2-8个汉字或4-16个字母、数字哦'
			),
			
			array('nickname', 'checkValidNickName'),
			array('gender', 'in', 'range' => array('F', 'M'), 'message' => '性别值非法'),
			array('mobile_no', 'match', 'pattern' => '/^1(?:(?:3[\d])|(?:4[57])|(?:[58][^4]))[\d]{8}$/', 'message' => '手机号码格式有误，手机号码格式类似：13800138000'),
			array('intro', 'length', 'max' => 255, 'encoding' => 'UTF-8', 'message' => '个人简介长度过长'),
			array('intro, mobile_no', 'safe'),
		);
	}
	
	public function attributeLabels()
	{
		return array(
			'nickname' => '昵称',
			'gender' => '性别',
			'mobile_no' => '手机号',
			'intro' => '个人简介',
		);	
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
		
	public function modify()
	{
		$info = $this->attributes();
		unset($info['errMsg']);
				
		$retcode = AccountUtil::modifyInfo(Yii::app()->user->getId(), $info);
		$info['retcode'] = $retcode;
		StatLogUtil::log("modify_profile", $info);
		
		if ($retcode !== AccountUtil::ERROR_OK)
		{
			$this->addError('errMsg', AccountUtil::$errorMessage[$retcode]);
			return false;
		}
		else 
		{
			return true;
		}
	}
}
?>