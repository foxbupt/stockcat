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
			<h3>股票上涨因子</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($datamap); ?></strong>支股票, 当前时刻: <?php echo $curTime; ?></caption>
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
					<?php foreach ($datamap as $sid => $dataItem): ?>
                    <?php $riseFactor = floatval($dataItem['rf']); ?>
                    <?php $dailyData = $dataItem['daily']; ?>
                    <?php $dailyPolicyData = $dataItem['policy']; ?>
                    <?php $stockInfo = isset($dataItem['daily'])? $dailyData : $dataItem['stock']; ?>
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code'], $stockInfo['ecode'], $stockInfo['location']); ?>
                    <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $day); ?>
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>
                        <td><?php echo implode("|", $datamap[$sid]['tags']); ?></td>

						<td><?php echo $riseFactor; ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['last_close_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['open_price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($dailyData['close_price']); ?></td>
						<td><?php echo $dailyData['predict_volume']; ?></td>
						<td><?php echo round($dailyPolicyData['volume_ratio'], 2); ?></td>

                        <td><?php echo CommonUtil::formatNumber($dailyPolicyData['day_vary_portion']); ?> </td>
                        <td><?php echo CommonUtil::formatNumber($dailyPolicyData['high_portion']); ?> </td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

