<div class="hero-unit">
	<?php if ($success): ?>
	<p class="text-info">
		<small>
		帐号激活成功，请立即登录。
		</small>
	</p>	
	<p>
		<a class="btn btn-primary" href="<?php echo $this->createUrl('/member/account/login', array('username' => $email)); ?>" >登录</a>
	</p>
	<?php else: ?>
	<p class="text-info">
		<small>激活链接失效，点击按钮重新发送激活邮件。</small>
	</p>
	<p>
		<a class="btn btn-primary" href="<?php echo $this->createUrl('/member/account/sendmail', array('username' => $email)); ?>" >发送邮件</a>
	</p>
	<?php endif; ?>
</div>