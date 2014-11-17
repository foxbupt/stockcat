#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 总览价格分析器
#date: 2014/06/27

import sys, re, json, os
import datetime, time, logging
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from base_policy import BasePolicy

class DailyPolicy(BasePolicy):
    # 用"daily-policy-{sid}-{day}" 存储daily分析的动态结果

    def serialize(self, item):
        key = "daily-" + str(item['sid']) + "-" + str(item['day'])
        result = self.redis_conn.set(key, json.dumps(item), 86400)
        self.logger.debug("%s", format_log("daily_item", item))

        # 收市后的item需要转为离线分析
        #if item['time'] >= 150300:

    # 计算实时上涨因子
    def rise_factor(self, item):
        sid = item['sid']
        sid_str = str(sid)
        if sid_str not in self.datamap['past_data']:
            self.logger.warning("op=non_exist_pastdata sid=%d day=%d", sid, item['day'])
            return

        past_data_value = self.datamap['past_data'][sid_str]
        if past_data_value is None:
            return
        past_data = json.loads(past_data_value)

        open_vary_portion = (item['open_price'] - item['last_close_price']) / item['last_close_price'] * 100
        day_vary_portion = (item['close_price'] - item['open_price']) / item['open_price'] * 100
        volume_ratio = item['predict_volume'] / past_data['avg_volume']
        if abs(item['open_price'] - item['high_price']) < 0.01:
            high_portion = item['close_price'] / item['high_price']
        else:
            high_portion = (item['close_price'] - item['open_price']) / (item['high_price'] - item['open_price'])
        rise_factor = round(day_vary_portion * volume_ratio * high_portion, 1)

        daily_policy_key = "daily-policy-" + str(sid) + "-" + str(item['day'])
        stock_rise_map = {'open_vary_portion': open_vary_portion, 'day_vary_portion': day_vary_portion, 'volume_ratio': volume_ratio, 'high_portion': high_portion, 'rise_factor': rise_factor}
        self.redis_conn.hmset(daily_policy_key, stock_rise_map)

        stock_rise_map['sid'] = sid
        stock_rise_map['day'] = item['day']
        self.logger.info(format_log("daily_rise_factor", stock_rise_map))

        # 当前价格比昨日收盘价上涨1%以上 且 高于开盘价
        rf_zset_key = "rf-" + str(item['day'])
        if item['vary_portion'] >= 1.00 and item['close_price'] > item['open_price'] and rise_factor >= 3.0:
            self.redis_conn.zadd(rf_zset_key, rise_factor, sid)
            self.logger.info(format_log("add_rise_factor", stock_rise_map))
        else:
            self.redis_conn.zrem(rf_zset_key, sid)

        # 把涨幅超过1% 或 涨跌幅在1%以内的股票加入拉取分笔交易的集合
        daily_ts_key = "tsset-" + str(item['day'])
        if volume_ratio >= 2.0 and (item['vary_portion'] > 1.00 or abs(item['vary_portion']) <= 1.00):
            self.redis_conn.sadd(daily_ts_key, item['sid'])
        else:
            self.redis_conn.srem(daily_ts_key, [item['sid']])

    # 分析盘中的实时趋势
    # 操作字段(op): 1 卖出  2 待定 3 买入
    # 趋势/波段方向(trend): 1 下跌 2 震荡 3 上涨
    def day_trend(self, item):
        trend = op = 0
        day_vary_portion = (item['close_price'] - item['open_price']) / item['open_price'] * 100
        max_vary = item['high_price'] - item['close_price']
        min_vary = item['close_price'] - item['low_price']

        # 开盘即涨停
        if item['close_price'] == item['open_price'] and item['vary_portion'] >= 9.6:
            trend = 3
        # 涨跌幅在1%以内, 认为是震荡
        elif abs(day_vary_portion) <= 1:
            trend = 2
            op = 2
        elif item['vary_price'] > 0.0:
            if max_vary == 0.0 or max_vary < min_vary:
                trend = 3
            else:
                trend = 1
        else:
            if min_vary == 0.0 or min_vary < max_vary:
                trend = 1
            else:
                trend = 3

        if trend == 1:
            op = 1
        elif trend == 3:
            op = 3

        daily_policy_key = "daily-policy-" + str(item['sid']) + "-" + str(item['day'])
        trend_info = {'trend': trend, 'op': op}
        self.redis_conn.hmset(daily_policy_key, trend_info)

        trend_info['sid'] = item['sid']
        trend_info['day'] = item['day']
        self.logger.debug("%s", format_log("daily_day_trend", trend_info))
