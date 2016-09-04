#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 构造测试数据
#date: 2016/06/05

import sys, re, json, os
import datetime, time, traceback
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log

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

                #print type, item
                sid = item['sid']     
                if sid not in datamap['daily']:
                    datamap['daily'][sid] = list()
                if sid not in datamap['realtime']:
                    datamap['realtime'][sid] = list()

                if "daily" == type:
                    datamap['daily'][sid].append(item)
                elif "realtime" == type:
                    last_item = datamap['realtime'][sid][-1] if len(datamap['realtime'][sid]) > 0 else None
                    last_time = 0 if last_item is None else last_item['items'][-1]['time']
                    queue_item = {"sid": item['sid'], "day": item['day'], 'items': []}
                    for realtime_item in item['items']:
                        if last_time == 0 or realtime_item['time'] > last_time:
                            queue_item['items'].append(realtime_item)
                    datamap['realtime'][sid].append(queue_item)

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
    stock_set = set(stock_id) if stock_id > 0 else set(datamap['daily'].keys())
    offset = 0

    while True:
        if 0 == len(stock_set):
            break

        offset += 5
        finished_set = set()

        for sid in stock_set:
            for daily_item in datamap['daily'][sid][offset: offset + 5]:
                redis_conn.rpush("daily-queue", json.dumps(daily_item))

            realtime_list = datamap['realtime'][sid]
            realtime_count = len(realtime_list)
            if realtime_count > 1:
                for item in realtime_list[offset: offset + 5]:
                    redis_conn.rpush("realtime-queue", json.dumps(item))
            else:
                item = realtime_list[0]
                realtime_count = len(item['items'])
                loop_item = {"sid": item['sid'], "day": item['day']}
                loop_item["items"] = item['items'][offset : min(offset + 5, realtime_count)]
                redis_conn.rpush("realtime-queue", json.dumps(loop_item))

            print "op=pushdata sid=%d offset=%d realtime_count=%d" % (sid, offset, realtime_count)
            if offset >= len(datamap['daily'][sid]) and offset >= realtime_count:
                finished_set.add(sid)

        stock_set = stock_set - finished_set
        time.sleep(1)
    print "finish"

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
