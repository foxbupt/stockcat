#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 构造测试数据
#date: 2016/06/05

import sys, re, json, os
import datetime, time, traceback
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log, Util
import redis

def loaddata(filename):
    datamap = {}
    datamap['daily'] = dict()
    datamap['realtime'] = dict()

    try:
        content = open(filename).read()
        lines = content.split("\n")
        for line in lines:
            try:
                line = line.strip("\n ")
                if len(line) == 0:
                    continue

                #print line
                fields = line.split("|")
                if len(fields) < 2:
                    continue

                data = fields[1]
                parts = fields[0].split("-")
                type = None
                if len(parts) == 4:
                    type = parts[3].strip()

                #print data
                item = json.loads(data, encoding='utf-8')
                if type is None:
                    type = "daily" if 'code' in item else "realtime"

                #print type, data
                sid = item['sid']     
                if sid not in datamap['daily']:
                    datamap['daily'][sid] = list()
                if sid not in datamap['realtime']:
                    datamap['realtime'][sid] = dict()                 

                if "daily" == type:
                    datamap['daily'][sid].append(item)
                elif "realtime" == type:                    
                    item_time = item['items'][-1]['time']
                    if item_time not in datamap['realtime'][sid]:
                        datamap['realtime'][sid][item_time] = item
            except Exception as ex:
                traceback.print_exc()
                print "err=parse_line line=" + line + " err=" + str(ex)
                continue

    except Exception as e:
        print "err=loaddata filename=" + filename + " err=" + str(e)
        return False
    
    return datamap
    
def pushdata(config_info, datamap, stock_id=0):
    redis_conn = redis.StrictRedis(config_info['REDIS']['host'], config_info['REDIS']['port'])  
    for sid, daily_list in datamap['daily'].items():
        if stock_id > 0 and sid != stock_id:
            continue 
        for daily_item in daily_list:
            redis_conn.rpush("daily-queue", json.dumps(daily_item))
            
    for sid, realtime_map in datamap['realtime'].items(): 
        if stock_id > 0 and sid != stock_id:
            continue
            
        if len(realtime_map) > 1:
            for timenumber, item in realtime_map.items():
                redis_conn.rpush("realtime-queue", json.dumps(item))
        else:
            (timenumber, item) = realtime_map.popitem()
            count = len(item['items'])
            offset = 0
            while offset < count:
                loop_item = {"sid": item['sid'], "day": item['day']}
                loop_item["items"] = item['items'][offset : min(offset + 5, count)] 
                print offset, count
                offset += 5
                redis_conn.rpush("realtime-queue", json.dumps(loop_item))

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print "Usage: " + sys.argv[0] + " <conf> <filename>"
        sys.exit(1)

    filename = sys.argv[2]
    config_info = Util.load_config(sys.argv[1])
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

    datamap = loaddata(filename)  
    pushdata(config_info, datamap)
