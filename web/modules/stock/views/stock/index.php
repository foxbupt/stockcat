<div class="container-fluid">
	<div class="row container-fluid">
		<div class="span4">
			<div style="float:left;overflow:hidden;background:url(http://mat1.gtimg.com/finance/images/stock/p/hqhk_gg/hqpanel_v1.1/sprite-repeat.png) 0 0 repeat-x;">
				<h1 style="background: transparent; line-height: 26px; font-size: 14px; text-align: center; width: 120px; padding: 0; height: 26px; "><?php echo $stockInfo['name']; ?></h1>
				<h1 style="background: transparent; font-size: 11px; line-height: 22px; text-align: center; margin-top: -7px; "><?php echo CommonUtil::getShowCode($stockInfo['code'], $stockInfo['ecode']); ?></h1>
			</div>
		</div>
		<div class="offset2 span8">
			<h5>当前时间: <?php echo $curTime; ?></h5>
		</div>
	</div>
	<div class="row">
		<div class="span4">
			<span style="color:#fe0002"><?php echo $hqData['daily']['close_price']; ?></span>
			<span style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_price']; ?></span>
			<span style="color:#fe0002"><?php echo $prefix . $hqData['daily']['vary_portion'] . "%"; ?></span>
		</div>
	</div>
</div>
