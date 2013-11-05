#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 每日分析股票数据
#date: 2013-08-26

import os, sys, re, json, random
import datetime
#sys.path.append('../../../server')  
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from stock_util import *
from stock_analyzer import StockAnalyzer
from policy_util import PolicyUtil

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [day]"
        sys.exit(1)

    if len(sys.argv) >= 3:
        day = int(sys.argv[2])
    else:
        day = int("{0:%Y%m%d}".format(datetime.date.today()))

    config_info = Util.load_config(sys.argv[1])        
    db_config = config_info['DB']

    stock_list = get_stock_list(db_config, 1)
    pid = 5
    policy_info = PolicyUtil.load_policy(db_config, pid)
    print policy_info

    count = 0
    for sid in stock_list.keys():
        sid = int(sid)
        analyzer = StockAnalyzer(sid, config_info)
        result = analyzer.evaluate(day, policy_info)
        ana_result = 1 if result else 0
        print "op=analyze sid=" + str(sid) + " result="  + str(ana_result)

        count = count +1
        if count == 5:
            break
        

