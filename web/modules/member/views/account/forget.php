<div class="container">
	<div class="row">
		<div class="offset1 span6">
			<div class="hd">
				<h3>找回密码</h3>
			</div>
			
			<div>
				<?php if ($isFirstStep): ?>
				<form id="forgetForm" method="post" class="form-horizontal" onsubmit="return submitForgetForm();" >
					<div class="control-group">
						<div class="controls">										
							<p class="text-info">
								<small>提示：请输入您登录的Email地址，系统会发送密码重设信到您的邮箱，然后根据邮件提示，进行操作。</small>
							</p>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="email">登录邮箱</label>
						<div class="controls">
							<input type="text" name="email" id="email">
							<span class="label label-important" id="errorTips"><?php echo $msg; ?></span>
						</div>
					</div>
					
					<div class="control-group">
						<div class="controls">												    				
						    <button class="btn btn-primary btn-large" type="submit">发送邮件</button>
						</div>
					</div>					
				</form>
				<?php else: ?>
				<div class="hero-unit">
					<p class="text-info">
						<small>
						邮件已经发送到您的邮箱<em class="text-success"><?php echo $email; ?></em>
						</small>
					</p>
					<p class="text-info">
						<small>您可以根据邮件中的操作找回您的密码</small>
					</p>
					<p>
						<a class="btn btn-primary" href="<?php echo $emailLink; ?>" target="_blank">进入邮箱</a>
					</p>
				</div>
				<?php endif;?>
			</div>
		</div>
		
		<div class="span3" id="sidebar">
			<!--   -->
		</div>
	</div>
</div>

<script type="text/javascript">
//检查表单数据项
function submitForgetForm()
{
	var remail = $("#email").val();

	if (!remail)
	{
		$("#errorTips").text("邮箱不能为空");
		return false;
	}

	if (!isEmail(remail))	// 检查邮箱格式
	{
		$("#errorTips").text("邮箱格式有误");
		return false;
	}

	return true;
}	
</script>