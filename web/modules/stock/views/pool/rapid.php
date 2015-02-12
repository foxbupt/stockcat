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
			<h3>股票快速拉升</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/stock/stock/add');?>">添加股票</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($rapidList); ?></strong>支股票</caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>涨跌幅(%)</th>
						<th>持续时间(分)</th>
						<th>起始价格</th>
						<th>结束价格</th>
						<th>起始时间</th>
						<th>结束时间</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rapidList as $rapidInfo): ?>
                    <?php $sid = $rapidInfo['sid']; ?>
                    <?php $stockInfo = $stockMap[$sid]; ?>
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
                    <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $day); ?>
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo CommonUtil::formatNumber($rapidInfo['vary_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?></td>
						<td><?php echo $rapidInfo['duration']; ?></td>
						<td><?php echo $rise? CommonUtil::formatNumber($rapidInfo['low']) : CommonUtil::formatNumber($rapidInfo['high']); ?></td>
						<td><?php echo $rise? CommonUtil::formatNumber($rapidInfo['high']) : CommonUtil::formatNumber($rapidInfo['low']); ?></td>
						<td><?php echo sprintf("%02d:%02d", intval($rapidInfo['start_time']/100), intval($rapidInfo['start_time']%100)); ?></td>
						<td><?php echo sprintf("%02d:%02d", intval($rapidInfo['now_time']/100), intval($rapidInfo['now_time']%100)); ?></td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

