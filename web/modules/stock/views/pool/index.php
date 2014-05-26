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
					<?php foreach ($hqMap as $hqData): ?>
                    <?php $sid = $hqData['sid']; ?>
                    <?php $stockInfo = $hqData['stock']; ?>
                    <?php $stockData = $hqData['data']; ?>
                    <?php $highTypeValues = CommonUtil::getConfigObject("price.high_type"); ?>
                    <?php $qqhqUrl = "http://stockhtm.finance.qq.com/sstock/ggcx/" . $stockInfo['code'] . ".shtml"; ?>
                    <?php $trendUrl = $this->createUrl('/stock/stock/trend', array('sid' => $sid, 'type' => CommonUtil::TREND_FIELD_PRICE, 'start_day' => 20140101)); ?>

					<tr class="pull-center">
                        <td><a href="<?php echo $trendUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>
                        <?php if (isset($contMap[$sid])): ?>
                            <?php $stockContInfo = $contMap[$sid]; ?>
                            <td><?php echo $stockContInfo['cont_days'] . "天"; ?></td>
                            <td><?php echo $stockContInfo['sum_price_vary_portion'] . "%"; ?></td>
                            <td><?php echo $stockContInfo['max_volume_vary_portion']; ?></td>
                        <?php else: ?>
                            <td>-</td> 
                            <td>-</td> 
                            <td>-</td> 
                        <?php endif; ?>

						<td><?php echo isset($priceMap[$sid])? $highTypeValues[$priceMap[$sid]['high_type']] : "-"; ?></td>
						<td><?php echo $stockData['close_price']; ?></td>

                        <?php if (empty($hqData)): ?>  
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "0.00"; ?></td>
						<td><?php echo "-"; ?></td>
						<td><?php echo "-"; ?></td>
                        <?php else: ?>
                            <?php $openPrice = $hqData['open_price']; ?>
                            <?php $curPrice = $hqData['cur_price']; ?>
                            <?php $isHighOpen = ($openPrice >= $stockData['close_price']); ?>

                        <td class="<?php echo $isHighOpen? 'red': 'green'; ?>"><?php echo sprintf("%.2f", $openPrice); ?></td>
                        <td class="<?php echo $isHighOpen? 'red': 'green'; ?>"><?php echo sprintf("%.2f%%", $hqData['open_vary_portion']); ?></td>
                        <td><?php echo sprintf("%.2f", $curPrice); ?></td>
                        <td class="<?php echo ($hqData['vary_portion'] >= 0.00)? 'red': 'green'; ?>"><?php echo sprintf("%.2f%%", $hqData['vary_portion']); ?></td>

                        <td><?php echo isset($hqData['trend'])? $trendMap[$hqData['trend']['trend']] : "-"; ?></td>
                        <td class="<?php echo isset($hqData['trend']) && ($hqData['trend']['op'] == CommonUtil::OP_BUY)? 'red' : 'green'; ?>"><?php echo isset($hqData['trend'])? $opMap[$hqData['trend']['op']] : "-"; ?></td>
                            
                        <?php endif; ?>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

