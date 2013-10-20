<?php

Yii::import('common.components.BevaUtil');

/*
 * @desc 输出统计日志和错误日志, 格式为[op|error]=msg key=value key=value
 * 		 考虑不区分统计日志和错误日志, 两者格式一致, 方便后续对错误日志进行统计
 * @author fox
 * @date 2010/10/26
 * @copyright slanissue
 */
class StatLogUtil
{
	// key => value分隔符
	const PAIR_DELIM = "=";
	// pair之间的切分符号
	const SPLIT_SEPARATOR = " ";
	
	/*
	 * @desc 输出统计日志, 自动加上client_ip/session_id/login_uid
	 * @param opMsg string
	 * @param args array(key => value)
	 * @param level string 缺省为info
	 * @return none
	 */
	public static function log($opMsg, $args = array(), $level = "info")
	{
		$logMsg = "op" . self::PAIR_DELIM . $opMsg;
		$args['executionTime'] = microtime(true)-YII_BEGIN_TIME;
		$isWebApplication = (Yii::app() instanceof CWebApplication)? true : false;
		
		if ($isWebApplication)	// web访问才打印session相关的日志字段
		{
			$user = Yii::app()->user;
			if ($user && !$user->isGuest)	// 处于登录状态则记录对应的uid
			{
				$args['login_uid'] = $user->getId();
				if (isset($user->isOpen) && $user->isOpen)
				{
					$args['open_platform'] = $user->openPlatform;
					$args['open_id'] = $user->openId;
				}
			}
			// 自动加上用户ip和请求的session_id
			$args['client_ip'] = BevaUtil::getRemoteIP();
			$args['session_id'] = Yii::app()->getSession()->sessionID;
		}
				
		$jsonLogger = Yii::app()->getComponent('jsonlogger');
		if ($jsonLogger)
		{
			$jsonLogger->log($opMsg, $args);
		}
		foreach ($args as $key => $value)
		{
			$logMsg .= self::SPLIT_SEPARATOR;
			$logMsg .= $key . self::PAIR_DELIM . $value;
		}
		
		Yii::log($logMsg, $level, $isWebApplication? Yii::app()->getController()->getModule()->id : 'console');		
	}
	
	/*
	 * @desc 输出错误日志
	 * @param msg string
	 * @param args array(key => value)
	 * @return none
	 */
	public static function error($msg, $args = array())
	{
		$isWebApplication = (Yii::app() instanceof CWebApplication)? true : false;
		$logMsg = "error" . self::PAIR_DELIM . $msg;
		foreach ($args as $key => $value)
		{
			$logMsg .= self::SPLIT_SEPARATOR;
			$logMsg .= $key . self::PAIR_DELIM . $value;
		}
		
		Yii::log($logMsg, 'error', $isWebApplication? Yii::app()->getController()->getModule()->id : 'console');	
	}
	
	/**
	 * 记录系统内部产生的错误/异常，传入的array参数包含以下信息:
	 * <ul>
	 * <li>code - the HTTP status code (e.g. 403, 500)</li>
	 * <li>type - the error type (e.g. 'CHttpException', 'PHP Error')</li>
	 * <li>message - the error message</li>
	 * <li>file - the name of the PHP script file where the error occurs</li>
	 * <li>line - the line number of the code where the error occurs</li>
	 * <li>trace - the call stack of the error</li>
	 * <li>source - the context source code where the error occurs</li>
	 * </ul>
	 * @param array $error
	 */
	public static function systemError($error)
	{
		unset($error['trace'], $error['source']);
		if (!empty($error['type']))
		{
			$error['type'] = implode('_', explode(' ', $error['type']));
		}
		if (!empty($error['message']))
		{
			$error['message'] = implode('_', explode(' ', $error['message']));
		}
		
		StatLogUtil::log('system_error', $error, 'error');
	}
}
?>
