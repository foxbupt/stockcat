<div class="container">
	<div class="span6">
		<div class="hd">
			<h3>分析器列表</h3>
		</div>
		
		<div>
			<p>
				<a class="btn btn-primary" type="button" href="<?php echo $this->createUrl('/member/policy/add');?>">创建分析器</a>
			</p>
			<table class="table table-bordered">
				<caption>当前共有<strong><?php echo count($policyList); ?></strong>个分析器</caption>
				<thead>
					<tr>
						<th>名称</th>
						<th>类型</th>
						<th>创建时间</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($policyList as $policyInfo): ?>
					<?php $viewUrl = $this->createUrl('/member/policy/view', array('pid' => $policyInfo['id'])); ?>
					<?php $modifyUrl = $this->createUrl('/member/policy/modify', array('pid' => $policyInfo['id'])); ?>
					<?php $deleteUrl = $this->createUrl('/member/policy/delete'); ?>
					<tr>
						<td><a href="<?php echo $viewUrl; ?>" target="_self"><?php echo $policyInfo['name']; ?></a></td>
						<td><?php echo $policyTypes[$policyInfo['type']]; ?></td>
						<td><?php echo date('Y/m/d', $policyInfo['create_time']); ?></td>
						<td>
							<div class="btn-group">
								<button class="btn btn-primary" type="button" btype="open" link="<?php echo $viewUrl; ?>">查看</button>
								<button class="btn btn-primary" type="button" btype="open" link="<?php echo $modifyUrl; ?>" >编辑</button>
								<button class="btn btn-primary" type="button" onclick='javascript:deletePolicy("<?php echo $deleteUrl; ?>", <?php echo $policyInfo['id']; ?>)' >删除</button>
							</div>
						</td>
					</tr>	
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script type="text/javascript">
// ajax提交删除分析器, TODO: 换成bootstrap的alert
function deletePolicy(url, pid)
{
	if (confirm('确定删除?'))
	{
		$.ajax({
				type: 'post',
				dataType: 'json',
				url: url,
				data: {'pid': pid},
				success: function(data)
				{
					if (data.errorCode == 0) // 删除成功
					{
						window.location.reload(true);
					}
					else
					{
						alert(data.message);
					}
				}
			});
	}
}

$(document).ready(function(){
	$("[btype='open']").click(function(){
		window.open($(this).attr('link'), '_blank');
	});
});
</script>
