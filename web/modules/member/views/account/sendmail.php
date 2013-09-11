<div class="hero-unit">
	<?php if ($msg): ?>
	<p class="text-error">
		<b>
		<?php echo $msg; ?>
		</b>
	</p>	
	<?php else: ?>
	<p>
		<small>
		激活邮件已经发送到您的邮箱，请马上激活。
		</small>
	</p>	
	<p>
		<small>
		邮件没收到? <a href="<?php echo $this->createUrl('/member/account/sendmail', array('username' => $email)); ?>" >再次发送</a>
		</small>
	</p>
	<p>
		<a class="btn btn-primary" href="<?php echo $emailLink; ?>" target="_blank">进入邮箱</a>
	</p>
	<?php endif; ?>
</div>