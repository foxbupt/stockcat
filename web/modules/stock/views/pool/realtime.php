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

<script language="javascript">
function refreshPage()
{
    window.location.reload();
}

// 指定秒60刷新一次
setTimeout('refreshPage()', 30 * 1000); 
</script>

<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>股票上涨因子</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($riseFactorList); ?></strong>支股票, 当前时刻: <?php echo $curTime; ?></caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>所属板块</th>
						<th>上涨因子</th>
						<th>昨收</th>
						<th>今开</th>
						<th>当前价格</th>
						<th>预估成交量</th>
						<th>量比</th>
						<th>当日涨幅(%)</th>
						<th>最高价比例</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($riseFactorList as $sid => $riseFactor): ?>
                    <?php $riseFactor = floatval($riseFactor); ?>
                    <?php $dailyData = $datamap[$sid]['daily']; ?>
                    <?php $dailyPolicyData = $datamap[$sid]['daily_policy']; ?>
                    <?php $qqhqUrl = "http://stockhtm.finance.qq.com/sstock/ggcx/" . $dailyData['code'] . ".shtml"; ?>
                    <?php $sinahqUrl = "http://finance.sina.com.cn/realstock/company/" . strtolower($stockInfo['ecode']) . $stockInfo['code'] . "/nc.shtml"; ?>
                    <?php $trendUrl = $this->createUrl('/stock/stock/trend', array('sid' => $sid, 'type' => CommonUtil::TREND_FIELD_PRICE, 'start_day' => 20140101)); ?>

					<tr class="pull-center">
                        <td><a href="<?php echo $trendUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $dailyData['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $dailyData['code']; ?></a></td>
                        <td><?php echo implode("|", $datamap[$sid]['tags']); ?></td>

						<td><?php echo $riseFactor; ?></td>
						<td><?php echo $dailyData['last_close_price']; ?></td>
						<td><?php echo $dailyData['open_price']; ?></td>
						<td><?php echo $dailyData['close_price']; ?></td>
						<td><?php echo $dailyData['predict_volume']; ?></td>
						<td><?php echo round($dailyPolicyData['volume_ratio'], 2); ?></td>

                        <td><?php echo sprintf("%.2f", $dailyPolicyData['open_vary_portion']); ?> </td>
                        <td><?php echo sprintf("%.2f", $dailyPolicyData['high_portion']); ?> </td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

