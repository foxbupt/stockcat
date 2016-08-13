#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 交易订单执行及查询
#date: 2016/08/13

import sys, re, json, os
import datetime, time
import redis
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
from ib.ext.Contract import Contract
from ib.ext.Order import Order
from ib.opt import ibConnection, message
from stock_util import get_timenumber, get_current_day
from base_policy import BasePolicy
from minute_trend import MinuteTrend

class ExecutionHandler(BasePolicy):
    # 抽象订单执行
    def execute_order(self, item):
        pass

'''
    @desc IB订单执行处理
'''
class IBHandler(ExecutionHandler):
    order_routing = "SMART"
    currency = "USD"
    fill_map = {}

    def initialize(self, location, day):
        self.location = self.location
        self.day = self.day

        self.tws_conn = self.create_tws_connection()
        self.order_id = self.create_initial_order_id()
        self.register_handlers()

    '''
        @desc 所有错误消息处理
        @param msg object
    '''
    def _error_handler(self, msg):
        # Currently no error handling.
        print "Server Error: %s" % msg

    '''
        @desc 所有应答消息处理
        @param msg object
    '''
    def _reply_handler(self, msg):
        print "Server Response: %s, %s\n" % (msg.typeName, msg)
        # 处理传递下单的通知
        if msg.typeName == "openOrder" and msg.orderId == self.order_id and not self.fill_map.has_key(msg.orderId):
            self.create_fill_entry(msg)
        # c处理订单成交的状态通知
        if msg.typeName == "orderStatus" and msg.status == "Filled" and not self.fill_map[msg.orderId]["filled"]:
            self.create_fill(msg)


    # 创建ib网关连接
    def create_tws_connection(self):
        tws_conn = ibConnection()
        tws_conn.connect()
        return tws_conn

    '''
        @desc 每天的初始订单号以当天日期为前缀, 从1开始, 确保每天不会重复, 目前一天最多支持1000单
        @return int
    '''
    def create_initial_order_id(self):
        order_id_str = str(self.day) + "001"
        return int(order_id_str)

    def register_handlers(self):
        self.tws_conn.register(self._error_handler, 'Error')
        self.tws_conn.registerAll(self._reply_handler)

    '''
        @desc 创建交易合约
        @param symbol 股票代码
        @param sec_type 资产类型, 缺省为STK
        @param exchange 交易所, 缺省为SMART
        @param primary_exchange
        @param currency string 货币类型, 缺省为USD
        @return Contract
    '''
    def create_contract(self, symbol, sec_type, exchange, primary_exchange, currency):
        contract = Contract()
        contract.m_symbol = symbol
        contract.m_secType = sec_type
        contract.m_exchange = exchange
        contract.m_primaryExch = primary_exchange
        contract.m_currency = currency

        return contract

    '''
        @desc 创建订单
        @param order_type string MKT 市价订单, LMT 限价订单
        @param quantity int 交易数量
        @param action string 交易动作 BUY/SELL
        @return Order
    '''
    def create_order(self, order_type, quantity, action):
        order = Order()
        order.m_orderType = order_type
        order.m_totalQuantity = quantity
        order.m_action = action
        return order

    def create_fill_entry(self, msg):
        self.fill_map[msg.orderId] = {"symbol": msg.contract.m_symbol, "exchange": msg.contract.m_exchange, "direction": msg.order.m_action, "filled": False}

    def create_fill(self, msg):
        fd = self.fill_map[msg.orderId]
        symbol = fd["symbol"]
        quantity = fd["quantity"]
        op = MinuteTrend.OP_LONG if fd["direction"] == "BUY" else MinuteTrend.OP_SHORT
        price = msg.avgFillPrice
        cost = price * quantity
        filled = msg.filled

        fill_item = {'order_id': msg.orderId, 'code': symbol, 'op': op, 'quantity': quantity, 'price': price, 'cost': price * quantity, 'time': get_timenumber(3)}
        self.fill_map[msg.orderId]["filled"] = True
        self.redis_conn.rpush("fill-queue", json.dumps(fill_item))

    '''
        @desc 组织订单并调用IB API执行, 执行结果通过fill_event传入到fill-queue中
        @param item order_event {day, sid, code, order_type, op, quantity, [price], [stop_price]}
        @return order_id
    '''
    def execute_order(self, item):
        symbol = item['code']
        asset_type = "STK"
        order_type = item['order_type']
        quantity = item['quantity']
        direction = "BUY" if item['op'] == MinuteTrend.OP_LONG else "SELL"

        # 创建交易合约 和订单
        ib_contract = self.create_contract(symbol, asset_type, self.order_routing,self.order_routing, self.currency)
        ib_order = self.create_order(order_type, quantity, direction)

        # 传递订单执行
        self.tws_conn.placeOrder(self.order_id, ib_contract, ib_order)
        # 等待订单执行
        time.sleep(10)
        self.order_id += 1

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print "Usage:" + sys.argv[0] + " <location> <day> <config>"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[3])
    #logging.config.fileConfig(config_info["LOG"]["conf"])

    location = int(sys.argv[1])
    day = int(sys.argv[2])

    ib_handler = IBHandler(config_info, dict())
    ib_handler.init(location, day)

    sid = 2748
    code = "WUBA"

    # 建仓
    open_order = {'sid': sid, 'order_type': 'MKT', 'day': day, 'code': code, 'op': MinuteTrend.OP_LONG, 'quantity': 100, "price": 53.00}
    ib_handler.execute_order(open_order)

    time.sleep(30)

    # 平仓
    close_order = {'sid': sid, 'order_type': 'MKT', 'day': day, 'code': code, 'op': MinuteTrend.OP_SHORT, 'quantity': 100}
    ib_handler.execute_order(close_order)

    time.sleep(60)
