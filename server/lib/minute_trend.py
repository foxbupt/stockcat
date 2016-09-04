#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分钟级别行情趋势分析
#date: 2014/06/28

import sys, re, json, os
import datetime, time, random, traceback
import redis
from makedata import loaddata
from trend_helper import TrendHelper

class MinuteTrend(object):
    # 操作定义: 0 等待 1 做多 2 做空
    OP_WAIT = 0
    OP_LONG = 1
    OP_SHORT = 2

    # 趋势 -> 操作
    OP_MAP = {TrendHelper.TREND_FALL: OP_SHORT, TrendHelper.TREND_WAVE: OP_WAIT, TrendHelper.TREND_RISE: OP_LONG}

    # 趋势列表
    trend_list = []
    # 上次解析完最后一段趋势的起始下标
    last_trend_index = -1

    def __init__(self, sid):
        self.sid = sid

    '''
        @desc 结合实时价格及分钟行情给出当天趋势
        @param daily_item  dict 实时价格
        @param minute_items list 分钟价格列表
        @return (dict(sid, code, time, daily_trend, op, price_range, trend_item, daily_item), trend_list)
    '''
    def core(self, daily_item, minute_items):
        if len(minute_items) < 3:
            return None

        is_dapan = True if self.sid == 9609 else False
        trend_config = {'trend_vary_type': 'portion', 'trend_vary_portion': 1.0, 'min_trend_length': 3, 'stage_vary_portion': 1.0, 'daily_vary_portion': 2.0, 'latest_count': 30}
        if is_dapan: # DJI 道琼斯指数解析
            trend_config = {'trend_vary_type': 'value', 'trend_vary_portion': 30, 'min_trend_length': 3, 'stage_vary_portion': 30, 'daily_vary_portion': 50, 'latest_count': 30}

        # TODO: 当len(minute_items) >= 60时, 保存上次解析结果的下标, 从最后一段趋势开始解析, 优化过程
        # 存在问题: parse中解析的部分结果, 需要和之前的趋势统一放到一起进行归并处理
        current_time = minute_items[-1]['time']
        price_list = [item['price'] for item in minute_items]

        # 解析趋势分析
        trend_info = TrendHelper.core(price_list, trend_config)
        self.trend_list = trend_info['trend_list']
        daily_trend = trend_info['daily_trend']
        #print self.trend_list

        latest_trend_item = self.trend_list[-1]
        if latest_trend_item['length'] < 3 and len(self.trend_list) >= 2:
            latest_trend_item = self.trend_list[-2]

        plan_config = {'time': 940, 'vary_portion': 3.00}
        chance_info = dict()
        if not is_dapan:
            buy_chance = MinuteTrend.get_chance(daily_item, self.trend_list, TrendHelper.TREND_RISE, current_time, plan_config)
            if buy_chance and buy_chance['op'] == MinuteTrend.OP_LONG:
                chance_info = buy_chance
            else:
                chance_info = MinuteTrend.get_chance(daily_item, self.trend_list, TrendHelper.TREND_FALL, current_time, plan_config)

        # TODO: 输出日志
        trend_stage = {"sid": self.sid, "code": daily_item['code'], "daily_trend": daily_trend,
                       "time": current_time, "chance": chance_info,
                       "trend_item": latest_trend_item, "daily_item": daily_item}
        #print format_log("op=minute_trend", trend_stage)
        return (trend_stage, trend_info)


    '''
        @desc 分析趋势看是否存在交易机会
        @param trend_list
        @param current_time 当前时间, 格式为HHMM
        @param trend int 表明上涨/下跌趋势
        @param plan_config dict(time, vary_portion) 计划配置
        @return dict(op, price_range, stop_price)
    '''
    @staticmethod
    def get_chance(daily_item, trend_list, trend, current_time, plan_config):
        # 获取最近一段趋势节点, 要求长度>=3
        latest_trend_item = trend_list[-1]
        trend_count = len(trend_list)
        if latest_trend_item['length'] < 3 and trend_count >= 2:
            latest_trend_item = trend_list[-2]
        #print latest_trend_item
        if not latest_trend_item:
            return None

        '''
            分析当前处于整体趋势的哪个阶段, 找出买点/卖点, 目前仅考虑做多(做空逻辑类似, 判断刚好相反):
            买点 -> 整体处于上涨趋势, 目前处于回调或震荡阶段后的上升趋势, 价格比前一个低点高
            卖点 -> 处于上涨趋势结束后的下降趋势
        '''
        op = MinuteTrend.OP_WAIT
        price_range = ()
        stop_price = 0

        # 根据trend设置上涨标志位/方向/比较key/止损key
        isrise = True if TrendHelper.TREND_RISE == trend else False
        direction = 1 if TrendHelper.TREND_RISE == trend else -1
        compare_key = 'high_price' if TrendHelper.TREND_RISE == trend else 'low_price'
        stop_key = 'low_price' if TrendHelper.TREND_RISE == trend else 'high_price'

        # 找买入/卖出点: 当前趋势为上涨/下跌且节点数>=3 且当前价格>=/<=前两段上涨趋势的最高点/最低点
        # 当前趋势为震荡向上/向下, 判断是否有新高/新低, 决定买入/卖出
        if (trend == latest_trend_item['trend']) or (TrendHelper.TREND_WAVE == latest_trend_item['trend'] and direction == latest_trend_item['direction']):
            current_price = max(daily_item['close_price'], latest_trend_item[compare_key])
            # 短时间内涨幅超过>=3%, 直接操作
            if current_time <= plan_config['time'] and direction * daily_item['vary_portion'] >= plan_config['vary_portion']:
                op = MinuteTrend.OP_LONG if isrise else MinuteTrend.OP_SELL
                stop_price = latest_trend_item[stop_key]
                price_range = (current_price, current_price)

            # 趋势长度>=4 && <= 100(过滤掉多段震荡合并后的趋势节点) 且 abs(趋势幅度) >= 1.00
            elif latest_trend_item['length'] >= 4 and latest_trend_item['length'] <= 100 and direction * latest_trend_item['vary_portion'] >= 1.00:
                # 优先查找过去2段相同趋势节点, 找不到时查找过去2段震荡的节点, 避免前面全部是震荡, 第一段上涨/下跌趋势无法构成操作机会
                # 仍然存在一段下跌/一段上涨无法找到same_item形成操作的情形, 暂时忽略之
                # TODO: 其实这种机会可能也会比较弱势, 因为前面全是震荡, 不好说...
                same_item_list = TrendHelper.rfind_same_trend(trend_list, trend_count - 1, trend)
                if len(same_item_list) == 0:
                    same_item_list = TrendHelper.rfind_same_trend(trend_list, trend_count - 1, TrendHelper.TREND_WAVE)
                print same_item_list

                if len(same_item_list) > 0:
                    last_item = same_item_list[0]
                    far_item = same_item_list[1] if len(same_item_list) >= 2 else None
                    past_trend_price = max(last_item[compare_key], far_item[compare_key]) if far_item else last_item[compare_key]
                    past_stop_price = max(last_item[stop_key], far_item[stop_key]) if far_item else last_item[stop_key]
                    if (isrise and current_price >= past_trend_price) or (not isrise and current_price <= past_trend_price):
                        op = MinuteTrend.OP_LONG if isrise else MinuteTrend.OP_SHORT
                        price_range = (past_trend_price, current_price) if isrise else (current_price, past_trend_price)
                        stop_price = past_stop_price
            else:
                return None
        return {'op': op, 'price_range': price_range, 'stop_price': stop_price}

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print "Usage:" + sys.argv[0] + " <filename> <sid>"
        sys.exit(1)

    sid = int(sys.argv[2])
    import_datamap = loaddata(sys.argv[1])
    #print import_datamap
    daily_list = import_datamap['daily'][sid]
    items = import_datamap['realtime'][sid]
    instance = MinuteTrend(sid)

    step = 5
    index = 0
    min_count = len(items)
    #print min_count
    while index <= min_count:
        index += 5
        offset = min(index, min_count)
        price_list = [ minute_item['price'] for minute_item in items[0 : offset] ]
        close_price = items[offset]['price'] if offset < min_count else items[-1]['price']

        daily_item = daily_list[index] if index < len(daily_list) else daily_list[-1]
        daily_item["high_price"] = max(price_list)
        daily_item['low_price'] = min(price_list)
        daily_item['close_price'] = close_price
        daily_item['vary_portion'] = (close_price - daily_item['last_close_price']) / daily_item['last_close_price'] * 100

        (trend_stage, trend_list) = instance.core(daily_item, items[0 : offset])
        #print index, items[offset-1:offset]
        for trend_item in trend_list:
            print trend_item, price_list[trend_item['start']], price_list[trend_item['end']]
        #print trend_stage

        if trend_stage['chance'] and trend_stage['chance']['op'] != MinuteTrend.OP_WAIT:
            print "op=chance_info ", trend_stage['time'], trend_stage['chance'], trend_stage['trend_item']
        print "-----------"
    print "finish"
