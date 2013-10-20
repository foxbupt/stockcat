<link rel="stylesheet" href="/static/jqTree/jqtree.css">
<script src="/static/jqTree/tree.jquery.js"></script>

<div class="container">			
	<div class="span6">
		<div class="row">
			<dl class="dl-horizontal">
				<dt>名称: </dt>
				<dd><?php echo $policyInfo['name']; ?></dd>
				<dt>类型: </dt>
				<dd><?php echo $policyTypes[$policyInfo['type']]; ?></dd>
				<dt>说明：</dt>
				<dd><?php echo $policyInfo['remark'];?>
			</dl>
		</div>		
		
		<div class="row-fluid">
			<div class="span4 offset2">
				<span style="color:red;" id="error"></span>
			</div>
			<div class="span4 offset6">
				<ul class="nav nav-pills pull-right">
					<li><button class="btn btn-primary" type="button" id="addBtn">添加</button></li>
					<li><button class="btn btn-primary" type="button" id="editBtn">编辑</button></li>
					<li><button class="btn btn-primary" type="button" id="deleteBtn">删除</button></li>
				</ul>
			</div>
		</div>
	
		<div class="row">
			<div id="policyTree" data-url="<?php echo $treeDataUrl; ?>"></div>
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
   				<input type="hidden" name="Item[parent_id]" id="parent_id" value="" />
   				
   				<div class="control-group">
					<div class="controls">							
						<span class="label label-important" id="tipsError"></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="name">名称</label>
					<div class="controls">
						<?php echo CHtml::textField("Item[name]", '', array('id' => 'cname')); ?>
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
				
				<div class="control-group" id="logicControl">
					<label class="control-label" for="logic">逻辑关系</label>
					<div class="controls">
						<?php echo CHtml::dropDownList("Item[logic]", 1, $this->encodeDropdownList(PolicyUtil::$lopMap), array('id' => 'logic')); ?>
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
				data: {'pid': pid, 'item_id': itemId},
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

// 条件项表单提交
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
					$("#error").text(data.message);				
				}
			}
	});
}

$(document).ready(function(){
	var pid = <?php echo $pid; ?>;
	var rootId = <?php echo $policyInfo['root_item']; ?>;

	$('#policyTree').tree({		
		autoOpen: true,
		dragAndDrop: true,
		onCanMove: function(node) {
	        return (node.id != rootId);
	    },	
	});

	// 监听选中节点事件
	$('#policyTree').bind(
	    'tree.select',
	    function(event) {
	        if (event.node)
		    {
			    if (event.node.id == rootId)	// 根节点时隐藏删除按钮
			    {
					$("#deleteBtn").hide();
			    }
			    else
			    {
			    	$("#deleteBtn").show();
			    }				    
	        }
	    }
	);

	// 监听移动节点事件
	$('#policyTree').bind(
	    'tree.move',
	    function(event) {
	        move_node = event.move_info.moved_node;
	        target_node = event.move_info.target_node;
	        position = event.move_info.position;

	       	// 2个节点本来在同一层, 前后移动不需要提交服务器
	        if ((move_node.parent_id == target_node.parent_id) && ((position == "before") || (position == "after")))
	        {
		        return;
	        }

	        $.ajax({
				type: 'post',
				dataType: 'json',
				url: "<?php echo $moveUrl; ?>",
				data: {'pid': pid, 'mid': move_node.id, 'tid': target_node.id, 'position': position},
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
	);
		
	// 编辑条件项弹出modal
	$("#editBtn").click(function(){
		var curNode = $("#policyTree").tree('getSelectedNode');
		if (!curNode)
		{
			$("#error").text('请选择一个条件项');
			return false;
		}

		$("#item_id").val(curNode.id);
		$("#parent_id").val(curNode.parent_id);
		$("#cname").val(curNode.name);
		$("#vid").val(curNode.vid);
		$("#optor").val(curNode.optor);
		$("#cval").val(curNode.value);
		$("#param").val(curNode.param);

		if (curNode.node_type == 1) // 非叶子节点
		{
			$("#logicControl").hide();
		}
		else	// 叶子节点
		{
			$("#logicControl").show();
		}
		
		$("#itemForm").attr('form-url', '<?php echo $modifyUrl; ?>');	
		$("#myModalLabel").text("编辑条件项");
		$('#itemModal').modal('show');
	});

	// 创建弹出modal
	$("#addBtn").click(function(){
		var curNode = $("#policyTree").tree('getSelectedNode');
		if (!curNode)
		{
			alert('请选择一个条件项');
			return false;
		}

		if (curNode.node_type == 1) // 非叶子节点
		{
			$("#logicControl").hide();
		}
		else	// 叶子节点
		{
			$("#logicControl").show();
		}
		 
		$("#parent_id").val(curNode.id);
		$("#myModalLabel").text("添加条件项");
		$("#itemForm").attr('form-url', '<?php echo $addUrl; ?>');
		$("#itemModal").modal('show');
	});

	$("#deleteBtn").click(function(){
		var curNode = $("#policyTree").tree('getSelectedNode');
		if (!curNode)
		{
			alert('请选择一个条件项');
			return false;
		}
		else if (curNode.id == rootId) // 不能删除根节点
		{
			alert('不允许删除根节点');
			return false;
		}

		if (1 == curNode.node_type)
		{
			alert('该节点的子节点会自动上移');
		}
		deleteItem(pid, curNode.id);
	});
	
	
	// 默认选中根节点
	// $tree.tree('selectNode', $tree.tree('getNodeById', <?php echo $policyInfo['root_item']; ?>));	
});

</script>