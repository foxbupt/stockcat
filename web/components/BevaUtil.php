<?php

class BevaUtil
{
	public static function p3pHeader()
	{
		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
	}
	
	public static function getRemmeberLoginUrl($source = 0)
	{
		return 'http://' . Yii::app()->params['sso']['passport_domain'] . '/session/rememberLogin?isRememberUrl=1&returnUrl=http://' . Yii::app()->getRequest()->serverName . Yii::app()->getRequest()->url . '&source=' . $source;
	}
	
	public static function getRemoteIP()
	{ 
		if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
		{
			$REMOTE_ADDR = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
			$tmp_ip = explode( ",", $REMOTE_ADDR);
			$REMOTE_ADDR = $tmp_ip[0]; 
		}
		return empty($REMOTE_ADDR) ? ($_SERVER["REMOTE_ADDR"]) : ($REMOTE_ADDR);
	}
	
	public static function getRequest($param)
	{
		if(isset($_GET[$param]))
		{
			return $_GET[$param];
		}
		if(isset($_POST[$param]))
		{
			return $_POST[$param];
		}
		else 
		{
			return null;
		}
	}
	
	public static function allowIp($ip, $ipFilters)
	{
	    foreach($ipFilters as $filter)
		{
			if($filter==='*' || $filter===$ip || (($pos=strpos($filter,'*'))!==false && !strncmp($ip,$filter,$pos)))
				return true;
		}
		return false;
	}
	
	public static function getMainDomain($host = '')
	{
		if(empty($host)) $host = Yii::app()->request->serverName;
		$domain = '';
		preg_match('/([\w][\w\-]*\.(com\.cn|com|cn|co|net|org|gov|cc|biz|info|fm))$/isU', $host, $domain);
		return rtrim($domain[0], '/');
	}
	
	public static function getSubstr($content, $len, $suffix = '...')
	{
		return	mb_strlen($content, 'utf-8') > $len 
					? (mb_substr($content, 0, $len - 1, 'utf-8') . $suffix) 
					: $content;
	}
	
	/**
	 * 截断字符串，英文当作半个字符处理
	 *
	 * @param string $str
	 * @param int $len
	 * @param string $suffix
	 * @return string
	 */
    public static function getSubstrZhEn($str, $len, $suffix = '...')
    {
        $charset = 'utf-8';
        $strlen = mb_strlen($str, $charset);
        $realLen = 0;
        for($i = 0; $i < $strlen; $i++)
        {
            $word = mb_substr($str, $i, 1, $charset);
            $asc = ord($word);
            if($asc < 192)
            {
                $realLen += 0.5;
            }
            else
            {
                $realLen += 1;
            }
            if($realLen >= $len) break;
        }
        if($i < $strlen - 1)
        {
            $cutLen = $i;
            $suff = $suffix;
        }
        else
        {
            $cutLen = $strlen;
            $suff = '';
        }
        return mb_substr($str, 0, $cutLen, $charset) . $suff;
    }
    
