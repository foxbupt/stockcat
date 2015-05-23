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
				'cont.cont_days' => '连续上涨天数',
				'threshold.high_index' => '向上价格突破',
				'threshold.low_index' => '向下价格突破',
				'pivot.resist' => '阻力位',
				'pivot.support' => '支撑位',
			),
	        'refreshInterval' => 60,
		));?>
	</div>
</div>