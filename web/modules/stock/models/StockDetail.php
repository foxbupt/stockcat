<?php

/**
 * This is the model class for table "t_stock_detail".
 *
 * The followings are the available columns in table 't_stock_detail':
 * @property string $id
 * @property integer $sid
 * @property integer $day
 * @property integer $time
 * @property string $price
 * @property string $avg_price
 * @property integer $volume
 * @property string $swing
 * @property integer $create_time
 * @property string $status
 */
class StockDetail extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockDetail the static model class
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
		return 't_stock_detail';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, time, volume, create_time', 'numerical', 'integerOnly'=>true),
			array('price, avg_price, swing', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, time, price, avg_price, volume, swing, create_time, status', 'safe', 'on'=>'search'),
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
			'sid' => 'Sid',
			'day' => 'Day',
			'time' => 'Time',
			'price' => 'Price',
			'avg_price' => 'Avg Price',
			'volume' => 'Volume',
			'swing' => 'Swing',
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

		$criteria->compare('sid',$this->sid);

		$criteria->compare('day',$this->day);

		$criteria->compare('time',$this->time);

		$criteria->compare('price',$this->price,true);

		$criteria->compare('avg_price',$this->avg_price,true);

		$criteria->compare('volume',$this->volume);

		$criteria->compare('swing',$this->swing,true);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockDetail', array(
			'criteria'=>$criteria,
		));
	}
}