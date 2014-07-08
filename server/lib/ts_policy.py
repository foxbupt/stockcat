#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 成交明细分析器
#date: 2014/06/28

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from stock_util import time_diff
from base_policy import BasePolicy

class TSPolicy(BasePolicy):
    # 保存股票上次计算的时间: sid -> last_time(HHMM)
    time_map = dict()
    # 保存股票每分钟成交的最高/最低价: sid -> {time -> [high, low], ...}
    vary_map = dict()

    def serialize(self, item):
        sid = item['sid']
        key = "ts-" + str(sid) + "-" + str(item['day'])

        last_time = 0
        ts_map = dict()
        if sid in self.time_map:
            last_time = self.time_map[sid]
        else:
            self.time_map[sid] = last_time

        if sid in self.vary_map:
            ts_map = self.vary_map[sid]
        else: #TODO: 需要验证ts_map变化后vary_map里的值是否更新
            self.vary_map[sid] = ts_map

        old_last_time = last_time
        for ts_item in item['items']:
            ts_item['time'] = int(ts_item['time'])
            item_time = int(ts_item['time'] / 100)
            price = ts_item['price']
            self.redis_conn.rpush(key, json.dumps(ts_item))

            if item_time >= last_time:
                price_pair = [0.0, float("inf")]
                if item_time in ts_map:
                    price_pair = ts_map[item_time]

                if price > price_pair[0]:
                    price_pair[0] = price
                if price < price_pair[1]:
                    price_pair[1] = price

                last_time = max(item_time, last_time)
                ts_map[item_time] = price_pair

         # 更新last_time和每分钟的成交差异
        self.time_map[sid] = last_time
        for ts_time, price_pair in ts_map.items():
            if ts_time >= old_last_time:
                self.vary_map[sid][ts_time] = price_pair

    # 分析快速拉升
    def rapid_rise(self, item):
        #print self.vary_map
        #print self.time_map

        sid = item['sid']
        start_time = int(item['items'][0]['time'] / 100)
        last_time = self.time_map[sid]
        price_pair_map = self.vary_map[sid]
        if price_pair_map is None or len(price_pair_map) <= 3:
            print "nonexist_pairmap sid=" + str(sid)
            print self.vary_map
            return

        # 取出的key列表后按照时间大小排列
        time_list = price_pair_map.keys()
        time_list.sort()
        #print start_time, time_list

        rise_map = dict()
        rise_info = None

        key = "ts-rr-" + str(item['day'])
        cache_value = self.redis_conn.hget(key, sid)

        # 已经存在则判断[start_time, last_time]对应的最高价 >= high, 是则更新其时间
        # 连续拉升结束后, 超过5min后的再次拉升作为一个新的拉升波段, 根据起始时间存储多个拉升波段
        if cache_value:
            rise_map = json.loads(cache_value)

            for rise_start_time, rise_info in rise_map.items():
                now_time = rise_info['now_time']
                diff_sec = time_diff(int(str(start_time) + "00"), int(str(now_time) + "00"))

                # 5min 以内作为一个新的波段持续
                if diff_sec <= 60 * 5:
                    rise_info = self.refresh_rapid(sid, rise_info, start_time, True)
                    rise_map[rise_start_time] = rise_info
                    self.redis_conn.hmset(key, {sid: json.dumps(rise_map)})
                    return

        index = max(time_list.index(start_time), 2)
        while index >= 2 and index < len(time_list):
            now_time = time_list[index]
            past_time = time_list[index-2]
            index += 1

            # 对当前时间的最高价 减去 2分钟前的最低价，若涨幅比例超过1.6%，则认为存在快速拉升的可能
            cur_high_price = price_pair_map[now_time][0]
            past_low_price = price_pair_map[past_time][1]
            vary_portion = round((cur_high_price - past_low_price) / past_low_price * 100, 2)

            if (past_low_price >= 3.0 and vary_portion >= 1.6) or (past_low_price < 3.0 and vary_portion >= 2.5):
                rise_info = {'start_time': past_time, 'now_time': now_time, 'low': past_low_price, 'high': cur_high_price, 'vary_portion': vary_portion}
                rise_info['duration'] = time_diff(now_time, past_time)
                break

        #print rise_info, now_time
        if rise_info:
            if now_time < last_time:
                rise_info = self.refresh_rapid(sid, rise_info, time_list[index+1], True)

            rise_map[rise_info['start_time']] = rise_info
            self.redis_conn.hmset(key, {sid: json.dumps(rise_map)})

            rise_info['sid'] = sid
            rise_info['day'] = item['day']
            print format_log("ts_refresh_rapid_rise", rise_info)

    # 分析快速下降
    def rapid_fall(self, item):
        sid = item['sid']
        price_pair_map = self.vary_map[sid]
        if price_pair_map is None or len(price_pair_map) <= 2:
            print self.vary_map
            return

        start_time = int(item['items'][0]['time'] / 100)
        last_time = self.time_map[sid]

        #print price_pair_map
        time_list = price_pair_map.keys()
        time_list.sort()

        key = "ts-rf-" + str(item['day'])
        fall_info = None
        fall_map = dict()

        cache_value = self.redis_conn.hget(key, sid)
        # 已经存在则判断[start_time, last_time]对应的最低价 <= low, 是则更新其时间
        if cache_value:
            fall_map = json.loads(cache_value)

            for fall_start_time, fall_info in fall_map.items():
                now_time = fall_info['now_time']
                diff_sec = time_diff(int(str(start_time) + "00"), int(str(now_time) + "00"))

                # 超过5min不再认为是连续下跌
                if diff_sec <= 60 * 5:
                    fall_info = self.refresh_rapid(sid, fall_info, start_time, False)
                    fall_map[fall_start_time] = fall_info
                    self.redis_conn.hmset(key, {sid: json.dumps(fall_map)})
                    print format_log("ts_refresh_rapid_fall", fall_info)

                    return

        index = time_list.index(start_time)
        while index >= 2 and index < len(time_list):
            now_time = time_list[index]
            past_time = time_list[index-2]
            index += 1

            # 对当前时间的最低价 减去 2分钟前的最高价，若跌幅比例超过1.6%，则认为存在快速拉升的可能
            cur_low_price = price_pair_map[now_time][1]
            past_high_price = price_pair_map[past_time][0]
            vary_portion = round((cur_low_price - past_high_price) / past_high_price * 100, 1)

            if (past_high_price >= 3.0 and vary_portion <= -1.6) or (past_high_price < 3.0 and vary_portion <= -2.5):
                fall_info = {'start_time': past_time, 'now_time': now_time, 'low': cur_low_price, 'high': past_high_price, 'vary_portion': vary_portion}
                fall_info['duration'] = time_diff(now_time, past_time)
                break

        if fall_info:
            if now_time < last_time:
                fall_info = self.refresh_rapid(sid, fall_info, time_list[index+1], False)

            fall_map[fall_info['start_time']] = fall_info
            self.redis_conn.hmset(key, {sid: json.dumps(fall_map)})

            fall_info['sid'] = sid
            fall_info['day'] = item['day']
            print format_log("ts_rapid_fall", fall_info)


    '''
       @desc 刷新持续拉升/下跌的波段
       @param sid int
       @param rapid_info dict
       @param start_time int 起始时间
       @param rise_or_fall bool
       @return dict
    '''
    def refresh_rapid(self, sid, rapid_info, start_time, rise_or_fall):
        price_pair_map = self.vary_map[sid]
        time_list = price_pair_map.keys()
        time_list.sort()

        index = time_list.index(start_time)
        while index < len(time_list):
            now_time = time_list[index]
            price_pair = price_pair_map[now_time]
            index += 1

            # 上涨过程需要最高价持续突破
            if rise_or_fall and price_pair[0] > rapid_info['high']:
                rapid_info['now_time'] = now_time
                rapid_info['high'] = price_pair[0]
                rapid_info['vary_portion'] = round((rapid_info['high'] - rapid_info['low']) / rapid_info['low'] * 100, 2)

            elif not rise_or_fall and price_pair[1] < rapid_info['low']:
                rapid_info['now_time'] = now_time
                rapid_info['low'] = price_pair[1]
                rapid_info['vary_portion'] = round((rapid_info['low'] - rapid_info['high']) / rapid_info['high'] * 100, 2)

        rapid_info['duration'] = time_diff(rapid_info['now_time'], rapid_info['start_time'])
        return rapid_info
