#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分析器辅助类，提供分析器的公共方法
#date: 2013-10-23

import sys, re, json, random
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn
import redis

class AnalyzerHelper:
    config_info = dict()

    def __init__(self, config_info):
        self.config_info = config_info
        self.db_conn = SqlUtil.get_db(config_info['DB'])

    '''
        @desc: 获取股票基本信息
        @param: sid int 股票唯一id
        @param: read_cache bool 是否从cache中读取
        @return None/dict
    '''
    def get_stock_info(self, sid, read_cache = False):
        stock_key = "stock:info-" + str(sid)
        conn = None

        # 要求从redis中读取 且 配置中包含redis配置
        if read_cache and 'REDIS' in self.config_info:
            conn = redis.StrictRedis(self.config_info['REDIS']['host'], self.config_info['REDIS']['port'])
            cache_info = conn.get(stock_key)
            if cache_info:
                return json.loads(cache_info)

        sql = "select id, type, code, name, pinyin, ecode, alias, company, business, capital, out_capital, profit, assets, \
                hist_high, hist_low, year_high, year_low, month6_high, \
                month6_low, month3_high, month3_low from t_stock where id = " + str(sid)
        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            print e
            return None

        if len(record_list) < 1:
            return None

        stock_info = record_list[0]
        if read_cache and conn:
            conn.set(stock_key, json.dumps(stock_info))

        return stock_info

    '''
       @desc: 获取股票满足的条件项列表
       @param: sid int
       @param: day mixed int表示指定日期, tuple表示一段范围(start, end), 其中[start, end]
       @return list
    '''
    def get_stock_varlist(self, sid, day):
        if isinstance(day, tuple):
            sql = "select id, sid, vid, day, cont, sum from t_stock_var where sid = {sid} and day >= {start} and day < {end} and status = 'Y'".format(sid=sid, start=day[0], end=day[1])
        else:
            sql = "select id, sid, vid, day, cont, sum from t_stock_var where sid = {sid} and day = {day} and status = 'Y'".format(sid=sid, day=day)
        print sql

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            return None

        return record_list

    '''
        @desc: 获取股票近期的波谷波峰
        @param: sid int
        @param: range mixed int表示指定日期, tuple表示一段范围(start, end), 其中[start, end]
        @param: type int 数据类型: 0 全部 1 低点 2 高点
        @return list
    '''
    def get_price_threshold(self, sid, range, type = 0):
        if isinstance(range, tuple):
            sql = "select id, sid, day, price, low_type, high_type from t_stock_price_threshold where sid = {sid} and day >= {start} and day < {end} and status = 'Y'".format(sid=sid, start=range[0], end=range[1])
        else:
            sql = "select id, sid, day, price, low_type, high_type from t_stock_price_threshold where sid = {sid} and day <= {day} and status = 'Y'".format(sid=sid, day=range)

        if 1 == type:
            sql = sql + " and low_type > 0"
        elif 2 == type:
            sql = sql + " and high_type > 0"
        sql = sql + " order by day desc"
        print sql

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            return None

        return record_list

    '''
        @desc: 获取股票指定日期范围[start_day, end_day]的历史数据
        @param: sid int
        @param: start_day int
        @param: end_day int
        @return []
    '''
    def get_histdata_range(self, sid, start_day, end_day):
        sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
                vary_price, vary_portion from t_stock_data where sid = {sid} and \
                day >= {start_day} and day <= {end_day} order by day desc".format(sid=sid, start_day=start_day, end_day=end_day)
        print sql

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            return None

        return record_list

    '''
        @desc: 获取股票从日期往前指定个数交易日的数据
        @param: sid int
        @param: end_day int
        @param: limit int
        @return []
    '''
    def get_histdata_limit(self, sid, end_day, limit):
        sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
                vary_price, vary_portion from t_stock_data where sid = {sid} and \
                day <= {end_day} order by day limit {limit}".format(sid=sid, end_day=end_day, limit=limit)
        print sql

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            return None

        return record_list

    '''
        @desc: 根据指定日期区间内的历史数据列表来判断股票价格近期趋势
               默认为10个交易日, 5%为设定比例, >= 5% 为上涨, <= -%5% 为下降, (-5%, 5%)为震荡
        @param: data_list list
        @param: vary_threshold float 设定涨幅比例
        @return: dict('trend', 'wave', 'vary_portion') trend 趋势, wave 波段, vary_portion 涨跌幅
                trend/wave: 2 震荡, 3 上升, 1 下降
    '''
    def get_trend(self, data_list, vary_threshold):
        first_close_price = float(data_list[0]['close_price'])
        last_close_price = float(data_list[-1]['close_price'])
        close_price_list = [ float(day_info['close_price']) for day_info in data_list ]

        # 日期区间内涨幅
        vary_portion = (first_close_price - last_close_price) / last_close_price * 100
        min_close_price = min(close_price_list)
        max_close_price = max(close_price_list)

        # 日期区间内最低价/最高价出现的日期, 多个最高/最低价时, 选择离当前日期最近的一个
        min_index = -1
        max_index = - 1
        for index, day_info in enumerate(data_list):
            if float(day_info['close_price']) == min_close_price and min_index < 0:
                min_index = index

            if float(day_info['close_price']) == max_close_price and max_index < 0:
                max_index = index

        #vary_portion_list = [ float(data_info['vary_portion']) for day_info in data_list ]
        trend_info = {'vary_portion': vary_portion}

        # 涨幅超过最大比例
        if vary_portion >= vary_threshold:
            trend_info['trend'] = 3
            if max_index > 0 and min_index > max_index:
                trend_info['wave'] = 1
            else:
                trend_info['wave'] = 3

        # 跌幅超过最大比例
        elif vary_portion <= -1 * vary_threshold:
            trend_info['trend'] = 1
            if min_index > 0 and max_index > min_index:
                trend_info['wave'] = 3
            else:
                trend_info['wave'] = 1

        # 涨幅位于(-threshold, threashold) 比例
        else:
            trend_info['trend'] = 2
            if max_index == 0 or (min_index > 0 and max_index > min_index):
                trend_info['wave'] = 3
            elif min_index == 0 or (max_index > 0 and min_index > max_index):
                trend_info['wave'] = 1

        return trend_info

    # 添加股票到股票池中, day为当前添加的日期, 一周内同一支股票仅添加一次
    def add_stock_pool(self, sid, day, info):
        cur_day = str(day)
        current_time = datetime.datetime(int(cur_day[0:4]), int(cur_day[4:6]), int(cur_day[6:8]))
        # 本周一
        start_time = current_time + datetime.timedelta(days = -1 * (current_time.isoweekday() - 1))
        start_day = '{0:%Y%m%d}'.format(start_time)

        sql = "select id from t_stock_pool where sid={sid} and status = 'Y' and day >= {start_day} and day < {end_day}"\
                .format(sid=sid, start_day=start_day, end_day=day)
        print sql

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            print e
            return False

        if len(record_list) >= 1:
            return True

        info['add_time'] = time.mktime( datetime.datetime.now().timetuple() )
        info['status'] = 'Y'

        sql = SqlUtil.create_insert_sql("t_stock_pool", info)
        print sql
        try:
            self.db_conn.query_sql(sql, True)
        except Exception as e:
            print e
            return False

        return True
