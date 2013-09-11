<div class="container">
	<div class="row">
		<div class="offset1 span6">
			<div class="hd">
				<h3>欢迎登录贝瓦网</h3>
			</div>
			
			<div>

				<form id="loginForm" method="post" class="form-horizontal" onsubmit="return checkForm();" >
					<div class="control-group">
						<div class="controls">										
							<span class="label label-important" id="loginError"><?php echo $msg; ?></span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="username">帐号</label>
						<div class="controls">
							<?php echo CHtml::activeTextField($model, "username", array('id' => 'username', 'placeholder' => '邮箱')); ?>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" for="password">密码</label>
						<div class="controls">
							<?php echo CHtml::activePasswordField($model, "password", array('id' => 'password')); ?>
						</div>
					</div>
					
					<div class="control-group">
						<div class="controls">	
							<label class="checkbox">
								<?php echo CHtml::activeCheckBox($model, "rememberMe", array('checked' => 'checked')); ?><small>下次自动登录|</small>
						        <a href="<?php echo $this->createUrl('/member/account/forget'); ?>"><small>忘记密码</small></a> 
						    </label>						    				
						    <button class="btn btn-primary btn-large" type="submit">登录</button>
						</div>
					</div>
					
					<div class="control-group">
						<label class="control-label" ><small>其它帐号登陆</small></label>
						<div class="controls">
							<!-- <img src="" href="" /> -->
							
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
//检查表单数据项
function checkForm()
{
	var remail = $("#username").val();
	var rpwd = $("#password").val();

	if (!remail)
	{
		$("#loginError").text("邮箱不能为空");
		return false;
	}

	if (!rpwd)
	{
		$("#loginError").text("密码不能为空");
		return false;
	}
	
	if (!isEmail(remail))	// 检查邮箱格式
	{
		$("#loginError").text("邮箱格式有误");
		return false;
	}

	if (rpwd.length < 8)
	{
		$("#loginError").text("密码长度不足8个字符");
		return false;
	}

	return true;
}	
</script>