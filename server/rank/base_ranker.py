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

    def __init__(self, config_info):
        self.config_info = config_info
        self.db_config = config_info['DB']
        self.redis_config = config_info['REDIS']

        self.redis_conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))
        self.db_conn = SqlUtil.get_db(self.config_info["DB"])
        self.logger = logging.getLogger("ranker")

    '''
    @desc: 评级的逻辑实现
    @param: sid int 股票id
    @param: day int 当前日期
    @param: range int 时间跨度天数
    @return dict
    '''
    def rank(self, sid, day, range):
        pass
