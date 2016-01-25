#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 区间涨跌幅排行
#date: 2016/1/25

import sys, re, json, os
import datetime, time, logging
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from stock_util import *

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print "Usage: " + sys.argv[0] + " <conf> <start_date> <end_date>"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

     # 初始化日志
    logging.config.fileConfig(config_info["LOG"]["conf"])
    db_config = config_info['DB']

    start_day = int(sys.argv[2])
    end_day = int(sys.argv[3])

    stock_list = get_stock_list(db_config, 1, 1)
    vary_list = []
    start_hq_data = get_stock_data(db_config, start_day)
    end_hq_data = get_stock_data(db_config, end_day)

    for sid in stock_list.keys():
        # 忽略其中停牌的股票
        if sid not in start_hq_data or sid not in end_hq_data:
            continue

        stock_info = stock_list[sid]
        start_close_price = float(start_hq_data[sid]['close_price'])
        end_close_price = float(end_hq_data[sid]['close_price'])
        vary_portion = (start_close_price - end_close_price) / start_close_price * 100

        print format_log("vary_stock", {'sid': sid, 'code': stock_info['code'], 'name': stock_info['name'], 'start_close_price': start_close_price, 'end_close_price': end_close_price, 'vary_portion': vary_portion})
        vary_list.append((sid, stock_info['code'], stock_info['name'], start_close_price, end_close_price, vary_portion))

    #  按照跌幅的高低排序
    sorted(vary_list, key = lamba item : abs(item[3]), reverse=True)
    for item in vary_list:
        str = "\t".join(item)
        print str + "\n"










