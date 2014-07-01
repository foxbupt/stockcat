#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分时价格分析器
#date: 2014/06/28

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from base_policy import BasePolicy

class RTPolicy(BasePolicy):

    def serialize(self, item):
        key = "rt-" + str(item['sid']) + "-" + str(item['day'])
        for minute_item in item['items']:
            print minute_item
            self.redis_conn.rpush(key, json.dumps(minute_item))
