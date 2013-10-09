insert into t_policy_var(`code`, `name`, `type`, `status`) values('CODE', '股票编码', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('NAME', '股票名称', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CAPITAL', '总股本', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('OUT_CAPITAL', '流通股本', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('CAPITALISATION', '总市值', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('OUT_CAPTIALISATION', '流通市值', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIST_HIGH', '历史最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('HIGH_LOW', '历史最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_HIGH', '年内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('YEAR_LOW', '年内最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_HIGH', '60日内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY60_LOW', '60日内最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_HIGH', '30日内最高价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY30_HIGH', '30日内最低价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_EXCHANGE_PORTION', '当日换手率', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('PE', '市盈率', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('EPS', '动态市盈率', '1' 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('PBV', '市净率', '1', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_LOCATION', '所属地域', '2', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_PLATE', '所属板块', '2', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('TAG_BORAD', '股票类型', '2', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('CUR_PRICE', '当前价格', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_OPEN', '当日开盘', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_CLOSE', '当日收盘', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_HIGH', '当日最高', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_LOW', '当日最低', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_VARY_PRICE', '当日涨跌额', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_VARY_PORTION', '当日涨跌幅', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_VOLUME', '当日成交量', '1', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY_AMOUNT', '当日成交额', '1', 'Y');

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

insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY5_CONT_RISE_DAY', '最近5个交易日内连续上涨的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY5_CONT_RISE_PORTION', '最近5个交易日内连续上涨的涨幅', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY5_CONT_FALL_DAY', '最近5个交易日内连续下跌的天数', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('DAY5_CONT_FALL_PORTION', '最近5个交易日内连续下跌的跌幅', '4', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE_CONT_EXCHANGE_PORTION', '最近N个交易日内换手率', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE_MAX_EXCHANGE_PORTION', '最近N个交易日内最高换手率', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE_MIN_EXCHANGE_PORTION', '最近N个交易日内最低换手率', '4', 'Y');

insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE_PRICE_TREND', '最近N个交易日内价格趋势', '4', 'Y');
insert into t_policy_var(`code`, `name`, `type`, `status`) values('RANGE_PRICE_WAVE', '最近N个交易日内价格所处波段', '4', 'Y');

