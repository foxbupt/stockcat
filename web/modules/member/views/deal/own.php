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
			<h3>当前持仓</h3>
		</div>
		
		<div id="main">
			<p class="pull-right">
				<a class="btn btn-primary" type="button" deal_type="1" id="buyBtn">买入</a>
				<a class="btn btn-primary" type="button" deal_type="2" id="sellBtn">卖出</a>
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
					<?php $viewUrl = Yii::app()->createUrl('/stock/stock/index', array('sid' => $sid)); ?>
					
					<tr class="pull-center">
                        <td><a href="<?php echo $viewUrl;?>" target="_blank"><?php echo $sid; ?></a></td>
                        <td><?php echo $stockInfo['name']; ?></td>
						<td><a href="<?php echo $qqhqUrl; ?>" target="_blank"><?php echo $stockInfo['code']; ?></a></td>

						<td><?php echo date('Y/m/d', strtotime($holdInfo['day'])); ?></td>
						<td><?php echo $holdInfo['count']; ?></td>
						<td><?php echo CommonUtil::formatNumber($holdInfo['price']); ?></td>
						<?php $cost = $holdInfo['count'] * $holdInfo['price']; ?>
						<td><?php echo CommonUtil::formatNumber($cost); ?></td>
						<td><?php echo $dailyData['close_price']; ?></td>
						<?php $amount = $holdInfo['count'] * $dailyData['close_price']; ?>
						<td><?php echo CommonUtil::formatNumber($amount); ?></td>
						<td class="<?php echo ($amount >= $cost)? 'red': 'green'; ?>"><?php echo $amount - $cost; ?></td> 
                        <td class="<?php echo ($amount >= $cost)? 'red': 'green'; ?>"><?php echo CommonUtil::formatNumber(($amount - $cost) / $amount * 100, CommonUtil::FORMAT_TYPE_PORTION); ?> </td>
                        
                        <?php if (isset($hqData['policy'])): ?>
                        <td><?php echo $trendMap[$hqData['policy']['trend']]; ?></td>
                        <td class="<?php echo ($hqData['policy']['op'] == CommonUtil::OP_BUY)? 'red' : 'green'; ?>"><?php echo $opMap[$hqData['policy']['op']]; ?></td>
                        <?php else: ?>
                        <td>-</td>
                        <td>-</td>
                        <?php endif; ?>
                        <td>
                        	<button type="button" class="btn btn-primary" deal_type="1" id="buyBtn-<?php echo $stockInfo['code']; ?>">买入</button>
							<button type="button" class="btn btn-primary" deal_type="2" id="sellBtn-<?php echo $stockInfo['code']; ?>">卖出</button>
						</td>
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
				var code = response.errorCode;
				if (0 == code) {
					$('#dealModal').modal('hide');
				} else {
					$("#msg").text(response.msg);
					return;
				}
			});	
	});
});
</script>
