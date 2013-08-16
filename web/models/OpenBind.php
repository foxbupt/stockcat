<?php

/**
 * This is the model class for table "t_open_bind".
 *
 * The followings are the available columns in table 't_open_bind':
 * @property string $id
 * @property integer $open_platform
 * @property string $open_id
 * @property integer $uid
 * @property string $open_nickname
 * @property string $access_token
 * @property string $refresh_token
 * @property string $open_data
 * @property integer $bind_time
 * @property string $status
 */
class OpenBind extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return OpenBind the static model class
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
		return 't_open_bind';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('open_platform, uid, bind_time', 'numerical', 'integerOnly'=>true),
			array('open_id, open_nickname', 'length', 'max'=>32),
			array('access_token, refresh_token', 'length', 'max'=>128),
			array('open_data', 'length', 'max'=>512),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, open_platform, open_id, uid, open_nickname, access_token, refresh_token, open_data, bind_time, status', 'safe', 'on'=>'search'),
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
			'open_platform' => 'Open Platform',
			'open_id' => 'Open',
			'uid' => 'Uid',
			'open_nickname' => 'Open Nickname',
			'access_token' => 'Access Token',
			'refresh_token' => 'Refresh Token',
			'open_data' => 'Open Data',
			'bind_time' => 'Bind Time',
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

		$criteria->compare('open_platform',$this->open_platform);

		$criteria->compare('open_id',$this->open_id,true);

		$criteria->compare('uid',$this->uid);

		$criteria->compare('open_nickname',$this->open_nickname,true);

		$criteria->compare('access_token',$this->access_token,true);

		$criteria->compare('refresh_token',$this->refresh_token,true);

		$criteria->compare('open_data',$this->open_data,true);

		$criteria->compare('bind_time',$this->bind_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('OpenBind', array(
			'criteria'=>$criteria,
		));
	}
}