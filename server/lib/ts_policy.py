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
        sid = item['sid']
        start_time = int(item['items'][0]['time'] / 100)
        last_time = self.time_map[sid]
        price_pair_map = self.vary_map[sid]
        if price_pair_map is None or len(price_pair_map) <= 3:
            print "nonexist_pairmap sid=" + str(sid)
            print self.vary_map
            return

        # 取出的key列表应该是按照时间大小排列的
        time_list = price_pair_map.keys().sort()
        print start_time, time_list
        key = "ts-rr-" + str(item['day'])
        rise_info = None

        cache_value = self.redis_conn.hget(key, sid)
        # 已经存在则判断[start_time, last_time]对应的最高价 >= high, 是则更新其时间
        # TODO: 连续拉升结束后, 下落后再次拉升如何处理
        if cache_value:
            rise_info = json.loads(cache_value)
            now_time = rise_info['now_time']
            diff_sec = time_diff(int(str(start_time) + "00"), int(str(now_time) + "00"))
            # 超过5min不再认为是连续拉升
            if diff_sec > 60 * 5:
                return

            rise_info = self.refresh_rapid(sid, rise_info, start_time, True)
            self.redis_conn.hmset(key, {'sid': json.dumps(rise_info)})

        else:
            index = time_list.index(start_time)
            while index >= 2 and index < len(time_list):
                now_time = time_list[index]
                past_time = time_list[index-2]
                index += 1

                # 对当前时间的最高价 减去 2分钟前的最低价，若涨幅比例超过1.6%，则认为存在快速拉升的可能
                cur_high_price = price_pair_map[now_time][0]
                past_low_price = price_pair_map[past_time][1]
                vary_portion = round((cur_high_price - past_low_price) / past_low_price * 100, 1)

                if (past_low_price >= 3.0 and vary_portion >= 1.6) or (past_low_price < 3.0 and vary_portion >= 2.5):
                    rise_info = {'start_time': past_time, 'now_time': now_time, 'low': past_low_price, 'high': cur_high_price, 'vary_portion': vary_portion}
                    break

            if rise_info and now_time < last_time:
                rise_info = self.refresh_rapid(sid, rise_info, time_list[index+1], True)
            if rise_info:
                self.redis_conn.hmset(key, {'sid': json.dumps(rise_info)})
                print format_log("ts_rapid_rise", {'sid': sid, 'day': item['day']}.update(rise_info))

    # 分析快速下降
    def rapid_fall(self, item):
        sid = item['sid']
        price_pair_map = self.vary_map[sid]
        if price_pair_map is None or len(price_pair_map) <= 2:
            print self.vary_map
            return

        start_time = int(item['items'][0]['time'] / 100)
        last_time = self.time_map[sid]

        # 取出的key列表应该是按照时间大小排列的
        print price_pair_map
        time_list = price_pair_map.keys().sort()
        key = "ts-rf-" + str(item['day'])
        fall_info = None

        cache_value = self.redis_conn.hget(key, sid)
        # 已经存在则判断[start_time, last_time]对应的最低价 <= low, 是则更新其时间
        if cache_value:
            fall_info = json.loads(cache_value)
            now_time = fall_info['now_time']
            diff_sec = time_diff(int(str(start_time) + "00"), int(str(now_time) + "00"))
            # 超过5min不再认为是连续下跌
            if diff_sec > 60 * 5:
                return

            fall_info = self.refresh_rapid(sid, fall_info, start_time, False)
            self.redis_conn.hmset(key, {'sid': json.dumps(fall_info)})
            print format_log("ts_rapid_fall", {'sid': sid, 'day': item['day']}.update(fall_info))

        else:
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
                    break

            if fall_info and now_time < last_time:
                fall_info = self.refresh_rapid(sid, fall_info, time_list[index+1], False)
            if fall_info:
                self.redis_conn.hmset(key, {'sid': json.dumps(fall_info)})
                print format_log("ts_rapid_fall", {'sid': sid, 'day': item['day']}.update(fall_info))


    def refresh_rapid(self, sid, rapid_info, start_time, rise_or_fall):
        price_pair_map = self.vary_map[sid]
        time_list = price_pair_map.keys().sort()

        index = time_list.index(start_time)
        while index < len(time_list):
            now_time = time_list[index]
            price_pair = price_pair_map[now_time]
            if rise_or_fall and price_pair[0] > rapid_info['high']:
                rapid_info['now_time'] = now_time
                rapid_info['high'] = price_pair[0]
                rapid_info['vary_portion'] = round((rapid_info['high'] - rapid_info['low']) / rapid_info['low'], 1)
            elif not rise_or_fall and price_pair[1] < rapid_info['low']:
                rapid_info['now_time'] = now_time
                rapid_info['low'] = price_pair[1]
                rapid_info['vary_portion'] = round((rapid_info['low'] - rapid_info['high']) / rapid_info['high'] * 100, 1)

        return rapid_info
