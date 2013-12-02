#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 股票分析器基类, 评估单只股票的交易
#date: 2013-08-21

import sys, re, json, random
import datetime, time
sys.path.append('../../../../server')  
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn
import redis
from analyze_helper import AnalyzerHelper
from stock_util import get_stock_data
from policy_util import PolicyUtil

class StockAnalyzer:
    sid = 0
    config_info = dict()
    helper = None

    def __init__(self, sid, config_info):
        self.sid = sid
        self.config_info = config_info
        self.helper = AnalyzerHelper(self.config_info)

        self.db_config = config_info['DB']
        self.db_conn = SqlUtil.get_db(config_info['DB'])

    '''
        @desc: 分析该股票是否值得买入卖出
        @param: day int 所在日期
        @param: policy_info dict() 分析策略
        @return dict() 分析详情
    '''
    def evaluate(self, day, policy_info):
        print day
        if not self.prepare(day, policy_info):
            return None

        matched = self.check(day, policy_info)
        return matched

    '''
        @desc: 准备分析需要的数据
        @param: day int
        @param: policy_info dict
        @return bool
    '''
    def prepare(self, day, policy_info):
        self.stock_info = self.helper.get_stock_info(self.sid)
        if self.stock_info is None:
            return False

        # 获取当天股票的总览数据, 若当天停牌无数据, 则不需要计算
        self.stock_data = get_stock_data(self.db_config, day, self.sid)
        if not self.stock_data:
            return False

        # TODO: 获取当天的资金流向

        # 获取股票符合的标签列表
        self.stock_var = self.helper.get_stock_varlist(self.sid, day)

        # 获取股票价格近期的高/低点
        self.stock_threshold = self.helper.get_price_threshold(self.sid, day)

        items = policy_info['items']
        self.var_list = PolicyUtil.get_varlist(self.db_config)
        #print self.var_list

        # 获取数据字典
        self.data_map = self.make_datamap()

        # TODO: 根据分析器的var_list获取历史数据, 目前固定获取最近30日的数据
        self.hist_data = self.helper.get_histdata_limit(self.sid, day, 30)

        print self.stock_info
        print self.stock_data
        print self.stock_var
        print self.stock_threshold
        print self.data_map

        return True

    '''
        @desc: 检查股票数据是否符合指定分析器条件
        @param: day int 分析日期
        @param policy_info dict 分析策略
        @return bool
    '''
    def check(self, day, policy_info):
        condition = policy_info['condition']

        return self.check_condition(day, condition, items)

    '''
        @desc: 检查某个条件树节点的逻辑结果
        @param: day int
        @param: condition dict
        @param: items dict
        @return: bool
    '''
    def check_condition(self, day, condition, items):
        item_id = condition['item_id']
        logic = int(condition['logic'])
        item_info = items[item_id]
        print item_id, logic, item_info['node_type']

        # 父母节点
        if 1 == int(item_info['node_type']):
            result = True if 1 == logic else False
            for child_node in condition['children']:
                child_iteminfo = items[child_node['item_id']]
                #print logic, child_node['item_id'], child_iteminfo['node_type']
                if 1 == int(child_iteminfo['node_type']):
                    child_result = self.check_condition(day, child_node, items)
                else:
                    child_result = PolicyUtil.check_item(day, child_iteminfo, self.var_list, self.data_map, self.stock_var, self.hist_data)
                    print logic, child_node['item_id'], child_result

                if 1 == logic:  # and
                    result = result and child_result
                    if result is False:
                        return result
                else: # or
                    result = result or child_result
                    if result is True:
                        return result
            return result

        # 叶子节点
        else:
            return PolicyUtil.check_item(day, item_info, self.var_list, self.data_map, self.stock_var, self.hist_data)

    '''
        @desc: 组装变量数据字典, 数据分为3部分:
            1) 股票基本信息: t_stock基本字段和pe/pb/capitalisation/out_capitalisation
            2) 当天行情数据: t_stock_data/t_stock_fund
            3) 近期阶段数据: t_stock_price_threshold/t_stock_var 
        @return dict
    '''
    def make_datamap(self):
        datamap = dict()

        base_keys = ['code', 'name', 'capital', 'out_capital', 'pinyin', 'ecode', 'profit', 'assets', 'hist_high', 'hist_low', 'year_high', 'year_low']
        for key in base_keys:
            datamap[key] = self.stock_info[key]

        datamap['day60_high'] = self.stock_info['month6_high']
        datamap['day60_low'] = self.stock_info['month6_low']
        datamap['day30_high'] = self.stock_info['month3_high']
        datamap['day30_low'] = self.stock_info['month3_low']

        day_keys = ['open_price', 'close_price', 'high_price', 'low_price', 'volume', 'amount', 'vary_price', 'vary_portion']
        for key in day_keys:
            datamap[key] = self.stock_data[key]

        datamap["cur_price"] = cur_price = float(self.stock_data["close_price"])
        # 换手率 = 成交量(手) / 流通股本(亿股) * 100
        datamap["exchange_portion"] = int(self.stock_data['volume']) / float(self.stock_info['out_capital']) / 10000
        datamap["capitalisation"] = cur_price * float(self.stock_info['capital'])
        datamap["out_capitalisation"] = cur_price * float(self.stock_info['out_capital'])

        # 计算市盈率和 市净率, TODO: 后续计算动态市盈率
        profit = float(self.stock_info['profit'])
        if profit <= 0:
            datamap['pe'] = 0
        else:
            datamap['pe'] = int(cur_price / profit)

        asset = float(self.stock_info['assets'])
        if asset <= 0:
            datamap['pb'] = 0
        else:
            datamap['pb'] = float(cur_price / asset)

        # 获取当前价格离30/60日/年/历史新低/新高的差额和涨幅比例,
        # 差额从高到低计算, 所有涨幅都是从低到高计算
        common_prefix = ["day30", "day60", "year", "hist"]
        for key in common_prefix:
            low_prefix = key + "_low"
            datamap[low_prefix + "_vary_price"] = cur_price - float(datamap[low_prefix])
            datamap[low_prefix + "_vary_portion"] = (cur_price - float(datamap[low_prefix])) / float(datamap[low_prefix]) * 100

            high_prefix = key + "_high"
            datamap[high_prefix + "_vary_price"] = float(datamap[high_prefix]) - cur_price
            datamap[high_prefix + "_vary_portion"] = (float(datamap[high_prefix]) - cur_price) / cur_price * 100

        # 获取股票价格最近的高点/低点
        match_low = False
        match_high = False
        for threshold_record in self.stock_threshold:
            if match_low and match_high:
                break

            if not match_low and int(threshold_record['low_type']) > 0:
                datamap['last_low_day'] = int(threshold_record['day'])
                datamap['last_low_price'] = float(threshold_record['price')
                datamap['last_low_type'] = int(threshold_record['low_type'])
                match_low = True

            elif not match_high and int(threshold_record['high_type']) > 0:
                datamap['last_high_day'] = int(threshold_record['day'])
                datamap['last_high_price'] = float(threshold_record['price'])
                datamap['last_high_type'] = int(threshold_record['high_type'])
                match_high = True

        return datamap
