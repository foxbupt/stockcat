<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	public $layout = "//layouts/main";
	
	public $pageTitle = "";
	public $keywords = "贝瓦网,儿歌,儿歌视频大全,贝瓦儿歌,大头贴,淘奇包,儿童";
	public $description = '贝瓦网通过贝瓦儿歌、儿歌视频大全、儿歌大头贴、儿童故事、早教淘奇包、儿童小游戏、儿童舞蹈等优质产品为中国儿童提供全方位的教育资源，助力儿童成长，成就美好未来。';

	/**
	 * @desc 获取Model的错误信息显示第一个, 没有则显示默认错误信息
	 *
	 * @param CFormModel $model
	 * @param string $default
	 * @param bool $ignoreField 缺省不返回field
	 * @return string
	 */
	public function getModelError($model, $default = "", $ignoreField = true)
	{
		$field = "";
		$msg = $default;
		
		$errors = $model->getErrors();
		foreach ($errors as $key => $error)
		{
			$field = $key;
			$msg = $error[0];
			break;
		}
		
		return $ignoreField? $msg : array('field' => $field, 'msg' => $msg);
	}
	
	/**
	 * @desc 显示Model的所有错误信息
	 *
	 * @param CFormModel $model
	 * @param string $default
	 * @return array
	 */
	public function getModelAllErrors($model, $default = "")
	{
		$msgs = array();
		
		$errors = $model->getErrors();
		foreach ($errors as $error)
		{
			$msgs[] = $error[0];
			break;
		}
		
		return empty($msgs)? array($default) : $msgs;
	}
	
	/**
	 * @desc 为下拉选择框数据自动添加请选择
	 *
	 * @param array $list
	 * @param mixed $value
	 * @param string $text
	 * @return array
	 */
	public function encodeDropdownList($list, $value = 0, $text = "请选择")
	{
		$result = $list;
		$result[$value] = $text;
		
		ksort($result, is_string($value)? SORT_STRING : SORT_NUMERIC);
		return $result;
	}
}