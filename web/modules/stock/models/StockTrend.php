<?php

/**
 * This is the model class for table "t_stock_trend".
 *
 * The followings are the available columns in table 't_stock_trend':
 * @property string $id
 * @property integer $sid
 * @property integer $type
 * @property integer $start_day
 * @property integer $end_day
 * @property string $high
 * @property string $low
 * @property string $start_value
 * @property string $end_value
 * @property integer $trend
 * @property integer $create_time
 * @property string $status
 */
class StockTrend extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockTrend the static model class
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
		return 't_stock_trend';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, type, start_day, end_day, trend, create_time', 'numerical', 'integerOnly'=>true),
			array('high, low, start_value, end_value', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, type, start_day, end_day, high, low, start_value, end_value, trend, create_time, status', 'safe', 'on'=>'search'),
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
			'sid' => '名称',
			'type' => '类型',
			'start_day' => '起始日期',
			'end_day' => '结束日期 ',
			'high' => '最高值',
			'low' => '最低值',
			'start_value' => '起始值',
			'end_value' => '结束值',
			'trend' => '趋势',
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

		$criteria->compare('type',$this->type);

		$criteria->compare('start_day',$this->start_day);

		$criteria->compare('end_day',$this->end_day);

		$criteria->compare('high',$this->high,true);

		$criteria->compare('low',$this->low,true);

		$criteria->compare('start_value',$this->start_value,true);

		$criteria->compare('end_value',$this->end_value,true);

		$criteria->compare('trend',$this->trend);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockTrend', array(
			'criteria'=>$criteria,
		));
	}
}
