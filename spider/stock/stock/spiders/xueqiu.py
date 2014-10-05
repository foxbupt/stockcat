#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取雪球上的美股股票列表
#date: 2014/10/05

import sys, re, json, random
import time
sys.path.append('../../../server')  
from pyutil.util import safestr
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockItem

class XueQiuSpider(BaseSpider):
    name = "xueqiu"
    allowed_domains = ["xueqiu.com"]

    def __init__(self, page_count):
        self.page_count = int(page_count)
        self.start_urls = []
        self.cookies = {"bid": "0b3f8ffc3b0e62c8dff0a60f3cc59463_hzrroxf5", "xq_a_token": "BFpJco0vb0XJXjzmk8Cgif", "xq_r_token": "w7VlSRAvY1sS67zuaYDCxY", "xq_token_expire": "Thu%20Oct%2016%202014%2020%3A25%3A53%20GMT%2B0800%20(CST)", "xq_is_login": 1}

    # 获取当前时间戳, 单位为ms
    def get_milltime(self):
        timestamp = time.time()
        return int(timestamp * 1000)

    def start_requests(self):
        request_list = []
        for i in range(1, self.page_count + 1):
            url = "http://xueqiu.com/stock/cata/stocklist.json?page=" + str(i) + "&size=30&order=desc&orderby=marketCapital&type=0%2C1%2C2%2C3&_=" + str(self.get_milltime())
            print url
            request_list.append(Request(url, callback=self.parse, cookies=self.cookies))

        return request_list

    def parse(self, response):
        data = json.loads(response.body)
        if "stocks" not in data:
            return

        item_list = data['stocks']
        print len(item_list)

        code_list = []
        for info in item_list:
            code_list.append(info['symbol'])

        quotes_url = "http://xueqiu.com/stock/quote.json?code=" + ",".join(code_list) + "&_=" + str(self.get_milltime())
        print quotes_url
        request = Request(quotes_url, callback=self.parse_quotes, cookies=self.cookies)
        yield request

    # 解析批量quote的数据
    def parse_quotes(self, response):
        content = safestr(response.body)
        quotes_data = json.loads(content)

        for quote_info in quotes_data['quotes']:
            # 已退市
            if 3 == int(quote_info['flag']):
                print "op=stock_quit code=" + safestr(quote_info['symbol'])  + " name=" + safestr(quote_info['name'])
                continue

            item = StockItem()
            item['location'] = 3

            item['code'] = quote_info['symbol']
            item['name'] = quote_info['name']
            stock_name = safestr(quote_info['name'])
            exchange = safestr(quote_info['exchange'])

            if exchange == "NASDAQ":
                item['ecode'] = 4
            elif exchange == "NYSE":
                item['ecode'] = 5
            else:   # 非nasdaq/nyse的美股忽略
                #print quote_info
                print "op=stock_ignore code=" + safestr(quote_info['symbol']) + " name=" + stock_name + " exchange=" + exchange
                continue

            # 总股本 
            if len(quote_info['totalShares']) > 0:
                item['out_captial'] = float(quote_info['totalShares']) / 100000000
            # 股息
            if len(quote_info['dividend']) > 0:
                item['dividend'] = float(quote_info['dividend'])   
            # 每股净利润
            if len(quote_info['eps']) > 0:
                item['profit'] = float(quote_info['eps'])
            # 每股净资产
            if len(quote_info['net_assets']) > 0:
                item['assets'] = float(quote_info['net_assets'])

            #print item
            yield item    

