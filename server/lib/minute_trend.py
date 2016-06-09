#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分钟级别行情趋势分析
#date: 2014/06/28

import sys, re, json, os
import datetime, time
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

        '''
            分析当前处于整体趋势的哪个阶段, 找出买点/卖点, 目前仅考虑做多(做空逻辑类似, 判断刚好相反):
            买点 -> 整体处于上涨趋势, 目前处于回调或震荡阶段后的上升趋势, 价格比前一个低点高
            卖点 -> 处于上涨趋势结束后的下降趋势
        '''
        op = MinuteTrend.OP_WAIT
        price_range = ()

        # TODO: 当len(minute_items) >= 60时, 保存上次解析结果的下标, 从最后一段趋势开始解析, 优化过程
        # 存在问题: parse中解析的部分结果, 需要和之前的趋势统一放到一起进行归并处理
        #start_index = self.last_trend_index if self.last_trend_index > -1 else 0
        self.trend_list = self.parse(daily_item, minute_items)
        print self.trend_list

        latest_trend_item = self.trend_list[-1]
        #if len(minute_items) >= 60:
        #	self.last_trend_index = latest_trend_item['start']
        trend_count = len(self.trend_list)
        trend_length = latest_trend_item['end'] - latest_trend_item['start'] + 1
        print latest_trend_item

        if trend_length >= 3:
            # 找买入点: 当前趋势为上涨且节点数>=3 且当前价格>=上一段上涨趋势的最高点
            # 当前趋势为震荡向上, 判断是否有新高, 决定买入
            if (MinuteTrend.TREND_RISE == latest_trend_item['trend']) or (
                    MinuteTrend.TREND_WAVE == latest_trend_item['trend'] and 1 == latest_trend_item['direction']):
                same_item = MinuteTrend.rfind_same_trend(self.trend_list, trend_count - 1, MinuteTrend.TREND_RISE)
                print same_item

                current_price = max(daily_item['close_price'], latest_trend_item['high_price'])
                if same_item and current_price >= same_item['high_price']:
                    op = MinuteTrend.OP_BUY
                    price_range = (same_item['high_price'], current_price)

            # 找卖出点: 当前趋势为下跌且节点数>=3, 且当前价格<=上一段下跌趋势最低点
            # 当前趋势为震荡向上, 判断是否有新低, 决定卖出
            elif (MinuteTrend.TREND_FALL == latest_trend_item['trend']) or (
                    MinuteTrend.TREND_WAVE == latest_trend_item['trend'] and -1 == latest_trend_item['direction']):
                same_item = MinuteTrend.rfind_same_trend(self.trend_list, trend_count - 1, MinuteTrend.TREND_FALL)
                print same_item

                current_price = min(daily_item['close_price'], latest_trend_item['low_price'])
                if same_item and current_price <= same_item['low_price']:
                    op = MinuteTrend.OP_SELL
                    price_range = (current_price, same_item['low_price'])

        # TODO: 输出日志
        trend_stage = {"sid": self.sid, "code": daily_item['code'], "daily_trend": daily_trend,
                       "time": minute_items[latest_trend_item['end']]['time'], "op": op, "price_range": price_range,
                       "trend_item": latest_trend_item, "daily_item": daily_item}
        #print format_log("op=minute_trend", trend_stage)
        return trend_stage


    '''
        @desc 从指定位置往前找到相邻同方向的趋势节点
        @param trend_list [{}, ...]
        @param pos int 指定位置
        @param trend int 指定趋势
        @return trend_item/None
    '''
    @staticmethod
    def rfind_same_trend(trend_list, pos, trend):
        if pos >= len(trend_list):
            return None

        item = trend_list[pos]
        same_item = None
        offset = pos - 1

        while offset >= 0:
            if trend_list[offset]['trend'] == trend:
                same_item = trend_list[offset]
                break
            offset -= 1

        return same_item

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
        @desc 对分段的趋势节点进行归并处理, 每段趋势节点个数>=3
        @param range_list [(start, end, direction, length, high_price, low_price)]
        @return list[(start, end, direction, length, high_price, low_price)]
    '''
    @staticmethod
    def combine_list(range_list):
        loop = True
        loop_count = 0

        while loop:
            result_list = []
            loop_count += 1

            i = 0
            while i < len(range_list):
                item = range_list[i]
                last_item = result_list[-1] if len(result_list) > 0 else None
                next_item = range_list[i + 1] if i < len(range_list) - 1 else None
                count = item[3]

                if next_item and item[2] == next_item[2]:
                    merge_item = (item[0], next_item[1], item[2], next_item[1] - item[0] + 1, max(item[4], next_item[4]),
                            min(item[5], next_item[5]))
                    i = i+2
                # item与左右两边的趋势相反, 3段合并到一起
                elif i > 0 and i < len(range_list) - 1:
                    '''
                        前后相邻两段趋势肯定相同, 满足以下条件之一则合并:
                        前后两段长度 都 >= 3
                        相邻两段趋势都是上升, 且后一段的最高点 >= 前一段最高点
                        相邻两段趋势都是下跌, 且后一段的最低点 <= 前一段的最低点
                    '''
                    if (last_item[2] == next_item[2]) and ((last_item[3] >= 3 and next_item[3] >= 3) or (
                            last_item[2] == 1 and next_item[4] >= last_item[4]) or (
                            last_item[2] == -1 and next_item[5] <= last_item[5])):
                        merge_item = (last_item[0], next_item[1], last_item[2], next_item[1] - last_item[0] + 1,
                                      max(last_item[4], next_item[4]), min(last_item[5], next_item[5]))
                        result_list[-1] = merge_item
                        i = i + 2
                    else:
                        #  前2次循环, 对左右趋势节点<3的节点不做归并, 便于某些靠后的节点能靠后处理
                        '''
                        if loop_count <= 2:
                            i = i + 1
                            result_list.append(item)
                        else:
                     '''
                        # 当前节点为下跌, 下一个节点为上涨, 且上涨高点 <= 下跌高点, 则合并为下跌
                        #当前节点为上涨, 下一个节点为下跌, 且下跌低点 >= 上涨低点, 则合并为上涨
                        if (item[2] == -1 and next_item[4] <= item[4]) or (item[2] == 1 and next_item[5] >= item[5]):
                            merge_item = (
                            item[0], next_item[1], item[2], next_item[1] - item[0] + 1, max(item[4], next_item[4]),
                            min(item[5], next_item[5]))
                            result_list.append(merge_item)
                            i = i + 2
                        else:
                            merge_item = (last_item[0], next_item[1], last_item[2], next_item[1] - last_item[0] + 1,
                                          max(last_item[4], next_item[4]), min(last_item[5], next_item[5]))
                            result_list[-1] = merge_item
                            i += 2
                elif i == 0:
                    # 判断第2段趋势超过3个节点且与第1段趋势同方向时才合并
                    if next_item and next_item[3] >= 3 and item[2] == next_item[2]:
                        merge_item = (
                        item[0], next_item[1], next_item[2], next_item[1] - item[0] + 1, max(item[4], next_item[4]),
                        min(item[5], next_item[5]))
                        result_list.append(merge_item)
                        i += 2
                    else:
                        result_list.append(item)
                        i += 1

                # 最后一段趋势允许<3, 后面会新增节点
                elif i == len(range_list) - 1:
                    i += 1

            loop = False
            #print result_list
            range_list = result_list
            for index, item in enumerate(range_list):
                if item[3] < 3 and index != 0 and  index != len(range_list) - 1:
                    loop = True
                    break
            if not loop:
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
        combined_list = MinuteTrend.combine_list(range_list)
        #print combined_list

        # 10:00 之前不合并趋势
        need_merge = True if daily_item['time'] >= 1000 else False

        item_list = []
        for item in combined_list:
            last_item = item_list[-1] if len(item_list) > 0 else None
            trend = MinuteTrend.get_trend_by_price(price_list[item[0]], price_list[item[1]])

            # 合并相邻2段相同的趋势
            if need_merge and last_item and trend == last_item['trend']:
                merge_item = last_item
                merge_item['end'] = item[1]
                merge_item['direction'] = 1 if price_list[merge_item['end']] >= price_list[merge_item['start']] else -1
                merge_item['high_price'] = max(last_item['high_price'], item[4])
                merge_item['low_price'] = max(last_item['low_price'], item[5])
                merge_item['vary_portion'] = (price_list[merge_item['end']] - price_list[merge_item['start']]) / price_list[
                    merge_item['start']] * 100
                item_list[-1] = merge_item
            else:
                trend_item = dict()
                trend_item['start'] = item[0]
                trend_item['end'] = item[1]
                # 重新计算direction
                trend_item['direction'] = 1 if price_list[item[1]] >= price_list[item[0]] else -1
                trend_item['trend'] = trend
                trend_item['high_price'] = item[4]
                trend_item['low_price'] = item[5]
                trend_item['vary_portion'] = (price_list[item[1]] - price_list[item[0]]) / price_list[item[0]] * 100

                item_list.append(trend_item)
        #print len(item_list)
        #print item_list

        return item_list

if __name__ == "__main__":
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

                    #print data
                    item = json.loads(data, encoding='utf-8')
                    if type is None:
                        type = "daily" if 'code' in item else "realtime"

                    #print type, data
                    sid = item['sid']
                    if "daily" == type:
                        if sid not in daily_map:
                            daily_map[sid] = list()
                        print sid, daily_map[sid]
                        daily_map[sid].append(item)
                    elif "realtime" == type:
                        if sid not in realtime_map:
                            realtime_map[sid] = {}
                        item_time = item['items'][-1]['time']
                        if item_time not in realtime_map[sid]:
                            realtime_map[sid][item_time] = item
                except Exception as err:
                    print "err=parse_line line=" + line + " err=" + str(err)
                    continue

        except Exception as e:
            print "err=loaddata filename=" + filename + " err=" + str(e)
            return False

        return (daily_map, realtime_map)

    sid = 9606
    (daily_map, realtime_map) = loaddata("dump.log")
    print daily_map, realtime_map
    daily_item = daily_map[sid][0]
    (timenumber, minute_items) = realtime_map[sid].popitem()
    instance = MinuteTrend(sid)

    step = 5
    index = 0
    items = minute_items['items']
    min_count = len(items)
    print min_count
    while index <= min_count:
        index += 5
        offset = min(index, min_count)
        trend_stage = instance.core(daily_item, items[0 : offset])
        print index, items[offset-1:offset]
        print trend_stage
        print "price_op", trend_stage['op'], trend_stage['price_range'], trend_stage['time'], trend_stage['trend_item']
    print "finish"
