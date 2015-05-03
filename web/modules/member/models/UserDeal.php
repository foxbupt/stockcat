<?php

/**
 * This is the model class for table "t_user_deal".
 *
 * The followings are the available columns in table 't_user_deal':
 * @property string $id
 * @property integer $batch_no
 * @property integer $uid
 * @property integer $sid
 * @property integer $day
 * @property integer $deal_type
 * @property integer $count
 * @property string $price
 * @property string $fee
 * @property string $commission
 * @property string $tax
 * @property string $amount
 * @property string $remark
 * @property integer $create_time
 * @property string $status
 */
class UserDeal extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return UserDeal the static model class
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
		return 't_user_deal';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('batch_no, uid, sid, day, deal_type, count, create_time', 'numerical', 'integerOnly'=>true),
			array('price', 'length', 'max'=>6),
			array('fee, commission, tax, amount', 'length', 'max'=>10),
			array('remark', 'length', 'max'=>1024),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, batch_no, uid, sid, day, deal_type, count, price, fee, commission, tax, amount, remark, create_time, status', 'safe', 'on'=>'search'),
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
			'deal_type' => 'Deal Type',
			'count' => 'Count',
			'price' => 'Price',
			'fee' => 'Fee',
			'commission' => 'Commission',
			'tax' => 'Tax',
			'amount' => 'Amount',
			'remark' => 'Remark',
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

		$criteria->compare('batch_no',$this->batch_no);

		$criteria->compare('uid',$this->uid);

		$criteria->compare('sid',$this->sid);

		$criteria->compare('day',$this->day);

		$criteria->compare('deal_type',$this->deal_type);

		$criteria->compare('count',$this->count);

		$criteria->compare('price',$this->price,true);

		$criteria->compare('fee',$this->fee,true);

		$criteria->compare('commission',$this->commission,true);

		$criteria->compare('tax',$this->tax,true);

		$criteria->compare('amount',$this->amount,true);

		$criteria->compare('remark',$this->remark,true);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('UserDeal', array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * @desc 添加交易记录
	 *
	 * @param int $dealType
	 * @param array $params array('uid', 'sid', 'batchno', 'day', 'count', 'price', 'fee', 'commision', 'tax', 'amount')
	 * @return bool
	 */
	public static function addDealRecord($dealType, $params)
	{
		$record = new UserDeal();
		foreach ($params as $fieldName => $fieldValue)
		{
			$record->$fieldName = $fieldValue;	
		}
		$record->create_time = time();
		$record->status = 'Y';
		
		return $record->save();
	}
}