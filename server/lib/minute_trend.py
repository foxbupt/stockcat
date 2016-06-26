#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分钟级别行情趋势分析
#date: 2014/06/28

import sys, re, json, os
import datetime, time, random
#sys.path.append('../../../../server')
#from pyutil.util import safestr, format_log
#from pyutil.sqlutil import SqlUtil, SqlConn
import redis

class MinuteTrend(object):
    # 操作定义: 0 等待 1 买入 2 卖出
    OP_WAIT = 0
    OP_BUY = 1
    OP_SELL = 2

    # 趋势定义: 1 下跌 2 震荡 3 上涨
    TREND_FALL = 1
    TREND_WAVE = 2
    TREND_RISE = 3

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
        @return dict(sid, code, time, daily_trend, op, price_range, trend_item, daily_item)
    '''
    def core(self, daily_item, minute_items):
        if len(minute_items) < 3:
            return None

        # 当天整体趋势: 计算从开盘价格到当前价格的涨幅, 至少要>=2%才算上涨
        daily_trend = MinuteTrend.get_trend_by_price(daily_item['open_price'], daily_item['close_price'], 2.0)
        current_time = minute_items[-1]['time']

        # TODO: 当len(minute_items) >= 60时, 保存上次解析结果的下标, 从最后一段趋势开始解析, 优化过程
        # 存在问题: parse中解析的部分结果, 需要和之前的趋势统一放到一起进行归并处理
        self.trend_list = self.parse(daily_item, minute_items)
        #print self.trend_list

        latest_trend_item = self.trend_list[-1]
        if latest_trend_item['length'] < 3 and len(self.trend_list) >= 2:
            latest_trend_item = self.trend_list[-2]

        plan_config = {'time': 2140, 'vary_portion': 3.00}
        chance_info = dict()
        buy_chance = MinuteTrend.get_chance(daily_item, self.trend_list, MinuteTrend.TREND_RISE, current_time, plan_config)
        if buy_chance and buy_chance['op'] == MinuteTrend.OP_BUY:
            chance_info = buy_chance
        else:
            chance_info = MinuteTrend.get_chance(daily_item, self.trend_list, MinuteTrend.TREND_FALL, current_time, plan_config)

        # TODO: 输出日志
        trend_stage = {"sid": self.sid, "code": daily_item['code'], "daily_trend": daily_trend,
                       "time": current_time, "chance": chance_info,
                       "trend_item": latest_trend_item, "daily_item": daily_item}
        #print format_log("op=minute_trend", trend_stage)
        return trend_stage


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
        isrise = True if MinuteTrend.TREND_RISE == trend else False
        direction = 1 if MinuteTrend.TREND_RISE == trend else -1
        compare_key = 'high_price' if MinuteTrend.TREND_RISE == trend else 'low_price'
        stop_key = 'low_price' if MinuteTrend.TREND_RISE == trend else 'high_price'

        # 找买入/卖出点: 当前趋势为上涨/下跌且节点数>=3 且当前价格>=/<=前两段上涨趋势的最高点/最低点
        # 当前趋势为震荡向上/向下, 判断是否有新高/新低, 决定买入/卖出
        if (trend == latest_trend_item['trend']) or (MinuteTrend.TREND_WAVE == latest_trend_item['trend'] and direction == latest_trend_item['direction']):
            same_item_list = MinuteTrend.rfind_same_trend(trend_list, trend_count - 1, trend)
            print same_item_list

            current_price = max(daily_item['close_price'], latest_trend_item[compare_key])
            if len(same_item_list) > 0:
                last_item = same_item_list[0]
                far_item = same_item_list[1] if len(same_item_list) >= 2 else None
                past_trend_price = max(last_item[compare_key], far_item[compare_key]) if far_item else last_item[compare_key]
                past_stop_price = max(last_item[stop_key], far_item[stop_key]) if far_item else last_item[stop_key]
                if (isrise and current_price >= past_trend_price) or (not isrise and current_price <= past_trend_price):
                    op = MinuteTrend.OP_BUY if isrise else MinuteTrend.OP_SELL
                    price_range = (past_trend_price, current_price) if isrise else (current_price, past_trend_price)
                    stop_price = past_stop_price
            elif current_time <= plan_config['time'] and direction * daily_item['vary_portion'] >= plan_config['vary_portion']:
                op = MinuteTrend.OP_BUY if isrise else MinuteTrend.OP_SELL
                price_range = (current_price, current_price)
                stop_price = latest_trend_item[stop_key]

        return {'op': op, 'price_range': price_range, 'stop_price': stop_price}

    '''
        @desc 从指定位置往前找到相邻同方向的趋势节点
        @param trend_list [{}, ...]
        @param pos int 指定位置
        @param trend int 指定趋势
        @return trend_item_list/None
    '''
    @staticmethod
    def rfind_same_trend(trend_list, pos, trend):
        if pos >= len(trend_list):
            return []

        item = trend_list[pos]
        same_item_list = []
        offset = pos - 1

        while offset >= 0:
            if trend_list[offset]['trend'] == trend:
                same_item = trend_list[offset]
                same_item_list.append(same_item)
            offset -= 1

        return same_item_list

    '''
        @desc 根据两个价格输出趋势判断
        @param pre_price 时间在前的价格
        @param now_price 时间在后的价格
        @param vary_portion 涨跌幅, 缺省为1.0
        @return trend, 参见TREND_XXX
    '''
    @staticmethod
    def get_trend_by_price(pre_price, now_price, vary_portion=1.0):
        portion = abs(now_price - pre_price) / pre_price * 100
        if portion < vary_portion:
            return MinuteTrend.TREND_WAVE
        else:
            return MinuteTrend.TREND_RISE if now_price > pre_price else MinuteTrend.TREND_FALL


    '''
        @desc 对分钟级别价格进行分割处理
        @param price_list [price, ...]
        @return list[(start, end, direction, length, high_price, low_price)]
    '''
    @staticmethod
    def split_list(price_list):
        delta_list = []
        last_price = price_list[0]
        for price in price_list:
            delta_list.append(price - last_price)
            last_price = price
        #print price_list
        #print delta_list

        range_list = []
        start_index = 0
        end_index = 2

        # 根据阶段高点/低点分割, 这里没有考虑涨跌幅
        direction = 1 if delta_list[1] >= 0.0 else -1
        while end_index < len(delta_list):
            if delta_list[end_index] * direction < 0:
                range_list.append((
                start_index, end_index - 1, direction, end_index - start_index, max(price_list[start_index:end_index]),
                min(price_list[start_index:end_index])))
                start_index = end_index - 1
                direction = -1 * direction
            end_index += 1

        # 最后一段
        range_tuple = (start_index, len(delta_list) - 1, direction, len(delta_list) - start_index,
                       max(price_list[start_index:len(delta_list)]), min(price_list[start_index:len(delta_list)]))
        range_list.append(range_tuple)
        #print range_list
        return range_list


    '''
        TODO: 由于请求间隔的原因, 可能存在前后2段趋势相同, 需要优先合并
        TODO: 对于上次合并分析的结果需要缓存，下次从最后2段趋势开始合并
        @desc 对分段的趋势节点进行归并处理,
        @param price_list []
        @param range_list [(start, end, direction, length, high_price, low_price)]
        @return list[{start, end, direction, length, high_price, low_price}]
    '''
    @staticmethod
    def combine_list(price_list, range_list):
        result_list = []

        i = 0
        while i < len(range_list):
            item = range_list[i]
            # 由于往前合并, 上1个节点可能已被合并, 所以取结果中的最后1段节点
            last_item = result_list[-1] if len(result_list) > 0 else None
            next_item = range_list[i + 1] if i < len(range_list) - 1 else None
            count = item[3]
            need_append = False

            # 趋势节点个数>= 3 或者是收尾节点, 直接追加
            if count >= 3 or i == 0 or i == len(range_list) - 1:
                need_append = True
            # 当前趋势节点个数 < 3 且下段趋势节点>=3
            elif count < 3 and next_item and next_item[3] >= 3:
                '''
                    前后相邻两段趋势肯定相同, 满足以下条件之一则合并:
                    后段长度 >= 3
                    相邻两段趋势都是上升, 且后一段的最高点 >= 前一段最高点
                    相邻两段趋势都是下跌, 且后一段的最低点 <= 前一段的最低点
             '''
                # 前后2段趋势相同, 且后段趋势高点更高、低点更低, 则这3段趋势直接合并
                if (next_item[2] == 1 and next_item[4] >= last_item['high_price']) or (next_item[2] == -1 and next_item[5] <= last_item['low_price']):
                    trend_item = dict()
                    trend_item['start'] = last_item['start']
                    trend_item['end'] = next_item[1]
                    # 重新计算direction
                    trend_item['direction'] = next_item[2]
                    trend_item['length'] = next_item[1] - last_item['start'] + 1
                    trend_item['high_price'] = max(last_item['high_price'], next_item[4])
                    trend_item['low_price'] = min(last_item['low_price'], next_item[5])

                    trend_item['trend'] = MinuteTrend.get_trend_by_price(price_list[trend_item['start']], price_list[trend_item['end']])
                    trend_item['vary_portion'] = (price_list[trend_item['end']] - price_list[trend_item['start']]) / price_list[trend_item['start']] * 100
                    result_list[-1] = trend_item

                    need_append = False
                    i = i + 2
                else:
                    need_append = True
            else:
                need_append = True

            if need_append:
                trend_item = dict()
                trend_item['start'] = item[0]
                trend_item['end'] = item[1]
                # 重新计算direction
                trend_item['direction'] = item[2]
                trend_item['length'] = item[3]
                trend_item['trend'] = MinuteTrend.get_trend_by_price(price_list[item[0]], price_list[item[1]])
                trend_item['high_price'] = item[4]
                trend_item['low_price'] = item[5]
                trend_item['vary_portion'] = (price_list[item[1]] - price_list[item[0]]) / price_list[item[0]] * 100
                result_list.append(trend_item)
                i += 1

        return result_list

    '''
        desc 解析分钟行情, 划分为多段
        @param daily_item dict
        @param minute_items list
        @return list[{'start', 'end', 'direction', 'high_price', 'low_price', 'vary_portion', 'trend'}]
    '''
    def parse(self, daily_item, minute_items):
        # 根据分段行情解析出趋势
        price_list = [item['price'] for item in minute_items]
        range_list = MinuteTrend.split_list(price_list)

        # 把长度过小的趋势合并到相邻节点上, 确保每段趋势长度>=3
        combined_list = MinuteTrend.combine_list(price_list, range_list)
        #print combined_list

        # TODO: 把中间的震荡趋势节点合并, 组成大的趋势列表, 用于判断股票的走势强弱
        item_list = []
        index = 0
        while index < len(combined_list):
            item = combined_list[index]
            offset = index
            # 合并连续相同的几段趋势节点, 最后1段趋势不参与合并
            while offset < len(combined_list) - 1:
                if combined_list[offset + 1]['trend'] == item['trend']:
                    offset += 1
                else:
                    break

            if offset > index:
                next_item = combined_list[offset]
                trend_item = item
                trend_item['start'] = item['start']
                trend_item['end'] = next_item['end']
                trend_item['length'] = trend_item['end'] - trend_item['start'] + 1

                # 重新计算direction
                trend_item['direction'] = 1 if price_list[next_item['end']] >= price_list[item['start']] else -1
                trend_item['trend'] = item['trend']
                trend_item['high_price'] = max(item['high_price'], next_item['high_price'])
                trend_item['low_price'] = min(item['low_price'], next_item['low_price'])
                trend_item['vary_portion'] = (price_list[next_item['end']] - price_list[item['start']]) / price_list[item['start']] * 100
                item_list.append(trend_item)
                index = offset + 1
            else:
                item_list.append(item)
                index += 1

        return item_list

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print "Usage:" + sys.argv[0] + " <filename> <sid>"
        sys.exit(1)

    def loaddata(filename):
        daily_map = dict()
        realtime_map = dict()

        try:
            content = open(filename).read()
            lines = content.split("\n")
            for line in lines:
                try:
                    line = line.strip("\n ")
                    if len(line) == 0:
                        continue

                    #print line
                    fields = line.split("|")
                    if len(fields) < 2:
                        continue

                    data = fields[1]
                    parts = fields[0].split("-")
                    type = None
                    if len(parts) == 4:
                        type = parts[3].strip()

                    #print type, data
                    item = json.loads(data, encoding='utf-8')
                    if type is None:
                        type = "daily" if 'code' in item else "realtime"

                    #print type, data
                    sid = item['sid']
                    if "daily" == type:
                        daily_map[sid] = item
                    elif "realtime" == type:
                        if sid not in realtime_map:
                            realtime_map[sid] = []
                        last_time = realtime_map[sid][-1]['time'] if len(realtime_map[sid]) > 0 else 0
                        for realtime_item in item['items']:
                            if last_time == 0 or realtime_item['time'] > last_time:
                                realtime_map[sid].append(realtime_item)
                except Exception as err:
                    print "err=parse_line line=" + line + " err=" + str(err)
                    continue

        except Exception as e:
            print "err=loaddata filename=" + filename + " err=" + str(e)
            return False

        return (daily_map, realtime_map)

    sid = int(sys.argv[2])
    (daily_map, realtime_map) = loaddata(sys.argv[1])
    #print daily_map, realtime_map
    daily_info = daily_map[sid]
    items = realtime_map[sid]
    instance = MinuteTrend(sid)

    step = 5
    index = 0
    min_count = len(items)
    #print min_count
    while index <= min_count:
        index += 3
        offset = min(index, min_count)
        price_list = [ minute_item['price'] for minute_item in items[0 : offset] ]
        close_price = items[offset]['price'] if offset < min_count else items[-1]['price']

        daily_item = dict(daily_info)
        daily_item["high_price"] = max(price_list)
        daily_item['low_price'] = min(price_list)
        daily_item['close_price'] = close_price

        trend_stage = instance.core(daily_item, items[0 : offset])
        print index, items[offset-1:offset]
        #print trend_stage
        if trend_stage['chance']:
            print "op=chance_info ", trend_stage['time'], trend_stage['chance'], trend_stage['trend_item']
    print "finish"
