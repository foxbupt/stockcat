<?php

/**
 * This is the model class for table "t_stock_event".
 *
 * The followings are the available columns in table 't_stock_event':
 * @property string $id
 * @property integer $sid
 * @property integer $event_date
 * @property string $title
 * @property string $content
 * @property integer $trend
 * @property integer $create_time
 * @property string $status
 */
class StockEvent extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return StockEvent the static model class
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
		return 't_stock_event';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			// array('content', 'required'),
			array('sid, event_date, trend, create_time', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>255),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, sid, event_date, title, content, trend, create_time, status', 'safe', 'on'=>'search'),
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
			'sid' => '股票Id',
			'event_date' => '公告日期',
			'title' => '公告标题',
			'content' => '公告详情',
			'trend' => '公告影响',
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

		$criteria->compare('event_date',$this->event_date);

		$criteria->compare('title',$this->title,true);

		$criteria->compare('content',$this->content,true);

		$criteria->compare('trend',$this->trend);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('StockEvent', array(
			'criteria'=>$criteria,
		));
	}
}
