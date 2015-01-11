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
								<li><a href="/">首页</a></li>
								
								<li class="dropdown">
								  <a class="dropdown-toggle" id="poolMenu" data-toggle="dropdown">
								    	股票池 <span class="caret"></span>
								  </a>
								  <ul class="dropdown-menu" role="menu" >
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/index'); ?>">A股</a></li>
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/index', array('location' => CommonUtil::LOCATION_US)); ?>">美股</a></li>
								  </ul>
								</li>
								
								<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/realtime'); ?>">实时上涨列表</a></li>
								
								<li class="dropdown">
								  <a class="dropdown-toggle" data-toggle="dropdown">
								    	昨日涨停列表 <span class="caret"></span>
								  </a>
								  <ul class="dropdown-menu" role="menu" >
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/uplimit'); ?>">A股</a></li>
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/uplimit', array('location' => CommonUtil::LOCATION_US)); ?>">美股</a></li>
								  </ul>
								</li>
								
								<li class="dropdown">
								  <a class="dropdown-toggle" data-toggle="dropdown">
								    	价格突破列表 <span class="caret"></span>
								  </a>
								  <ul class="dropdown-menu" role="menu" >
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/threshold'); ?>">A股</a></li>
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/threshold', array('location' => CommonUtil::LOCATION_US)); ?>">美股</a></li>
								  </ul>
								</li>
								
								<li class="dropdown">
								  <a class="dropdown-toggle" data-toggle="dropdown">
								    	趋势突破列表 <span class="caret"></span>
								  </a>
								  <ul class="dropdown-menu" role="menu" >
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/upresist'); ?>">A股</a></li>
								    <li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo Yii::app()->createUrl('/stock/pool/upresist', array('location' => CommonUtil::LOCATION_US)); ?>">美股</a></li>
								  </ul>
								</li>
								
								<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rapid', array('rise' => 1)); ?>">快速拉升列表</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/stock/pool/rapid', array('rise' => 0)); ?>">快速下跌列表</a></li>
								<li><p class="navbar-text">|</p></li>
								<li><a href="/news">资讯</a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/stock/report/list'); ?>">研报</a></li>
								<li><a href="/faq">使用帮助</a></li>
							</ul>
							
							<ul class="nav pull-right">
								<?php if (Yii::app()->user->isGuest): ?>
								<li><a href="<?php echo Yii::app()->createUrl('/member/account/login'); ?>">登录</a></li>
								<?php else: ?>
								<li><a href="<?php echo Yii::app()->createUrl('/member/policy/index'); ?>"><?php echo Yii::app()->user->getName(); ?></a></li>
								<li><a href="<?php echo Yii::app()->createUrl('/member/account/logout'); ?>"><small>登出</small></a></li>
								<?php endif; ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			
			<div class="container" id="content">
				<div style="min-height:600px;">	
				<?php echo $content; ?>
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
