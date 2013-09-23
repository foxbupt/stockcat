/**
 * 账号数据表
 */
CREATE TABLE IF NOT EXISTS `t_account`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`email`     varchar(128) NOT NULL default '' COMMENT '邮箱',
	`type`      tinyint(1) NOT NULL default 0 COMMENT '注册类型: 1 email注册, 2 开放平台账号绑定, 3 手机注册',
	`salt`      char(6) NOT NULL default '' COMMENT 'salt',
	`password`  char(32) NOT NULL default '' COMMENT '密码',
	`nickname`  varchar(64) NOT NULL default '' COMMENT '昵称',
	`gender`    char(1) NOT NULL default 'M' COMMENT '性别: M 男, F 女',	
	`intro`     varchar(255) NOT NULL default '' COMMENT '简介',
	`mobile_no` varchar(16) NOT NULL default '' COMMENT '手机号',
	`birthday`  int(11) NOT NULL default '' COMMENT '出生日期',
	`active_code` varchar(32) NOT NULL default '' COMMENT '账号激活码',
    `referer_code` varchar(32) NOT NULL default '' COMMENT '账号邀请注册码',
    `register_ip` int(11) NOT NULL default 0 COMMENT '注册ip',  
	`update_time` int(11) NOT NULL default 0 COMMENT '最近修改时间',
    `create_time` int(11) NOT NULL default 0 COMMENT '注册时间',
    `status`    enum('Y', 'U', 'N') default 'Y' COMMENT '账号状态取值: U 未激活, Y 已激活, N 禁用',
	
	PRIMARY KEY(`id`),
	INDEX `idx_email` (`email`),
	INDEX `idx_referer` (`referer_code`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 开放平台绑定
 */
CREATE TABLE IF NOT EXISTS `t_open_bind`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`open_platform`     tinyint(1) NOT NULL default 0 COMMENT '开放平台标识',
	`open_id`   varchar(32) NOT NULL default '' COMMENT '开放平台uid',
	`uid`       int(11) NOT NULL default 0 COMMENT '绑定uid',
	`open_nickname`  varchar(32) NOT NULL default '' COMMENT '开放平台昵称',
	`access_token`  varchar(128) NOT NULL default '',
	`refresh_token`  varchar(128) NOT NULL default '',
	`open_data` varchar(512) NOT NULL default '',
	`bind_time` int(11) NOT NULL default 0 COMMENT '绑定时间',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_open` (`open_id`, `open_platform`),
	INDEX `idx_uid` (`uid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 用户持有股票: 由于单只股票可以间隔买入多次, 因此需要用batch_no批次来建立持有记录与交易记录的关联关系
 */
CREATE TABLE IF NOT EXISTS `t_user_own`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`batch_no`  int(11) unsigned NOT NULL default 0 COMMENT '批次编码, 格式为YYYYmmddHH',
	`uid`       int(11) NOT NULL default 0 COMMENT '用户uid',
	`sid`       int(11) NOT NULL default 0 COMMENT '股票id',
	`count`     int(11) NOT NULL default 0 COMMENT '持有数量',
    `cost`      decimal(6,2) NOT NULL default 0 COMMENT '每股买入成本单价',
	`amount`    decimal(10,2) NOT NULL default 0 COMMENT '当前市值',
    `profit`    decimal(10,2) NOT NULL default 0 COMMENT '收益总额',
    `profit_portion` decimal(6,2) NOT NULL default 0 COMMENT '收益比例',
	`create_time` int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_uid` (`uid`, `sid`),
	INDEX `idx_count` (`uid`, `count`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 用户交易记录
 */
CREATE TABLE IF NOT EXISTS `t_user_deal`
(
	`id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`batch_no`  int(11) NOT NULL default 0 COMMENT '批次编码, 用于关联股票持有记录',
	`uid`       int(11) NOT NULL default 0 COMMENT '用户uid',
	`sid`       int(11) NOT NULL default 0 COMMENT '股票id',
	`day`       int(11) NOT NULL default 0 COMMENT '交易日',
	`time`      int(11) NOT NULL default 0 COMMENT '交易时刻',
    `deal_type` tinyint(1) NOT NULL default 0 COMMENT '交易类型: 1 买入, 2 卖出',
	`count`     int(11) NOT NULL default 0 COMMENT '交易数量',
	`price`     decimal(6,2) NOT NULL default 0 COMMENT '交易价格',
	`fee`       decimal(10,2) NOT NULL default 0.0 COMMENT '交易金额',
	`tax`       decimal(10,2) NOT NULL default 0.0 COMMENT '印花税及费用',
	`amount`    decimal(10,2) NOT NULL default 0.0 COMMENT '总金额',
	`create_time` int(11) NOT NULL default 0 COMMENT '创建时间',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_uid` (`uid`, `sid`, `deal_type`),
	INDEX `idx_day` (`uid`, `day`),
	INDEX `idx_batch` (`uid`, `batch_no`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

