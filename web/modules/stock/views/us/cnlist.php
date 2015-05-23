<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>中概股列表</h3>
		</div>
		
		<?php $this->widget('application.extensions.PoolHqWidget', array(
			'location' => CommonUtil::LOCATION_US,
			'day' => $day,
	        'poolList' => $poolList,
			'customFields' => array(
				'cont.cont_days' => array('label' => '连续上涨天数'),
				'threshold.high_index' => array(
					'label' => '向上价格突破',
					'map' => array(
						0 => '历史最高',
						1 => '年内最高',
						2 => '60日最高',
						3 => '30日最高',			
					)
				),
				'threshold.low_index' => array(
					'label' => '向下价格突破',
					'map' => array(
						0 => '历史最低',
						1 => '年内最低',
						2 => '60日最低',
						3 => '30日最低',			
					)
				),
				'pivot.resist' => array(
					'label' => '阻力位',
				),
				'pivot.support' => array(
					'label' => '支撑位',
				),
			),
	        'refreshInterval' => 60,
		));?>
	</div>
</div>