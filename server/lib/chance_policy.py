#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 操作机会决策分析
#date: 2016/07/02

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
import redis
from base_policy import BasePolicy
from minute_trend import MinuteTrend
from stock_util import get_stock_list

'''
    @desc: 对所有操作机会进行筛选过滤排序, 决策最终执行的操作
        1) 当日涨/跌幅 >= 6%, 不宜追入
        2) 价格高于昨日收盘, 不建议做空
        3) 大盘走势整体为下跌, 且当前价格离昨日收盘超过2%, 不建议做多
        3) 结合大盘走势、股票近期日K走势、开盘价格和日内趋势指定股票日内走势方向, 尽量同方向操作
        3) 已买入的股票组合由PortofioManager进行管理
        4) 对于已买入的股票, 提示卖出点时, 检查是否触及止损位, 触及直接卖出; 否则看是否达到3%-5%的界限, 达到也卖出; 没有提示则收盘卖出

'''
class ChancePolicy(BasePolicy):
    # 保存操作过的item, 可用sid -> time来唯一标识每个item
    item_map = dict()
    # 持仓操作的股票 sid -> {order_event, closed}
    stock_map = dict()
    # 大盘sid: location -> sid, A股选择上证指数, 美股为道琼斯指数
    dapan_map = {1: 2469, 3: 9609}
    # 建仓时间段
    chance_config = {3: {'open_time': 2130, 'deadline_time': 1200, 'stop_portion': 2.00, 'profit_portion': (3.00, 6.00)}}
    traded_count = 0

    '''
    @desc 过滤不合适的操作机会
    @param item dict
    @return None
    '''
    def filter(self, item):
        sid = item['sid']
        day = item['daily_item']['day']

        chance_info = item['chance']
        daily_item = item['daily_item']
        daily_policy_key = "daily-policy-" + str(sid) + "-" + str(item['day'])
        daily_policy_info = self.redis_conn.hgetall(daily_policy_key)
        # 日内涨幅 >= 6%不建议追高
        if chance_info['op'] == MinuteTrend.OP_LONG and daily_policy_info['day_vary_portion'] >= 6.00:
            return

        # TODO: 从redis中获取大盘趋势
        key = "chance-" + str(sid) + "-" + str(day)
        self.redis_conn.rpush(key, json.dumps(item))

        # 全局list, 倒序排列
        self.redis_conn.lpush("chance-" + str(day), json.dumps(item))

    '''
    @desc 定时运行对目前的操作机会进行综合排序, 每次取出最近前20个, 得到top3
    @param location int
    @param day int
    @param cur_timenumber int
    @return
    '''
    def rank(self, location, day, cur_timenumber):
        data_list = self.redis_conn.lrange("chance-" + str(day), 0, 20)
        if data_list is None or len(data_list) == 0:
            pass

        dapan_trend = MinuteTrend.TREND_WAVE
        dapan_sid = self.dapan_map[location]
        daily_cache_value = self.redis_conn.get("daily-"+ str(dapan_sid) + "-" + str(day))
        dapan_data = json.load(daily_cache_value) if daily_cache_value is not None else dict()
        if dapan_data:
            dapan_trend = MinuteTrend.TREND_RISE if (dapan_data['close_price'] - dapan_data['last_close_price']) >= 50 else MinuteTrend.TREND_FALL

        # 获取所有股票列表
        stock_info_map = get_stock_list(self.config_info["DB"], 1, location)
        item_list = []

        for data in data_list:
            item = json.load(data)
            sid = item['sid']
            item_key = (item['sid'], item['time'])
            # 获取最新的价格信息
            daily_cache_value = self.redis_conn.get("daily-"+ str(sid) + "-" + str(day))
            daily_item = json.load(daily_cache_value) if daily_cache_value else item['daily_item']

            # 操作机会已经交易过直接忽略
            if sid in self.item_map and self.item_map[sid] == item['time']:
                self.logger.info("desc=chance_traded location=%d sid=%d code=%s day=%d time=%d", location, sid, item['code'], day, item['time'])
                continue
            # 该股票的同方向操作已经交易, 直接忽略
            elif sid in self.stock_map:
                # 同方向超过12点的、相反方向的机会可用于尝试平仓
                if item['chance']['op'] != self.stock_map[sid]['op'] or item['time'] > self.chance_config[location]['deadline_time']:
                    self.close_postion(location, day, sid, item)
                    continue
                # 相反的方向提示平仓
                else:
                    self.logger.info("desc=chance_exist location=%d sid=%d code=%s day=%d time=%d", location, sid, item['code'], day, item['time'])
                    continue
            # 价格高于max(昨日收盘价, 当日开盘价), 不建议做空, 考虑到当日高开后低走下跌, 这种情况下低于开盘价也OK
            elif item['chance']['op'] == MinuteTrend.OP_SHORT and daily_item['close_price'] >= max(daily_item['last_close_price'], daily_item['open_price']):
                self.logger.info("desc=short_not_match location=%d sid=%d code=%s day=%d time=%d op=%d close_price=%.2f",
                                 location, sid, item['code'], day, item['time'], item['chance']['op'], daily_item['close_price'])
                continue
            # 操作机会随着大盘趋势演变, 还有可能进入视野
            elif (dapan_trend == MinuteTrend.TREND_RISE and item['chance']['op'] == MinuteTrend.OP_SHORT) or (dapan_trend == MinuteTrend.TREND_FALL and item['chance']['op'] == MinuteTrend.OP_LONG):
                self.logger.info("desc=ignore_contray_stock location=%d sid=%d code=%s day=%d dapan=%d time=%d op=%d",
                            location, sid, item['code'], day, dapan_trend, item['time'], item['chance']['op'])
                continue
            # 判断股票市值, 必须>=5亿刀
            elif float(stock_info_map[sid]['capital']) * daily_item['close_price'] <= 50000:
                self.logger.info("desc=ignore_small_cap location=%d sid=%d code=%s day=%d time=%d op=%d capital=%s close_price=%.2f",
                        location, sid, item['code'], day, item['time'], item['chance']['op'], stock_info_map[sid]['capital'], daily_item['close_price'])
                continue

            item_list.append(item)

        # 尝试对已建仓的股票进行平仓
        if cur_timenumber > self.chance_config[location]['deadline_time']:
            for sid, stock_open_info in self.stock_map.items():
                if stock_open_info['closed']:
                    continue
                self.close_position(location, day, sid, None)

        # 按照多个维度进行倒序排列, 依次为(趋势单位长度的振幅、当日趋势、当日涨跌幅/开盘时间), 目前开盘时间写死
        # TODO: 这里实际上基于股票多个维度的特征, 进行综合排序, 类似于搜索的排序, 需要抽取成通用的排序引擎
        # TODO: 比如这里可以结合股票市值、最近5日/30日换手率、最近5日/30日振幅、股票日趋势
        if len(item_list) > 0:
            item_list.sort(key=lambda item: (abs(item['trend_item']['vary_portion']/item['trend_item']['length']), item['daily_trend'], abs(item['daily_item']['vary_portion']) / (item['time'] - 2130)), reverse=True)
            print item_list

            offset = min(len(item_list), 3)
            for item in item_list[0:offset]:
                # 超过建仓时间 或者 趋势强度 < 0.10, 不考虑建仓
                if item['time'] > self.chance_config[location]['deadline_time'] or abs(item['trend_item']['vary_portion']/item['trend_item']['length']) < 0.10:
                    continue
                # 排序选出来的可能存在重复, TODO: 这里需要对同1个股票去重, 选择最新的1个item操作机会进行交易
                elif item['sid'] in self.item_map:
                    continue

                self.open_position(location, day, item['sid'], item)
                self.item_map[item['sid']] = item['time']

    '''
    @desc 根据操作机会进行交易建仓
    @param location int
    @param day int
    @param sid int
    @param item chance_item
    @return
    '''
    def open_position(self, location, day, sid, item):
        # 获取该股票所有的操作机会
        same_count = 0
        contray_count = 0

        key = "chance-" + str(sid) + "-" + str(day)
        # 该机会不一定是最新的1个
        chance_item_list = self.redis_conn.lrange(key, 0, -1)
        for chance_item in chance_item_list:
            if chance_item['op'] == item['op']:
                same_count += 1
            else:
                contray_count += 1

        # 直接买入
        if contray_count == 0 or same_count >= 2:
            # TODO: 调用PortfioManager进行持仓管理, 推送交易事件(order_event)
            order_event = {'sid': sid, 'day': day, 'code': item['code'], 'time': item['time']}
            open_price = (item['chance']['price_range'][0] + item['chance']['price_range'][1]) / 2
            stop_price = item['chance']['stop_price']
            if item['daily_trend'] != MinuteTrend.TREND_WAVE :
                if item['chance']['op'] == MinuteTrend.OP_LONG:
                    open_price = item['chance']['price_range'][1]
                    stop_price = min(stop_price, open_price * (1 - self.chance_config[location]['stop_portion']))
                else:
                    open_price = item['chance']['price_range'][0]
                    stop_price = max(stop_price, open_price * (1 + self.chance_config[location]['stop_portion']))

            order_event['chance'] = item['chance']
            order_event['open_price'] = open_price
            order_event['stop_price'] = stop_price
            self.stock_map[sid] = {'order': order_event, 'closed': False}
            self.logger.info("desc=open_position %s", format_log(order_event))

    '''
    @desc 根据操作机会提示进行平仓
    @param location int
    @param day int
    @param sid int
    @param item chance_item 可选
    @return
    '''
    def close_position(self, location, day, sid, item):
        if sid not in self.stock_map or self.stock_map[sid]['closed']:
            return

        stock_open_info = self.stock_map[sid]
        order_event = stock_open_info['order']
        daily_cache_value = self.redis_conn.get("daily-"+ str(sid) + "-" + str(day))
        daily_item = json.load(daily_cache_value) if daily_cache_value else None

        # 反方向的操作机会时, 立即卖出
        if item is not None and item['chance']['op'] != order_event['chance']['op']:
            if daily_item is None:
                daily_item = item['daily_item']
        if daily_item is None:
            return

        #TODO: 需要利用订单实际成交的价格来计算目前获利和止损
        current_timenumber = item['time'] if item is not None else int(daily_item['time']/100)
        current_price = daily_item['close_price']
        vary_portion = (current_price - order_event['open_price']) / order_event['open_price'] * 100
        need_close = False

        # 需要平仓: 越过止损位/出现反方向趋势且获利达到最小要求/临近收盘且时间>=1530
        if (order_event['chance']['op'] == MinuteTrend.OP_LONG and current_price <= order_event['stop_price']) or (order_event['chance']['op'] == MinuteTrend.OP_SHORT and current_price >= order_event['stop_price']):
            need_close = True
        elif item is not None and abs(vary_portion) >= self.chance_config[location]['profit_portion'][0]:
            need_close = True
        elif current_timenumber >= 1530:
            need_close = True

        #TODO: 调用订单平仓
        if need_close:
            self.stock_map[sid]['closed'] = True
            self.logger.info("desc=close_position location=%d sid=%d code=%s day=%d time=%d op=%d open_price=%.2f stop_price=%.2f close_price=%.2f vary_portion=%.2f",
                location, sid, order_event['code'], current_timenumber, order_event['chance']['op'], order_event['open_price'], order_event['stop_price'], order_event['close_price'], vary_portion)










