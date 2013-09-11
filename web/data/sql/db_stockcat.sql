/*
 * 公共配置项
 */
CREATE TABLE IF NOT EXISTS `t_config`
(
	`id` 			int(11) unsigned NOT NULL AUTO_INCREMENT,
	`name`          varchar(255) NOT NULL default '' COMMENT '配置项名称',
	`key` 		    varchar(32) NOT NULL default '' COMMENT '配置项编码',
	`value`         text NOT NULL default '' COMMENT '' COMMENT '配置项取值',
    `status`	  	enum('Y', 'U', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_key` (`key`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 标签数据表
 */
CREATE TABLE IF NOT EXISTS `t_tag`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`name`      varchar(255) NOT NULL default '' COMMENT '标签名称',	
	`slug`      varchar(32) NOT NULL default '' COMMENT '标签的英文唯一标识, 通常是首字母简写',
	`category`  tinyint(1) NOT NULL default 0 COMMENT '标签分类: 1 行业 2 地域, 3 概念 政策, 4 热点等',
	`pid`       int(11) NOT NULL default 0 COMMENT '父类标签id',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_category` (`category`),
	INDEX `idx_slug` (`slug`),
	INDEX `idx_pid` (`pid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票数据表
 */
CREATE TABLE IF NOT EXISTS `t_stock`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`type`      tinyint(1) NOT NULL default 0 COMMENT '股票类型: 1 股票, 2 指数',
	`code`      char(6) NOT NULL default '' COMMENT '股票唯一代码',
    `name`      varchar(32) NOT NULL default '' COMMENT '股票名称',
    `pinyin`    varchar(8) NOT NULL default '' COMMENT '首字母拼音简写',
	`alias`     varchar(32) NOT NULL default '' COMMENT '股票别名',
    `ecode`     char(2) NOT NULL default '' COMMENT '证券交易所代码, 如SH/SZ/HK等',
    `company`   varchar(256) NOT NULL default '' COMMENT '公司名称', 
    `business`  varchar(2048) NOT NULL default '' COMMENT '主营业务',
    `capital`   decimal(6, 2) NOT NULL default 0 COMMENT '总股本, 单位为亿股',
    `out_capital` decimal(6, 2) NOT NULL default 0 COMMENT '流通股本, 单位为亿股',
    `profit`    decimal(6, 2) NOT NULL default 0 COMMENT '每股净收益', 
    `assets`    decimal(6, 2) NOT NULL default 0 COMMENT '每股净资产',
    `hist_high` decimal(6,2) NOT NULL default 0.00 COMMENT '历史最高',
    `hist_low`  decimal(6,2) NOT NULL default 0.00 COMMENT '历史最低',
    `year_high` decimal(6,2) NOT NULL default 0.00 COMMENT '1年内最高',
    `year_low`  decimal(6,2) NOT NULL default 0.00 COMMENT '1年内最低',
    `month6_high` decimal(6,2) NOT NULL default 0.00 COMMENT '6个月内最高',
    `month6_low`  decimal(6,2) NOT NULL default 0.00 COMMENT '6个月内最低',
    `month3_high` decimal(6,2) NOT NULL default 0.00 COMMENT '3个月内最高',
    `month3_low`  decimal(6,2) NOT NULL default 0.00 COMMENT '3个月内最低',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	enum('Y', 'U', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	UNIQUE INDEX `idx_code` (`code`, `type`),
	INDEX `idx_name` (`name`),
	INDEX `idx_pinyin` (`pinyin`),
	INDEX `idx_ecode` (`ecode`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 股票标签
 */
CREATE TABLE IF NOT EXISTS `t_stock_tag`
(
	`id` 		int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid`       int(11) NOT NULL default 0 COMMENT '股票id',   
	`tid`       int(11) NOT NULL default 0 COMMENT '标签id',
	`display_order` tinyint(1) NOT NULL default 0 COMMENT '显示顺序',
    `create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'U', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `display_order`),
	INDEX `idx_tid` (`tid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 股票资讯
 */
CREATE TABLE IF NOT EXISTS `t_news`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`title`     varchar(255) NOT NULL default '' COMMENT '资讯标题',
	`content`   text NOT NULL default '' COMMENT '资讯内容',
	`refer`     tinyint(1) NOT NULL default 0 COMMENT '文章来源',
	`url`       varchar(255) NOT NULL default '' COMMENT '原文链接',
	`publish_time`  int(11) NOT NULL default 0 COMMENT '发布时间',  
	`create_time`   int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`	  	enum('Y', 'U', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_publish` (`publish_time`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 资讯标签
 */
CREATE TABLE IF NOT EXISTS `t_news_tag`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`nid`   int(11) NOT NULL default 0 COMMENT '资讯id',
	`tid`   int(11) NOT NULL default 0 COMMENT '标签id',
	`create_time` 	int(11) NOT NULL default 0,
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_nid` (`nid`),
	INDEX `idx_tid` (`tid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 用户评论
 */
CREATE TABLE IF NOT EXISTS `t_comment`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`nid`   int(11) NOT NULL default 0 COMMENT '资讯id',
	`content` text NOT NULL default '' COMMENT '评论内容',
	`uid`   int(11) NOT NULL default 0,
	`nickname` varchar(255) NOT NULL default '',
	`comment_ip` int(11) NOT NULL default 0,
	`comment_time` int(11) NOT NULL default 0,
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_nid` (`nid`, `comment_time`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 股票研报
 */
CREATE TABLE IF NOT EXISTS `t_stock_report`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid` 	int(11) NOT NULL default 0 COMMENT '股票id',
	`name`  varchar(32) NOT NULL default '' COMMENT '股票名称',
	`title` varchar(255) NOT NULL default '' COMMENT '研报标题',
	`content` text NOT NULL default '' COMMENT '研报内容',
	`day`   int(11) NOT NULL default 0 COMMENT '研报日期',	 
	`rank`  tinyint(1) NOT NULL default 0 COMMENT '推荐评级',      
	`goal_price`  decimal(6,2) NOT NULL default 0 COMMENT '建议目标价',
	`agency` varchar(32) NOT NULL default '' COMMENT '研报机构',   
    `create_time`    int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_day` (`day`, `rank`),
	INDEX `idx_sid` (`sid`, `day`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 股票关注池
 */
CREATE TABLE IF NOT EXISTS `t_stock_pool`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid` 	int(11) NOT NULL default 0 COMMENT '股票id',
	`day`   int(11) NOT NULL default 0 COMMENT '关注日期',
	`trend` tinyint(1) NOT NULL default 0 COMMENT '价格趋势: 1 下跌, 2 震荡, 3 上涨',
    `wave`	tinyint(1) NOT NULL default 0 COMMENT '所处波段: 1 下跌, 2 震荡, 3 上涨, ',
    `current_price` decimal(6,2) NOT NULL default 0 COMMENT '当前价格',
    `low_price`  decimal(6,2) NOT NULL default 0 COMMENT '建议购买价格的下限',
	`high_price`  decimal(6,2) NOT NULL default 0 COMMENT '建议购买价格的上限',
	`score` tinyint(1) NOT NULL default 0 COMMENT '评分',
	`create_time` int(11) NOT NULL default 0 COMMENT '推荐时间',
    `status`	  enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_score` (`day`, `score`),
	INDEX `idx_sid` (`sid`, `day`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 系统预定义变量
 */
CREATE TABLE IF NOT EXISTS `t_cond_var`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`code` 	varchar(32) NOT NULL default '' COMMENT '变量唯一编码',
	`name`  varchar(64) NOT NULL default '' COMMENT '变量名称',
	`type`  tinyint(1) NOT NULL default 0 COMMENT '变量分类: 1 基本信息, 2 标签, 3 近期股价',
	`expression` varchar(64) NOT NULL default '' COMMENT '计算值的表达式',
	`add_time` int(11) NOT NULL default 0 COMMENT '添加时间',
    `status`	  enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	UNIQUE INDEX `idx_code` (`code`),
	INDEX `idx_type` (`type`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 单个策略条件项, 条件表达式为: var_name optor value
 */
CREATE TABLE IF NOT EXISTS `t_cond_item`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
    `vid`   int(11) NOT NULL default 0 COMMENT '变量id',
    `optor`    tinyint(1) NOT NULL default 0 COMMENT '操作符, 取值: 1 ==, 2 >=, 3 >, 4 <=, 5 ==, 6 !=, 7 字符串包含',  
    `value`    decimal(6,2) NOT NULL default 0 COMMENT '条件项设定值',
	`create_time` int(11) NOT NULL default 0 COMMENT '添加时间',
    `status`	  enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_vid` (`vid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/*
 * 策略分析器
 */
CREATE TABLE IF NOT EXISTS `t_policy`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`category`  tinyint(1) NOT NULL default 0 COMMENT '分析器类别', 
    `formula` varchar(2048) NOT NULL default '' COMMENT '策略公式', 
    `update_time` int(11) NOT NULL default 0 COMMENT '上次修改时间', 
    `create_time` int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`	  enum('Y', 'N') default 'Y',
    	
    PRIMARY KEY(`id`),
    INDEX `category` (`category`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 用户分析器, 每个分析器可能包含对应多个t_policy的记录
 */
CREATE TABLE IF NOT EXISTS `t_user_policy`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`  varchar(64) NOT NULL default '' COMMENT '分析器名称',
    `type`  tinyint(1) NOT NULL default 0 COMMENT '分析器类型: 1 离线买入分析 2 离线卖出 3 实时买入 4 实时卖出',
    `remark`  text NOT NULL default '' COMMENT '分析器描述信息',
    `policy_info` varchar(256) NOT NULL default '' COMMENT '分析器详情, 仅存储内部子分析器的id',
    `uid`   int(11) NOT NULL default 0 COMMENT '用户id',
    `update_time` int(11) NOT NULL default 0 COMMENT '上次修改时间',  
	`create_time` int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`	  enum('Y', 'N') default 'Y',
    	
    PRIMARY KEY(`id`),
    INDEX `idx_uid` (`uid`),
    INDEX `idx_type` (`type`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;
 
/*
 * 策略条件列表: 
 * 采用树状结构存储, 同一个括号内的条件项可以归为一个子策略
 * 一个策略中按照条件项顺序依次计算, 最后一个条件项的逻辑符号忽略.
 */
/*
CREATE TABLE IF NOT EXISTS `t_policy_item`
(
    `id`   int(11) NOT NULL default 0,
    `policy_id`  int(11) NOT NULL default 0 COMMENT '' COMMENT '策略id',
    `child_type`  tinyint(1) NOT NULL default 0 COMMENT '子节点类型: 0 条件项, 1 子策略',  
    `child_id`     int(11) NOT NULL default 0 COMMENT '根据子节点类型取值, 决定是策略id还是条件项id',
    `logic`       tinyint(1) NOT NULL default 0 COMMENT '逻辑操作符, 取值: 1 and, 2 or',
    `sequence`    tinyint(1) NOT NULL default 0 COMMENT '条件项在分析器中的顺序',
	`create_time` int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`	  enum('Y', 'N') default 'Y',
    	
    PRIMARY KEY(`id`),
    INDEX `idx_policy` (`policy_id`, `sequence`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;
*/