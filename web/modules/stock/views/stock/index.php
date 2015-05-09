<div class="container-fluid">
	<div class="row container-fluid">
		<div class="offset1 span1">
			<div class="row">
				<p class="text-center"><?php echo $stockInfo['name']; ?></p>
				<p class="text-center"><?php echo CommonUtil::getShowCode($stockInfo['code'], $stockInfo['ecode']); ?></p>
			</div>
			<div class="row">
				<h1 class="text-center" style="color:#fe0002;"><?php echo CommonUtil::formatNumber($dailyData['close_price']); ?></h1>
			</div>
			<div class="row">
                <p class="text-center" style="color:#fe0002">
                	<?php echo $prefix . CommonUtil::formatNumber($hqData['daily']['vary_price']); ?> 
                	<?php echo $prefix . CommonUtil::formatNumber($hqData['daily']['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?>
                </p>
			</div>
		</div>
		<div class="offset1 span8">
			<div class="row">
				<p class="text-center" style="font-size:16px;">当前时间: <?php echo $curTime; ?></p>
			</div>
			<div class="row">
				<div class="span2">
					<p>昨收:  <?php echo CommonUtil::formatNumber($dailyData['last_close_price']); ?></p>
				</div>
				<div class="span2">
					<p>今开:  <?php echo CommonUtil::formatNumber($dailyData['open_price']); ?></p>
				</div>
				<div class="span2">
					<p>最高: <?php echo CommonUtil::formatNumber($dailyData['high_price']); ?></p>
				</div>
				<div class="span2">
					<p>最低:<?php echo CommonUtil::formatNumber($dailyData['low_price']); ?></p>
				</div>
			</div>
			<div class="row">	
				<div class="span2">
					<p>成交量: <?php echo CommonUtil::formatNumber($dailyData['volume']/10000) . "万手"; ?></p>
					<p>换手率: <?php echo CommonUtil::formatNumber($dailyData['exchange_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></p>
					<p>量比: <?php echo CommonUtil::formatNumber($dailyPolicy['volume_ratio'], CommonUtil::FORMAT_TYPE_PORTION); ?></p>
					<p>上涨因子: <?php echo CommonUtil::formatNumber($dailyPolicy['rise_factor'], CommonUtil::FORMAT_TYPE_PORTION); ?></p>
				</div>		
			</div>			
		</div>
	</div>
	
	<div class="row">
		<table class="table table-bordered">
            <caption>共有<strong><?php echo count($poolList); ?></strong>条关注记录</caption>
			<thead>
				<tr>
					<th>日期</th>
					<th>价格</th>
					<th>成交量放大</th>
					<th>上涨因子</th>
					<th>关注原因</th>	
					<th>操作</th>					
				</tr>
			</thead>
			<tbody>
				<?php $highTypeValues = CommonUtil::getConfigObject("price.high_type"); ?>
                <?php $lowTypeValues = CommonUtil::getConfigObject("price.low_type"); ?>
                   				
				<?php foreach ($poolList as $poolRecord): ?>
				<tr class="pull-center">
					<td><?php echo $poolRecord->day; ?></td>
					<td><?php echo CommonUtil::formatNumber($poolRecord->close_price); ?></td>
					<td><?php echo $poolRecord->volume_ratio; ?></td>
					<td><?php echo $poolRecord->rise_factor; ?></td>
					<td><?php echo CommonUtil::formatSource($poolRecord->source); ?></td>
					<td>
						<a class="btn btn-primary" data-toggle="collapse" href="#poolDetail-<?php echo $poolRecord->day;?>" aria-expanded="false" aria-controls="collapseExample">展开</a>											
					</td>
					<div class="collapse" id="poolDetail-<?php echo $poolRecord->day;?>">
						  <div class="well">
						  		<?php $poolday = $poolRecord->day; ?>
						  		<?php $poolInfo = $poolMap[$poolday]; ?>
						  		<?php if (isset($poolInfo['cont'])): ?>
						  		<p><?php echo $poolday;?>|累计涨幅:<?php echo CommonUtil::formatNumber($poolInfo['cont']['sum_price_vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?>|连续上涨:<?php echo $poolInfo['cont']['cont_days']; ?>天|开始日期:<?php echo $poolInfo['cont']['start_day'];?></p>
						  		<?php endif; ?>
						  		
						  		<?php if (isset($poolInfo['threshold'])): ?>
						  		<?php $isHighThreshold = ($poolInfo['threshold']['high_type'] > 0); ?>						  		
						  		<p><?php echo $poolday;?>|价格突破:<?php echo $isHighThreshold? $highTypeValues[$poolInfo['threshold']['high_type']] : $lowTypeValues[$poolInfo['threshold']['low_type']]; ?></p>
						  		<?php endif; ?>
						  		
						  		<?php if (isset($poolInfo['pivot'])): ?>
						  		<p><?php echo $poolday;?>|阻力位:<?php echo CommonUtil::formatNumber($poolInfo['pivot']['resist']); ?>|阻力位突破涨幅:<?php echo CommonUtil::formatNumber($poolInfo['pivot']['resist_vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?>|支撑位:<?php echo CommonUtil::formatNumber($poolInfo['pivot']['support']);?>|支撑位幅度:<?php echo CommonUtil::formatNumber($poolInfo['pivot']['support_vary_portion'], CommonUtil::FORMAT_TYPE_PORTION);?></p>
						  		<?php endif; ?>
						  </div>
					</div>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>	
	</div>
		
</div>

<div>
<?php $this->widget('application.extensions.StockTrendWidget', array(
        'sid' => $sid,
        'startDay' => $trendStartDay,
        'endDay' => $openDay,
		'height' => 400,
));?>
</div>
