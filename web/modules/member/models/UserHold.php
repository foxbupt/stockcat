<?php

/**
 * This is the model class for table "t_user_hold".
 *
 * The followings are the available columns in table 't_user_hold':
 * @property string $id
 * @property string $batch_no
 * @property integer $uid
 * @property integer $sid
 * @property integer $count
 * @property string $price
 * @property string $cost
 * @property string $amount
 * @property string $profit
 * @property string $profit_portion
 * @property integer $update_time
 * @property integer $create_time
 * @property string $status
 */
class UserHold extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserHold the static model class
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
		return 't_user_hold';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('uid, sid, count, day, state, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('batch_no, day, close_day', 'length', 'max'=>11),
			array('price, profit_portion', 'length', 'max'=>6),
			array('cost, amount, profit', 'length', 'max'=>10),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, batch_no, uid, sid, count, day, close_day, state, price, cost, amount, profit, profit_portion, update_time, create_time, status', 'safe', 'on'=>'search'),
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
			'batch_no' => 'Batch No',
			'uid' => 'Uid',
			'sid' => 'Sid',
			'day' => 'Day',
			'close_day' => 'CloseDay',
			'count' => 'Count',
			'state' => 'State',
			'price' => 'Price',
			'cost' => 'Cost',
			'amount' => 'Amount',
			'profit' => 'Profit',
			'profit_portion' => 'Profit Portion',
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

		$criteria->compare('batch_no',$this->batch_no,true);

		$criteria->compare('uid',$this->uid);

		$criteria->compare('sid',$this->sid);

		$criteria->compare('count',$this->count);

		$criteria->compare('price',$this->price,true);

		$criteria->compare('cost',$this->cost,true);

		$criteria->compare('amount',$this->amount,true);

		$criteria->compare('profit',$this->profit,true);

		$criteria->compare('profit_portion',$this->profit_portion,true);

		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('UserHold', array(
			'criteria'=>$criteria,
		));
	}
}