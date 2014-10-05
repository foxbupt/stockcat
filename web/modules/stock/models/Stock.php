<?php

/**
 * This is the model class for table "t_stock".
 *
 * The followings are the available columns in table 't_stock':
 * @property string $id
 * @property integer $type
 * @property string $code
 * @property string $name
 * @property string $pinyin
 * @property string $alias
 * @property integer $location
 * @property string $ecode
 * @property string $company
 * @property string $business
 * @property string $capital
 * @property string $out_capital
 * @property string $profit
 * @property string $assets
 * @property string $hist_high
 * @property string $hist_low
 * @property string $year_high
 * @property string $year_low
 * @property string $month6_high
 * @property string $month6_low
 * @property string $month3_high
 * @property string $month3_low
 * @property integer $create_time
 * @property string $status
 */
class Stock extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Stock the static model class
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
		return 't_stock';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, location, ecode, create_time', 'numerical', 'integerOnly'=>true),
			array('capital, out_capital, hist_high, hist_low, year_high, year_low, month6_high, month6_low, month3_high, month3_low', 'length', 'max'=>6),
			array('profit, assets, dividend', 'length', 'max'=>8),
			array('name', 'length', 'max'=>64),
			array('code', 'length', 'max'=>12),
			array('alias', 'length', 'max'=>64),
			array('pinyin', 'length', 'max'=>8),
			array('company', 'length', 'max'=>256),
			array('business', 'length', 'max'=>2048),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, code, name, pinyin, alias, location, ecode, company, business, capital, out_capital, profit, assets, hist_high, hist_low, year_high, year_low, month6_high, month6_low, month3_high, month3_low, create_time, status', 'safe', 'on'=>'search'),
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
			'id' => '股票Id',
			'type' => '类型',
			'code' => '代码',
			'name' => '名称',
			'pinyin' => '拼音简写',
			'alias' => '别名',
			'location' => '国家',
			'ecode' => '交易所',
			'company' => '公司名称',
			'business' => '主营业务',
			'capital' => '总股本(亿)',
			'out_capital' => '流通股本(亿)',
			'profit' => '每股净收益(元)',
			'assets' => '每股净资产(元)',
			'hist_high' => '历史最高价(元)',
			'hist_low' => '历史最低价(元)',
			'year_high' => '年内最高价(元)',
			'year_low' => '年内最低价(元)',
			'month6_high' => '60天内最高价(元)',
			'month6_low' => '60天内最低价(元)',
			'month3_high' => '30天内最高价(元)',
			'month3_low' => '30天内最低价(元)',
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

		$criteria->compare('type',$this->type);

		$criteria->compare('code',$this->code,true);

		$criteria->compare('name',$this->name,true);

		$criteria->compare('pinyin',$this->pinyin,true);

		$criteria->compare('alias',$this->alias,true);

        $criteria->compare('location',$this->alias,true);
        
		$criteria->compare('ecode',$this->ecode,true);

		$criteria->compare('company',$this->company,true);

		$criteria->compare('business',$this->business,true);

		$criteria->compare('capital',$this->capital,true);

		$criteria->compare('out_capital',$this->out_capital,true);

		$criteria->compare('profit',$this->profit,true);

		$criteria->compare('assets',$this->assets,true);

		$criteria->compare('hist_high',$this->hist_high,true);

		$criteria->compare('hist_low',$this->hist_low,true);

		$criteria->compare('year_high',$this->year_high,true);

		$criteria->compare('year_low',$this->year_low,true);

		$criteria->compare('month6_high',$this->month6_high,true);

		$criteria->compare('month6_low',$this->month6_low,true);

		$criteria->compare('month3_high',$this->month3_high,true);

		$criteria->compare('month3_low',$this->month3_low,true);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('Stock', array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * @desc 添加股票记录
	 *
	 * @param string $code
	 * @param string $name
	 * @param int $type
	 * @param array $fields
	 * @return int
	 */
	public static function addStock($code, $name, $type, $fields = array())
	{
		$record = new Stock();
		$record->code = $code;
		$record->name = $name;
		$record->type = $type;
		
		foreach ($fields as $key => $value)
		{
			$record->$key = $value;
		}
		
		$record->create_time = time();
		$record->status = 'Y';
		
		return $record->save()? $record->getPrimaryKey() : 0;
	}	
}
