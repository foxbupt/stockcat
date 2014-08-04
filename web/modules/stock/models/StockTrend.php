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
 * @property integer $count
 * @property string $high
 * @property string $low
 * @property string $start_value
 * @property string $end_value
 * @property integer $trend
 * @property integer $shave
 * @property integer $update_time
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
			array('sid, type, start_day, end_day, count, high_day, low_day, trend, shave, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('high, low, start_value, end_value, vary_portion', 'length', 'max'=>6),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, type, start_day, end_day, count, high_day, low_day, high, low, start_value, end_value, vary_portion, trend, shave, update_time, create_time, status', 'safe', 'on'=>'search'),
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
			'sid' => '名称',
			'type' => '类型',
			'start_day' => '起始日期',
			'end_day' => '结束日期',
			'count' => '天数',
			'high_day' => '最高价日期',
			'low_day' => '最低价日期',
			'high' => '最高价',
			'low' => '最低价',
			'start_value' => '起始价',
			'end_value' => '结束价',
			'vary_portion' => '涨跌幅',
			'trend' => '趋势',
			'shave' => '震荡',
			'update_time' => '修改时间',
			'create_time' => '创建时间',
			'status' => '有效状态',
		);
	}

    public function beforeSave()
    {
        if ($this->isNewRecord)
        {
            $this->create_time = $this->update_time = time();
        }
        else
        {
            $this->update_time = time();
        }

        return parent::beforeSave();
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

		$criteria->compare('count',$this->count);

		$criteria->compare('high_day',$this->high_day,true);
		
		$criteria->compare('low_day',$this->low_day,true);
		
		$criteria->compare('high',$this->high,true);

		$criteria->compare('low',$this->low,true);

		$criteria->compare('start_value',$this->start_value,true);

		$criteria->compare('end_value',$this->end_value,true);

		$criteria->compare('vary_portion',$this->vary_portion,true);

		$criteria->compare('trend',$this->trend);

		$criteria->compare('shave',$this->shave);

		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockTrend', array(
			'criteria'=>$criteria,
		));
	}
	
}
