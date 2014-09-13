/* 股票基本信息 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CODE', '股票编码', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('NAME', '股票名称', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CAPITAL', '总股本', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('OUT_CAPITAL', '流通股本', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CAPITALISATION', '总市值', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('OUT_CAPTIALISATION', '流通市值', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_LOCATION', '所属地域', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_PLATE', '所属板块', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_BORAD', '股票类型', '1', 'Y');

/* 历史价格数据 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_HIGH', '历史最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIGH_LOW', '历史最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_HIGH', '年内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_LOW', '年内最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_HIGH', '60日内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_LOW', '60日内最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_HIGH', '30日内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_LOW', '30日内最低价格', '1', 'Y');

/* 当日行情数据 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CUR_PRICE', '当前价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('OPEN_PRICE', '当日开盘', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CLOSE_PRICE', '当日收盘', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIGH_PRICE', '当日最高', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LOW_PRICE', '当日最低', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('VARY_PRICE', '当日涨跌额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('VARY_PORTION', '当日涨跌幅', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('VOLUME', '当日成交量', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('AMOUNT', '当日成交额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('EXCHANGE_PORTION', '当日换手率', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('SWING', '当日振幅', '1', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('PE', '市盈率', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('PB', '市净率', '1', 'Y');

/* 财务指标 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('EPS', '每股净利润', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('NAVPS', '每股净资产', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('ROE', '净资产收益率', '1', 'Y');

/* 当前价格所处位置指标 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_HIGH_VARY_PRICE', '当前价格距离30日最高差额', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_HIGH_VARY_PORTION', '当前价格距离30日最高涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_LOW_VARY_PRICE', '30日最低距离当前价格差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_LOW_VARY_PORTION', '30日最低距离当前价格涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_HIGH_VARY_PRICE', '当前价格距离60日最高差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_HIGH_VARY_PORTION', '当前价格距离60日最高涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_LOW_VARY_PRICE', '60日最低距离当前价格差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_LOW_VARY_PORTION', '60日最低距离当前价格涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_HIGH_VARY_PRICE', '当前价格距离年内最高差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_HIGH_VARY_PORTION', '当前价格距离年内最高涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_LOW_VARY_PRICE', '年内最低距离当前价格差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_LOW_VARY_PORTION', '年内最低距离当前价格涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_HIGH_VARY_PRICE', '当前价格距离历史最高差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_HIGH_VARY_PORTION', '当前价格距离历史最高涨幅比例', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_LOW_VARY_PRICE', '年内最低距离当前价格差额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_LOW_VARY_PORTION', '年内最低距离当前价格涨幅比例', '1', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_VALID_DAY', '最近30日内有效交易日天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_VALID_DAY', '最近60日内有效交易日天数', '4', 'Y');

/* 近阶段动态连续的行情指标 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:PRICE_RISE_DAY', '价格连续上涨的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:PRICE_RISE_VARY_PORTION', '价格连续上涨的累计涨幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:PRICE_FALL_DAY', '价格连续下跌的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:PRICE_FALL_VARY_PORTION', '价格连续下跌的累计跌幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_RISE_DAY', '持续放量的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_RISE_VARY_PORTION', '持续放量的阶段涨幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_FALL_DAY', '持续缩量的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_FALL_VARY_PORTION', '持续缩量的阶段涨幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_PRICE_RISE_DAY', '量价齐升持续的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_PRICE_RISE_VARY_PORTION', '持续量价齐升的阶段涨幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_PRICE_FALL_DAY', '量价齐跌连续的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CONT:VOLUME_PRICE_FALL_VARY_PORTION', '持续量价齐跌的阶段涨幅', '4', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_LOW_DAY', '近阶段低点的交易日', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_LOW_PRICE', '近阶段低点的交易价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_LOW_TYPE', '近阶段低点类型', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_HIGH_DAY', '近阶段高点的交易日', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_HIGH_PRICE', '近阶段高点的交易价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('LAST_HIGH_TYPE', '近阶段高点类型', '1', 'Y');

/* 阶段范围数据的指标 */
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE:EXCHANGE_PORTION.MAX', '最近N个交易日内最高换手率', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE:EXCHANGE_PORTION.MIN', '最近N个交易日内最低换手率', '4', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE:PRICE_TREND', '最近N个交易日内价格趋势', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE:PRICE_WAVE', '最近N个交易日内价格所处波段', '4', 'Y');

