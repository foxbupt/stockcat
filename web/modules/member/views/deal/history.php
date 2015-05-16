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
						<th>操作</th>					
					</tr>
				</thead>
				<tbody>
					<?php foreach ($historyList as $sid => $holdInfo): ?>
                    <?php $stockInfo = $stockMap[$sid]; ?>
                    <?php $dealList = $dealMap[$sid]; ?>
                    
                    <?php $qqhqUrl = CommonUtil::getHQUrl($stockInfo['code']); ?>
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo date('Y/m/d', strtotime($holdInfo['day'])); ?></td>
						<td><?php echo date('Y/m/d', strtotime($holdInfo['close_day'])); ?></td>
						<td><?php echo $holdInfo['count']; ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['price']); ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['cost']); ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['amount']); ?></td>
						<td class="<?php echo ($holdInfo['profit'] >= 0)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($holdInfo['profit']); ?></td> 
                        <td class="<?php echo ($holdInfo['profit'] >= 0)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber($holdInfo['profit_portion'], CommonUtil::FORMAT_TYPE_PORTION); ?> </td>
						
						<td>
							<a class="btn btn-primary" data-toggle="collapse" href="#detail-<?php echo $holdInfo['sid'] . "-" . $holdInfo['batch_no'] ;?>" aria-expanded="false" aria-controls="collapseExample">详情</a>											
						</td>
						<div class="collapse" id="poolDetail-<?php echo $holdInfo['sid'] . "-" . $holdInfo['batch_no'] ;?>">
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
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	
	<div class="modal fade" id="dealModal" tabindex="-1" role="dialog" aria-labelledby="dealModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
		    	<div class="modal-header">
		    		<button type="button" class="close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true">&times;</span></button>
		        	<h4 class="modal-title" id="dealModalLabel">股票交易-</h4>
		      	</div>
		      	<div class="modal-body">
		        	<form id="dealForm" action="">	
		        		<input type="hidden" id="deal_type" value="1">
		        		<div class="form-group">
			            	<label for="code" class="control-label">代码:</label>
			            	<input type="text" class="form-control" id="code">
			          	</div>	
			          	<div class="form-group">
			            	<label for="day" class="control-label">日期:</label>
			            	<input type="text" class="form-control" id="day">
			          	</div>	        	
			        	<div class="form-group">
			            	<label for="count" class="control-label">数目:</label>
			            	<input type="text" class="form-control" id="count">
			          	</div>
			          	<div class="form-group">
			            	<label for="price" class="control-label">价格:</label>
			            	<input class="form-control" id="price"></textarea>
			          	</div>
			          	<div class="form-group">
			            	<label id="msg" class="control-label"></label>
			          	</div>
			        </form>
		      	</div>
		      	<div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
			        <button type="button" class="btn btn-primary" id="dealButton">确定</button>
		      	</div>
			</div>
		</div>
	</div>
	
</div>

<script type="text/javascript">
$(document).ready(function(){
	$('#dealModal').on('show', function (event) {
		//var button = $(event.relatedTarget); // Button that triggered the modal
		//var type = button.attr('deal_type'); // Extract info from data-* attributes
		//var code = button.attr('code');

		var modal = $(this);
        var type = $("#deal_type").val();
		var title = "";
		
		if (type == "1") {
			title = "股票交易-买入";
		} else {
			title = "股票交易-卖出";
		}	

		modal.find('.modal-title').text(title);
	});

    $("#main .btn").click(function(){
        var id = $(this).attr('id');
        //alert(id);
        var type = $(this).attr('deal_type');      
        //alert(type);
        $("#deal_type").val(type);

        var code = "";
        if (id.indexOf("-") != -1) {
            code = id.substring(id.indexOf("-")+1);
        }
		$("#dealModal").find(".modal-body [id='code']").val(code);
        $("#dealModal").modal();
    });

	$("#dealButton").click(function(){
		var type = $("#deal_type").val();
		var code = $("#code").val();
		var day = $("#day").val();
		var price = $("#price").val();
		var count = $("#count").val();
        //alert(type);
        //alert(code);

		if ((count <= 0) || (price <= 0)) {
			$("#msg").text("价格或数量不能为0");
			return;
		}
		
		if (type == "1")
		{
			url = "<?php echo Yii::app()->createUrl('/member/deal/buy'); ?>";
		} else {
			url = "<?php echo Yii::app()->createUrl('/member/deal/sell'); ?>";
		}

		$.post(url, {'code': code, 'day': day, 'count': count, 'price': price}, function(response) {
				var errorCode = response.errorCode;
				if (0 == errorCode) {
					$('#dealModal').modal('hide');
                    window.location.reload();
				} else {
					$("#msg").text(response.msg);
					return;
				}
			}, 'json');	
	});
});
</script>
