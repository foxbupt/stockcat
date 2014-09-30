#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票连续上涨评级
#date: 2014/09/25

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_util import get_stock_info
from base_ranker import BaseRanker

class BasisRanker(BaseRanker):
    trend_list = []

    def rank(self, sid, day, range):
