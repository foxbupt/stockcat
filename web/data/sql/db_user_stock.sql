/**
 * 用户动态信息
 */
CREATE TABLE IF NOT EXISTS `t_user_coin`
(
	`uid`       int(11) NOT NULL default 0,
	`coin`      int(11) NOT NULL default 0 COMMENT '元宝数量',
    `level`     tinyint(1) NOT NULL default 0 COMMENT '用户等级',
    `point`     int(11) NOT NULL default 0 COMMENT '经验值',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`uid`)
	INDEX `idx_coin` (`coin`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 用户股票信息
 */
CREATE TABLE IF NOT EXISTS `t_user_stock`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`uid`       int(11) NOT NULL default 0,
	`sid`       int(11) NOT NULL default 0 COMMENT '股票id',
    `count`     int(11) NOT NULL default 0 COMMENT '股票数量',
    `buy_price` decimal(6,2) NOT NULL default 0 COMMENT '购买价格',
    `buy_time`  int(11) NOT NULL default 0 COMMENT '购买时间',
    `cost_coin` int(11) NOT NULL default 0 COMMENT '花费元宝数量',   
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_uid` (`uid`, `sid`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 股票交易订单
 */
CREATE TABLE IF NOT EXISTS `t_stock_order`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
    `order_no`  bigint(20) unsigned NOT NULL default 0 COMMENT '订单编号',
    `order_type` tinyint(1) NOT NULL default 0 COMMENT '交易类型: 1 买入, 2 卖出',
    `order_state` tinyint(1) NOT NULL default 0 COMMENT '交易状态: 1 已提交 2 已成交 3 已取消',
	`uid`       int(11) NOT NULL default 0,
    `sid`       int(11) NOT NULL default 0 COMMENT '股票id',
    `scode`     char(6) NOT NULL default '',
    `sname`     varchar(32) NOT NULL default '',
    `count`     int(11) NOT NULL default 0 COMMENT '交易数量',
    `price`     decimal(6,2) NOT NULL default 0 COMMENT '交易价格',
    `cost`      int(11) NOT NULL default 0 COMMENT '花费元宝数量', 
    `tax`       int(11) NOT NULL default 0 COMMENT '税费',
    `amount`    int(11) NOT NULL default 0 COMMENT '总金额',    
    `add_time`  int(11) NOT NULL default 0 COMMENT '订单提交时间',  
    `deal_time`  int(11) NOT NULL default 0 COMMENT '成交时间',
    `cancel_time` int(11) NOT NULL default 0 COMMENT '取消时间',
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_order_no` (`order_no`)
	INDEX `idx_uid` (`uid`, `add_time`),
)ENGINE=Innodb DEFAULT CHARSET=utf8;

/**
 * 用户交易结算
 */
CREATE TABLE IF NOT EXISTS `t_user_exchange`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
	`uid`       int(11) NOT NULL default 0,
	`sid`       int(11) NOT NULL default 0 COMMENT '股票id',
    `count`     int(11) NOT NULL default 0 COMMENT '股票数量',
    `buy_price` decimal(6,2) NOT NULL default 0 COMMENT '购买价格',
    `buy_time`  int(11) NOT NULL default 0 COMMENT '购买时间',
    `buy_cost`  int(11) NOT NULL default 0 COMMENT '购买成本',
    `buy_tax`   int(11) NOT NULL default 0 COMMENT '购买税费',  
    `buy_amount`   int(11) NOT NULL default 0 COMMENT '购买总金额',
    `buy_order_no` bigint(20) NOT NULL default 0 COMMENT '购买订单号', 
    `sell_price` decimal(6,2) NOT NULL default 0 COMMENT '卖出价格',
    `sell_time`  int(11) NOT NULL default 0 COMMENT '卖出时间',
    `sell_cost`  int(11) NOT NULL default 0 COMMENT '卖出成本',
    `sell_tax`   int(11) NOT NULL default 0 COMMENT '卖出税费',  
    `sell_amount` int(11) NOT NULL default 0 COMMENT '卖出总金额', 
    `profit`     int(11) NOT NULL default 0 COMMENT '收益',
    `profit_rate` decimal(4,2) NOT NULL default 0 COMMENT '收益率',
    `create_time` int(11) NOT NULL default 0,     
    `status`    enum('Y', 'N') default 'Y',
	
	PRIMARY KEY(`id`),
	INDEX `idx_uid` (`uid`, `create_time`),
	INDEX `idx_sid` (`sid`, `profit`)
)ENGINE=Innodb DEFAULT CHARSET=utf8;