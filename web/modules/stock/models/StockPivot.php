<?php

/**
 * This is the model class for table "t_stock_pivot".
 *
 * The followings are the available columns in table 't_stock_pivot':
 * @property string $id
 * @property integer $sid
 * @property integer $day
 * @property integer $trend
 * @property string $close_price
 * @property string $resist
 * @property string $resist_vary_portion
 * @property string $support
 * @property string $support_vary_portion
 * @property integer $create_time
 * @property string $status
 */
class StockPivot extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockPivot the static model class
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
		return 't_stock_pivot';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, trend, create_time', 'numerical', 'integerOnly'=>true),
			array('close_price, resist, support', 'length', 'max'=>10),
			array('resist_vary_portion, support_vary_portion', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, trend, close_price, resist, resist_vary_portion, support, support_vary_portion, create_time, status', 'safe', 'on'=>'search'),
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
			'trend' => 'Trend',
			'close_price' => 'Close Price',
			'resist' => 'Resist',
			'resist_vary_portion' => 'Resist Vary Portion',
			'support' => 'Support',
			'support_vary_portion' => 'Support Vary Portion',
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

		$criteria->compare('trend',$this->trend);

		$criteria->compare('close_price',$this->close_price,true);

		$criteria->compare('resist',$this->resist,true);

		$criteria->compare('resist_vary_portion',$this->resist_vary_portion,true);

		$criteria->compare('support',$this->support,true);

		$criteria->compare('support_vary_portion',$this->support_vary_portion,true);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockPivot', array(
			'criteria'=>$criteria,
		));
	}
}