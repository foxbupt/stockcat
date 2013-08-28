#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票买入分析器类, 评估单只股票的买入
#基本条件: 股票当前价格在[3, 20]区间以内, 流通股本 >= 10亿, 净资产不低于股票当前价格的1/5, 近阶段换手率在1%以上.
#         趋势条件:
#               a) 上证指数不处于近期低点(年/60天/30天), 但股票价格处于近期低点 [0 - 3%]
#               b) 股票处于下降趋势, 价格连续多日下降, 每日下降比例在6%以内, 价格处于近期低点[0 - 3%]
#               c) 股票整体处于上升趋势, 连续3日上涨, 总体上涨比例在[6% - 15%]区间内, 价格出现回调时.
#               d) 股票处于震荡趋势, 价格出现回调, 且价格比30日最低点超出比例在10%以内, 与30日最高点在5%以上.
#
#date: 2013-08-21

import sys, re, json, random
sys.path.append('../../../server')  
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn
import StockAnalyzer

class StockBuyAnalyzer(StockAnalyzer):
    def analyze(self, policy):
        stock_info = self.get_stock_info(self.sid)
        today = datetime.date.today() 
        cur_day = '{:%Y%m%d}'.format(today)
        start_day = '{:%Y%m%d}'.format(today + datetime.timedelta(days = -60)) 

        # 获取最近60天内的交易数据
        day60_history_data = self.get_histdata_range(self.sid, start_day, cur_day)
        today_data = day60_data[0]
        print today_data
         
        if not self.check(stock_info, day60_data):
            return None

        today_open_price = float(today_data['open_price'])
        today_close_price = float(today_data['close_price'])
        
        # 股票趋势
        trend = self.get_trend(day60_history_data[0 : 10])

        judge_info = self.judge(trend, stock_info, day60_history_data)
        if judge_info is False:
            return None

        analyze_data = dict()
        analyze_data['day'] = cur_day
        analyze_data['stock_info'] = stock_info
        analyze_data['today_data'] = today_data
        analyze_data['judge_info'] = {'low_buy_price': judge_info[0], 'high_buy_price': judge_info[1]}
        
        return analyze_data

    # 检查股票是否满足基本条件
    def check(self, stock_info, day60_data):
        today_data = day60_data[0]
        open_price = float(today_data['open_price'])
        close_price = float(today_data['close_price'])

        # 当天价格在[3.00 - 15.00]之间, 开盘/收盘任一满足即可
        if (open_price < 3.00 or open_price > 15.00) and (close_price < 3.00 and close_price > 15.00):
            return False

        # 净资产 >= 股票价格/4
        if float(stock_info['assets']) < close_proce/4:
            return False

        # 流通市值低于10亿
        out_capital_amount = float(stock_info['out_capital']) * close_price
        if out_capital_amount < 10.0:
            return False

        # 最近60天的有效交易日 < 20个, 表明停牌了一个月
        if len(day60_data) < 20:
            return False

        # 最近15天的换手率都必须 >= 1.0%, 换手率 = 成交量(手) / 流通股本(亿股) * 100
        for day_data in day60_data[0 : 15]:
            exchange_rate = day_data['volume'] / float(stock_info['out_capital']) / 100000
            if exchange_rate < 1.0:
                return False
        
        return True

    # 判断股票当前是否值得买入
    def judge(self, trend, stock_info, day60_data):
        today_data = day60_data[0]
        today_open_price = float(today_data['open_price'])
        today_close_price = float(today_data['close_price'])

        day30_high = float(stock_info['month30_high'])
        day30_low = float(stock_info['month30_low'])

        rise_portion = (today_close_price - day30_low) / day30_low
        high_portion = (day30_high - day30_low) / day30_low
        #vary_portion_list = [ float(data_info['vary_portion']) for day_info in data_list ]

        low_buy_price = 0.0
        high_buy_price = 0.0

        # 30日内最高价 与 30日最低价涨幅 < 10%, 直接忽略
        if high_portion < 0.10:
            return False

        # 处于上升趋势
        if trend == 1:
            # 上涨比例不超过15%, 当天为30天最高价
            if float(today_data['high_price']) == day30_high and rise_portion <= 0.15:
                low_buy_price = today_close_price
                high_buy_price = today_close_price * (1 + 0.03)
            
            # 当前上涨比例低于10% 且 当前价格离最高价在6%以上
            elif rise_portion <= 0.10 and high_portion - rise_portion >= 0.60:
                low_buy_price = today_close_price * (1 - 0.02)
                high_buy_price = today_close_price 

        # 震荡/下降趋势中, 离最低价在3%以下
        elif rise_portion <= 0.03:
             low_buy_price = day30_low
             high_buy_price = today_close_price * (1 + 0.02)
        
        return (low_buy_price, high_buy_price)
