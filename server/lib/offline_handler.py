#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 离线数据处理, 目前用来更新t_stock_dyn表
#date: 2016/07/23

import sys, re, json, os, datetime, time
import numpy, pymysql
import pandas as pd
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn

class OfflineHander:
    def __init__(self, config_info):
        self.config_info = config_info
        self.db_config = db_config = config_info['DB']
        charset = db_config['charset'] if 'charset' in db_config else 'utf8'
        conn = pymysql.connect(db_config['host'], db_config['username'], db_config['password'], db_config.get('database'),int(db_config['port']), charset=db_config['charset'])

    def core(self, location, day):
        stock_df = pd.read_sql_query("select * from t_stock where location = " + str(location) + " and status = 'Y'", self.conn, index_col='id')
        for sid in stock_df:
            dyn_info = self.update_dyn(day, sid)

            sql = SqlUtil.create_insert_sql("t_stock_dyn", dyn_info)
            try:
                db_conn = SqlUtil.get_db(self.db_config)
                db_conn.query_sql(sql, True)
            except Exception as e:
                print "err=insert_dyn sid=" + str(sid) + " day=" + str(day) + " ex=" + str(e)
                continue

            print format_log("add_stock_dyn", dyn_info)
        print format_log("finish_dyn", {'location': location, 'day': day})

    def update_dyn(self, day, sid):
        dyn_info = dict()

        sql = "select * from t_stock_data where sid = {sid} and day <= {day} order by day desc limit 120".format(sid=sid, day=day)
        print sql
        sd_df = pd.read_sql_query(sql, self.conn, index_col='day')
        print sd_df

        price_series = sd_df['close_price']
        dyn_info['ma5_price'] = price_series[:5].mean()
        dyn_info['ma20_price'] = price_series[:20].mean()
        dyn_info['ma60_price'] = price_series[:60].mean()
        dyn_info['ma120_price'] = price_series.mean()

        swing_series = sd_df['swing']
        dyn_info['ma5_swing'] = swing_series[:5].mean()
        dyn_info['ma20_swing'] = swing_series[:20].mean()

        vary_portion_series = sd_df['vary_portion']
        dyn_info['ma5_vary_portion'] = vary_portion_series[:5].mean()
        dyn_info['ma20_vary_portion'] = vary_portion_series[:20].mean()

        exchange_portion_series = sd_df['exchange_portion']
        dyn_info['ma5_exchange_portion'] = exchange_portion_series[:5].mean()
        dyn_info['ma20_exchange_portion'] = exchange_portion_series[:20].mean()

        dyn_info['sid'] = sid
        dyn_info['day'] = day
        dyn_info['create_time'] = time.mktime( datetime.datetime.now().timetuple())
        return dyn_info

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> <location> [day]"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])

    location = int(sys.argv[2])
    if len(sys.argv) >= 4:
        day = int(sys.argv[3])
    else:
        day = int("{0:%Y%m%d}".format(datetime.date.today()))




