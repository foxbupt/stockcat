#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票分析器基类, 评估单只股票的交易
#date: 2013-08-21

import sys, re, json, random
sys.path.append('../../../server')  
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn
import redis

class StockAnalyzer:
    sid = 0
    code = ""
    config_info = dict()

    def __init__(self, sid, code, config_info):
        self.sid = sid
        self.code = code
        self.config_info = config_info
        self.db_conn = SqlUtil.get_db(config_info['db'])
        
    '''
        @desc: 分析该股票是否值得买入卖出
        @param: policy dict() 分析策略
        @return dict() 分析详情
    '''
    def analyze(self, policy):
        pass

    '''
        @desc: 获取股票基本信息
        @param: sid int 股票唯一id
        @return None/dict()
    '''
    def get_stock_info(self, sid):
        conn = redis.StrictRedis(config_info['redis'])
        stock_key = "stock:info-" + str(sid)

        cache_info = conn.get(stock_key)
        if cache_info is None:
            sql = "select id, type, code, name, pinyin, ecode, alias, company, business, captial, out_captial, profit, assets, \
                    hist_high, hist_low, year_high, year_low, month6_high, \
                    month6_low, month3_high, month3_low from t_stock where id = " + str(sid)
            try:
                record_list = self.db_conn.query_sql(sql)
            except Exception as e:
                return None

            if len(record_list) < 1:
                return None

            stock_info = record_list[0]
            conn.set(stock_key, json.dumps(stock_info))
            return stock_info

        stock_info = json.loads(cache_info)
        print stock_info
        return stock_info

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
        record_list = []

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
        record_list = []

        try:
            record_list = self.db_conn.query_sql(sql)
        except Exception as e:
            return None

        return record_list

    '''
        @desc: 根据历史数据列表来判断股票价格近期趋势
            [-3% - 3%] 震荡区间
        @param: data_list list
        @return: int -1 下降, 0 震荡, 1 上升
    '''
    def get_trend(self, data_list):
        first_close_price = float(data_list[0]['close_price']) 
        last_close_price = float(data_list[-1]['close_price']) 

        vary_portion_list = [ float(data_info['vary_portion']) for day_info in data_list ]
        vary_portion_list.sort()
        max_portion = vary_portion_list[0]
        min_portion = vary_portion_list[-1]
        #TODO: 根据上涨/下降的最大幅度判断趋势

        if first_close_price < last_close_price * (1 - 0.03):
            return -1
        elif first_close_price > last_close_price * (1 + 0.03):
            return 1
        else:
            return 0


