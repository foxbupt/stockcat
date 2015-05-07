<?php

/**
 * This is the model class for table "t_account".
 *
 * The followings are the available columns in table 't_account':
 * @property string $id
 * @property string $email
 * @property integer $type
 * @property string $salt
 * @property string $password
 * @property string $nickname
 * @property string $gender
 * @property string $intro
 * @property string $mobile_no
 * @property integer $birthday
 * @property string $active_code
 * @property string $referer_code
 * @property integer $register_ip
 * @property integer $update_time
 * @property integer $create_time
 * @property string $status
 */
class Account extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Account the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 't_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, birthday, register_ip, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('email', 'length', 'max'=>128),
			array('salt', 'length', 'max'=>6),
			array('password, active_code, referer_code', 'length', 'max'=>32),
			array('nickname', 'length', 'max'=>64),
			array('gender, status', 'length', 'max'=>1),
			array('intro', 'length', 'max'=>255),
			array('mobile_no', 'length', 'max'=>16),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, email, type, salt, password, nickname, gender, intro, mobile_no, birthday, active_code, referer_code, register_ip, update_time, create_time, status', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'Id',
			'email' => 'Email',
			'type' => 'Type',
			'salt' => 'Salt',
			'password' => 'Password',
			'nickname' => 'Nickname',
			'gender' => 'Gender',
			'intro' => 'Intro',
			'mobile_no' => 'Mobile No',
			'birthday' => 'Birthday',
			'active_code' => 'Active Code',
			'referer_code' => 'Referer Code',
			'register_ip' => 'Register Ip',
			'update_time' => 'Update Time',
			'create_time' => 'Create Time',
			'status' => 'Status',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);

		$criteria->compare('email',$this->email,true);

		$criteria->compare('type',$this->type);

		$criteria->compare('salt',$this->salt,true);

		$criteria->compare('password',$this->password,true);

		$criteria->compare('nickname',$this->nickname,true);

		$criteria->compare('gender',$this->gender,true);

		$criteria->compare('intro',$this->intro,true);

		$criteria->compare('mobile_no',$this->mobile_no,true);

		$criteria->compare('birthday',$this->birthday);

		$criteria->compare('active_code',$this->active_code,true);

		$criteria->compare('referer_code',$this->referer_code,true);

		$criteria->compare('register_ip',$this->register_ip);

		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('Account', array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * @desc 检查email是否存在
	 *
	 * @param string $email
	 * @return bool
	 */
	public static function checkEmailExist($email)
	{
		return Account::model()->count(array(
									"condition" => "email = :email and status != 'N'",
									"params" => array('email' => $email),
						)) >= 1;
	}
	
	/**
	 * @desc 获取uid对应的记录
	 *
	 * @param int $uid
	 * @return Account/null
	 */
	public static function getRecord($uid)
	{
		return Account::model()->findByPk($uid, "status != 'N'");
	}
	
	/**
	 * @desc 根据email查找对应的记录
	 *
	 * @param string $email
	 * @return Account/null
	 */
	public static function getRecordByEmail($email)
	{
		return Account::model()->find(array(
								'condition' => "email = :email and status != 'N'",
								"params" => array('email' => $email),
							));
	}
}