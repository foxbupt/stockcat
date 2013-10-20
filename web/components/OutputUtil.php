<?php

class OutputUtil extends CComponent 
{
	/**
	 * JSON输出
	 *
	 * @param array $data
	 * @param int $retcode
	 * @param string $retmsg
	 * @return string
	 */
	public static function json($data, $retcode = 0, $retmsg = 'success')
	{
		return json_encode(array(
			'errorCode' => $retcode,
			'message' => $retmsg,
			'data' => $data, 
		));
	}
	
	public static $imgTypeMap = array(
		'1' => 'GIF图片',
		'2' => 'JPG图片',
		'3' => 'PNG图片',
		'4' => 'SWF FLASH文件',
		'6' => 'BMP图片',
		'13' => 'FLASH文件',
	);
	
	/**
	 * 获取image数据
	 *
	 * @param string $imagePath like static/a/b/c.jpg
	 */
	public static function imageData($imagePath)
	{
		$path = Yii::app()->params['staticPath'] . $imagePath;
		if(!file_exists($path))
		{
			return null;
		}
		list($width, $height, $type, $attr) = getimagesize($path);
		$bytes = filesize($path);
		return array(
			'file' => $imagePath,
			'filename' => basename($imagePath),
			'width' => $width,
			'height' => $height,
			'size' => (int)($bytes / 1000) . 'K',
			'type' => self::$imgTypeMap[$type] ? self::$imgTypeMap[$type] : '未知',
			'attr' => $attr,
		);
	}
}