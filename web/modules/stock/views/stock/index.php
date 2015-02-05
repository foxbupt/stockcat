<div class="container-fluid">
	<div class="row container-fluid">
		<div class="span4">
			<div style="float:left;overflow:hidden;background:url(http://mat1.gtimg.com/finance/images/stock/p/hqhk_gg/hqpanel_v1.1/sprite-repeat.png) 0 0 repeat-x;">
				<h1 style="background: transparent; line-height: 26px; font-size: 14px; text-align: center; width: 120px; padding: 0; height: 26px; "><?php echo $stockInfo['name']; ?></h1>
				<h1 style="background: transparent; font-size: 11px; line-height: 22px; text-align: center; margin-top: -7px; "><?php echo CommonUtil::getShowCode($stockInfo['code'], $stockInfo['ecode']); ?></h1>
			</div>
		</div>
		<div class="offset2 span8">
			<h5>当前时间: <?php echo $curTime; ?></h5>
		</div>
	</div>
	<div class="row">
		<div class="span4">
			<i style="color:#fe0002"><?php echo $hqData['daily']['close_price']; ?></i>
			<i style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_price']; ?></i>
			<i style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_portion'] . "%"; ?></i>
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
				<?php foreach ($poolList as $poolRecord): ?>
				<tr class="pull-center">
					<td><?php echo $poolRecord->day; ?></td>
					<td><?php echo CommonUtil::formatNumber($poolRecord->close_price); ?></td>
					<td><?php echo $poolRecord->volume_ratio; ?></td>
					<td><?php echo $poolRecord->rise_factor; ?></td>
					<td><?php echo CommonUtil::formatSource($poolRecord->source); ?></td>
					<td><button class="btn btn-primary" id="op-<?php echo $poolRecord->day; ?>">展开</button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>	
	</div>
	
	<div>
	<?php $this->widget('application.extensions.StockTrendWidget', array(
         'sid' => $sid,
         'trendType' => CommonUtil::TREND_FIELD_PRICE,
         'startDay' => $trendStartDay,
         'endDay' => $openDay,
	));?>
	</div>
</div>
