#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 策略分析器的基类
#date: 2014/06/27

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis

class BasePolicy(object):

    def __init__(self, config_info, datamap):
        self.config_info = config_info
        self.datamap = datamap

        self.redis_conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))
        self.db_conn = SqlUtil.get_db(self.config_info["DB"])

