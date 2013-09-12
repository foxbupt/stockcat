<div class="container">
	<div class="row">
		<div class="span3">
			<dl class="dl-horizontal">
				<dt>名称: </dt>
				<dd><?php echo $policyInfo['name']; ?></dd>
				<dt>类型: </dt>
				<dd><?php echo $policyTypes[$policyInfo['type']]; ?></dd>
				<dt>说明：</dt>
				<dd><?php echo $policyInfo['remark'];?>
			</p>
		</div>
	</div>		
				
	<div class="span8">		
		<div class="row">
			<button class="btn btn-primary pull-right" type="button" id="addBtn">添加</button>
		</div>
	
		<div class="row">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>Id</th>
						<th>变量名</th>
						<th>运算符</th>
						<th>变量值</th>
						<th>参数值</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($itemList as $itemInfo): ?>
						<tr>
						<?php if (!isset($itemInfo['vid'])): ?>
							<td></td>
							<td colspan="4"><?php echo empty($itemInfo['name'])? PolicyUtil::$lopMap[$itemInfo['logic']]: $itemInfo['name']; ?></td>
							<td></td>
							
						<?php else: ?>
							<td><?php echo $itemInfo['id']; ?></td>
							<td><?php echo $varList[$itemInfo['vid']]['name']; ?></td>
							<td><?php echo PolicyUtil::$eopMap[$itemInfo['optor']]; ?></td>
							<td><?php echo $itemInfo['value']; ?></td>
							<td><?php echo $itemInfo['param']; ?></td>
							<td>
								<div class="btn-group">
									<button class="btn" type="button" name="editBtn" data-item="<?php echo json_encode($itemInfo); ?>" >编辑</button>
									<button class="btn" type="button" onclick="javascript:deleteItem(<?php echo $pid; ?>, <?php echo $itemInfo['id']; ?>)">删除</button>
								</div>
							</td>
						<?php endif; ?>
						</tr>							
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	
	<!--  编辑弹出的浮层  -->
	<div id="itemModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
		   <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		   <h3 id="myModalLabel">编辑条件项</h3>
		</div>
		
 		<div class="modal-body">
   			<form id="itemForm" class="form-horizontal" form-url="" >
   				<input type="hidden" name="pid" id="pid" value="<?php echo $pid; ?>" />
   				<input type="hidden" name="item_id" id="item_id" value="" />
   				
   				<div class="control-group">
					<div class="controls">							
						<span class="label label-important" id="tipsError"></span>
					</div>
				</div>
				
   				<div class="control-group">
					<label class="control-label" for="vid">变量名</label>
					<div class="controls">
						<?php echo CHtml::dropDownList("Item[vid]", 0, $this->encodeDropdownList(CHtml::listData($varList, 'id', 'name')), array('id' => 'vid')); ?>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="optor">操作符</label>
					<div class="controls">
						<?php echo CHtml::dropDownList("Item[optor]", 0, $this->encodeDropdownList(PolicyUtil::$eopMap), array('id' => 'optor')); ?>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="cval">变量值</label>
					<div class="controls">
						<?php echo CHtml::textField("Item[value]", '', array('id' => 'cval')); ?>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="param">参数</label>
					<div class="controls">
						<?php echo CHtml::textField("Item[param]", '', array('id' => 'param')); ?>
						<span class="help-inline">参数可选</span>
					</div>
				</div>
				
   			</form>
 		</div>
 		
 		<div class="modal-footer">
   			<button class="btn btn-primary" onclick="javascript:submitForm();">提交</button>
   			<button class="btn" data-dismiss="modal" aria-hidden="true">取消</button>
 		</div>
	</div>
</div>

<script type="text/javascript">

// 删除条件项
function deleteItem(pid, itemId)
{
	if (confirm('确定删除?'))
	{
		$.ajax({
				type: 'post',
				dataType: 'json',
				url: '<?php echo $deleteUrl; ?>',
				data: {'pid': pid, 'item_id': item_id},
				success: function(data)
				{
					if (data.errorCode == 0) // 删除成功
					{
						window.location.reload(true);
					}
					else
					{
						alert(data.message);
					}
				}
			});
	}
}

// 修改条件项
function submitForm()
{
	var url = $("#itemForm").attr('form-url');
	var postParam = $("#itemForm").serializeArray();
		
	$.ajax({
			type: 'post',
			dataType: 'json',
			url: url,
			data: postParam,
			success: function(data)
			{
				if (data.errorCode == 0) // 修改成功
				{
					window.location.reload(true);
				}
				else
				{
					$("#tipsError").text(data.message);				
				}
			}
	});
}

$(document).ready(function(){
	// 编辑条件项弹出modal
	$("[name='editBtn']").click(function(){
		var itemInfo = eval("'" + $(this).attr('data-item') + "'");
		// $("#pid").val(itemInfo['pid']);
		$("#item_id").val(itemInfo['id']);
		
		$("#vid").val(itemInfo['vid']);
		$("#optor").val(itemInfo['optor']);
		$("#cval").val(itemInfo['value']);
		$("#param").val(itemInfo['param']);

		$("#itemForm").attr('form-url', '<?php echo $modifyUrl; ?>');	

		$("#myModalLabel").text("编辑条件项");
		$('#itemModal').modal('show');
	});

	// 创建弹出modal
	$("#addBtn").click(function(){
		$("#myModalLabel").text("添加条件项");
		$("#itemForm").attr('form-url', '<?php echo $addUrl; ?>');
		$("#itemModal").modal('show');
	});
});

</script>