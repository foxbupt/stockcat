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
			<h3>历史交易</h3>
		</div>
		
		<div id="main">			
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($historyList); ?></strong>支股票</caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>买入日期</th>
						<th>卖出日期</th>
						<th>数量</th>
						<th>买入均价</th>
						<th>总成本</th>
						<th>总收入</th>
						<th>获利金额</th>
						<th>获利比例</th>	
						<th>日均收益</th>
						<th>操作</th>					
					</tr>
				</thead>
				<tbody>
					<?php foreach ($historyList as $holdInfo): ?>
					<?php $sid = $holdInfo['sid']; ?>
                    <?php $stockInfo = $stockMap[$sid]; ?>
                    <?php $dealList = $dealMap[$sid]; ?>
                    <?php $openDayCount = CommonUtil::getOpenDayCount($holdInfo['day'], $holdInfo['close_day']); ?>
                    
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo date('Y/m/d', strtotime($holdInfo['day'])); ?></td>
						<td><?php echo date('Y/m/d', strtotime($holdInfo['close_day'])); ?></td>
						<td><?php echo $holdInfo['total_count']; ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['cost']); ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['amount']); ?></td>
						<td class="<?php echo ($holdInfo['profit'] >= 0)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($holdInfo['profit']); ?></td> 
                        <td class="<?php echo ($holdInfo['profit'] >= 0)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($holdInfo['profit_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?> </td>
                        <?php $dayProfitPortion = ($holdInfo['profit_portion'] >= 0)? ($holdInfo['profit_portion'] / $openDayCount) : 0.00; ?>
						<td class="<?php echo ($holdInfo['profit'] >= 0)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($dayProfitPortion, CommonUtil::FORMAT_TYPE_PORTION) . "(" . $openDayCount . ")"; ?> </td>
						
						<td>
							<a class="btn btn-primary" data-toggle="collapse" href="#detail-<?php echo $holdInfo['sid'] . "-" . $holdInfo['batch_no'] ;?>" aria-expanded="false" aria-controls="collapseExample">详情</a>											
						</td>
						                  
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	
	<div class="span12">
		<?php foreach ($historyList as $sid => $holdInfo): ?>
	        <?php $stockInfo = $stockMap[$sid]; ?>
	        <?php $dealList = $dealMap[$sid]; ?>
		<div class="collapse" id="detail-<?php echo $holdInfo['sid'] . "-" . $holdInfo['batch_no'] ;?>">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>名称</th>
						<th>交易日期</th>
						<th>交易类型</th>
						<th>数量</th>
						<th>价格</th>
						<th>费用</th>
						<th>手续费</th>
						<th>印花税</th>
						<th>金额 </th>	
					</tr>
				</thead>
				<tbody>
					<?php foreach ($dealList as $dealInfo): ?>
					<tr>
						<?php $isBuy = (DealHelper::DEAL_TYPE_BUY == $dealInfo['deal_type']); ?>
						<?php $sign = $isBuy? "-" : "";?>
						<td><?php echo $stockInfo['name']; ?>
						<td><?php echo date('Y/m/d', strtotime($dealInfo['day'])); ?></td>
						<td><?php echo $isBuy? "买入" : "卖出";?></td>
						<td><?php echo $dealInfo['count']; ?></td>
						<td><?php echo $dealInfo['price']; ?></td>
						<td><?php echo $sign . $dealInfo['fee']; ?></td>
						<td><?php echo $sign . $dealInfo['commission']; ?></td>
						<td><?php echo $sign . $dealInfo['tax']; ?></td>
						<td><?php echo $sign . $dealInfo['amount']; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>	  
		</div>      	
		<?php endforeach; ?>
	</div>
</div>
