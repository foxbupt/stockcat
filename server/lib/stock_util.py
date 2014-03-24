#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 公共接口
#date: 2013-09-16

import datetime, sys
sys.path.append('../../../../server')  
from pyutil.sqlutil import SqlUtil, SqlConn

# 假期定义
holidays = [20140101, 20140131, {'start': 20140203, 'end': 20140206}, 20140407, {'start':20140501, 'end':20140502}, 20140602, 20140908, {'start': 20141001, 'end':20141003}, {'start':20141006, 'end':20141007}]

# 判断当天是否开市
def is_market_open(day):
    current_time = datetime.datetime(int(day[0:4]), int(day[4:6]), int(day[6:8]))  
    weekday = current_time.weekday()

    # 周六或周日休市
    if weekday == 0 or weekday == 6:
        return False

    intday = int(day)
    for item in holidays:
        if (isinstance(item, dict) and item['start'] <= intday and intday <= item['end']) or (intday == item):
            return False
    return True

# 获取当前日期最近的第几个交易日
def get_past_openday(current_day, offset):
    current_time = datetime.datetime(int(current_day[0:4]), int(current_day[4:6]), int(current_day[6:8]))  
    step = 1
    # 含当天
    open_count = 1

    while True:
        last_day = '{0:%Y%m%d}'.format(current_time + datetime.timedelta(days = -1 * step))
        step += 1
        if is_market_open(last_day):
            open_count += 1
            if open_count == offset:
                return last_day

    return 0

# 获取最近N日连续上涨/下跌的股票
def get_cont_stock(db_config, current_day, day_count, sum_portion, rise = True):
    start_day = get_past_openday(current_day, day_count)
    operator = ">" if rise else "<"

    sql = "select sid from t_stock_data where day >= {start_day} and day <= {current_day} and vary_portion {operator} 0 \
        group by sid having count(*) >= {day_count} and sum(vary_portion) >= {low_portion} and sum(vary_portion) <= {high_portion}"\
        .format(start_day=start_day, current_day=current_day, operator=operator, day_count=day_count, \
                low_portion=sum_portion[0], high_portion=sum_portion[1])
    print sql

    record_list = []

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    return [item['sid'] for item in record_list]

# 获取所有股票列表, 包含指数
def get_stock_list(db_config, type = 0):
    sql = "select id, code, name, pinyin, ecode, alias, company, business, hist_high, hist_low, year_high, year_low, month6_high, \
            month6_low, month3_high, month3_low from t_stock where status = 'Y' "
    if type > 0:
        sql = sql + " and type = " + str(type)

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    stock_list = dict()
    for stock_info in record_list:
        stock_list[int(stock_info['id'])] = stock_info

    return stock_list

'''
  @desc: 获取指定日期股票的总览数据
  @param db_config dict DB配置
  @param day int
  @param sid int 股票id, 缺省0表示获取所有股票
  @return dict()
'''
def get_stock_data(db_config, day, sid=0):
    if sid == 0:
        sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and status = 'Y'".format(day=day)
    else:
        sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and sid = {sid} and status = 'Y'".format(day=day, sid=sid)
    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    data = dict()
    for stock_data in record_list:
        data[int(stock_data['sid'])] = stock_data

    return data if 0 == sid else data[sid]