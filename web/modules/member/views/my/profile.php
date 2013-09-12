<div class="container">
	<div class="span6">
		<div class="hd">
			<h3>完善个人资料</h3>
		</div>
		
		<div>
			<form id="profileForm" method="post" class="form-horizontal" onsubmit="return submitForm();" >
				<div class="control-group">
					<div class="controls">										
						<span class="label label-important" id="tipsError"><?php echo empty($field)? $msg : ""; ?></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="nickname">昵称</label>
					<div class="controls">
						<?php echo CHtml::activeTextField($model, "nickname", array('id' => 'nickname')); ?>
						<span class="label label-important" id="nicknameError"></span>
					</div>
				</div>
														
				<div class="control-group">
					<label class="control-label" for="gender">性别</label>
					<div class="controls">
						<?php echo CHtml::activeDropdownList($model, "gender", array('' => '请选择', 'M' => '男', 'F' => '女'), array('id' => 'gender')); ?>
						<span class="label label-important" id="genderError"></span>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label" for="mobile_no">手机号</label>
					<div class="controls">
						<?php echo CHtml::activeTextField($model, "mobile_no", array('id' => 'mobile_no')); ?>
						<span class="label label-important" id="mobile_noError"></span>
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
var fields = ['nickname', 'gender', 'mobile_no'];

// 提交表单
function submitForm()
{
	var rnickname = $("#nickname").val();
	var rmobile_no = $("#mobile_no").val();

	if (!rnickname || (rname.length < 2) || (rname.length > 16))
	{
		$("#nicknameError").text("昵称长度位于2-16个字符");
		return false;
	}
	
	if (rmobile_no.length != 13)
	{
		$("#mobile_noError").text("手机号位数不对");
		return false;
	}
	
	return true;
}	

// 清除所有错误
function clearErrors()
{
	$("#tipsError").text('');
	for(var i = 0; i < fields.length; i++)
	{
		$("#" + fields[i] + "Error").text('');
	}
}

// 显示错误信息
function showError(field, msg)
{
	if (field)
	{
		$("#" + field + "Error").text(msg);
	}
	else
	{
		$("#tipsError").text(msg);
	}
}

$(document).ready(function(){
	clearErrors();	
	showError('<?php echo $field; ?>', '<?php echo $msg; ?>');
});
</script>