#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票连续上涨评级
#date: 2014/09/25

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_util import get_stock_info
from base_ranker import BaseRanker

class ContRanker(BaseRanker):
    cont_list = []
    threshold_list = []

    def rank(self, sid, day, range):
    	# 连续上涨取最近3周的(有连续10天上涨)、价格突破取最近2周
    	self.cont_list = self.get_cont_list(sid, self.get_pastday(day, 3*7), day)
    	self.threshold_list = self.get_threshold_list(sid, self.get_pastday(day, 2*7), day)

    	(cont_score, cont_desc) = self.quantize_cont(sid)
    	(threshold_score, threshold_desc) = self.quantize_threshold(sid)

    '''
      @desc: 根据连续上涨列表输出评级分数
      	TODO: 后续数值表考虑从配置文件中读取
      @param: sid int
      @return int
    '''	
    def quantize_cont(self, sid):
    	if 0 == len(self.cont_list):
    		return (0, "")	

    	# 取最近的一条连续上涨记录
    	last_cont_record = self.cont_list[-1]

    	cont_days = int(last_cont_record['cont_days'])	
    	cont_days_score = self.quantize(cont_days, [(2, 2), ((3, 4), 4), ((4, 6), 5), ((6, 8), 4), ((8, 10), 3)])
    	max_volume_vary_portion = float(last_cont_record['max_volume_vary_portion'])
    	max_volume_score = self.quantize(max_volume_vary_portion, [((0.00, 2.00), 2), ((2.00, 4.00), 4), ((4.00, 6.00), 5), ((6, 8), 4), ((8, 10), 3), ((10, 100), 2)])
    	sum_price_vary_portion = float(last_cont_record['sum_price_vary_portion'])
    	
    	# 每日日均涨幅,
    	day_avg_vary_portion = sum_price_vary_portion / cont_days
    	day_avg_score = self.quantize(day_avg_vary_portion, [((0, 2.00), 1), ((2.00, 3.00), 2), ((3.00, 4.00), 3), ((4.00, 6.00), 5), ((6.00, 8.00), 4), ((8.00, 10.00), 3)])

    	desc = "连续上涨%d天, 累计涨幅.2f%%, 单日成交量最大放大.2f%" % (cont_days, sum_price_vary_portion, max_volume_vary_portion)
    	score = round((cont_days_score + max_volume_score + day_avg_score) / 3)
    	return (score, desc)

    def quantize_threshold(self, sid):
    	if 0 == len(self.threshold_list):
    		return (0, "desc")

    	# 判断high_type/low_type

    # 获取指定股票对应日期范围内的连续上涨列表
    def get_cont_list(self, sid, past_day, day):
    	sql = "select sid, day, start_day, cont_days, current_price, sum_price_vary_amount, sum_price_vary_portion, max_volume_vary_portion \
    			from t_stock_cont where sid = {sid} and start_day >= {past_day} and day <= {day} and status = 'Y'".format(sid=sid, past_day=past_day, day=day)
    	print sql

    	try:
		    db_conn = SqlUtil.get_db(self.db_config)
		    record_list = db_conn.query_sql(sql)
		except Exception as e:
		    print e
		    return None

		return record_list

	# 获取指定股票对应日期范围内的价格突破
	def get_threshold_list(self, sid, past_day, day):
		sql = "select sid, day, price, low_type, high_type from t_stock_price_threshold where \
			sid = {sid} and day >= {past_day} and day <= {day} and status = 'Y'".format(sid=sid, past_day=past_day, day=day)
		print sql

		try:
			db_conn = SqlUtil.get_db(self.db_config)
			record_list = db_conn.query_sql(sql)
		except Exception as e:
			print e
			return None

		return record_list
