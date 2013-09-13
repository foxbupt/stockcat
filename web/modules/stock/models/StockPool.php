<?php

/**
 * This is the model class for table "t_stock_pool".
 *
 * The followings are the available columns in table 't_stock_pool':
 * @property string $id
 * @property integer $sid
 * @property string $name
 * @property integer $day
 * @property integer $trend
 * @property integer $wave
 * @property string $current_price
 * @property string $low_price
 * @property string $high_price
 * @property integer $score
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
			array('sid, day, trend, wave, score, create_time', 'numerical', 'integerOnly'=>true),
			array('current_price, low_price, high_price', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, day, trend, wave, current_price, low_price, high_price, score, create_time, status', 'safe', 'on'=>'search'),
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
			'sid' => '代码',
			'day' => '日期',
			'trend' => '趋势',
			'wave' => '波段',
			'current_price' => '当前价格',
			'low_price' => '买入最低价',
			'high_price' => '买入最高价',
			'score' => '评分',
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

		$criteria->compare('trend',$this->trend);

		$criteria->compare('wave',$this->wave);

		$criteria->compare('current_price',$this->current_price,true);

		$criteria->compare('low_price',$this->low_price,true);

		$criteria->compare('high_price',$this->high_price,true);

		$criteria->compare('score',$this->score);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockPool', array(
			'criteria'=>$criteria,
		));
	}
}
