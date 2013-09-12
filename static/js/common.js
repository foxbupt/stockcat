function isLikelyKonqueror3() {
	if(!document.getElementById) return false;
	if(document.defaultCharset || window.opera || !window.print) return false;
	if(window.home) return false;
	if(document.all) return true;
	var likely = 1;
	eval("try{var str=document.all[0].tagName;}catch(err){likely=0;}");
	return likely;
}
function isIe()
{
	return (navigator.appName == "Microsoft Internet Explorer");;
}

function addBookmarkObj(url, title)
{		
	try
    {
        window.external.addFavorite(url, title);
    }
    catch (e)
    {
        try
        {
            window.sidebar.addPanel(url, title, "");
        }
        catch (e)
        {
            alert("对不起，您需要通过浏览器手动添加！");
        }
    }
}

function setHome(obj, vrl) 
{　
	try
	{
		document.body.style.behavior='url(#default#homepage)';
		document.body.sethomepage(vrl);
		//obj.style.behavior = 'url(#default#homepage)';
		//obj.setHomePage(vrl);
	}
	catch(e)
	{
		alert("抱歉！您的浏览器不支持设置为首页！");
	}
}

function bevaTrackEvent(category, action, label, value)
{
	try
	{
		var url = "http://tj.beva.com:9000/event?c=" + encodeURIComponent(category) + "&a=" + encodeURIComponent(action) + "&l=" + encodeURIComponent(label) + "&all=" + encodeURIComponent(category + "_" + action + "_" + label);
		$.getScript(url, $.noop);
		_gaq.push(['_trackEvent', category, action, label, value]);
	}
	catch(e)
	{
	}
}
function setCookie(name, value, expires, path, domain, secure){
	document.cookie = name + "=" + escape(value)+((domain)?";domain="+domain:"")+((expires)?";expires="+expires.toGMTString():"")+((path)?";path="+path:"")+((secure)?";secure":"");
}

function getCookie(name) {
	var arr = document.cookie.match(new RegExp('(^| )' + name + '=([^;]*)(;|$)'));
	if (arr != null) {
		return unescape(arr[2]);
	}
	return null;
}

function delCookie(name, path) {
	if(!path) path = "/";
	var exp = new Date();
	exp.setTime(exp.getTime() - 1);
	var cval = getCookie(name);
	if (cval != null) document.cookie = name + '=' + cval + ';path=' + path + ';expires=' + exp.toGMTString();
}

function bevaGetPageSize()
{
	var xScroll, yScroll;
	if (window.innerHeight && window.scrollMaxY) {
		xScroll = window.innerWidth + window.scrollMaxX;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		if(document.documentElement.clientWidth){
			windowWidth = document.documentElement.clientWidth; 
		} else {
			windowWidth = self.innerWidth;
		}
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}	
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else { 
		pageHeight = yScroll;
	}
	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){	
		pageWidth = xScroll;		
	} else {
		pageWidth = windowWidth;
	}
	
	return {
		pageW:pageWidth,
		pageH:pageHeight,
		windowW:windowWidth,
		windowH:windowHeight
	};
};

function getPageScroll()
{
	var yScroll;
	var xScroll;
	if(self.pageYOffset)
	{ 
		yScroll = self.pageYOffset;
		xScroll = self.pageXOffset;
	}
	else if (document.documentElement && document.documentElement.scrollTop)
	{ 
		yScroll = document.documentElement.scrollTop;
		xScroll = document.documentElement.scrollLeft;
	}
	else if (document.body)
	{
		yScroll = document.body.scrollTop;
		xScroll = document.body.scrollLeft;
	}
	return {x:xScroll,y:yScroll};
}

function getUrlParam(name)
{   
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r!=null) return unescape(r[2]);
	return null;
} 

function isEmail(email) 
{	 
	var regex = /^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
	return regex.test(email);
}

var g_maskId = 'beva_maskDiv';
var g_loginId = 'beva_LoginDiv';
var g_loginCallback = null;

