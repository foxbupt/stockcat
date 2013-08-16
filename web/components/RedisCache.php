<?php

/**
 * @desc 基于phpredis封装的yii cache component
 * @author fox
 * @copyright slanissue
 * @date 2012/03/12
 */
class RedisCache extends CApplicationComponent implements ICache 
{
	// redis instance
	private $_redis = null;
	// server config
	public $servers = array();
	
	function __destruct()
	{
		$this->close();	
	}
	
	/**
	 * @desc 初始化当前组件, 实现IComponent的init接口, 创建redis实例并connect到server
	 * @return 失败时会抛出RedisException 
	 */
	public function init()
	{
		parent::init();
		
		$serverConfigs = $this->getServers();
		$redis = $this->getInstance();
		
		if (count($serverConfigs))	// 暂时只支持一个redis server
		{
			$server = $serverConfigs[0];
			$success = $redis->pconnect($server['host'], $server['port'], $server['timeout'], isset($server['persistent_id'])? $server['persistent_id'] : "");
			if ($success)
			{
				$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE); 
			}
		}
		
	}
	
	// 关闭连接
	public function close()
	{
		if ($this->_redis != null)
		{
			$this->_redis->close();	
			$this->_redis = null;
		}
	}
	
	public function getServers()
	{
		return $this->servers;	
	}
	
	/**
	 * @desc 获取redis实例
	 *
	 * @return Redis
	 */
	public function getInstance()
	{
		if ($this->_redis != null)
		{
			return $this->_redis;
		}
		else
		{
			return $this->_redis = new Redis;
		}
	}
	
	public function get($key)
	{
		return $this->_redis->get($key);
	}
	
	public function mget($keys)
	{
		return $this->_redis->mget($keys);	
	}
	
	public function set($key, $value, $expire = 0, $dependency = null)
	{
		return ($expire > 0)? $this->_redis->setex($key, $expire, $value) : $this->_redis->set($key, $value);
	}
	
	public function add($key, $value, $expire = 0, $dependency = null)
	{
		return $this->_redis->setnx($key, $value);
	}
	
	public function delete($key)
	{
		return $this->_redis->delete($key);	
	}
	
	public function flush()
	{
		return true;
	}
	
}
?>