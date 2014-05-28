#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 获取股票当前总体数据
#date: 2014-05-28

import os, sys, random
import datetime, urllib2
from multiprocessing.dummy import Pool as ThreadPool
#sys.path.append('../../../server')  
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
import redis

stock_map = {}
config_info = {}
items = []
#day = int("{0:%Y%m%d}".format(datetime.date.today()))

# 解析单个股票行情数据
def parse_stock_daily(line):
    parts = line.split("=")
    #print line, parts
    content = parts[1].strip('"')
    #print content

    fields = content.split("~")
    #print fields

    # 当日停牌则不能存入
    open_price = float(fields[5])
    close_price = float(fields[3])
    if open_price == 0.0 or close_price == 0.0:
        return None 

    item = dict()

    item['code'] = stock_code = fields[2]
    item['sid'] = stock_map[stock_code]
    item['last_close_price'] = fields[4]
    item['open_price'] = open_price
    item['high_price'] = fields[33]
    item['low_price'] = fields[34]
    item['close_price'] = close_price
    item['vary_price'] = fields[31]
    item['vary_portion'] = fields[32]
    # 成交量转化为手
    item['volume'] = int(fields[36])
    # 成交额转化为万元
    item['amount'] = int(fields[37])
    item['exchange_portion'] = fields[38]
    item['swing'] = fields[43]

    return item

# 获取股票当前价格及成交量等信息
def get_stock_daily(stock_info):
    scode = stock_info[1]
    url = "http://qt.gtimg.cn/r=" + str(random.random()) + "q=" + scode
    #print scode, url

    try:
        response = urllib2.urlopen(url, timeout=1)
        content = response.read()
    except Exception as e:
        print "err=get_stock_daily sid=" + sid
        return 

    if content:
        lines = content.strip("\n").split(";")
        for line in lines:
            if 0 == len(line):
                continue

            item = parse_stock_daily(line)
            if item is None:
                continue

            # TODO: 存到缓存中
            print format_log("fetch_daily", item)
            items.append(item)

# 获取股票盘中实时交易价格和成交量
def get_stock_realtime(stock_info):
    sid = stock_info[0]
    scode = stock_info[1]
    url = "http://data.gtimg.cn/flashdata/hushen/minute/" + scode + ".js?maxage=10&" + str(random.random())
    #print scode, url

    try:
        response = urllib2.urlopen(url, timeout=1)
        content = response.read()
    except Exception as e:
        print "err=get_stock_overview sid=" + sid
        return None

    content = content.strip(' ;"\n').replace("\\n\\", "")
    lines = content.split("\n")
    #print lines

    date_info = lines[1].split(":")
    day = int("20" + date_info[1])

    hq_time = list()
    hq_price = list()
    hq_volume = list()

    for line in lines[2:]:
        fields = line.split(" ")
        # 直接用小时+分组成的时间, 格式为HHMM
        time = int(fields[0])

        item = dict()
        item['sid'] = sid
        item['code'] = scode[2:]
        item['day'] = day
        item['time'] = time
        item['price'] = float(fields[1])
        item['volume'] = int(fields[2])

        if item['volume'] <= 0:
            continue

        hq_time.append(time)
        hq_price.append(item['price'])
        hq_volume.append(item['volume'])

    # 表示当天所有的成交量都为0, 当天停牌
    if len(hq_volume) == 0:
        return

    hq_dict = {'time': hq_time, 'price': hq_price, 'volume': hq_volume}
    #print hq_dict

    if 'REDIS' in config_info :
        key = "daily-" + str(sid) + "-" + str(day)
        conn = redis.StrictRedis(config_info['REDIS']['host'], int(config_info['REDIS']['port']))
        conn.set(key, json.dumps(hq_dict), 86400)

    print format_log("fetch_realtime", {'sid': sid, 'scode': scode, 'time': hq_time[len(hq_time) - 1], 'price': hq_price[len(hq_price) - 1]})
    return hq_dict

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print "Usage: " + sys.argv[0] + " <conf> <type> <sid:scode> [sid] [..]"
        print "type: daily/realtime"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])        
    type = sys.argv[2]

    stock_list = []
    for stock_str in sys.argv[3:]:
        (sid, scode) = stock_str.split(":")

        stock_list.append((sid, scode))
        stock_map[scode[2:]] = sid

    #print stock_list, stock_map

    count = max(int(len(stock_list) / 100), 1)
    if count > 1:
        pool = ThreadPool(count)
        if type == "daily":
            pool.map(get_stock_daily, stock_list)
        else:
            pool.map(get_stock_realtime, stock_list)    

        pool.close()
        pool.join()
    else:
        if type == "daily":
            map(get_stock_daily, stock_list)
        else:
            map(get_stock_realtime, stock_list)