/**
 * @param string url 登录的URL，必填，用于登录FORM的action，需要处理登录请求
 * @param int source 登录来源
 * @param string maskId 透明遮挡层ID，可选，一般使用的默认的就行了
 * @param string loginId 登录窗口ID，可选，一般使用的默认的就行了
 * @param function callback 登录成功之后的回调函数，默认的行为是刷新页面，如果需要特定的行为，可以传递一个回调函数进来
 * @param string serviceDomain 业务域名
 */
function bevaLogin(serviceDomain, maskId, loginId, callback, accountDomain, source, returnUrl)
{
	if(!maskId) maskId = g_maskId;
	if(!loginId) loginId = g_loginId;
	if(!callback) g_loginCallback = callback;
	if(!accountDomain) accountDomain = "account.beva.com";
	if(!source) source = 0;
	if(!returnUrl) returnUrl = location.href;
	g_maskId = maskId;
	g_loginId = loginId;
	if($("#" + maskId).length <= 0)
	{
		$("<div>", {
			id:maskId,
			'class':'bevamask'
		}).appendTo("body");
	}
	if($("#" + loginId).length <= 0)
	{
		var loginDiv = $(
			'<div class="bevaloginCon" id=' + loginId + '>' + 
				'<div class="close" id="beva_loginClose" title="关闭"></div>' +
				//'<iframe src="http://' + accountDomain + '/account/login?t=login&layerlogin=1&returnUrl=' + returnUrl + '&source=' + source + '" id="loginIframe" width="333" height="245" frameBorder="0" scrolling="no" allowtransparency="true"></iframe> ' +
			'</div>'
		).appendTo("body");
		
		var myiframe = $(document.createElement("iframe"));
		myiframe.attr('src', 'about:blank');
		myiframe.attr('id', "loginIframe");
		myiframe.attr('width', 333);
		myiframe.attr('height', 245);
		myiframe.attr('frameBorder', 0);
		myiframe.attr('scrolling', 'no');
		myiframe.attr('allowTransparency', true);
		myiframe.appendTo($("#" + loginId));

		
		myiframe.attr('src', 'http://' + accountDomain + '/account/login?t=login&layerlogin=1&returnUrl=' + returnUrl + '&source=' + source);
		
		$("#beva_loginClose").click(bevaHidelogin);
	}
	var mask = $("#" + maskId);
	var login = $("#" + loginId);
	var size = bevaGetPageSize();
	mask.css("width", size.pageW + "px");
	mask.css("height", size.pageH + "px");
	var loginW = 333;
	var loginH = 245;
	login.css("left", parseInt((size.pageW - loginW)/2, 10) + "px");
	login.css("top", getPageScroll()["y"] + parseInt((size.windowH - loginH)/2, 10) + "px");
	mask.show();
	login.show();
}

function bevaHidelogin()
{
	$("#" + g_loginId).hide();
	$("#" + g_maskId).hide();
}

var g_is2012 = false;
function loginSuccss()
{
	if(arguments.length < 2)
	{
		if(typeof afterLoginSucc == "function")
		{
			afterLoginSucc();
		}
		else
		{
			location.reload(true);
		}
	}
	else
	{
		bevaHidelogin();
		//var url = window.location.href, reg = /\/(family)\./i;
		var url = window.location.href;
		if(g_is2012)
		{
			verifyLayer2012(arguments[0], arguments[1], url);
		}
		else
		{
			verifyLayer(arguments[0], arguments[1], url);
		}
	}
}

var g_login = 1;
function ajaxLoginSucc(ssoUrls, ticket)
{
	
}

function bevaCheckLogin(url)
{
	jQuery.getScript(url, function()
	{
		if(typeof isLogin == "boolean")
		{
			if(isLogin)
			{
				//$("#bevaNavLogin").html(bevaNavHtml);
			}
			if(typeof afterBevaLogin == "function")
			{
				afterBevaLogin(isLogin);
			}
		}
	});
}

//0 success, 1 failed
function isLoginSuccess()
{
	return g_login;
}

