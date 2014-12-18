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
			<h3>价格突破列表</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前日期为<?php echo $lastDay;?>, 当前共有<strong><?php echo count($hqMap); ?></strong>支关注股票</caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>价格突破</th>
						<th>昨收</th>
						<th>昨日涨幅</th>
						<th>今开</th>
						<th>当前价格</th>
						<th>当日涨幅(%)</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($hqMap as $sid => $dataItem): ?>
					<?php $isHighThreshold = ($dataItem['high_type'] > 0); ?>
                    <?php $dailyData = $dataItem['daily']; ?>
                    <?php $dailyPolicyData = $dataItem['policy']; ?>
                    <?php $stockInfo = $dataItem['stock']; ?>
                    <?php $highTypeValues = CommonUtil::getConfigObject("price.high_type"); ?>
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
                    <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $lastDay); ?>

					<tr class="pull-center">
                        <td><a href="<?php echo $trendUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td class="<?php echo $isHighThreshold? 'red': 'green'; ?>"><?php echo ($dataItem["high_type"] > 0)? $highTypeValues[$dataItem['high_type']] : $highTypeValues[$dataItem['low_type']]; ?></td>

                        <?php if (empty($dailyData)): ?>  
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						
                        <?php else: ?>
						<td><?php echo CommonUtil::formatNumber($dailyData['close_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>
                        <td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
                        <?php endif; ?>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

