#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 每日刷新股票基本数据
#date: 2013-08-26

import os, sys, re, json, random
import datetime
#sys.path.append('../../../server')  
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from buy_analyzer import StockBuyAnalyzer
from stock_util import *


# 刷新股票的历史最高/最低价
def refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list, day, refresh = True):
    db_conn = SqlUtil.get_db(db_config)
    high_field_list = ["hist_high", "year_high", "month6_high", "month3_high"]
    low_field_list = ["hist_low", "year_low", "month6_low", "month3_low"]
    vary_stock_list = dict()

    for sid, stock_info in stock_list.items():
        # 忽略指数
        if sid not in today_data_list or int(stock_info['type']) == 2:
            continue

        #print stock_info
        stock_data = today_data_list[sid]
        #print stock_data
        high_index = 4
        low_index = 4
        close_price = float(stock_data['close_price'])

        for index, field_name in enumerate(high_field_list):
            if close_price > float(stock_info[field_name]):
                high_index = index
                break

        for index, field_name in enumerate(low_field_list):
            if close_price < float(stock_info[field_name]):
                low_index = index
                break

        # 表明当天价格存在最高价或者最低价
        #print sid, high_index, low_index
        if high_index < 4 or low_index < 4:  
            vary_stock_list[sid] = {'high_index': high_index, 'low_index': low_index}
            sql = "update t_stock set "
            field_list = []
            high_type = low_type = 0

            if high_index < 4:
                high_type = high_index + 1
                add_stock_price_threshold(db_config, sid, day, close_price, high_type, low_type)
                for field_name in high_field_list[high_index:]:
                    stock_info[field_name] = close_price
                    field_list.append(field_name + "=" + str(stock_info[field_name]))

            if low_index < 4:
                low_type = low_index + 1
                add_stock_price_threshold(db_config, sid, day, close_price, high_type, low_type)
                for field_name in low_field_list[low_index:]:
                    stock_info[field_name] = close_price
                    field_list.append(field_name + "=" + str(stock_info[field_name]))
           
            sql = sql + ", ".join(field_list) + " where id=" + str(stock_info['id'])
            print sql

            # 股票且设置刷新, 才插入价格突破记录
            if refresh and int(stock_info['type']) == 1:
                try:
                    db_conn.query_sql(sql, True)
                except Exception as e:
                    continue
            
            log_info = {'sid': sid, 'code': stock_info['code'], 'name': stock_info['name'], 'day': day, 
                        'close_price': stock_data['close_price'], 'high_price': stock_data['high_price'], 'low_price': stock_data['low_price'], 
                       'high_index': high_index, 'low_index': low_index}

            print format_log("refresh_stock_info", log_info)

    #TODO: 统一删除变化的stock_info
    if refresh:
        conn = redis.StrictRedis(redis_config['host'], redis_config['port'])
        key_list = [ "stock:info-" + str(sid) for sid in vary_stock_list.keys() ]
        conn.delete(tuple(key_list))

    return vary_stock_list

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [day] [refresh]"
        sys.exit(1)

    if len(sys.argv) >= 3:
        day = int(sys.argv[2])
    else:
        day = int("{0:%Y%m%d}".format(datetime.date.today()))

    need_refresh = True
    if len(sys.argv) >= 4:
        refresh = int(sys.argv[3])
        if refresh <= 0:
            need_refresh = False

    config_info = Util.load_config(sys.argv[1])        
    db_config = config_info['DB']
    db_config['port'] = int(db_config['port'])

    redis_config = config_info['REDIS']
    redis_config['port'] = int(redis_config['port'])
    print db_config, redis_config

    today_data_list = get_stock_data(db_config, day)
    print len(today_data_list)

    if len(today_data_list) > 0:
        stock_list = get_stock_list(db_config)
        vary_stock_list = refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list, day, need_refresh)

        #print len(vary_stock_list)
        #analyze_stock_set = set()
        #for sid, vary_info in vary_stock_list.items():
        #    analyze_stock_set.add(sid)

        ## 连续3日上涨且涨幅在5%以内的股票
        #cont_rise_stock = get_cont_stock(db_config, str(day), 3, (2, 5))
        #print cont_rise_stock
        #for sid in cont_rise_stock:
        #    analyze_stock_set.add(sid)

        ## 连续3日下跌且跌幅在10%以内的股票
        #cont_fall_stock = get_cont_stock(db_config, str(day), 3, (-10, -3), False)
        #print cont_fall_stock
        #for sid in cont_fall_stock:
        #    analyze_stock_set.add(sid)

        #print len(analyze_stock_set)
        #for sid in analyze_stock_set:
        #    policy = dict()
        #    analyzer = StockBuyAnalyzer(sid, config_info)

        #    analyze_info = analyzer.evaluate(day, policy)
        #    if analyze_info is None:
        #        print "not suitable, sid=" + sid
        #        continue
        #    else:
        #        #analyze_info['high_index'] = vary_info['high_index']
        #        #analyze_info['low_index'] = vary_info['low_index']
        #        analyze_info['name'] = stock_list[sid]['name']
        #        analyze_info['code'] = stock_list[sid]['code']

        #        print format_log("analyze_stock_info", analyze_info)
