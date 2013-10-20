<div class="container">
	<div class="span6">
			<div class="hd">
				<h3>修改密码</h3>
			</div>
			
			<div>
				<?php if (!$success): ?>
				<form id="modifyForm" method="post" class="form-horizontal" onsubmit="return submitForm();" >
					<div class="control-group">
						<div class="controls">										
							<span class="label label-important" id="tipsError"><?php echo $msg; ?></span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="oldPassword">密码</label>
						<div class="controls">
							<input type="password" name="oldPassword" id="oldPassword">
						</div>
					</div>
															
					<div class="control-group">
						<label class="control-label" for="password">新密码</label>
						<div class="controls">
							<input type="password" name="password" id="password">
							<span class="help-inline" id="passwordTip">最短8位，包含字母和数字</span>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="confirmPassword">确认密码</label>
						<div class="controls">
							<input type="password" name="confirmPassword" id="confirmPassword">
							<span class="help-inline" id="confirmPasswordTip">与密码输入一致</span>
						</div>
					</div>
															
					<div class="control-group">
						<div class="controls">
						    <button class="btn btn-primary btn-large" id="modify" type="submit">提交</button>
						</div>
					</div>
				</form>
				<?php else: ?>
				<div class="hero-unit">
					<p class="text-info">
						密码修改成功, 请重新登录。
					</p>
					<p>
						<b id="second">3</b> 秒后自动跳转到登录页面
					</p>
					<p>
						<a class="btn btn-primary" href="<?php echo $loginUrl; ?>" >马上登录</a>
					</p>
				</div>
			<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
// 提交表单
function submitForm()
{
	var ropwd = $("#oldPassword").val();
	var rpwd = $("#password").val();
	var rcpwd = $("#confirmPassword").val();

	if (!ropwd)
	{
		$("#tipsError").text("旧密码不能为空");
		return false;
	}
	
	if (!rpwd || !rpcwd)
	{
		$("#tipsError").text("新密码不能为空");
		return false;
	}

	if (rpwd.length < 8)
	{
		$("#tipsError").text("密码长度不足8个字符");
		return false;
	}

	if (rpwd == ropwd)
	{
		$("#tipsError").text("新密码不能与旧密码相同");
		return false;
	}
	
	if (rpwd != rpcwd)
	{
		$("#tipsError").text("两次输入的密码不一致");
		return false;
	}
	
	return true;
}	

function showTime()
{
	if (total > 0)
	{
		$("#second").text(total);
		total -= 1;
		timer = setTimeout('showTime()', 1000);
	}
	else
	{
		clearTimeout(timer);
		window.location.href = "<?php echo $loginUrl; ?>";
	}	
}

var total = 3;
var timer;

$(document).ready(function(){
	<?php if ($success): ?>
		showTime();
	<?php endif; ?>
});
</script>