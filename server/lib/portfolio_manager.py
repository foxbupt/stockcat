#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 持仓组合管理
#date: 2016/08/06

import sys, re, json, os, random
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

    # 股票组合信息: sid -> {sid, code, op, quantity, state, open_price, open_cost, close_price, close_cost, profit}
    # state: 1 等待成交 2 已成交 3 已关闭
    order_stock = dict()
    # 交易记录列表: sid -> [{sid, order_id, op, order_time, quantity, price, cost}, ...]
    traded_map = dict()
    # 当前持仓市值: total/cash/commission/{code -> cost}
    holdings = dict()

    '''
        @desc portfolio_config 持仓组合配置, 详细字段列举如下:
            initial_money 初始资金池
            max_stock_count 允许最多的股票个数
            max_short_stock 最多做空股票只数
            max_trade_count 最多交易次数
            max_stock_portion 单只股票市值最大占比
            trade_period 交易频度控制, 表示多长时间(interval为10min的整数)内最多交易几只股票, 如缺省为30min内最多只能交易2只 {'interval', 'threshold'}
    '''
    def __init__(self, location, day, config_info, portfolio_config):
        self.location = location
        self.day = day
        self.config_info = config_info

        self.port_config = portfolio_config
        self.initial_money = self.port_config['initial_money']

        self.logger = logging.getLogger("order")
        self.redis_conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))
        self.holdings = {'total': self.initial_money, 'cash': self.initial_money, 'commission': 0}
        self.port_statinfo = {'trade_period': dict(), 'max_stock_count': 0, 'max_short_count': 0, 'max_trade_count': 0}

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

        # 建仓是否允许
        if not self.check_allow("OPEN", open_item):
            self.logger.info("%s %s", format_log("open_not_allowed", self.open_item), json.dumps(self.port_statinfo))
            return False

        # TODO: cash仅在成交后才会扣减, 实际通过IB下单时, 会出现成交前多只股票都可以下单
        rest_money = self.holdings['cash']
        quantity = self.cal_quantity(sid, open_item['op'], rest_money, open_item['open_price'])
        if quantity <= 0:
            self.logger.info("op=no_enough_money sid=%d code=%s op=%d rest_money=%d open_price=%.2f", 
                sid, open_item['code'], open_item['op'], rest_money, open_item['open_price'])
            return False

        # TODO: 推送下单消息, 设置建仓价格 + 止损价格, 设置下单类型为限价单
        order_info =  {'sid': sid, 'day': open_item['day'], 'code': open_item['code'], 'op': open_item['op'], 'quantity': quantity, 'stop_price': open_item['stop_price']}
        order_event = dict(order_info)
        order_event['order_type'] = "LMT"
        order_event['price'] = open_item['open_price']
        push_result = self.redis_conn.rpush("order-queue", json.dumps(order_event))

        # 更新剩余的现金
        sign = 1 if open_item['op'] == MinuteTrend.OP_LONG else -1
        cost = sign * quantity * open_item['open_price']

        order_event['time'] = open_item['time']
        self.logger.info("%s cost=%d", format_log("open_order", order_event), cost)

        order_info['open_price'] = open_item['open_price']
        order_info['state'] = self.STATE_WAIT_OPEN
        self.order_stock[sid] = order_info

        # 更新持仓管理的统计
        self.update_stat("OPEN", open_item)

        # TODO: 目前先手动构造fill_event来成交
        fill_event = {'code': order_event['code'], 'op': order_event['op'], 'quantity': quantity, 'price': order_event['price'], 'cost': cost, 'time': open_item['time']}
        fill_event['order_id'] = random.randint(100, 500)
        self.fill_order(fill_event)

        return True

    '''
        @desc 股票平仓
        @param sid int
        @param close_item dict(sid, day, code, time, op, price)
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
        elif not self.check_allow("CLOSE", close_item):
            self.logger.info("%s %s", format_log("close_not_allowed", close_item), json.dumps(self.port_statinfo))
            return False

        # TODO: 推送下单消息, 默认为市价卖出, 设置close_price时极为触及市价卖出, 后续支持order_type指定订单类型(市价/限价)
        order_event = {'sid': sid, 'order_type': 'MKT', 'day': close_item['day'], 'code': close_item['code'], 'op': close_item['op'], 'quantity': opened_map[sid]['quantity']}
        if 'price' in close_item:
            order_event['price'] = close_item['price']

        # TODO: 设置订单类型为触及市价
        self.redis_conn.rpush("order-queue", json.dumps(order_event))

        # 更新订单状态
        opened_map[sid]['state'] = PortfolioManager.STATE_WAIT_CLOSE
        order_event['time'] = close_item['time']
        # 更新持仓管理的统计
        self.update_stat("CLOSE", close_item)
        self.logger.info("%s", format_log("close_order", order_event))

        # TODO: 目前手动构造成交订单
        fill_event = {'code': order_event['code'], 'op': order_event['op'], 'quantity': opened_map[sid]['quantity'], 'price': order_event['price'], 'cost': order_event['price'] * opened_map[sid]['quantity'], 'time': close_item['time']}
        fill_event['order_id'] = random.randint(100, 500)
        self.fill_order(fill_event)
        return True

    '''
        @desc: 计算股票操作的数量
        @param sid int 
        @param op int
        @param rest_money int 当前可用的现金
        @param price float
        @return int
    '''
    def cal_quantity(self, sid, op, rest_money, price):
        quantity = 0
        min_cost = 1000

        # 做多交易
        if op == MinuteTrend.OP_LONG:
            # 剩下的钱不够
            if rest_money <= 0 or rest_money < min_cost:
                return 0

            # 判断可买的股票数
            avail_money = min(rest_money, self.initial_money * self.port_config['max_stock_portion'])
            quantity = int(round(avail_money/price))

        # 做空交易
        elif self.port_config['max_short_stock'] > 0:
            avail_money = self.initial_money * self.port_config['max_stock_portion'] 
            if rest_money > 0 and rest_money < avail_money:
                avail_money = max(rest_money, min_cost)

            quantity = int(round(avail_money / price))
            self.port_config['max_short_stock'] -= 1
         
        # 考虑到手续费, 对于>=100的单数股, 统一按200取整
        (base, mod) = divmod(quantity, 200)
        if mod >= 100:
            quantity = (base + 1) * 200

        return quantity

    '''
        @desc: 根据订单成交信息更新组合信息
        @param fill_event dict(order_id, code, op, quantity, price, cost, time)
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
        if order_info['state'] == PortfolioManager.STATE_WAIT_OPEN and order_info['op'] == fill_event['op']:
            order_info['state'] = PortfolioManager.STATE_OPENED
            order_info['quantity'] = fill_event['quantity']
            order_info['open_price'] = fill_event['price']
            order_info['open_cost'] = fill_event['cost']
        elif order_info['state'] == PortfolioManager.STATE_WAIT_CLOSE and order_info['op'] != fill_event['op']:
            order_info['quantity'] = order_info['quantity'] - fill_event['quantity']
            order_info['close_price'] = fill_event['price']
            order_info['close_cost'] = fill_event['cost']
            if order_info['quantity'] <= 0:
                order_info['state'] = PortfolioManager.STATE_CLOSED
                order_info['profit'] = order_info['close_cost'] - order_info['open_cost']
                order_info['profit_portion'] = (order_info['close_cost'] - order_info['open_cost']) / order_info['open_cost'] * 100
                self.logger.info("%s", format_log("order_profit", order_info))

        trade_info = {'code': fill_event['code'], 'op': fill_event['op'], 'quantity': fill_event['quantity'], 'order_id': fill_event['order_id'], 'price': fill_event['price'], 'cost': fill_event['cost']}
        trade_info['sid'] = sid
        trade_info['order_time'] = fill_event['time']

        if sid not in self.traded_map:
            self.traded_map[sid] = list()
        self.traded_map[sid].append(trade_info)

        self.update_stat("FILL", fill_event)
        self.logger.info("%s", format_log("fill_order", trade_info))

        self.update_holdings_from_fill(fill_event)
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

    # 根据成交订单更新持仓市值
    def update_holdings_from_fill(self, fill_event):
        sid = self.code2sid(fill_event['code'])
        sign = 1 if fill_event['op'] == MinuteTrend.OP_LONG else -1
        cost = sign * fill_event['cost']
        commission = max(1, round(fill_event['quantity'] / 200))

        self.holdings[sid] += cost
        self.holdings['commission'] += commission
        self.holdings['cash'] -= (cost + commission)
        self.holdings['total'] -= (cost + commission)

    '''
        @desc 更新持仓组合的统计信息
        @param type 取值 OPEN/CLOSE/FILL
        @param item
    '''
    def update_stat(self, type, item):
        if type == "OPEN":
            (hour, min) = divmod(item['time'], 100)
            (div, mod) = divmod(min, self.port_config['trade_period']['interval'])
            key = str(hour) + str(div * self.port_config['trade_period']['interval'])

            self.port_statinfo['trade_period'][key] += 1
            self.port_statinfo['max_stock_count'] += 1
            if item['op'] == MinuteTrend.OP_SHORT:
                self.port_statinfo['max_short_count'] += 1
        elif type == "FILL":
            self.port_statinfo['max_trade_count'] += 1

    '''
        @desc 检查指定的建仓/平仓是否允许
        @param type OPEN/CLOSE
        @param item dict
        @return bool
    '''
    def check_allow(self, type, item):
        if self.port_statinfo['max_trade_count'] >= self.port_config['max_trade_count']:
            return False

        if type == "OPEN":
            if self.port_statinfo['max_stock_count'] >= self.port_config['max_stock_count'] or \
            (item['op'] == MinuteTrend.OP_SHORT and self.port_statinfo['max_short_count'] >= self.port_config['max_short_count']):
                return False

            (hour, min) = divmod(item['time'], 100)
            (div, mod) = divmod(min, self.port_config['trade_period']['interval'])
            key = str(hour) + str(div * self.port_config['trade_period']['interval'])
            period_count = self.port_statinfo['trade_period'][key] if key in self.port_statinfo['trade_period'] else 0
            if period_count >= self.port_config['trade_period']['threshold']:
                return False

        return True

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
    fill_event['quantity'] = 80
    fill_event['price'] = 21.25
    fill_event['cost'] = fill_event['quantity'] * fill_event['price']
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
    close_event['quantity'] = 80
    close_event['price'] = 22.00
    close_event['cost'] = close_event['quantity'] * close_event['price']
    close_event['time'] = 1030
    manager.fill_order(close_event)

    for sid, order_info in manager.order_stock.items():
        print format_log("order_info", order_info)
        records = manager.get_trade_records(sid)
        for record in records:
            print format_log("trade_record", record)

