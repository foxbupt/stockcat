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
			<h3>关注股票列表</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($hqMap); ?></strong>支关注股票, 当前时刻: <?php echo $curTime; ?></caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>连续上涨</th>
						<th>累计涨幅(%)</th>
						<th>成交量放大</th>
						<th>价格突破</th>
						<th>昨收</th>
						<th>今开</th>
						<th>开盘涨幅</th>
						<th>当前价格</th>
						<th>涨跌幅</th>
						<th>开盘走势</th>
						<th>建议操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($hqMap as $dataItem): ?>
                    <?php $sid = $dataItem['sid']; ?>
                    <?php $dailyData = $dataItem['daily']; ?>
                    <?php $dailyPolicyData = $dataItem['policy']; ?>
                    <?php $stockInfo = isset($dataItem['daily'])? $dailyData : $dataItem['stock']; ?>
                    <?php $highTypeValues = CommonUtil::getConfigObject("price.high_type"); ?>
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
                    <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $lastDay); ?>

					<tr class="pull-center">
                        <td><a href="<?php echo $trendUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo isset($hqData['name'])? $hqData['name'] : $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo isset($hqData['code'])? $hqData['code'] : $stockInfo['code']; ?></a></td>
                        <?php if (isset($dataItem['cont_days'])): ?>
                            <td><?php echo $dataItem['cont_days'] . "天"; ?></td>
                            <td><?php echo $dataItem['sum_price_vary_portion'] . "%"; ?></td>
                            <td><?php echo $dataItem['max_volume_vary_portion']; ?></td>
                        <?php else: ?>
                            <td>-</td> 
                            <td>-</td> 
                            <td>-</td> 
                        <?php endif; ?>

						<td><?php echo isset($dataItem["high_type"])? $highTypeValues[$dataItem['high_type']] : "-"; ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['last_close_price']); ?></td>

                        <?php if (empty($dailyData)): ?>  
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "-"; ?></td>
						<td><?php echo "-"; ?></td>
                        <?php else: ?>
                            <?php $openPrice = $dailyData['open_price']; ?>
                            <?php $curPrice = $dailyData['close_price']; ?>
                            <?php $isHighOpen = ($openPrice >= $dailyData['last_close_price']); ?>
                            <?php $openVaryPortion = $dailyPolicyData['open_vary_portion'] ;?>

                        <td class="<?php echo $isHighOpen? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($openPrice); ?></td>
                        <td class="<?php echo $isHighOpen? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($openVaryPortion, CommonUtil::FORMAT_TYPE_PORTION); ?></td>
                        <td><?php echo CommonUtil::formatNumber($curPrice); ?></td>
                        <td class="<?php echo ($dailyData['vary_portion'] >= 0.00)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($dailyData['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>

                        <td><?php echo empty($dailyPolicyData)? "-" : $trendMap[$dailyPolicyData['trend']]; ?></td>
                        <td class="<?php echo !empty($dailyPolicyData) && ($dailyPolicyData['op'] == CommonUtil::OP_BUY)? 'red' : 'green'; ?>"><?php echo !empty($dailyPolicyData)? $opMap[$dailyPolicyData['op']] : "-"; ?></td>
                            
                        <?php endif; ?>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

