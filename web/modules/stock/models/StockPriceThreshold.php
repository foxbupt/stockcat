<?php

/**
 * This is the model class for table "t_stock_price_threshold".
 *
 * The followings are the available columns in table 't_stock_price_threshold':
 * @property string $id
 * @property integer $sid
 * @property integer $day
 * @property string $price
 * @property integer $low_type
 * @property integer $high_type
 * @property integer $create_time
 * @property string $status
 */
class StockPriceThreshold extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockPriceThreshold the static model class
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
		return 't_stock_price_threshold';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, low_type, high_type, create_time', 'numerical', 'integerOnly'=>true),
			array('price', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, price, low_type, high_type, create_time, status', 'safe', 'on'=>'search'),
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
			'price' => '收盘价',
			'low_type' => '新低类型',
			'high_type' => '新高类型',
			'create_time' => '创建时间',
			'status' => '有效状态',
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

		$criteria->compare('price',$this->price,true);

		$criteria->compare('low_type',$this->low_type);

		$criteria->compare('high_type',$this->high_type);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockPriceThreshold', array(
			'criteria'=>$criteria,
		));
	}
}
