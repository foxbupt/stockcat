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
<?php if ($refreshInterval > 0): ?>
function refreshPage()
{
    window.location.reload();
}

// 指定120秒刷新一次
setTimeout('refreshPage()', <?php echo $refreshInterval; ?> * 1000); 
<?php endif; ?>
</script>

<div>
	<table class="table table-bordered">
        <caption>有<strong><?php echo count($poolList); ?></strong>只股票, 交易日: <?php echo $day;?></caption>
		
		<thead>
			<tr>
				<?php foreach ($hqFields as $fieldName => $label): ?>
				<th><?php echo $label; ?></th>
				<?php endforeach;?>
				
				<?php foreach ($customFields as $fieldName => $fieldConfig): ?>
				<th><?php echo $fieldConfig['label']; ?></th>
				<?php endforeach;?>
			</tr>
		</thead>
		
		<tbody>
			<?php foreach (array_keys($poolList) as $sid): ?>
                      <?php $dataItem = $datamap[$sid]; ?>
                      <?php $hqItem = $hqmap[$sid]; ?>
                      <?php $stockInfo = $hqItem['stock']; ?>
                      
                      <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code'], $stockInfo['ecode'], $stockInfo['location']); ?>
					  <?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
				
			<tr class="pull-center">
				<?php foreach (array_keys($hqFields) as $fieldName): ?>
					<?php if ("stock.name" == $fieldName): ?>
                	<td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $stockInfo['name'] . "(" . $sid . ")"; ?></a></td>
					<?php elseif ("stock.code" == $fieldName): ?>
					<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>
					<?php else: ?>
						<?php $value = $this->getFieldValue($hqItem, $fieldName); ?>
						<?php if (strstr($fieldName, "vary_portion") !== FALSE): ?>
						<td class="<?php echo ($value >= 0.00)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($value, CommonUtil::FORMAT_TYPE_PORTION); ?></td>
						<?php else: ?>
						<td><?php echo CommonUtil::formatNumber($value); ?></td>
						<?php endif;?>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php foreach ($customFields as $fieldName => $fieldConfig): ?>					
					<?php $value = $this->getFieldValue($dataItem, $fieldName, $fieldConfig); ?>
					<?php if (strstr($fieldName, "vary_portion") !== FALSE): ?>
					<td class="<?php echo ($value >= 0.00)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($value, CommonUtil::FORMAT_TYPE_PORTION); ?></td>
					<?php else: ?>
					<td><?php echo CommonUtil::formatNumber($value); ?></td>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>	
			<?php endforeach; ?>
		</tbody>
	</table>
</div>


