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
import datetime
sys.path.append('../../../../server')  
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_analyzer import StockAnalyzer

class StockBuyAnalyzer(StockAnalyzer):
    def evaluate(self, day, policy):
        stock_info = self.get_stock_info(self.sid)
        print stock_info

        cur_day = str(day)
        current_time = datetime.datetime(int(cur_day[0:4]), int(cur_day[4:6]), int(cur_day[6:8]))  
        start_day = '{0:%Y%m%d}'.format(current_time + datetime.timedelta(days = -60))
        
        # 获取最近60天内的交易数据
        history_data = self.get_histdata_range(self.sid, start_day, cur_day)
        today_data = history_data[0]
        print today_data
         
        check_result = self.check(stock_info, history_data, today_data)
        if check_result < 0:
            print format_log("check_failed", {'sid': self.sid, 'name': stock_info['name'], 
                'day': day, 'result': 'check_result', 'close_price': today_data['close_price']})
            return None

        today_open_price = float(today_data['open_price'])
        today_close_price = float(today_data['close_price'])
        
        # 股票趋势
        trend_info = self.get_trend(history_data[0 : 10], 5)
        print trend_info

        # 判断股票是否符合指定的分析策略
        judge_info = self.judge(trend_info, stock_info, history_data, policy)
        print judge_info
        if judge_info is False:
            print format_log("judge_failed", {'sid': self.sid, 'name': stock_info['name'], 'day': day,
                'trend': trend_info['trend'], 'wave': trend_info['wave'], 'close_price': today_data['close_price']})
            return None

        # TODO: 评估股票的综合得分
        score = 1

        pool_info = {'low_price': judge_info[0], 'high_price': judge_info[1]}
        pool_info.extend(trend_info)
        pool_info['current_price'] = today_data['close_price']
        pool_info['sid'] = sid
        pool_info['day'] = cur_day
        pool_info['score'] = score

        # 把股票加入股票池中
        add = self.add_stock_pool(self.sid, day, pool_info)

        return pool_info

    # 检查股票是否满足基本条件
    def check(self, stock_info, day60_data, today_data):
        open_price = float(today_data['open_price'])
        close_price = float(today_data['close_price'])

        # 当天价格在[3.00 - 15.00]之间, 开盘/收盘任一满足即可
        if (open_price < 3.00 or open_price > 15.00) and (close_price < 3.00 or close_price > 15.00):
            return -1

        # 净资产 >= 股票价格/4
        if float(stock_info['assets']) <= close_price/5:
            return -2

        # 流通市值低于10亿
        out_capital_amount = float(stock_info['out_capital']) * close_price
        if out_capital_amount < 10.0:
            return -3

        # 最近60天的有效交易日 < 20个, 表明停牌了一个月
        if len(day60_data) < 20:
            return -4

        # 最近10天的换手率都必须 >= 0.5%, 最高的换手率必须 >= 1.0%,  换手率 = 成交量(手) / 流通股本(亿股) * 100
        exchange_rate_list = [int(day_data['volume']) / float(stock_info['out_capital']) / 10000 for day_data in day60_data[0: 10]]
        print exchange_rate_list
        if max(exchange_rate_list) < 1.0 or min(exchange_rate_list) < 0.5:
            return -5
        
        return 0

    # 判断股票当前是否值得买入
    def judge(self, trend_info, stock_info, day60_data, policy):
        today_data = day60_data[0]
        today_open_price = float(today_data['open_price'])
        today_close_price = float(today_data['close_price'])

        day30_high = float(stock_info['month3_high'])
        day30_low = float(stock_info['month3_low'])

        rise_portion = (today_close_price - day30_low) / day30_low * 100
        high_portion = (day30_high - day30_low) / day30_low * 100
        print rise_portion, high_portion
        #vary_portion_list = [ float(data_info['vary_portion']) for day_info in data_list ]

        low_buy_price = 0.0
        high_buy_price = 0.0

        # 30日内最高价 与 30日最低价涨幅 < 10%, 直接忽略
        # 单日内涨幅或跌幅超过10%, 直接忽略
        if high_portion < 10 or abs(float(today_data['vary_portion'])) >= 11 :
            return False

        trend = trend_info['trend']
        wave = trend_info['wave']

        # 处于上升趋势
        if trend == 1:
            #TODO: 需要细化wave代表的波段类型, 如一直上涨/冲高回落
            # 上涨比例不超过15%, 当天为30天最高价
            if today_close_price == day30_high and rise_portion <= 15:
                low_buy_price = today_close_price * (1 - 0.02)
                high_buy_price = today_close_price * (1 + 0.02)
            
            # 当前上涨比例低于10% 且 当前价格离最高价在6%以上
            elif rise_portion <= 10 and high_portion - rise_portion >= 6:
                low_buy_price = today_close_price * (1 - 0.02)
                high_buy_price = today_close_price 
            else:
                return False

        # 震荡/下降趋势中, 离最低价在3%以下
        elif rise_portion <= 3:
            low_buy_price = today_close_price * (1 - 0.02)
            high_buy_price = today_close_price * (1 + 0.02)
        else:
            return False
        
        return (round(low_buy_price, 2), round(high_buy_price, 2))

    # 对股票进行买入评分
    def rank(self, trend_info, stock_info, day60_data, policy):
        today_data = day60_data[0]
        today_open_price = float(today_data['open_price'])

        # 默认为10分, 判断某项条件符合进行扣分
        score = 10

        return score
