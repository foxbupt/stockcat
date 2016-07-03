#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 操作机会收益回归分析
#date: 2016/07/02

import sys, re, json, os
import datetime, time, logging, logging.config
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log    
#sys.path.append('../lib/')
from stock_util import get_hqdata, get_current_day
import redis      

# 单个chance_item的收益回归分析
def chance_return(config_info, location, day, item):    
    #print item    
    
    hqdata = get_hqdata(config_info['DB'], config_info['REDIS'], day, item['sid'])
    print hqdata['daily']
    
    chance_item = item['chance']
    trend_item = item['trend_item']    
    # 卖出时用平仓价格减卖出价格, 需要乘-1
    factor = 1 if 1 == chance_item['op'] else -1
    
    # 严格一点, 应该用time之后的high_price/low_price计算收益和回撤, 买入点以较大价格进入, 卖出以较低价格卖出    
    enter_price = chance_item['price_range'][1] if 1 == chance_item['op'] else chance_item['price_range'][0]
    exit_price = float(hqdata['daily']['close_price'])
    fall_price = float(hqdata['daily']['low_price'])  if 1 == chance_item['op'] else float(hqdata['daily']['high_price'])

    rise_portion = factor * (exit_price - enter_price) / enter_price * 100
    fall_portion = factor * (fall_price - enter_price) / enter_price * 100   
    
    logging.getLogger("chance").info("%s", format_log("chance_item", item))
    return {"sid": item['sid'], "code": item['code'], "time": item['time'], "op": chance_item['op'], "trend_length": trend_item['length'], "trend_portion": trend_item['vary_portion'], 
            "enter": enter_price, "exit": exit_price, "fall": fall_price, "close_portion": rise_portion, "fall_portion": fall_portion, "vary_portion": hqdata['daily']['vary_portion']}
  
# 核心逻辑
def core(config_info, queue, location, day):  
    redis_config = config_info['REDIS']
    conn = redis.StrictRedis(redis_config['host'], redis_config['port'])      
    count = 0
    return_list = []
    
    while True:
        try:
            pop_data = conn.blpop(queue, 1)
            if pop_data is None:
                time.sleep(1)
                break

            data = pop_data[1]
            item = json.loads(data)
            #print item
            if item is None:
                continue

            if 'daily_item' in item and item['daily_item']['day'] != day:
                logging.getLogger("chance").error("err=ignore_expired_item item_day=%d day=%d", item['daily_item']['day'], day)
                break
            
            return_info = chance_return(config_info, location, day, item)
            #print return_info
            return_list.append(return_info)
            logging.getLogger("chance").info("%s", format_log("chance_return", return_info))

            '''
            count += 1    
            if count % 5 == 0:
                break            
            '''
        except Exception as e:
            logging.getLogger("chance").exception("err=pop_item")

    return_list.sort(key=lambda x : (x['close_portion'], x['fall_portion']), reverse = True)
    for return_item in return_list[0:50]:
        logging.getLogger("chance").info("%s", format_log("top_return", return_item))
    print return_list[0:30]

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [location] [day]"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    print config_info
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

    # 初始化日志
    print config_info['LOG'], config_info['LOG']['conf']
    logging.config.fileConfig(config_info['LOG']['conf'])
                                                            
    location = 1                                                        
    day = get_current_day(location) 
    if len(sys.argv) >= 3:
        location = int(sys.argv[2])
    if len(sys.argv) >= 4:
        day = int(sys.argv[3])    
        
    core(config_info, "chance-queue", location, day)
