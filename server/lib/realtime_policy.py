#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分时价格分析器
#date: 2014/06/28

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
import redis
from base_policy import BasePolicy
from trend_helper import TrendHelper
from minute_trend import MinuteTrend

class RTPolicy(BasePolicy):

    def serialize(self, item):
        key = "rt-" + str(item['sid']) + "-" + str(item['day'])
        last_time = 0

        if self.redis_conn.llen(key) > 0:
            last_item = json.loads(self.redis_conn.lindex(key, -1))
            last_time = last_item['time']

        for minute_item in item['items']:
            #print minute_item
            if minute_item['time'] <= last_time:
                continue

            self.redis_conn.rpush(key, json.dumps(minute_item))
            self.logger.debug("desc=realtime_item sid=%d day=%d volume=%.2f price=%.2f time=%d",
                    item['sid'], item['day'], minute_item['volume'], minute_item['price'], minute_item['time'])

    '''
        @desc 结合每分钟价格和成交量分析实时趋势, 用于指导日内交易操作
        @param item dict
        @return trend_stage
    '''
    def realtime_trend(self, item):
        sid = int(item['sid'])
        day = item['day']

        daily_key = "daily-" + str(sid) + "-" + str(day)
        daily_cache_value = self.redis_conn.get(daily_key);
        if daily_cache_value is None:
            self.logger.error("err=fetch_daily sid=%d day=%d", sid, day)
            return

        daily_item = json.loads(daily_cache_value)	
        rt_key = "rt-" + str(sid) + "-" + str(day)
        item_count = self.redis_conn.llen(rt_key)
        if item_count < 3:
            return

        if item_count % 5 == 0:
            # 暂定每5分钟调用分析一次, 后续根据时间段调整
            item_list = self.redis_conn.lrange(rt_key, 0, -1)
            minute_items = []
            for item_json in item_list:
                minute_items.append(json.loads(item_json))

            now_time = minute_items[-1]['time']
            instance = MinuteTrend(sid)
            (trend_stage, trend_info) = instance.core(daily_item, minute_items)
            self.logger.debug("%s", format_log("minute_trend", trend_stage))
            self.logger.debug("%s", format_log("trend_parse", trend_info))

            trend_detail = self.refresh_trend(sid, day, minute_items, trend_info)
            if 'trend' in trend_detail:
                self.logger.info("%s sid=%d day=%d item_count=%d time=%d", format_log("trend_detail", trend_detail), sid, day, item_count, now_time)

            if trend_stage['chance'] and trend_stage['chance']['op'] != MinuteTrend.OP_WAIT:
                trend_stage['trend_detail'] = trend_detail
                self.redis_conn.rpush("chance-queue", json.dumps(trend_stage))
                self.logger.info("%s", format_log("realtime_chance", trend_stage))

    '''
        @desc 更新整体趋势节点列表, 若趋势改变时建议
        @param sid int
        @param day int
        @param minute_items list
        @param trend_info dict
        @return dict('trend', 'op', 'changed') 大盘趋势改变时op为建议操作方向
    '''
    def refresh_trend(self, sid, day, minute_items, trend_info):
        item_count = len(minute_items)
        trend_detail = dict()

        # 存储(core_trend, active_trend, item_count, length) 到趋势队列中
        if trend_info['latest_trend']:
            trend_overview = {"count": item_count}
            trend_overview['trend'] = (trend_info['latest_trend']['core_item']['trend'], trend_info['latest_trend']['active_item']['trend'])
            if 'pivot' in trend_info and trend_info['pivot']:
                trend_overview['pivot'] = trend_info['pivot']

            trend_key = "trend-" + str(sid) + "-" + str(day)
            trend_node_count = self.redis_conn.llen(trend_key)
            trend_changed = False
            last_trend_node = None

            if trend_node_count > 0:
                last_trend_value = self.redis_conn.lindex(trend_key, -1)
                last_trend_node = json.loads(last_trend_value)
                #print last_trend_node, trend_overview

                # 与上一段趋势相同, 延长长度, 把最后一个节点pop出来
                if last_trend_node['trend'][0] == trend_overview['trend'][0] and last_trend_node['trend'][1] == trend_overview['trend'][1]:
                    trend_overview['length'] = last_trend_node['length'] + item_count - last_trend_node['count']
                    trend_overview['count'] = item_count
                    self.redis_conn.rpop(trend_key)
                # 与上一段趋势不相同, 表明趋势发生了变化, 需要关注已有持仓和新的建仓机会
                else:
                    trend_overview['length'] = item_count - last_trend_node['count']
                    trend_changed = True
            else:
                trend_overview['length'] = item_count

            self.redis_conn.rpush(trend_key, json.dumps(trend_overview))
            trend_detail['trend'] = trend_overview['trend']
            trend_detail['changed'] = trend_changed
            trend_detail['op'] = self.suggest_op(sid, day, last_trend_node, trend_overview) if trend_changed and last_trend_node else MinuteTrend.OP_MAP[trend_overview['trend'][0]]

        return trend_detail

    '''
        @desc 趋势改变时提供建议操作, TODO: 对建议操作加上概率, 比如核心趋势变化是强建议, 当前趋势变化是弱建议
        @param sid int
        @param day int
        @param last_trend_node dict(trend, count, length, pivot)
        @param trend_node dict(trend, count, length, pivot)
        @return op
    '''
    def suggest_op(self, sid, day, last_trend_node, trend_node):
        last_trend = last_trend_node['trend']
        trend = trend_node['trend']
        op = MinuteTrend.OP_MAP[trend[1]]

        if last_trend_node['length'] <= 10:
            return op

        # 主体趋势相同, 当前趋势不同
        if last_trend[0] == trend[0]:
            if last_trend[0] == TrendHelper.TREND_WAVE:
                return MinuteTrend.OP_MAP[trend[1]] if trend[1] != TrendHelper.TREND_WAVE else MinuteTrend.OP_MAP[last_trend[1]]
            else: # 暂时对(3,3) -> (3,1) 或(1,1) -> (1,3) 没有处理, 这种应该是过渡状态
                return MinuteTrend.OP_MAP[trend[1]] if 2 == abs(last_trend[1] - trend[1]) else MinuteTrend.OP_WAIT
        else:
            return MinuteTrend.OP_MAP[trend[0]]
