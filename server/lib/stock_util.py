#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 公共接口
#date: 2013-09-16

import datetime, sys
sys.path.append('../../../../server')  
from pyutil.sqlutil import SqlUtil, SqlConn

# 假期定义
holidays = [{'start': 20130919, 'end': 20130921}, {'start':20131001, 'end':20131007}]

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

