<?php

class HtmlUtil
{
	const USER_AGENT = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)";
	
	/**
	 * @desc 取出html代码一行中的文本
	 * @param str: <td .... > text </td> 单个object的html代码, 可以为多行
	 * @return Array('name' => 'td',
	 * 				 'attr' => array(name => value, ...),
	 * 				 'value' => 'xxx',
	 * 				 'object_list' => array()	
	 * 				)
	 */
	public static function parseNode($str)
	{
		if (preg_match("/<(\w+) (.*)>([^<>]*)<\/(1)>/", $str, $match))
		{
			//print_r($match);
			$object = array();
			$object['name'] = $match[1];
			$object['attr'] = array();
			
			$tmp = split(" ", $match[2]);
			foreach ($tmp as $elem)
			{
				$pair = split("=", $elem);
				$object['attr'][trim($pair[0], " ")] = trim($pair[1], '"');
			}
			//print_r($object);
			
			$object['value'] = $match[3];
			
		}
		else
		{
			return $str;
		}
		
		return $object;
	}
	
	/*
	 * @desc 获取内容中包含href前缀的所有链接
	 * @param content string
	 * @param hrefPrefix string
	 * @return array
	 */
	public static function fetchHref($content, $hrefPrefix)
	{
		$urls = array();
		
		// TODO: 匹配链接这里还需要优化
		//$pattern = '|href="?(' . $hrefPrefix . '(?:\w+(?:\.\w+)*/*)+)"?[^<>]*>([^<>]*)<|';
		$pattern = '|href\s*=(?:["\|\'\|\s]*)(' . $hrefPrefix . '[^"\'\s]*)["\|\'\|\s]*|i';
		echo "pattern = $pattern\n";
		
		if (preg_match_all($pattern, $content, $match, PREG_SET_ORDER))
		{
			// print_r($match);
			foreach ($match as $elem)
			{
				// href => name
				//$urls[ $elem[0] ] = $elem[1];
				$urls[] = $elem[1];
			}
		}
		
		return array_unique($urls);
	}
	
	/*
	 * @desc 从内容中根据字段中文描述获取对应值
	 * @param content string
	 * @param name string 字段中文名
	 * @param delim string 分隔符
	 */
	public static function fetchField($content, $name, $delim = "：")
	{
		// $content = "<dd>地址：北京市东城区安定门内分司厅胡同57号</dd>";
		$pattern = '|' . $name . $delim . '([^<>]*)<|';
		echo "pattern = $pattern\n";
		
		if (preg_match($pattern, $content, $match))
		{
			//print_r($match);
			return trim($match[1]);
		}
		
		return false;
	}

	/*
	 * @desc 从内容中取出前后tag中间的内容
	 * @param content string
	 * @param begin array ('tag' => 'div', 'attr' => 'class', 'value' => 'xxx')
	 * @param end array ('tag' => 'div', 'attr' => 'class', 'value' => 'xxx')
	 * @return string
	 */
	public static function fetchFieldWithTag($content, $begin, $end)
	{
		$pattern = '|<' . $begin['tag'] . '[^<>]+' . $begin['attr'] . '\s*=\s*"' . $begin['value'] . '"[^<>]*>';		
		$pattern .= '[\w\W]*</' . $begin['tag'] . '>';
		$pattern .= '([\w\W]*)<' . $end['tag'] . '[^<>]+' . $end['attr'] . '\s*=\s*"' . $end['value'] . '">';
		$pattern .= '|i';
		echo "pattern = $pattern\n";

		if (preg_match($pattern, $content, $match))
		{
			// print_r($match);
			$value = preg_replace('|(</?[^<>]*+>)|', '', $match[1]);
			// var_dump($value);
			return $value;
		}
		else
		{
			echo "not match\n";
		}
		
		return "";
	}
	
/*
	 * @desc 从内容中取出begin_tag内的内容
	 * @param content string
	 * @param begin array ('tag' => 'div', 'attr' => 'class', 'value' => 'xxx')
	 * @param end array ('tag' => 'div', 'attr' => 'class', 'value' => 'xxx')
	 * @return string
	 */
	public static function fetchFieldInTag($content, $begin, $end)
	{
		$pattern = "|";
		if (is_array($begin))
		{
			$pattern .= '<' . $begin['tag'] . '[^<>]+' . $begin['attr'] . '\s*=\s*"?' . $begin['value'] . '"?[^<>]*>';					
		}
		else
		{
			$pattern .= $begin;
		}		
		$pattern .= "([\w\W]*)";
		
		if (is_array($end))
		{
			$pattern .= '</' . $begin['tag'] . '>';
			$pattern .= '\s*<' . $end['tag'] . '[^<>]+' . $end['attr'] . '\s*=\s*"?' . $end['value'] . '"?>';
		}
		else
		{
			$pattern .= $end; 
		}
		$pattern .= "|i";
		
		echo "pattern = $pattern\n";
		//echo $content;
		
		if (preg_match($pattern, $content, $match))
		{
			print_r($match);
			return $match[1];
		}
		else
		{
			echo "not match\n";
		}
		
		return "";
	}
	
	/*
	 * @desc 获取指定url的内容
	 * @param url string
	 * @param refer
	 * @return string/false
	 */
	public static function fetchContent($url, $refer = "")   
	{   
		return file_get_contents($url);
		$c = curl_init();   
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($c, CURLOPT_USERAGENT, self::USER_AGENT);
		if (!empty($refer))
		{
			curl_setopt($c, CURLOPT_REFERER, $refer);  
		}
		curl_setopt($c, CURLOPT_URL, $url);   
		
		$content = curl_exec($c);   
		curl_close($c);   
		
		if ($content) 
		{
			return $content;   
		}
		else 
		{
			return false;   
		}
	} 

	/*
	 * @desc 把字符串转换成utf-8编码
	 * @param from string 待转换的字符串
	 * @param fromEncode string 字符串编码
	 * @return string
	 */
	public static function convert($from, $fromEncode, $toEncode = "utf-8")
	{
		if (strcasecmp($fromEncode, $toEncode))
		{
			return iconv($fromEncode, $toEncode . '//IGNORE', $from);
		}	
		
		return $from;
	}
	
}


?>