<?php

/**
 * This is the model class for table "t_policy_var".
 *
 * The followings are the available columns in table 't_policy_var':
 * @property string $id
 * @property string $code
 * @property string $name
 * @property integer $type
 * @property string $expression
 * @property integer $add_time
 * @property string $status
 */
class PolicyVar extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return PolicyVar the static model class
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
		return 't_policy_var';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, add_time', 'numerical', 'integerOnly'=>true),
			array('code', 'length', 'max'=>64),
			array('name, expression', 'length', 'max'=>255),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, code, name, type, expression, add_time, status', 'safe', 'on'=>'search'),
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
			'code' => '唯一编码',
			'name' => '名称',
			'type' => '类型',
			'expression' => '表达式',
			'add_time' => '添加时间',
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

		$criteria->compare('code',$this->code,true);

		$criteria->compare('name',$this->name,true);

		$criteria->compare('type',$this->type);

		$criteria->compare('expression',$this->expression,true);

		$criteria->compare('add_time',$this->add_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('PolicyVar', array(
			'criteria'=>$criteria,
		));
	}
}