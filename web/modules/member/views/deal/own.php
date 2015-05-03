<div class="container">
	<div class="span12">
		<div class="hd">
			<h3>当前持仓</h3>
		</div>
		
		<div>
			<p class="pull-right">
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/member/deal/buy');?>">买入</a>
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/member/deal/sell');?>">卖出</a>
			</p>
			<table class="table table-bordered">
            <caption>当前共有<strong><?php echo count($userHoldList); ?></strong>支股票, 交易日:<?php echo $day;?></caption>
				<thead>
					<tr>
						<th>股票id</th>
						<th>名称</th>
						<th>代码</th>
						<th>买入日期</th>
						<th>数量</th>
						<th>买入成本</th>
						<th>总成本</th>
						<th>当前价格</th>
						<th>当前市值</th>
						<th>获利金额</th>
						<th>获利比例</th>
						<th>当前趋势</th>
						<th>建议操作</th>
						<th>操作栏</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($userHoldList as $sid => $holdInfo): ?>
                    <?php $hqData = $stockHqMap[$sid]; ?>
                    <?php $stockInfo = $hqData['stock']; ?>
                    <?php $dailyData = $hqData['daily']; ?>
                    
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
                    <?php $trendUrl = $this->getTrendUrl($sid, CommonUtil::TREND_FIELD_PRICE, $day); ?>
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo $holdInfo['day']; ?></td>
						<td><?php echo $holdInfo['count']; ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['price']); ?></td>
						<?php $cost = $holdInfo['count'] * $holdInfo['price']; ?>
						<td><?php echo $cost; ?></td>
						<td><?php echo $dailyData['close_price']; ?></td>
						<?php $amount = $holdInfo['count'] * $dailyData['close_price']; ?>
						<td><?php echo $amount; ?></td>
						<td><?php echo $amount - $cost; ?></td> 
                        <td><?php echo CommonUtil::formatNumber(($amount - $cost) / $amount, CommonUtil::FORMAT_TYPE_PORTION); ?> </td>
                        
                        <?php if (isset($hqData['policy'])): ?>
                        <td><?php echo $hqData['policy']['trend']; ?></td>
                        <td><?php echo $hqData['policy']['op']; ?></td>
                        <?php else: ?>
                        <td>-</td>
                        <td>-</td>
                        <?php endif; ?>
                        <td>
							<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/member/deal/buy', array('sid' => $sid));?>">买入</a>
							<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/member/deal/sell', array('sid' => $sid));?>">卖出</a>
						</td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	
	<div id="buy">
		<form>
		</form>
	</div>
	
	<div id="sell">
		<form>
		</form>
	</div>
</div>