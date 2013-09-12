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

/**
 * 股票每日资金流向数据表
 */
CREATE TABLE IF NOT EXISTS `t_stock_fund`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`day`	int(11) NOT NULL default 0 COMMENT '交易日, 格式为YYYYmmdd',
	`total` int(11) NOT NULL default 0 COMMENT '总流入资金, 单位为元',
    `low`   int(11) NOT NULL default 0 COMMENT '散户流入资金, 单位为元',
    `medium` int(11) NOT NULL default 0 COMMENT '中户资金, 单位为元',
    `large`  int(11) NOT NULL default 0 COMMENT '大户流入资金, 单位为元',
    `super` int(11) NOT NULL default 0 COMMENT '超大户流入资金, 单位为元',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `day`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票财务报表: TODO 添加资产总计、未分配利润
 */
CREATE TABLE IF NOT EXISTS `t_stock_finance_report`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`report_day`  int(11) NOT NULL default 0 COMMENT '财报对应日期, 格式为YYYYmmdd',
	`publish_day`	int(11) NOT NULL default 0 COMMENT '发布日期, 格式为YYYYmmdd',
	`type` tinyint(1) NOT NULL default 0 COMMENT '报表类型: 1 季报, 2 中报, 3 年报',   
   	`eps`		decimal(6,3) NOT NULL default 0 COMMENT '每股收益即每股净利润',
    `navps`    decimal(6,3) NOT NULL default 0 COMMENT '每股净资产',
    `profit`	decimal(6,3) NOT NULL default 0 COMMENT '每股税后利润',
    `cps`    	decimal(6,3) NOT NULL default 0 COMMENT '每股现金流量',
	`apcps`		decimal(6,3) NOT NULL default 0 COMMENT '每股资本公积金',
   	`mips`			decimal(6,3) NOT NULL default 0 COMMENT '每股营业收入',
	`roe`		decimal(6,2) NOT NULL default 0 COMMENT '净资产收益率',	
   	`netprofitmargin` decimal(6,2) NOT NULL default 0 COMMENT '净利率',
   	`profitmargin`	decimal(6,2) NOT NULL default 0 COMMENT '毛利率',
   	`debt2asset`	decimal(6,2) NOT NULL default 0 COMMENT '资产负债比',
   	`netprofit`		decimal(10,2) NOT NULL default 0 COMMENT '净利润, 单位为万元',
	`income`		decimal(10,2) NOT NULL default 0 COMMENT '营业收入, 单位为万元',	
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `day`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票业绩预告表
 */
CREATE TABLE IF NOT EXISTS `t_stock_finance_predict`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`publish_day` int(11) NOT NULL default 0 COMMENT '业绩预告日期',
	`report_day`  int(11) NOT NULL default 0 COMMENT '截止日期',
	`trend`	tinyint(1) NOT NULL default 0 COMMENT '预测方向: 1 预盈 2 预亏',
	`trend_note` varchar(255) NOT NULL default '' COMMENT '预测说明',
	`vary_low` decimal(6,2) NOT NULL default 0 COMMENT '增幅区间低值',
	`vary_high` decimal(6,2) NOT NULL default 0 COMMENT '增幅区间高值',
	`digest`  varchar(512) NOT NULL default '' COMMENT '摘要',
	`reason`  varchar(512) NOT NULL default '' COMMENT '原因',
	`content`  varchar(2048) NOT NULL default '' COMMENT '内容',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `report_day`, `trend`)		
)ENGINE=Innodb DEFAULT CHARSET=utf8;
 
/**
 * 股票每日大宗交易表
 */ 
CREATE TABLE IF NOT EXISTS `t_stock_large_trade`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`day` int(11) NOT NULL default 0 COMMENT '交易日期',
	`price` decimal(6,2) NOT NULL default 0 COMMENT '成交价格',
	`count` int(11) NOT NULL default 0 COMMENT '成交数量(单位为手)',
	`amount` decimal(8,2) NOT NULL default '' COMMENT '成交金额(单位为万元)',
	`sell_agency` varchar(255) NOT NULL default '' COMMENT '卖出方',
	`buy_agency`  varchar(255) NOT NULL default '' COMMENT '买入方',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `day`)		
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票每日公告数据表
 */  
CREATE TABLE IF NOT EXISTS `t_stock_bulletin`
(
	`id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
	`sid`   int(11) NOT NULL default 0 COMMENT '股票id',
	`day` int(11) NOT NULL default 0 COMMENT '交易日期',
	`trend` tinyint(1) NOT NULL default 0 COMMENT '公告影响: 1 利好, 2 利空',
	`digest` varchar(255) NOT NULL default '' COMMENT '公告摘要',
	`content`  varchar(255) NOT NULL default '' COMMENT '公告内容',
	`create_time` 	int(11) NOT NULL default 0,
	`status`	  	enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_sid` (`sid`, `day`, `trend`)		
)ENGINE=Innodb DEFAULT CHARSET=utf8; 