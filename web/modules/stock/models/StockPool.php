<?php

/**
 * This is the model class for table "t_stock_pool".
 *
 * The followings are the available columns in table 't_stock_pool':
 * @property string $id
 * @property integer $sid
 * @property integer $day
 * @property string $current_price
 * @property string $volume_ratio
 * @property string $rise_factor
 * @property integer $trend
 * @property integer $wave
 * @property integer $source
 * @property integer $rank
 * @property integer $create_time
 * @property string $status
 */
class StockPool extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockPool the static model class
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
		return 't_stock_pool';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, trend, wave, source, rank, create_time', 'numerical', 'integerOnly'=>true),
			array('close_price', 'length', 'max'=>10),
			array('volume_ratio, rise_factor', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, current_price, volume_ratio, rise_factor, trend, wave, source, rank, create_time, status', 'safe', 'on'=>'search'),
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
			'close' => 'Close Price',
			'volume_ratio' => 'Volume Ratio',
			'rise_factor' => 'Rise Factor',
			'trend' => 'Trend',
			'wave' => 'Wave',
			'source' => 'Source',
			'rank' => 'Rank',
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

		$criteria->compare('close_price',$this->close_price,true);

		$criteria->compare('volume_ratio',$this->volume_ratio,true);

		$criteria->compare('rise_factor',$this->rise_factor,true);

		$criteria->compare('trend',$this->trend);

		$criteria->compare('wave',$this->wave);

		$criteria->compare('source',$this->source);

		$criteria->compare('rank',$this->rank);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockPool', array(
			'criteria'=>$criteria,
		));
	}
}