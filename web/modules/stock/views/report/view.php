<div class="container">
	<div class="offset2">
		<h2><?php echo $record->title; ?></h2>
	</div>
	<div class="meta-block muted">
		<span class="meta">
			机构：<?php echo $record->agency; ?>
		</span>
		<span class="meta">
			日期: 
			<?php echo date('Y年m月d日', strtotime($record->day)); ?>
		</span>
		
		<?php if ($record->goal_price > 0): ?>
		<span class="meta">
			目标价: 
			<?php echo $record->goal_price; ?>元
		</span>
		<?php endif; ?>
	</div>
	<div class="content">
		<?php echo $record->content; ?>
	</div>
</div>