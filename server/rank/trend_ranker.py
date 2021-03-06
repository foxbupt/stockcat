#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票趋势评级
#date: 2014/09/25

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
sys.path.append('../lib')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_util import get_stock_info, get_stock_trendlist

from base_ranker import BaseRanker

class TrendRanker(BaseRanker):
    trend_list = []
    

    '''
    @desc: 趋势评级的逻辑实现
    @param: sid int 股票id
    @param: day int 当前日期
    @param: range int 时间跨度天数
    @return dict
    '''
    def rank(self, sid, day, range):
    	past_day = self.get_pastday(day, range)
    	print past_day

    	self.trend_list = get_stock_trendlist(self.db_config, sid, past_day, day)

