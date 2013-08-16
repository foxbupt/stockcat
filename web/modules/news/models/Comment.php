<?php

/**
 * This is the model class for table "t_comment".
 *
 * The followings are the available columns in table 't_comment':
 * @property string $id
 * @property integer $nid
 * @property string $content
 * @property integer $uid
 * @property string $nickname
 * @property integer $comment_ip
 * @property integer $comment_time
 * @property string $status
 */
class Comment extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Comment the static model class
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
		return 't_comment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('content', 'required'),
			array('nid, uid, comment_ip, comment_time', 'numerical', 'integerOnly'=>true),
			array('nickname', 'length', 'max'=>255),
			array('status', 'length', 'max'=>1),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, nid, content, uid, nickname, comment_ip, comment_time, status', 'safe', 'on'=>'search'),
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
			'nid' => 'Nid',
			'content' => 'Content',
			'uid' => 'Uid',
			'nickname' => 'Nickname',
			'comment_ip' => 'Comment Ip',
			'comment_time' => 'Comment Time',
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

		$criteria->compare('nid',$this->nid);

		$criteria->compare('content',$this->content,true);

		$criteria->compare('uid',$this->uid);

		$criteria->compare('nickname',$this->nickname,true);

		$criteria->compare('comment_ip',$this->comment_ip);

		$criteria->compare('comment_time',$this->comment_time);

		$criteria->compare('status',$this->status,true);

		return new CActiveDataProvider('Comment', array(
			'criteria'=>$criteria,
		));
	}
}