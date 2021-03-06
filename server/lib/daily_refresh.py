#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 每日刷新股票基本数据
#date: 2013-08-26

import os, sys, re, json, random
import datetime
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
#from buy_analyzer import StockBuyAnalyzer
from stock_util import *


# 刷新股票的历史最高/最低价
def refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list, day, location = 1, refresh = True):
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
        out_capital = close_price * float(stock_info['out_capital']);
        capital_limit = 10
        if 3 == location:
            out_capital = out_capital / 10000
            capital_limit = 15
        # capital < 10/15(us)
        if out_capital <= capital_limit:
            continue

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

            # 起始日期定为之前3个交易日, 3个交易日内无同类型突破记录  
            range_start_day = get_past_openday(day, 3, location)

            if high_index < 4:
                high_type = high_index + 1

                high_threshold_list = get_stock_price_threshold(db_config, sid, range_start_day, day, high_type, 0)
                if 0 == len(high_threshold_list):
                    add_result = add_stock_price_threshold(db_config, sid, day, close_price, high_type, low_type)
                    print format_log("add_high_price_threshold", {'sid': sid, 'day': day, 'close_price': close_price, 'high_type': high_type, 'result':add_result})
                    if add_result and high_type <= 2: # 年内新高/历史最高才加入股票池
                        pool_result = add_stock_pool(db_config, redis_config, sid, day, 2, {'wave':1})    
                        print format_log("add_stock_pool", {'sid': sid, 'day': day, 'close_price': close_price, 'result':pool_result})

                for field_name in high_field_list[high_index:]:
                    stock_info[field_name] = close_price
                    field_list.append(field_name + "=" + str(stock_info[field_name]))

            if low_index < 4:
                low_type = low_index + 1

                low_threshold_list = get_stock_price_threshold(db_config, sid, range_start_day, day, 0, low_type)
                if 0 == len(low_threshold_list):
                    add_result = add_stock_price_threshold(db_config, sid, day, close_price, high_type, low_type)
                    print format_log("add_low_price_threshold", {'sid': sid, 'day': day, 'close_price': close_price, 'low_type': low_type, 'result':add_result})
                    # TODO: 把下跌突破也加入股票池                       

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
        print "Usage: " + sys.argv[0] + " <conf> <location> [day] [refresh]"
        sys.exit(1)

    location = int(sys.argv[2])
    if len(sys.argv) >= 4:
        day = int(sys.argv[3])
    else:
        day = int("{0:%Y%m%d}".format(datetime.date.today()))

    need_refresh = True
    if len(sys.argv) >= 5:
        refresh = int(sys.argv[4])
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
        stock_list = get_stock_list(db_config, 0, location)
        vary_stock_list = refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list, day, location, need_refresh)

