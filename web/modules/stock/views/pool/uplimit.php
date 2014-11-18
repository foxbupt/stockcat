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

// 指定120秒刷新一次
setTimeout('refreshPage()', 120 * 1000); 
</script>

<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>涨停股票列表</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($uplist); ?></strong>支股票, 当前时刻: <?php echo $curTime; ?></caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>昨收</th>
						<th>昨日涨幅</th>
						<th>今开</th>
						<th>当前价格</th>
						<th>当日涨幅(%)</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($uplist as $stockData): ?>
                        <?php $sid = $stockData['sid']; ?>

                        <?php $dataItem = $datamap[$sid]; ?>
                        <?php $dailyData = $dataItem['daily']; ?>
                        <?php $dailyPolicyData = $dataItem['policy']; ?>
                        <?php $stockInfo = $dataItem['stock']; ?>
                        <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code'], $stockInfo['ecode'], $stockInfo['location']); ?>
                        <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $lastDay); ?>

					<tr class="pull-center">
                        <td><a href="<?php echo $trendUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo CommonUtil::formatNumber($stockData['close_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($stockData['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['open_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['close_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