function resendVerify(mail)
{
	var url = 'http://account.beva.com/account/resendVerifyEmail';
	if(window.location.hostname != 'account.beva.com')
	{
		url += '?callback=?';
	}
	$.getJSON(url, {}, function(obj){
		alert(obj.message);
	});
}

function getLayerTopHtml(titleClass)
{
	return [ 
		'<div class="floating-layer">',
			'<div class="floating-layer-box">', 
				'<div class="close"><a class="neclass" id="floating-layer-close" href="javascript:void(0);" title="关闭">&nbsp;</a></div>', 
				'<div id="f-layer-wrapper">',
					'<div class="f-layer-hd">', 
						'<div class="f-layer-title ', titleClass, '"><h1>&nbsp;</h1></div>',
						'<div class="up-r"></div>',
						'<div class="up-l"></div>',
					'</div>',
					'<div class="f-layer-cont">'
	].join('');
}

function getLayerBotHtml()
{
	return [
					'</div>',
					'<div class="f-layer-ft">',
						'<div class="ft-bg">&nbsp;</div>',
						'<div class="down-r"></div>',
						'<div class="down-l"></div>',
					'</div>',
				'</div>',
			'</div>',
		'</div>',
	].join('');
}

var g_layerMaskId = 'layer-shadow-mask';
function showLayerMask()
{
	if($("#" + g_layerMaskId).length <= 0)
	{
		$("<div>", {
			id:g_layerMaskId,
			'class':'shadow-mask'
		}).appendTo("body");
	}
	var mask = $("#" + g_layerMaskId);
	var size = bevaGetPageSize();
	mask.css("width", size.pageW + "px");
	mask.css("height", size.pageH + "px");
	mask.show();
}

function hideLayerMask()
{
	$("#" + g_layerMaskId).hide();
}

function verifyLayer(mail, mailLink, jmpUrl)
{
	var size = bevaGetPageSize(), 
		yScroll = getPageScroll()["y"],
		yLayer = yScroll + 95,
		yLayerShadow = yScroll + 90,
		html = ['<div class="layer" style="top:', yLayer, 'px;"><div class="layerCont"><h2><a href="', jmpUrl
	            , '" class="layerClose">close</a><span class="cat2">cat icon</span>请您验证邮箱</h2>'
	            , '<div class="layerW"><div class="M2Cont"><p>为了使您今后更加方便地使用贝瓦网为您提供的各种信息与服务，我们建议您对您的注册邮箱进行有效性验证。通过验证后，您可以：</p>'
	            , '<ul><li><b>&#8226;</b>抢先体验每周贝瓦网精彩更新内容</li><li><b>&#8226;</b>订阅贝瓦网为您提供原创的育儿指导</li><li><b>&#8226;</b>定期获得宝宝在贝瓦儿童中的成长报告</li>'
	            , '</ul><p>我们已发送验证邮件至<b>', mail 
	            , '</b>，请您前往您的邮箱中查收，点击邮件里的确认连接，即可完成验证。如果您没有收到邮件，请点此<a href="javascript:void(0)" onclick="resendVerify(', "'", mail, "'" 
	            , ')">重新发送验证邮件</a>。</p>'
	            , '</div><div class="bt"><a target="_blank" class="Ybt9" title="立即登录邮箱" href="', mailLink 
	            , '">&nbsp;</a></div></div></div></div><div class="layerShadow" style="top:', yLayerShadow, 'px;"><div class="loginls"></div></div><div class="pageShadow" style="height:', size.pageH,'px"></div>'];
	$('body').append(html.join(''));
}
function verifyLayer2012(mail, mailLink, jmpUrl)
{
	bevaHidelogin2012();
	showLayerMask();
	var mailHtml = [
		'<div class="mail-v">',
			'<p>为了使您今后更加方便地使用贝瓦网为您提供的各种信息与服务，我们建议您对您的注册邮箱进行有效性验证。通过验证后，您可以：</p>',
				'<ul>',
					'<li><b>•</b>抢先体验每周贝瓦网精彩更新内容</li><li><b>•</b>订阅贝瓦网为您提供原创的育儿指导</li>',

					'<li><b>•</b>定期获得宝宝在贝瓦儿童中的成长报告</li>',
				'</ul>',
				'<p>我们已发送验证邮件至<b>', mail, '</b>，请您前往您的邮箱中查收，点击邮件里的确认连接，即可完成验证。如果您没有收到邮件，请点此<a onclick="resendVerify(\'', mail, '\')" href="javascript:void(0)">重新发送验证邮件</a>。</p>',
				'<div class="mail-v-bt"><a target="_blank" href="', mailLink, '">登录邮箱</a></div>',
		'</div>'
	].join('');
	$('body').append([getLayerTopHtml('mail-v-title'), mailHtml, getLayerBotHtml()].join(''));
	$("#floating-layer-close").click(function(){location.reload();});
}

