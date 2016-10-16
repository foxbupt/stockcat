#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: ib测试demo, 利用opt下的connection/dispatcher, 在统一的handle方法里根据msg.typeName来进行分发处理
#TODO: 另外一种思路: 直接使用EClientSocket和EWrapper, 实现一个EWrapperImpl 继承EWrapper实现所有的方法, 把该实例传入EClientSocket的构造函数中
#   该实例获取到消息后, 把消息push到队列中, 由PortfolioManager进行处理

import time
import ib, ib.ext
from ib.ext.Contract import Contract
from ib.ext.Order import Order
from ib.opt import ibConnection, message

def create_contract(symbol, sec_type, exchange, primary_exchange, currency):
	contract = Contract()
	contract.m_symbol = symbol
	contract.m_secType = sec_type
	contract.m_exchange = exchange
	contract.m_primaryExch = primary_exchange
	contract.m_currency = currency

	return contract

def create_order(order_type, quantity, action):
	order = Order()
	order.m_orderType = order_type
	order.m_totalQuantity = quantity
	order.m_action = action
	return order
		
def format_contract(contract):
	return "{symbol:" + contract.m_symbol + " sectype:" + str(contract.m_secType) + " exchange=" + contract.m_exchange + " currency:" + contract.m_currency + "}" if contract else "{}" 

def format_order(order):
	return "{order_id:" + str(order.m_orderId) + " action:" + order.m_action + " order_type:" + order.m_orderType + " quantity:" + str(order.m_totalQuantity) + " limit_price:" + str(order.m_lmtPrice) + " aux_price=" + str(order.m_auxPrice) if order else "{}"
	
def format_msg(msg):
	if msg.typeName.find("Before") != -1 or msg.typeName.find("After") != -1:
		return ""
	
	fields = ["type=" + msg.typeName]
	for item in msg.items():
		if item[0] == "contract":
			#print format_contract(item[1])
			fields.append(item[0] + "=" + format_contract(item[1]))
		elif item[0] == "order":
			#print format_contract(item[1])
			fields.append(item[0] + "=" + format_order(item[1]))
		else:
			fields.append(item[0] + "=" + str(item[1]))
	return " ".join(fields)
	
def handle(msg):
	print "Server Response: %s, %s\n" % (msg.typeName, msg)
	#print dir(msg)
	#print msg.keys(), msg.values()
	#print msg.items()

	print format_msg(msg)
	# 处理传递下单的通知
	'''
	if msg.typeName == "openOrder" and msg.orderId == self.order_id and not self.fill_map.has_key(msg.orderId):
		self.create_fill_entry(msg)
	# c处理订单成交的状态通知
	if msg.typeName == "orderStatus" and msg.status == "Filled" and not self.fill_map[msg.orderId]["filled"]:
		self.create_fill(msg)
	if msg.typeName == "NextValidId":
		print "type=NextValidId order_id=" + str(msg.orderId)
	if msg.typeName == "ManagedAccounts":
		print "type=ManagedAccounts account_list=" + str(msg.accountsList)
	if msg.typeName == "CurrentTime":
		print "CurrentTime: time=" + str(msg.time)
	if msg.typeName == "UpdateAccountValue":
		print "type=UpdateAccountValue key=" + str(msg.key) + " value=" + str(msg.value) + " currency=" + str(msg.currency) + " account_name=" + str(msg.accountName)
	if msg.typeName == "UpdatePortfolio":
		print "type=UpdatePortfolio symbol=" + str(msg.contract.m_symbol) + " sectype=" + str(msg.contract.m_secType) + " exchange=" + str(msg.contract.m_exchange) + " position=" + str(msg.position) + " market_price=" + str(msg.marketPrice) + " market_value=" + str(msg.marketValue) +  " avg_cost=" + str(msg.averageCost) + " upnl=" + str(msg.unrealizedPNL) + " pnl=" + str(msg.realizedPNL) + " account_name=" + msg.accountName
	'''

if __name__ == "__main__":
	conn = ibConnection('localhost', 7496)
	conn.connect()
	conn.registerAll(handle)
	
	conn.reqCurrentTime()
	time.sleep(1)
	
	conn.reqAccountUpdates(True, "DU358071");
	time.sleep(1)
	
	symbol = "YRD"
	asset_type = "STK"
	order_type = "MKT"
	quantity = 100
	direction = "BUY"
	order_id = 60001
	
	ib_contract = create_contract(symbol, asset_type, "SMART", "SMART", "USD")
	buy_order = create_order(order_type, quantity, direction)
	conn.placeOrder(order_id, ib_contract, buy_order)
	time.sleep(1)

	sell_order = create_order(order_type, quantity, "SELL")
	order_id += 2
	conn.placeOrder(order_id, ib_contract, sell_order)
	time.sleep(1)
		
	while True:
		time.sleep(3)
	print "finish"