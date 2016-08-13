#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 持仓组合管理
#date: 2016/08/06

import sys, re, json, os
import datetime, time, logging, logging.config
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log
import redis
from minute_trend import MinuteTrend


class PortfolioManager:
    # 订单状态定义: 0 所有, 1 已下单建仓等待成交, 2 已建仓, 3  已下单平仓等待成交 4 已平仓
    STATE_ALL = 0
    STATE_WAIT_OPEN = 1
    STATE_OPENED = 2
    STATE_WAIT_CLOSE = 3
    STATE_CLOSED = 4

    # 初始可用现金和剩余可用现金
    initial_money = 3000
    rest_money = 0
    # 持仓组合的配置
    port_config = dict()
    # 组合管理运行时统计信息
    port_statinfo = dict()

    # 股票code到id的映射map: code -> sid
    code2id_map = dict()

    # 股票组合信息: sid -> {sid, code, op, count, state, open_price, open_cost, close_price, close_cost, profit}
    # state: 1 等待成交 2 已成交 3 已关闭
    order_stock = dict()
    # 交易记录列表: sid -> [{sid, order_id, op, order_time, count, price, cost}, ...]
    traded_map = dict()

    # 初始化接口, portfolio_config包含initial_money/max_trade_count(最多交易次数)/max_stock_count(允许最多的股票个数)/max_stock_portion(单只股票市值最大占比)
    def __init__(self, location, day, config_info, portfolio_config):
        self.location = location
        self.day = day
        self.config_info = config_info

        self.port_config = portfolio_config
        self.initial_money = self.port_config['initial_money']
        self.rest_money = self.initial_money

        self.logger = logging.getLogger("order")
        self.redis_conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))
        #self.db_conn = SqlUtil.get_db(self.config_info["DB"])

    '''
        @desc 股票建仓
        @param sid int
        @param open_item dict(sid, code, day, time, op, open_price, stop_price)
        @return
    '''
    def open(self, sid, open_item):
        wait_open_map = self.get_portfolio(PortfolioManager.STATE_WAIT_OPEN)
        if sid in wait_open_map:
            self.logger.info("op=stock_wait_open sid=%d code=%s", sid, wait_open_map[sid]['code'])
            return False

        # 暂时不允许已建仓的股票再建仓
        opened_map = self.get_portfolio(PortfolioManager.STATE_OPENED)
        if sid in opened_map and open_item['op'] == opened_map[sid]['op']:
            self.logger.info("%s", format_log("stock_order_opened", opened_map[sid]))
            return False

        min_count = 20
        # 剩下的钱不够
        if self.rest_money <= 0 or self.rest_money < open_item['open_price'] * min_count:
            self.logger.info("op=no_enough_money sid=%d code=%d rest_money=%d open_price=%.2f min_count=%d", sid, open_item['code'], rest_money, open_item['open_price'], min_count)
            return False

        # 判断可买的股票数
        avail_money = min(self.rest_money, self.initial_money * self.port_config['max_stock_portion'])
        avail_count = int(round(avail_money/open_item['open_price']))

        # 考虑到手续费, 对于>=100的单数股, 按200取整
        (base, mod) = divmod(avail_count, 200)
        print avail_money, avail_count, base, mod 
        if mod >= 100:
            avail_count = (base + 1) * 200

        # TODO: 推送下单消息, 设置建仓价格 + 止损价格
        order_event = {'sid': sid, 'day': open_item['day'], 'code': open_item['code'], 'op': open_item['op'], 'count': avail_count, 'open_price': open_item['open_price'], 'stop_price': open_item['stop_price']}
        self.redis_conn.rpush("order-queue", json.dumps(order_event))

        self.logger.info("%s avail_money=%d", format_log("open_order", order_event), avail_money)
        order_event['state'] = self.STATE_WAIT_OPEN
        self.order_stock[sid] = order_event
        return True

    '''
        @desc 股票平仓
        @param sid int
        @param close_item dict(sid, day, code, op, close_price)
        @return
    '''
    def close(self, sid, close_item):
        opened_map = self.get_portfolio(PortfolioManager.STATE_OPENED)
        if sid not in opened_map:
            self.logger.info("op=stock_not_open sid=%d", sid)
            return False
        elif close_item['op'] == opened_map[sid]['op']:
            self.logger.info("op=close_same_op sid=%d code=%s open_op=%d count=%d close_op=%d ", sid, opened_map[sid]['code'], opened_map[sid]['op'], opened_map[sid]['count'], close_item['op'])
            return False

        # TODO: 暂时仅考虑一次全部卖出

        # TODO: 推送下单消息, 默认为市价卖出, 设置close_price时极为触及市价卖出, 后续支持order_type指定订单类型(市价/限价)
        order_event = {'sid': sid, 'day': close_item['day'], 'code': close_item['code'], 'op': close_item['op'], 'count': opened_map[sid]['count']}
        if 'close_price' in close_item:
            order_event['close_price'] = close_item['close_price']
        self.redis_conn.rpush("order-queue", json.dumps(order_event))

        # 更新订单状态
        opened_map[sid]['state'] = PortfolioManager.STATE_WAIT_CLOSE
        self.logger.info("%s", format_log("close_order", order_event))
        return True

    '''
        @desc: 根据订单成交信息更新组合信息
        @param fill_event dict(order_id, code, op, count, price, cost, time)
        @return sid int
    '''
    def fill_order(self, fill_event):
        sid = self.code2sid(fill_event['code'])
        if sid == 0:
            self.logger.error("err=invalid_code code=%s", fill_event['code'])
            return 0

        order_info = self.order_stock[sid]
        if order_info['state'] == PortfolioManager.STATE_CLOSED:
            self.logger.info("%s", format_log("ignore_closed_order", order_info))
            return 0

        # TODO: 暂时不支持追加买入/卖出订单, 或者做多平仓后做空
        #print order_info
        if order_info['state'] == PortfolioManager.STATE_WAIT_OPEN and order_info['op'] == fill_event['op']:
            order_info['state'] = PortfolioManager.STATE_OPENED
            order_info['count'] = fill_event['count']
            order_info['open_price'] = fill_event['price']
            order_info['open_cost'] = fill_event['cost']
        elif order_info['state'] == PortfolioManager.STATE_OPENED and order_info['op'] != fill_event['op']:
            order_info['count'] = order_info['count'] - fill_event['count']
            order_info['close_price'] = fill_event['price']
            order_info['close_cost'] = fill_event['cost']
            if order_info['count'] <= 0:
                order_info['state'] = PortfolioManager.STATE_CLOSED

        # TODO: 追加交易记录
        trade_info = { 'code': fill_event['code'], 'op': fill_event['op'], 'count': fill_event['count'], 'order_id': fill_event['order_id'], 'price': fill_event['price'], 'cost': fill_event['cost']}
        trade_info['sid'] = sid
        trade_info['order_time'] = fill_event['time']

        if sid not in self.traded_map:
            self.traded_map[sid] = list()
        self.traded_map[sid].append(trade_info)

        self.logger.info("%s", format_log("fill_order", trade_info))
        return sid

    '''
        @desc: 根据股票代码获取对应sid
        @param: code string
        @return int 0 表示获取失败
    '''
    def code2sid(self, code):
        if not self.code2id_map:
            cache_value = self.redis_conn.get("stock:map-" + str(self.location))
            if not cache_value:
                return 0
            self.code2id_map = json.loads(cache_value)

        return int(self.code2id_map[code])

    '''
        @desc 获取指定state的组合信息
        @param state 取值参见STATE_XXX
        @return dict
    '''
    def get_portfolio(self, state):
        portfolio = dict()

        for sid, order_info in self.order_stock.items():
            if state == PortfolioManager.STATE_ALL or state == order_info['state']:
                portfolio[sid] = order_info
        return portfolio

    '''
        @desc 获取股票的交易记录列表
        @param sid int
        @return list
    '''
    def get_trade_records(self, sid):
        return self.traded_map[sid] if sid in self.traded_map else None

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print "Usage:" + sys.argv[0] + " <location> <day> <config>"
        sys.exit(1)

    portfolio_config = {"initial_money": 3000, "max_stock_count": 3, "max_stock_portion": 0.5, "max_trade_count": 8}
    config_info = Util.load_config(sys.argv[3])
    logging.config.fileConfig(config_info["LOG"]["conf"])

    location = int(sys.argv[1])
    day = int(sys.argv[2])
    manager = PortfolioManager(location, day, config_info, portfolio_config)

    sid = 2748
    code = "WUBA"

    open_item = dict()
    open_item['sid'] = sid
    open_item['code'] = code
    open_item['day'] = day
    open_item['time'] = 935
    open_item['op'] = MinuteTrend.OP_LONG
    open_item['open_price'] = 21.20
    open_item['stop_price'] = 21.00

    # 建仓下单
    open_result = manager.open(sid, open_item)
    portfolio_list = manager.get_portfolio(PortfolioManager.STATE_ALL)
    print portfolio_list

    # 建仓订单成交
    fill_event = dict()
    fill_event['order_id'] = 10012
    fill_event['code'] = code
    fill_event['op'] = MinuteTrend.OP_LONG
    fill_event['count'] = 80
    fill_event['price'] = 21.25
    fill_event['cost'] = fill_event['count'] * fill_event['price']
    fill_event['time'] = 940
    fill_open_result = manager.fill_order(fill_event)
    print fill_open_result

    portfolio_list = manager.get_portfolio(PortfolioManager.STATE_ALL)
    print portfolio_list

    # 平仓下单
    close_item = dict()
    close_item['sid'] = sid
    close_item['code'] = code
    close_item['day'] = day
    close_item['time'] = 950
    close_item['op'] = MinuteTrend.OP_SHORT
    close_item['close_price'] = 22.00
    close_result = manager.close(sid, close_item)

    # 平仓订单成交
    close_event = dict()
    close_event['order_id'] = 10013
    close_event['code'] = code
    close_event['op'] = MinuteTrend.OP_SHORT
    close_event['count'] = 80
    close_event['price'] = 22.00
    close_event['cost'] = fill_event['count'] * fill_event['price']
    close_event['time'] = 1030
    manager.fill_order(close_event)

    for sid, order_info in manager.order_stock.items():
        print format_log("order_info", order_info)
        records = manager.get_trade_records(sid)
        for record in records:
            print format_log("trade_record", record)