/**
 * 贝瓦网2012版浮层登录框
 * @param string url 登录的URL，必填，用于登录FORM的action，需要处理登录请求
 * @param int source 登录来源
 * @param string maskId 透明遮挡层ID，可选，一般使用的默认的就行了
 * @param string loginId 登录窗口ID，可选，一般使用的默认的就行了
 * @param function callback 登录成功之后的回调函数，默认的行为是刷新页面，如果需要特定的行为，可以传递一个回调函数进来
 * @param string serviceDomain 业务域名
 */
function bevaLogin2012(serviceDomain, maskId, loginId, callback, accountDomain, source, returnUrl)
{
	if(!maskId) maskId = g_maskId;
	if(!loginId) loginId = g_loginId;
	if(!callback) g_loginCallback = callback;
	if(!accountDomain) accountDomain = "account.beva.com";
	if(!source) source = 0;
	if(!returnUrl) returnUrl = location.href.split("#")[0];
	g_loginId = loginId;
	var loginW = 520;
	var loginH = 340;
	if($("#" + loginId).length <= 0)
	{
		var loginDiv = $(['<div class="floating-layer" id="', loginId, '"><div class="floating-layer-box"><div class="close"><a href="javascript:bevaHidelogin2012();" title="关闭">&nbsp;</a></div></div></div>'].join('')).appendTo("body");
		
		var myiframe = $(document.createElement("iframe"));
		myiframe.attr('src', 'about:blank');
		myiframe.attr('id', "loginIframe2012");
		myiframe.attr('width', loginW);
		myiframe.attr('height', loginH);
		myiframe.attr('frameBorder', 0);
		myiframe.attr('scrolling', 'no');
		myiframe.attr('allowTransparency', true);
		myiframe.appendTo($(["#", loginId, ' div.floating-layer-box'].join('')));
		
		myiframe.attr('src', 'http://' + accountDomain + '/account/login?t=login_2012&layerlogin=1&returnUrl=' + returnUrl + '&source=' + source);
	}
	
	var mask = $("#" + maskId);
	var login = $("#" + loginId);
	var size = bevaGetPageSize();
	login.css("top", getPageScroll()["y"] + parseInt((size.windowH - loginH)/2, 10) + "px");
	showLayerMask();
	login.show();
	g_is2012 = true;
}
function bevaHidelogin2012()
{
	$("#" + g_loginId).hide();
	hideLayerMask();
}
function bevaCheckLogin2012(url)
{
	jQuery.getScript(url, function()
	{
		if(typeof isLogin == "boolean")
		{
			if(isLogin)
			{
				//$("#bevaNavLogin").html(bevaNavHtml);
			}
			if(typeof afterBevaLogin == "function")
			{
				afterBevaLogin(isLogin);
			}
		}
	});
}
function bevaFormLogin2012()
{
	$("#tl_form").submit(function(){
		var  uname = $("#tl_beva_username");
		if($.trim(uname.val()) == '')
		{
			alert("请输入用户名！");
			uname.focus();
			return false;
		}
		
		if(!isEmail(uname.val()))
		{
			alert("帐号必须是有效的邮箱，请重新输入！");
			uname.focus();
			return false;
		}
		
		var pwd = $("#tl_beva_password");
		if(pwd.val() == '')
		{
			alert("请输入密码！");
			pwd.focus();
			return false;
		}
		return true;
	});
}