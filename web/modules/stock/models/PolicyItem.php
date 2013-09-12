<?php

/**
 * This is the model class for table "t_policy_item".
 *
 * The followings are the available columns in table 't_policy_item':
 * @property string $id
 * @property string $name
 * @property integer $vid
 * @property integer $optor
 * @property string $param
 * @property string $value
 * @property integer $pid
 * @property integer $level
 * @property integer $parent_id
 * @property integer $node_type
 * @property integer $logic
 * @property integer $update_time
 * @property integer $create_time
 * @property string $status
 */
class PolicyItem extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return PolicyItem the static model class
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
		return 't_policy_item';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('vid, optor, pid, parent_id, node_type, logic, update_time, create_time', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>64),
			array('param, value', 'length', 'max'=>255),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, vid, optor, param, value, pid, parent_id, node_type, logic, update_time, create_time, status', 'safe', 'on'=>'search'),
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
			'name' => 'Name',
			'vid' => 'Vid',
			'optor' => 'Optor',
			'param' => 'Param',
			'value' => 'Value',
			'pid' => 'Pid',
			'parent_id' => 'Parent',
			'node_type' => 'Node Type',
			'logic' => 'Logic',
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

		$criteria->compare('name',$this->name,true);

		$criteria->compare('vid',$this->vid);

		$criteria->compare('optor',$this->optor);

		$criteria->compare('param',$this->param,true);

		$criteria->compare('value',$this->value,true);

		$criteria->compare('pid',$this->pid);

		$criteria->compare('parent_id',$this->parent_id);

		$criteria->compare('node_type',$this->node_type);

		$criteria->compare('logic',$this->logic);

		$criteria->compare('update_time',$this->update_time);

		$criteria->compare('create_time',$this->create_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('PolicyItem', array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * @desc 添加条件项记录
	 *
	 * @param int $pid
	 * @param int $logic
	 * @param int $nodeType
	 * @param int $parentId
	 * @param array $fields
	 * @return int itemId
	 */
	public static function addItem($pid, $logic, $nodeType, $parentId = 0, $fields = array())
	{
		$record = new PolicyItem;
		
		foreach ($fields as $key => $value)
		{
			$record->$key = $value;	
		}
		
		$record->pid = $pid;
		$record->logic = $logic;
		$record->node_type = $nodeType;
		$record->parent_id = $parentId;		
		$record->create_time = $record->update_time = time();
		$record->status = 'Y';
		
		return $record->save()? $record->getPrimaryKey() : 0;
	}
}