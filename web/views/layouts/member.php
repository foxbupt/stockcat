<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo CHtml::encode($this->pageTitle); ?></title>
		<meta name="keywords" content="<?php echo $this->keywords; ?>" />
		<meta name="description" content="<?php echo $this->description; ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" type="text/css" href="/static/css/center.css" />
		<link rel="shortcut icon" href="/favicon.ico" />
		<?php Yii::app()->bootstrap->register(); ?>
		<script src="/static/js/common.js"></script>
	
		<!--[if lt IE 7]>
		<script src="http://s.beva.cn/js/DD_belatedPNG_0.0.8a.js"></script>
		<script>DD_belatedPNG.fix('*');</script>
		<![endif]-->
	</head>
	
	<body>
		<div id="page">
			<div class="container" id="header">
				<div class="navbar">
					<div class="navbar-inner">
						<div class="container">
							<a class="brand" href="#">笨财猫</a>
							<ul class="nav">
								<li><a href="#">首页</a></li>
								<li><a href="/news">资讯</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/stock/report/list'); ?>">研报</a></li>
								<li><a href="/faq">使用帮助</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			
			<div class="container" id="content">	
				<div class="container-fluid" style="min-height:600px;">
					<div class="row-fluid">
						<div class="span2">
							<p>
								欢迎你, <strong><?php echo Yii::app()->user->getName(); ?></strong>
							</p>
							<p>
								<small><a href="<?php echo Yii::app()->createUrl('/member/account/logout'); ?>">退出</a></small>
							</p>							
							<ul class="nav nav-list">
								<li class="nav-header"></li>
								<li class="active"><a href="<?php echo Yii::app()->createUrl('/member/policy/index'); ?>">管理分析器</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/member/my/setting'); ?>">个人设置</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/member/my/profile'); ?>">基本资料</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/member/my/password'); ?>">修改密码</a></li>
							</ul>
						</div>
						<div class="span10">
							<?php echo $content; ?>
						</div>
					</div>
				</div>
		
				<div class="clear"></div>
				
				<div id="links" class="container">
					<div class="offset1 span6 well well-small">
						<h5>友情链接</h5>
						<ul class="inline">
							<li><a target="_blank" href="http://www.beva.com">贝瓦网</a></li>
							<li><a target="_blank" href="http://www.pad4fun.com">平板之家</a></li>
							<li><a target="_blank" href="http://www.ibibikan.com">早教比比看</a></li>
						</ul>
					</div>
				</div>
				
				<div id="footer" style="align:center;">
					<div class="offset3">
						<p><strong>本网站不包含人工荐股功能, 所有信息来源于各大财经网站。 </strong></p>
						<p>Copyright &copy; 2012-<?php echo date('Y'); ?> 笨财猫 | <a href="/sitemap.html" target="_blank">站点地图</a> </p>
						<p><small>联系我们: bencaimao#gmail.com （请用@替换#）</small></p>
					</div>
				</div><!-- footer -->
		
			</div><!-- content -->
		</div>
	</body>
	
<script>
function setPageHeight()
{
    var size = bevaGetPageSize();
    document.getElementById("page").style.height = size.windowH + "px";
}
setPageHeight();
</script>
 	
</html>
