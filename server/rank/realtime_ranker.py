#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票实时评级
#date: 2014/09/25

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_util import get_stock_info
from base_ranker import BaseRanker

class RealtimeRanker(BaseRanker):
    daily_info = dict()
    daily_policy = dict()
    realtime_list = []

    '''
    @desc: 实时分析逻辑: 根据当天量比/涨幅和趋势来评级
    '''
    def rank(self, sid, day, range):
        rise_factor = self.redis_conn.zscore("rf-" + str(day), sid)
        daily_value = self.redis_conn.get("daily-" + str(sid) + "-" + str(day), sid)
        if daily_value:
            self.daily_info = json.loads(daily_value)
        self.daily_policy = self.redis_conn.hgetall("daily-policy-" + str(sid) + "-" + str(day))

        rt_list = self.redis_conn.lrange("rt-" + str(sid) + "-" + str(day), 0, -1)
        for item in rt_list:
            self.realtime_list.appen(json.loads(item))






