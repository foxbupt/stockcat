<?php

/**
 * @desc 展现股票列表的实时行情
 * 		TODO: 如何展现trend/op 和 支持业务自定义字段展现		
 * @date 2015/05/03
 *
 */
class PoolHqWidget extends CWidget
{
	public $day = 0;
	public $location = CommonUtil::LOCATION_CHINA;
	
	// 股票列表
	public $poolList = array();
	// 自定义业务数据, 为空时直接用poolList
	public $datamap = array();
	
	// 缺省行情字段  
	public $hqFields = array(
			'stock.name' => '名称',
			'stock.code' => '代码',
			'daily.last_close_price' => '昨收',
			'daily.open_price' => '今开',
			'policy.open_vary_portion' => '开盘涨幅',
			'daily.close_price' => '当前价格',
			'daily.vary_portion' => '涨幅',			
		);
		
	/**
	 * @desc 自定义显示字段, 格式为
	 * array(
	 * 		field => array(
	 * 			'label' => name, 
	 * 			'map' => array(
	 * 				value1 => show1,
	 * 				value2 => show2
	 * 			),
	 *  ...)
	 */
	public $customFields = array();
	
	// 自动刷新间隔, 0 表示不刷新
	public $refreshInterval = 0;
	
	public function run()
	{
		if (empty($this->datamap))
		{
			$this->datamap = $this->poolList;
		}
		
		$hqmap = array();
		foreach (array_keys($this->poolList) as $sid)
		{
			$hqmap[$sid] = DataModel::getHQData($sid, $this->day, $this->location); 
		}
		
		$this->render('hq', array(
				'day' => $this->day,
				'poolList' => $this->poolList,
				'hqmap' => $hqmap,
				'datamap' => $this->datamap,
				'hqFields' => $this->hqFields,
				'customFields' => $this->customFields,
				'refreshInterval' => $this->refreshInterval,
			));
	}
	
	/**
	 * @desc 获取指定字段的值
	 *
	 * @param array $map
	 * @param string $fieldPath 形如daily.vary_portion
	 * @param array $fieldConfig 
	 * @return mixed
	 */
	public function getFieldValue($map, $fieldPath, $fieldConfig = array())
	{
		$fields = explode(".", $fieldPath);
		$value = $map;
		
		foreach ($fields as $fieldName)
		{
			if (!is_array($value) || !isset($value[$fieldName]))
			{
				return "";
			}
			
			$value = $value[$fieldName];
		}
		
		if (!empty($fieldConfig) && isset($fieldConfig['map']))
		{
			return $fieldConfig['map'][$value];
		}
		return $value;
	}
}
?>