#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票评级的基类
#date: 2014/09/25

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn

class BaseRanker(object):
    # 评分和评级原因列表
    rank_score = 0
    reason_list = []

    def __init__(self, config_info):
        self.config_info = config_info
        self.db_config = config_info['DB']
        self.redis_config = config_info['REDIS']

        self.redis_conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))
        self.db_conn = SqlUtil.get_db(self.config_info["DB"])
        self.logger = logging.getLogger("ranker")

    '''
        @desc: 从指定日期获取过去天数对应的日期
        @param: day int
        @param: range int
        @return int
    '''
    def get_pastday(self, day, range):
        day_date = datetime.date.strptime("%Y%m%d", str(day))
        past_date = day_date - datetime.timedelta(days=range)
        return int(past_date.strftime("%Y%m%d"))

    '''
    @desc: 评级的逻辑实现
    @param: sid int 股票id
    @param: day int 当前日期
    @param: range int 时间跨度天数
    @return dict
    '''
    def rank(self, sid, day, range):
        pass


    '''
        @desc: 把数据项量化为数值, table为[(range, value), ...]. range 支持单个数值或区间, 区间为左闭右开
        @param: item_value int/float
        @param: table list [(1, xxx), ((start, end), xxx), ...]
        @param: default int/float 缺省值
        @return number
    '''
    def quantize(self, item_value, table, default = 0):
        for unit in table:
            condition, value = unit
            if isinstance(condition, tuple):
                if item_value >= condition[0] and item_value < condition[1]:
                    return value
                else:
                    continue
            else if condition == item_value:
                return value

        return default
