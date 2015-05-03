<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>短线操作股票列表</h3>
		</div>
		
		<?php $this->widget('application.extensions.PoolHqWidget', array(
	        'day' => $day,
			'location' => $location,
	        'poolList' => $shortList,
	        'refreshInterval' => 60,
		));?>
	</div>
</div>

