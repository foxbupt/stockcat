<div class="container-fluid">
	<div class="row container-fluid">
		<div class="span4">
			<div class="row">
				<p><?php echo $stockInfo['name']; ?></p>
				<p><?php echo CommonUtil::getShowCode($stockInfo['code'], $stockInfo['ecode']); ?></p>
			</div>
			<div class="row">
				<h1 style="color:#fe0002;font-size:16px;"><?php echo $hqData['daily']['close_price']; ?></h1>
			</div>
			<div class="row">
				<p style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_price']; ?></p>
				<p style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_portion']; ?></p>
			</div>
		</div>
		<div class="offset2 span8">
			<div class="row">
				<p>当前时间: <?php echo $curTime; ?></p>
			</div>
			<div class="row">
				<div class="span3">
					<label>昨收:</label>
					<p><?php echo $dailyData['last_close_price']; ?></p>
				</div>
				<div class="offset2 span3">
					<label>今开:</label>
					<p><?php echo $dailyData['open_price']; ?></p>
				</div>
				<div class="offset2 span3">
					<label>最高:</label>
					<p><?php echo $dailyData['high_price']; ?></p>
				</div>
				<div class="offset2 span3">
					<label>最低:</label>
					<p><?php echo $dailyData['low_price']; ?></p>
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
		
</div>

<div>
<?php $this->widget('application.extensions.StockTrendWidget', array(
        'sid' => $sid,
        'startDay' => $trendStartDay,
        'endDay' => $openDay,
		'height' => 400,
));?>
</div>
