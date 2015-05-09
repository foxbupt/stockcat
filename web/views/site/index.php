<div class="container">
	<ul>
		<li><strong>A股</strong></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/index'); ?>">股票池</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/realtime'); ?>">实时上涨列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rankList'); ?>">评级列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/threshold'); ?>">价格突破</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/upresist'); ?>">趋势突破</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/uplimit'); ?>">昨日涨停列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rapid', array('rise' => 1)); ?>">快速拉升列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rapid', array('rise' => 0)); ?>">快速下跌列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/shortTerm/index'); ?>">短线追踪</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/member/deal/own'); ?>">我的持仓</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/member/deal/own', array('state' => DealHelper::DEAL_STATE_CLOSE)); ?>">我的持仓-已结算</a></li>
		
	</ul>
	
	<ul>
		<li><strong>美股</strong></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/index', array('location' => CommonUtil::LOCATION_US)); ?>">股票池</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rankList', array('location' => CommonUtil::LOCATION_US)); ?>">评级列表</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/threshold', array('location' => CommonUtil::LOCATION_US)); ?>">价格突破</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/upresist', array('location' => CommonUtil::LOCATION_US)); ?>">趋势突破</a></li>
		<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/uplimit', array('location' => CommonUtil::LOCATION_US)); ?>">昨日涨停列表</a></li>
	</ul>
</div>

