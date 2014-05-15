<?php

/**
 * This is the model class for table "t_stock_cont".
 *
 * The followings are the available columns in table 't_stock_cont':
 * @property string $id
 * @property integer $sid
 * @property string $name
 * @property integer $day
 * @property integer $trend
 * @property integer $wave
 * @property integer $start_day
 * @property integer $cont_days
 * @property string $current_price
 * @property string $sum_price_vary_amount
 * @property string $sum_price_vary_portion
 * @property string $max_volume_vary_portion
 * @property integer $score
 * @property integer $add_time
 * @property string $status
 */
class StockCont extends CActiveRecord
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
		return 't_stock_cont';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('sid, day, trend, wave, start_day, cont_days, score, add_time', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>32),
			array('current_price, sum_price_vary_amount, sum_price_vary_portion, max_volume_vary_portion', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, name, day, trend, wave, start_day, cont_days, current_price, sum_price_vary_amount, sum_price_vary_portion, max_volume_vary_portion, score, add_time, status', 'safe', 'on'=>'search'),
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
			'sid' => '代码',
			'name' => '名称',
			'day' => '日期',
			'trend' => '整体趋势',
			'wave' => '当前波段',
			'start_day' => '起始日期',
			'cont_days' => '持续天数',
			'current_price' => '当日收盘价',
			'sum_price_vary_amount' => '价格累计金额',
			'sum_price_vary_portion' => '价格累计幅度',
			'max_volume_vary_portion' => '最大成交量变化',
			'score' => '评分',
			'add_time' => '添加时间',
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

		$criteria->compare('name',$this->name,true);

		$criteria->compare('day',$this->day);

		$criteria->compare('trend',$this->trend);

		$criteria->compare('wave',$this->wave);

		$criteria->compare('start_day',$this->start_day);

		$criteria->compare('cont_days',$this->cont_days);

		$criteria->compare('current_price',$this->current_price,true);

		$criteria->compare('sum_price_vary_amount',$this->sum_price_vary_amount,true);

		$criteria->compare('sum_price_vary_portion',$this->sum_price_vary_portion,true);

		$criteria->compare('max_volume_vary_portion',$this->max_volume_vary_portion,true);

		$criteria->compare('score',$this->score);

		$criteria->compare('add_time',$this->add_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockPool', array(
			'criteria'=>$criteria,
		));
	}
}
