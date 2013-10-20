<?php

/**
 * This is the model class for table "t_user_policy".
 *
 * The followings are the available columns in table 't_user_policy':
 * @property string $id
 * @property integer $type
 * @property string $name
 * @property string $remark
 * @property string $expression
 * @property integer $uid
 * @property integer $update_time
 * @property integer $create_time
 * @property string $status
 */
class PolicyInfo extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return PolicyInfo the static model class
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
		return 't_policy';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, uid, root_item, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>128),
			array('remark', 'length', 'max'=>1024),
			// array('expression', 'length', 'max'=>2048),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, name, remark, expression, uid, root_item, update_time, create_time, status', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'name' => 'Name',
			'remark' => 'Remark',
			'expression' => 'Expression',
			'uid' => 'Uid',
			'root_item' => 'RootItem',
			'update_time' => 'Update Time',
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

		$criteria->compare('name',$this->name,true);

		$criteria->compare('remark',$this->remark,true);

		$criteria->compare('expression',$this->expression,true);

		$criteria->compare('uid',$this->uid);
		
		$criteria->compare('root_item',$this->root_item);
		
		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('PolicyInfo', array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * @desc 获取用户的分析器列表
	 *
	 * @param int $uid
	 * @return array
	 */
	public static function getUserPolicyList($uid)
	{
		$data = array();
		
		$recordList = self::model()->findAllByAttributes(array('uid' => $uid, 'status' => 'Y'));
		foreach ($recordList as $record)
		{
			$data[] = $record->getAttributes();
		}
		
		return $data;
	}
}