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


# 获取所有股票列表, 包含指数
def get_stock_list(db_config, type = 0):
    sql = "select id, code, name, pinyin, ecode, alias, company, business, hist_high, hist_low, year_high, year_low, month6_high, \
            month6_low, month3_high, month3_low from t_stock where status = 'Y' "
    if type > 0:
        sql = sql + " and type = " + str(type)
    #print sql

    record_list = []

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    stock_list = dict()
    for stock_info in record_list:
        stock_list[stock_info['id']] = stock_info

    return stock_list

# 获取当天所有的股票数据
def get_stock_data(db_config, day):
    sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and status = 'Y'".format(day=day)
    #print sql
    record_list = []

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None
    
    data = dict()
    for stock_data in record_list:
        data[stock_data['sid']] = stock_data

    return data

# 刷新股票的历史最高/最低价
def refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list):
    db_conn = SqlUtil.get_db(db_config)
    high_field_list = ["hist_high", "year_high", "month6_high", "month3_high"]
    low_field_list = ["hist_low", "year_low", "month6_low", "month3_low"]
    vary_stock_list = dict()

    for sid, stock_info in stock_list.items():
        if sid not in today_data_list:
            continue

        #print stock_info
        stock_data = today_data_list[sid]
        #print stock_data
        high_index = 4
        low_index = 4

        for index, field_name in enumerate(high_field_list):
            if float(stock_data['high_price']) > float(stock_info[field_name]):
                high_index = index
                break

        for index, field_name in enumerate(low_field_list):
            if float(stock_data['low_price']) < float(stock_info[field_name]):
                low_index = index
                break

        # 表明当天价格存在最高价或者最低价
        #print sid, high_index, low_index
        if high_index < 4 or low_index < 4:  
            vary_stock_list[sid] = {'high_index': high_index, 'low_index': low_index}
            sql = "update t_stock set "
            field_list = []

            if high_index < 4:
                for field_name in high_field_list[high_index:]:
                    stock_info[field_name] = stock_data['high_price']
                    field_list.append(field_name + "=" + stock_info[field_name])

            if low_index < 4:
                for field_name in low_field_list[low_index:]:
                    stock_info[field_name] = stock_data['low_price']
                    field_list.append(field_name + "=" + stock_info[field_name])
           
            sql = sql + ", ".join(field_list) + " where id=" + str(stock_info['id'])
            print sql

            try:
                db_conn.query_sql(sql, True)
            except Exception as e:
                continue
            
            log_info = {'sid': sid, 'code': stock_info['code'], 'name': stock_info['name'], 'day': day, 
                        'high_price': stock_data['high_price'], 'low_price': stock_data['low_price'], 
                       'high_index': high_index, 'low_index': low_index}

            print format_log("refresh_stock_info", log_info)

    #TODO: 统一删除变化的stock_info
    conn = redis.StrictRedis(redis_config['host'], redis_config['port'])
    key_list = [ "stock:info-" + str(sid) for sid in vary_stock_list.keys() ]
    conn.delete(tuple(key_list))

    return vary_stock_list

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
    db_config['port'] = int(db_config['port'])

    redis_config = config_info['REDIS']
    redis_config['port'] = int(redis_config['port'])
    print db_config, redis_config

    today_data_list = get_stock_data(db_config, day)
    #print today_data_list

    if len(today_data_list) > 0:
        stock_list = get_stock_list(db_config)

        vary_stock_list = refresh_stock_histdata(redis_config, db_config, stock_list, today_data_list)
        print len(vary_stock_list)