<div class="container">
	<div class="span6">
		<div class="hd">
			<h3>编辑分析器</h3>
		</div>
		
		<div>
			<form id="policyForm" method="post" class="form-horizontal" onsubmit="return submitForm();" >
				<div class="control-group">
					<div class="controls">	
						<?php if (!$isAdd || ($isAdd && $result)): ?>
						<a class="btn btn-primary" href="<?php echo $this->createUrl('view', array('pid' => $pid)); ?>">查看分析器</a>									
						<?php endif; ?>
						<span class="label label-important" id="tipsError"><?php echo $msg; ?></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="name">名称</label>
					<div class="controls">
						<?php echo CHtml::activeTextField($model, "name", array('id' => 'name')); ?>
						<span class="label label-important" id="nameError"></span>
					</div>
				</div>
														
				<div class="control-group">
					<label class="control-label" for="type">类型</label>
					<div class="controls">
						<?php echo CHtml::activeDropdownList($model, "type", $policyTypes, array('id' => 'type')); ?>
						<span class="label label-important" id="typeError"></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="remark">描述</label>
					<div class="controls">
						<?php echo CHtml::activeTextArea($model, "remark", array('id' => 'remark', 'cols' => 120, 'rows' => 5)); ?>
						<span class="label label-important" id="remarkError"></span>
					</div>
				</div>
														
				<div class="control-group">
					<div class="controls">
					    <button class="btn btn-primary btn-large" id="modify" type="submit">提交</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">

// 提交表单
function submitForm()
{
	var rname = $("#name").val();
	var rtype = $("#type").val();
	var rremark = $("#remark").val();

	if (!rname )
	{
		$("#nameError").text("名称不能为空");
		$("#name").foucs();
		return false;
	}
	
	if (rname.length > 128)
	{
		$("#nameError").text("名称长度不能超过128");
		$("#name").foucs();
		return false;
	}

	if (rtype == 0)
	{
		$("#typeError").text("请选择分析器类型");
		$("#type").foucs();
	}
	
	if (rremark && rremark.length > 1024)
	{
		$("#remarkError").text("描述长度不能超过1024");
		$("#remark").foucs();
		return false;
	}
	
	return true;
}	

</script>