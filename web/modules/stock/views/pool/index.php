<div class="container">
	<div class="span10">
		<div class="hd">
			<h3>关注股票列表</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
				<caption>当前共有<strong><?php echo count($sidList); ?></strong>支关注股票</caption>
				<thead>
					<tr>
						<th>名称</th>
						<th>代码</th>
						<th>连续上涨</th>
						<th>累计涨幅(%)</th>
						<th>成交量放大</th>
						<th>价格突破</th>
						<th>昨收</th>
						<th>今开</th>
						<th>9:40价格</th>
						<th>开盘走势</th>
						<th>涨跌幅</th>
						<th>建议操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($sidList as $sid): ?>
                    <?php $stockInfo = StockUtil::getStockInfo($sid); ?>
                    <?php $stockData = StockData::model()->findByAttributes(array('sid' => $sid, 'day' => $lastDay, 'status' => 'Y')); ?>
                    <?php $highTypeValues = CommonUtil::getConfigObject("price.high_type"); ?>
                    <?php $qqhqUrl = "http://stockhtm.finance.qq.com/sstock/ggcx/" . $stockInfo['code'] . ".shtml"; ?>

					<tr>
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
						<td><?php echo "-"; ?></td>
						<td><?php echo "-"; ?></td>
						<td><?php echo "-"; ?></td>
						<td><?php echo "-"; ?></td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

