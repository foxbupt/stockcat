<div class="container">
	<div class="row">
		<div class="offset1 span6">
			<div class="hd">
				<h3>欢迎加入贝瓦网</h3>
			</div>
			
			<div>
				<form id="registerForm" method="post" class="form-horizontal" onsubmit="return checkForm();" >
					<div class="control-group">
						<label class="control-label" for="username">邮箱</label>
						<div class="controls">
							<?php echo CHtml::activeTextField($model, "username", array('id' => 'username')); ?>
							<span class="help-inline" id="usernameTip">接收到激活邮件才能完成注册</span>
							<span class="label label-important" id="usernameError"></span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="password">密码</label>
						<div class="controls">
							<?php echo CHtml::activePasswordField($model, "password", array('id' => 'password')); ?>
							<span class="help-inline" id="passwordTip">至少包含字母和数字, 最短8个字符, 区分大小写</span>
							<span class="label label-important" id="passwordError"></span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="confirmPassword">确认密码</label>
						<div class="controls">
							<?php echo CHtml::activePasswordField($model, "confirmPassword", array('id' => 'confirmPassword')); ?>
							<span class="help-inline" id="confirmPasswordTip">与密码输入一致</span>
							<span class="label label-important" id="confirmPasswordError"></span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="nickname">昵称</label>
						<div class="controls">
							<?php echo CHtml::activeTextField($model, "nickname", array('id' => 'nickname', 'class' => 'input-medium')); ?>
							<span class="help-inline" id="nicknameTip">起个响亮的名号吧</span>
							<span class="label label-important" id="nicknameError"></span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="verifyCode">验证码</label>
						<div class="controls">
							<?php echo CHtml::activeTextField($model,'verifyCode', array('id' => 'verifyCode', 'class' => 'input-small')); ?>
							<?php $this->widget('CCaptcha', array(
								'id' => 'verifyCodeImage',
								'buttonLabel' => '看不清，换一个',
								'clickableImage' => true,
							)); ?>
							<span class="label label-important" id="verifyCodeError"></span>
						</div>
					</div>
					
					<div class="control-group">
						<div class="controls">
							<label class="checkbox">
						        <input type="checkbox" checked="checked"> 我已经认真阅读并同意笨财猫的《<a href="<?php echo $this->createUrl('/about/policy'); ?>" target="_blank" class="info">使用协议</a>》
						    </label>
						    <button class="btn btn-primary btn-large" id="register" type="submit">注册</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<div class="span3" id="sidebar">
			<!--   -->
		</div>
	</div>
</div>

<script type="text/javascript">
var validEmailUrl = "<?php echo CHtml::normalizeUrl(array('/member/account/validateEmail')); ?>";
var fields = ['username', 'password', 'confirmPassword', 'nickname', 'verifyCode'];
var names = ['邮箱', '密码', '确认密码', '昵称', '验证码'];
var isValidMap = {};
for(var i = 0; i < fields.length; i++)
{
	isValidMap[fields[i]] = true;
}

$(document).ready(function(){
	$("#email").focus();
	
	var errors = <?php echo json_encode($model->getErrors()); ?>;
	for(var attr in errors)
	{
		if(isValidMap[attr])
		{
			isValidMap[attr] = false;
			//var elem = $("#" + attr + "_error");
			//elem.html(errors[attr][0]);
			//elem.css('display', 'block');
			showError(attr, errors[attr][0]);
		}
	}		
});

//显示错误信息
function showError(fieldName, msg)
{
	// alert(msg);
	if (fieldName != 'verifyCode')
	{
		$("#" + fieldName + "Tip").hide();
	}
	
	$("#" + fieldName + "Error").text(msg).show();
}


// 检查表单数据项
function checkForm()
{
	var remail = $("#username").val();
	var rpwd = $("#password").val();
	var rcpwd = $("#confirmPassword").val();
	var rname = $("#nickname").val();
	var rcode = $("#verifyCode").val();

	for(var i = 0; i < fields.length; i++)
	{
		var fieldName = fields[i];
		$("#" + fieldName + "Error").text('').hide();
		
		if (!$("#" + fieldName).val())
		{
			alert(fieldName);
			showError(fieldName, names[i] + "不能为空");
			return false;
		}
	}

	if (!isEmail(remail))	// 检查邮箱格式
	{
		showError("username", "邮箱格式有误");
		return false;
	}

	if (rpwd.length < 8)
	{
		showError("password", "密码长度不足8个字符");
		return false;
	}

	if (rpwd.length != rcpwd.length)
	{
		showError("confirmPassword", "两次输入的密码不一致");
		return false;
	}

	if ((rname.length < 2) || (rname.length > 16))
	{
		showError("nickname", "昵称长度位于2-16个字符");
		return false;
	}

	if (rcode.length != 4)
	{
		showError("verifyCode", "请输入4位验证码");
		return false;
	}

	return true;
}
</script>
