<style>
.red {
    color: #ff0000;
}
.green {
    color: #008000;
}
    
td {
    text-align:center;
}
</style>


<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>股票评级列表</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/pool/rankList', array('day' => $nextDay, 'location' => $location));?>">明天</a>
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前日期为<?php echo $day;?>, 当前共有<strong><?php echo count($orderList); ?></strong>支关注股票</caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>评级</th>
						<th>标签</th>
						<th>量比</th>
						<th>昨收</th>						
						<th>今开</th>
						<th>当前价格</th>
						<th>当日涨幅(%)</th>
					</tr>
				</thead>
				<tbody>
        			<?php foreach ($orderList as $sid): ?>
       					<?php $rankInfo = $rankmap[$sid]; ?>
       					<?php $dataItem = $datamap[$sid]; ?>       					
                        <?php $dailyData = $dataItem['daily']; ?>
                        <?php $dailyPolicyData = $dataItem['policy']; ?>
                        <?php $stockInfo = $dataItem['stock']; ?>
                        <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code'], $stockInfo['ecode'], $stockInfo['location']); ?>
                        <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $lastDay); ?>
						<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
						
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo $rankInfo['rank']; ?></td>
						<td><?php echo CommonUtil::formatSource($rankInfo['source']); ?></td>
						<td><?php echo CommonUtil::formatNumber($rankInfo['volume_ratio']); ?></td>
						<td><?php echo CommonUtil::formatNumber($rankInfo['close_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['open_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['close_price']); ?></td>
						<td class="<?php echo ($dailyData['vary_portion'] >= 0.00)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($dailyData['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>
					</tr>	
        			<?php endforeach; ?>   		
				</tbody>
			</table>
		</div>
	</div>
</div>

