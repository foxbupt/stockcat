#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 实时分析股票数据
#date: 2014-06-06

import os, sys, re, json, random
import datetime
#sys.path.append('../../../server')  
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from stock_util import get_past_openday

'''
    @desc: 获取过去几天的总览数据, 目前暂定过去5天, 先从缓存加载, 数据字段包括:
            平均成交量(avg_volume)/累计涨幅(sum_vary_portion)/累计涨跌额(sum_vary_price)
    @param: db_config dict
    @param: cur_day int 当前日期
    @param: count int 过去的天数
    @return dict
'''
def get_past_data(db_config, redis_config, cur_day, count):
    key = "pastdata-" + str(cur_day)
    stock_datamap = dict()
    redis_conn = redis.StrictRedis(redis_config['host'], redis_config['port'])

    datamap = redis_conn.hgetall(key)
    if datamap:
        return datamap

    db_conn = SqlUtil.get_db(db_config)
    start_day = get_past_openday(cur_day, count)

    try:
        sql = "select sid, avg(volume) as avg_volume, sum(vary_price) as sum_vary_price, sum(vary_portion) as sum_vary_portion from t_stock_data \
        where day >= " + start_day + " and day < " + cur_day + " group by sid"
        print sql
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None
    
    for record in record_list:
        item = dict()
        sid = record['sid']

        item['sid'] = int(sid)
        item['avg_volume'] = round(float(record['avg_volume']))
        item['sum_vary_price'] = float(record['sum_vary_price'])
        item['sum_vary_portion'] = float(record['sum_vary_portion'])
        stock_datamap[sid] = json.dumps(item)
    #print stock_datamap

    redis_conn.hmset(key, stock_datamap)
    redis_conn.expire(key, 86400)
    return stock_datamap

'''
    @desc: 更新当日上涨股票的上涨因子
    @param: redis_config dict
    @param: cur_day int
    @param: past_datamap dict
    @param: rise_key string
    @return 
'''
def refresh_rise_factor(redis_config, cur_day, past_datamap, riseset_key):
    redis_conn = redis.StrictRedis(redis_config['host'], redis_config['port'])
    rise_set = redis_conn.smembers(riseset_key)
    #print rise_set
    rf_zset_key = "risefactor-" + str(cur_day)

    for sid in rise_set:
        past_data_value = past_datamap[sid]
        past_data = json.loads(past_data_value)

        daily_data_value = redis_conn.get("daily-" + str(sid) + "-" + str(cur_day))
        #print daily_data_value
        if daily_data_value is None:
            continue

        stock_daily_data = json.loads(daily_data_value)

        vary_portion = (stock_daily_data['close_price'] - stock_daily_data['open_price']) / stock_daily_data['open_price'] * 100
        volume_ratio = stock_daily_data['predict_volume'] / past_data['avg_volume']
        high_portion = (stock_daily_data['close_price'] - stock_daily_data['open_price']) / (stock_daily_data['high_price'] - stock_daily_data['open_price']);
        rise_factor = round(vary_portion * volume_ratio * high_portion, 1)

        print format_log("refresh_rise_factor", {'sid': sid, 'vary_portion': vary_portion, 'volume_ratio': volume_ratio, 'high_portion': high_portion, 'rise_factor': rise_factor})
        if rise_factor >= 1.0:
            redis_conn.zadd(rf_zset_key, rise_factor, sid)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [day]"
        sys.exit(1)

    day = "{0:%Y%m%d}".format(datetime.date.today())
    if len(sys.argv) >= 3:
        day = sys.argv[2]

    config_info = Util.load_config(sys.argv[1])        
    db_config = config_info['DB']
    db_config['port'] = int(db_config['port'])

    redis_config = config_info['REDIS']
    redis_config['port'] = int(redis_config['port'])
    #print db_config, redis_config

    past_datamap = get_past_data(db_config, redis_config, day, 5)
    refresh_rise_factor(redis_config, day, past_datamap, "daily-riseset-" + str(day))
