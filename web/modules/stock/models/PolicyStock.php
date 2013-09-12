<?php

/**
 * This is the model class for table "t_policy_stock".
 *
 * The followings are the available columns in table 't_policy_stock':
 * @property string $id
 * @property integer $uid
 * @property integer $pid
 * @property integer $sid
 * @property integer $day
 * @property integer $score
 * @property integer $update_time
 * @property integer $create_time
 * @property string $status
 */
class PolicyStock extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return PolicyStock the static model class
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
		return 't_policy_stock';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, pid, sid, day, score, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, uid, pid, sid, day, score, update_time, create_time, status', 'safe', 'on'=>'search'),
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
			'uid' => 'Uid',
			'pid' => 'Pid',
			'sid' => 'Sid',
			'day' => 'Day',
			'score' => 'Score',
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

		$criteria->compare('uid',$this->uid);

		$criteria->compare('pid',$this->pid);

		$criteria->compare('sid',$this->sid);

		$criteria->compare('day',$this->day);

		$criteria->compare('score',$this->score);

		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('PolicyStock', array(
			'criteria'=>$criteria,
		));
	}
}