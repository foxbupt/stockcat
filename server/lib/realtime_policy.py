#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分时价格分析器
#date: 2014/06/28

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from base_policy import BasePolicy

class RTPolicy(BasePolicy):

	def serialize(self, item):
		key = "rt-" + str(item['sid']) + "-" + str(item['day'])
		for minute_item in item['items']:
			#print minute_item
			self.redis_conn.rpush(key, json.dumps(minute_item))
			self.logger.debug("desc=realtime_item sid=%d day=%d volume=%.2f price=%.2f time=%d", 
					item['sid'], item['day'], minute_item['volume'], minute_item['price'], minute_item['time'])

	'''
		@desc: 结合当日行情和分时价格行情分析趋势
		@param: item dict
			设置trend/op到daily-policy key中
			操作字段(op): 1 卖出  2 待定 3 买入
			趋势/波段方向(trend): 1 下跌 2 震荡 3 上涨
	'''
	def day_trend(self, item):
		trend = op = 0

		daily_key = "daily-" + str(item['sid']) + "-" + str(item['day'])
		daily_cache_value = self.redis_conn.get(daily_key);
		if daily_cache_value is None:
			return

		daily_item = json.loads(daily_cache_value)	
		daily_policy_key = "daily-policy-" + str(sid) + "-" + str(item['day'])
		daily_policy_info = self.redis_conn.hgetall(daily_policy_key)

		rt_key = "rt-" + str(item['sid']) + "-" + str(item['day'])
		minute_items = self.redis_conn.lrange(rt_key, 0, -1)

		day_vary_portion = (daily_item['close_price'] - daily_item['open_price']) / daily_item['open_price'] * 100
		open_vary = daily_item['close_price'] - daily_item['open_price']
		max_vary = daily_item['high_price'] - daily_item['close_price']
		min_vary = daily_item['close_price'] - daily_item['low_price']

		# 开盘即涨停
		if daily_item['close_price'] == daily_item['open_price'] and daily_item['vary_portion'] >= 9.6:
			trend = 3
			op = 2
		elif abs(day_vary_portion) < 2:
			trend = 2
			op = 2
			volume_ratio = daily_policy_info['volume_ratio']
			# 缩量下跌震荡或放量上涨震荡, 考虑买入           
			if (volume_ratio <= 0.5 and day_vary_portion <= 0) or (volume_ratio >= 2 and day_vary_portion > 0):
				op = 3	

		elif daily_item['vary_price'] > 0.0:
			if max_vary == 0.0 or max_vary < min_vary:
				trend = 3
			else:
				trend = 1
		else:
			if min_vary == 0.0 or min_vary < max_vary:
				trend = 1
			else:
				trend = 3

		if trend == 1:
			op = 1
		elif trend == 3:
			op = 3

		trend_info = {'trend': trend, 'op': op}
		#TODO: 结合(max_vary/day_vary/min_vary)分析当日K线图形状
		if (trend == 3 and max_vary >= 0.5 * min_vary) or (trend == 1 and min_vary >= 0.5 * max_vary):
			minute_trend_info = self.minute_trend(daily_item, trend, minute_items)
			for key in minute_trend_info:
				trend_info[key] = minute_trend_info[key]

		daily_policy_key = "daily-policy-" + str(item['sid']) + "-" + str(item['day'])      
		self.redis_conn.hmset(daily_policy_key, trend_info)

		trend_info['sid'] = item['sid']
		trend_info['day'] = item['day']
		self.logger.debug("%s", format_log("daily_day_trend", trend_info))


	'''
		@desc: 根据分时价格分析盘中的实时趋势
		@param: daily_item dict
		@param: trend int
		@param: minute_items list
		@return dict('trend', 'op')
	'''
	def minute_trend(self, daily_item, trend, minute_items):
		# 分时行情个数<=5, 直接忽略
		if len(minute_items) <= 5:
			return {'trend': trend}
			
		max_vary = daily_item['high_price'] - daily_item['close_price']
		min_vary = daily_item['close_price'] - daily_item['low_price']
		price_list = list()
		for item in minute_items:
			price_list.append(item['price'])
			
		start_price = -1
		is_high = True
		if trend == 3:
			start_price = daily_item['high_price']
		else:
			start_price = daily_item['low_price']
			is_high = False
			
		start_index = price_list.index(start_price)
		if start_index == -1:
			return {'trend': trend}
			
		range_items = minute_items[start_index: -1]
		if len(range_items) <= 5:
			return {'trend': trend}

		range_price_list = price_list[start_index+1: -1]
		peak_list = []
		# mode: True表示下跌方向, False表示上涨
		mode = is_high
		last_peak = start_price

		# 遍历从高点/低点后的分时价格, 最高点取用于后面的每个高点, 最低点取后面的每个低点
		# TODO: 可以完善从日期区间的趋势波段中取波峰或波谷, 来判断当前股票的阻力位/支撑位和所处通道
		for minute_price in range_price_list:
			if mode and minute_price <= last_peak:
				last_peak = minute_price
			elif minute_price >= last_peak and not mode:
				last_peak = minute_price
			else:
				if (trend == 3 and not mode) or (trend == 1 and mode):
					peak_list.append(last_peak)
				mode = not mode	

		#TODO: 对取出的节点价格, 需要合并相邻且价格相近(1%)的点

		# 若从最高点往后, 需要看每个高点是否越来越低, 验证为下跌
		if trend == 3:
			high_price = start_price
			cont_fall = cont_rise = 0		
			for price in peak_list:
				vary_portion = abs(price - high_price) / max(price, high_price) * 100
				if (price <= high_price) or (vary_portion <= 1.00):
					cont_fall = cont_fall + 1
					high_price = min(price, high_price)
				elif (price >= high_price) or (vary_portion <= 1.00):
					cont_rise = cont_rise + 1
					high_price = max(price, high_price)

			if cont_fall / len(peak_list) >= 0.6:
				return {'trend': 1, 'op': 1}

		# 从最低点往后, 验证每个低点是否越来越高
		else:
            low_price = start_price
            cont_fall = cont_rise = 0		
			for price in peak_list:
				vary_portion = abs(price - low_price) / min(price, low_price) * 100
				if (price >= low_price) or (vary_portion <= 1.00):
					cont_rise = cont_rise + 1
					low_price = max(price, low_price)
				elif (price <= low_price) or (vary_portion <= 1.00):
					cont_fall = cont_fall + 1
					low_price = max(price, low_price)

			if cont_rise / len(peak_list) >= 0.6:
				return {'trend': 3, 'op': 3}

		return {'trend': trend}