	/**
	 * @desc 生成缩略图
	 *
	 * @param string $originalPath 源文件路径
	 * @param string $destPath 缩略图文件路径
	 * @param int $width 缩略图宽度
	 * @param int $height 缩略图高度
	 * @param bool $fixed 是否固定尺寸, 缺省为false, false表示按比例生成, true则表示固定尺寸 
	 * @return bool
	 */
	public static function generateThumb($originalPath, $destPath, $width, $height, $fixed = false)
	{
		Yii::import('common.extensions.phpthumb.PhpThumbFactory');
		
		try
		{
		     $thumb = PhpThumbFactory::create($originalPath);
		}
		catch (Exception $e)
		{
		     return false;
		}
		
        /*
		if (!$fixed) // 计算百分比
		{
			list($oriWidth, $oriHeight) = getimagesize($originalPath);
			$widthPercent = $width / $oriWidth;
			$heightPercent = $height / $oriHeight;
			
			if ($widthPercent <= $heightPercent)
			{
				$height = (intval($oriHeight * $widthPercent) < $height)? $height : intval($oriHeight * $widthPercent);
			}
			else 
			{
				$width = (intval($oriWidth * $heightPercent) < $width)? $width: intval($oriWidth * $heightPercent);
			}
        }*/
		
		try 
		{
			$fixed? $thumb->adaptiveResize($width, $height) : $thumb->resize($width, $height);
			$thumb->save($destPath);
		}
		catch (Exception $e)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * @desc 生成缓存的key
	 *
	 * @param string $prefix
	 * @param mixed $params string/array
	 * @param string $delim 连接分隔符
	 * @return string
	 */
	public static function genCacheKey($prefix, $params = array(), $delim = "-")
	{
		$key = $prefix . $delim;
		return is_array($params)? $key . implode($delim, $params) : $key . $params;
	}
	
	/**
	 * @desc 获取文件的扩展名
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function suffix($filename)
	{
		$ext = strrchr($filename, ".");
		if (FLASE === $ext)
		{
			return "";
		}
		
		return substr($ext, 1);
	}

	
	/**
	 * @desc 调整图片尺寸
	 *
	 * @param unknown_type $imagePath
	 * @param unknown_type $destPath
	 * @param unknown_type $width
	 * @param unknown_type $height
	 * @return unknown
	 */
	public static function paddingImage($srcPath,$destPath,$width,$height)
	{
	    Yii::import('common.extensions.phpthumb.ImageCrop');
		
		try
		{
		     $thumb = new ImageCrop($srcPath,$destPath);
		     $thumb->Crop($width,$height,2);
		     $thumb->SaveImage();
		     $thumb->Destory();
		}
		catch (Exception $e)
		{
		     return false;
		}
		return $destPath;
	}

	
	/**
	 * @desc 生成图片水印
	 *
	 * @param string $originalPath 源文件路径
	 * @param string $waterImage 水印图片文件
	 * @param int $rightMargin 距离右边宽度
	 * @param int $bottomMargin 距离下边高度
	 * @param string $destPath 输出目标文件路径, 为空则默认覆盖源文件
	 * @return bool
	 */
	public static function createImageWaterMark($originalPath, $waterImage, $rightMargin, $bottomMargin, $destPath = "")
	{
		Yii::import('common.extensions.phpthumb.PhpThumbFactory');
		
		try
		{
		     $thumb = PhpThumbFactory::create($originalPath);
		}
		catch (Exception $e)
		{
		     return false;
		}
		
		/*
		$thumb->createImageWatermark($waterImage, $rightMarin, $bottomMargin); 
		*/
		
		$stamp = imagecreatefrompng( $waterImage );

		$sx = imagesx($stamp);
		$sy = imagesy($stamp);

		$img = $thumb->getOldImage();
		imagecopy($img,
				  $stamp,
				  imagesx($img) - $sx - $rightMargin,
				  imagesy($img) - $sy - $bottomMargin,
				  0,
				  0,
				  imagesx($stamp),
				  imagesy($stamp));

		$thumb->setOldImage($img);
		$thumb->save(empty($destPath)? $originalPath : $destPath);
		
		return true;
	}
	
	/**
	 * @desc 生成文字水印
	 *
	 * @param string $originalPath 源文件路径
	 * @param string $text 水印文字
	 * @param int $rightMargin 距离右边宽度
	 * @param int $bottomMargin 距离下边高度
	 * @param array $options 水印选项, 可选字段包含size/angel/color/font/padding(多行文本之间的间距)
	 * @param string $destPath 输出目标文件路径, 为空则默认覆盖源文件
	 * @return bool
	 */
	public static function createTextWaterMark($originalPath, $text, $rightMargin, $bottomMargin, $options = array(), $destPath = "")
	{
		Yii::import('common.extensions.phpthumb.PhpThumbFactory');
		
		try
		{
		     $thumb = PhpThumbFactory::create($originalPath);
		}
		catch (Exception $e)
		{
		     return false;
		}
		
		// $thumb->createTextWatermark($text, $rightMargin, $bottomMargin, $options); 
		// $thumb->save(empty($destPath)? $originalPath : $destPath);
		
		$img = $thumb->getOldImage();
		$size = empty($options['size'])? 12 : $options['size'];
		$angel = empty($options['angel'])? 0 : $options['angel'];
		$font = empty($options['font'])? "arial.ttf" : $options['font'];
		$padding = empty($options['padding'])? 0 : $options['padding'];
		$color = empty($options['color'])? imagecolorallocatealpha($img, 0, 0, 0, 0) : 
				imagecolorallocatealpha($img, $options['color'][0], $options['color'][1], $options['color'][2], $options['color'][3]);
		
		// 计算每行文字的起始位置
		$textLines = explode("\r\n", $text);		
		$dimension = $thumb->getCurrentDimensions();
		$sx = $dimension['width'];
		$sy = $dimension['height'];
		$bottomPos = $sy - $bottomMargin;
		$rightPos = $sx - $rightMargin;
		//var_dump($bottomPos);
		//var_dump($rightPos);
		
		while (!empty($textLines))	// 从下往上遍历文本, 依次写到文本上
		{
			$line = array_pop($textLines);
			$position = imagettfbbox($size, $angel, $font, $line);
			
			$lineWidth = ($position[2] - $position[6]);
			$lineHeight = ($position[3] - $position[7]);
			$tx = $rightPos - $lineWidth;
			$ty = $bottomPos - $lineHeight - $padding;

			/*
			var_dump($position);
			var_dump($tx);
			var_dump($ty);
			var_dump($line);
			*/
			imagettftext($img, $size, $angel, $tx, $ty, $color, $font, $line);
			$bottomPos = $ty;
		}
		
		$thumb->setOldImage($img);
		$thumb->save(empty($destPath)? $originalPath : $destPath);		
		return true;
	}
	
	/**
	 * @desc 从服务集群中选择可用的服务配置信息
	 *
	 * @param array $servers
	 * @param int $key 可选, 缺省为0
	 * @return int 找到返回服务所在下标, 无可用则返回-1
	 */
	public static function selectServer($servers, $key = 0)
	{
		$count = count($servers);
		$initial = ($key == 0)? rand() % $count : $key % $count;
		
		do 
		{
			$index = $initial;
			// 没有设置enable 或者 设置了enable为true, 表明服务为可用状态
			if (!isset($servers[$index]['enable']) || (isset($servers[$index]['enable']) && $servers[$index]['enable']))
			{
				return $index;
			}
			
			$index = ($index + 1) % $count;
		}while ($index != $initial);
		
		return -1;
	}
}
