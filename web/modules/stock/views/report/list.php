
<div class="container">
	<div class="pull-right">
		<small>当前第<?php echo $pageNo;?>页有<?php echo $realCount;?>条记录，总共<?php echo $totalPageCount;?>页.</small>
	</div>
	
	<div id="report-list">
		<ul style="list-style:none;">
		<?php foreach ($recordList as $record): ?>
			<li>
				<div>
					<h3 class="title"><a href="<?php echo $this->createUrl('/stock/report/view', array('id' => $record->id)); ?>" target="_blank"><?php echo $record->title; ?></a></h3>
					<div class="info-block muted">
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
					<div>
						<?php echo ReportController::getDigest($record->content); ?>
						<a href="<?php echo $this->createUrl('/stock/report/view', array('id' => $record->id)); ?>" target="_blank" class="show_more">更多</a>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
		</ul>
	</div>
	
	<div class="pagination">
		<?php if ($totalPageCount > 1): ?>
		<ul>
			<?php if ($pageNo > 1): ?>
		    <li><a href="<?php echo $this->createUrl('/stock/report/list', array('page_no' => $pageNo - 1)); ?>">上一页</a></li>
		    <?php endif; ?>
		    <?php foreach (range(1, $totalPageCount) as $pageIndex): ?>
		    	
		    	<?php if ($pageIndex == $pageNo): ?>
		    	<li><?php echo $pageNo; ?></li>
		    	<?php else: ?>
		    	<li><a href="<?php echo $this->createUrl('/stock/report/list', array('page_no' => $pageIndex)); ?>"><?php echo $pageIndex?></a></li>
				<?php endif; ?>
		    <?php endforeach; ?>
		    <?php if ($pageNo < $totalPageCount): ?>
		    <li><a href="<?php echo $this->createUrl('/stock/report/list', array('page_no' => $pageNo + 1)); ?>">下一页</a></li>
		    <?php endif; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>
