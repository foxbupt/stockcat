/*
 * 股票事件
 */
CREATE TABLE IF NOT EXISTS `t_stock_event`
(
	`id` 		int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid`       int(11) NOT NULL default 0 COMMENT '股票id',   
    `event_date` int(11) NOT NULL default 0 COMMENT '事件日期, 格式为YYYYmmdd',
    `title`     varchar(255) NOT NULL default '' COMMENT '事件标题',
    `content`   text NOT NULL default '' COMMENT '事件详情',  
    `trend`     tinyint(1) NOT NULL default 0 COMMENT '事件影响: 1 利好, 2 中性, 3 利空',      
    `create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `event_date`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;


/*
 * 股票每日总览数据表: 换手率/振幅/总市值/市盈率/市净率等通过公式计算
 */
CREATE TABLE IF NOT EXISTS `t_stock_data`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`day`	int(11) NOT NULL default 0 COMMENT '交易日, 格式为YYYYmmdd',
    `open_price` decimal(10,2) NOT NULL default 0.00 COMMENT '今开数值',
    `high_price` decimal(10,2) NOT NULL default 0.00 COMMENT '今日最高',
    `low_price`  decimal(10,2) NOT NULL default 0.00 COMMENT '今日最低',
    `close_price` decimal(10,2) NOT NULL default 0.00 COMMENT '今收数值',
    `volume`    int(11) NOT NULL default 0 COMMENT '成交量, 单位为手',
    `amount`    int(11) NOT NULL default 0 COMMENT '成交金额, 单位为万元', 
    `vary_price` decimal(6,2) NOT NULL default 0.00 COMMENT '涨跌额', 
    `vary_portion` decimal(6,2) NOT NULL default 0.00 COMMENT '涨跌幅, 格式为百分比',   
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`),
	INDEX `idx_day` (`day`, `vary_portion`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票每日详情数据表: 记录每个时刻股票的价格和交易信息
 */
CREATE TABLE IF NOT EXISTS `t_stock_detail`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`day`	int(11) NOT NULL default 0 COMMENT '交易日, 格式为YYYYmmdd',
    `time`  int(11) NOT NULL default 0 COMMENT '交易时刻, 从当天0点开始的秒数',
    `price` decimal(6,2) NOT NULL default 0.00 COMMENT '当前时刻价格',
    `avg_price` decimal(6,2) NOT NULL default 0.00 COMMENT '均价', 
    `volume`    bigint(20) NOT NULL default 0 COMMENT '成交量, 单位为股',
    `swing`     decimal(6,2) NOT NULL default 0.00 COMMENT '振幅',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `day`, `time`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

