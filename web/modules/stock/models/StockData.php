<?php

/**
 * This is the model class for table "t_stock_data".
 *
 * The followings are the available columns in table 't_stock_data':
 * @property string $id
 * @property integer $sid
 * @property integer $day
 * @property string $open_price
 * @property string $high_price
 * @property string $low_price
 * @property string $close_price
 * @property integer $volume
 * @property integer $amount
 * @property string $vary_price
 * @property string $vary_portion
 * @property integer $create_time
 * @property string $status
 */
class StockData extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockData the static model class
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
		return 't_stock_data';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, volume, amount, create_time', 'numerical', 'integerOnly'=>true),
			array('open_price, high_price, low_price, close_price, vary_price, vary_portion', 'length', 'max'=>10),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, open_price, high_price, low_price, close_price, volume, amount, vary_price, vary_portion, create_time, status', 'safe', 'on'=>'search'),
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
            'stock' => array(self::BELONGS_TO, 'Stock', 'sid'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'Id',
			'sid' => '股票名称',
			'day' => '日期',
			'open_price' => '开盘价(元)',
			'high_price' => '最高价(元)',
			'low_price' => '最低价(元)',
			'close_price' => '收盘价(元)',
			'volume' => '成交量(手)',
			'amount' => '成交金额(万元)',
			'vary_price' => '涨跌额(元)',
			'vary_portion' => '涨跌比例',
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

		$criteria->compare('open_price',$this->open_price,true);

		$criteria->compare('high_price',$this->high_price,true);

		$criteria->compare('low_price',$this->low_price,true);

		$criteria->compare('close_price',$this->close_price,true);

		$criteria->compare('volume',$this->volume);

		$criteria->compare('amount',$this->amount);

		$criteria->compare('vary_price',$this->vary_price,true);

		$criteria->compare('vary_portion',$this->vary_portion,true);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockData', array(
			'criteria'=>$criteria,
		));
	}
}
