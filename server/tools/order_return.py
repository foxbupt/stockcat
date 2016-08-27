#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 交易订单分析
#date: 2016/08/19

import sys, re, json, os, traceback
import datetime, time, logging, logging.config
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log    
import pandas as pd     
from log_loader import LogLoader


def loadfile(filename, day=0): 
    order_map = dict()

    try:
        content = open(filename).read()
        lines = content.split("\n")
        for line in lines:
            line = line.strip("\n ")
            if len(line) == 0:
                continue

            #print line
            item = LogLoader.line2json(line, "op=(open|close)_order")
            if item is None:
                continue    
            elif day > 0 and item['day'] != day:
                continue
            
            itemday = item['day']
            if itemday not in order_map:
                order_map[itemday] = {}

            #print item
            sid = item['sid']
            if sid not in order_map[itemday]:
                order_map[itemday][sid] = []
            order_map[itemday][sid].append(item)
            #print sid, order_map[day][sid]

    except Exception as e:
        print traceback.format_exc()
        print "err=loadfile filename=" + filename
        return None
    
    return order_map

# 核心逻辑
def core(order_map):
    return_list = []

    print order_map
    for day, day_map in order_map.items():
        for sid, order_list in day_map.items():
            item = dict()

            open_order = order_list[0]
            close_order = order_list[1]

            for key, value in open_order.items():
                if key not in ["price", "time", "cost"]:
                    item[key] = value
                else:
                    item['open_' + key] = abs(value)

            item['close_price'] = close_order['price']
            item['close_time'] = close_order['time']
            item['close_cost'] = close_order['price'] * close_order['quantity']

            factor = 1 if open_order['op'] == 1  else -1
            item['profit'] = (item['close_cost'] - item['open_cost']) * factor
            item['profit_portion'] = "%.2f%%" % (item['profit'] / item['open_cost'] * 100)
            #print item
            return_list.append(item)
            

    print return_list
    return_pd = pd.DataFrame(return_list)
    print return_pd
    filename = "./order_return.csv"
    return_pd.to_csv(filename, index=False)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <filename> [day]"
        sys.exit(1)

                                                            
    filename = sys.argv[1]
    day = 0 if len(sys.argv) < 3 else int(sys.argv[2])

    order_map = loadfile(filename, day)
    core(order_map)
