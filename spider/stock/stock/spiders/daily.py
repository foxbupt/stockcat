#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取ifeng上股票的每日总览数据
#date: 2013-08-06

import sys, re, json, random
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from stock.items import StockDataItem

class DailySpider(BaseSpider):
    name = "daily"
    allowed_domains = ["ifeng.com", "finance.ifeng.com"]
    code2id = dict()
    start_urls = []

    # 传入列表文件, 批量请求
    def __init__(self, filename, request_count, day):
        self.day = day
        count = 0
        code_list = []
        file = open(filename)

        while True:
            line = file.readline().strip("\r\n")
            if not line:
                break

            fields = line.split()
            #print fields, fields[1:]
            self.code2id[fields[1]] = fields[0]

            count += 1
            code_list.append(fields[1])
            if count % int(request_count) == 0:
                url = "http://hq.finance.ifeng.com/q.php?l=" + ",".join(code_list) + "&f=json&&r=" + str(random.random())
                self.start_urls.append(url)
                del code_list[:]

        file.close()

    def parse(self, response):
        body = response.body
        parts = body.split("=")
        content = parts[1].strip(";")
        #print content

        daily_map = json.loads(content)
        #print daily_map
        for stock_code, stock_data in daily_map.items():
            if not stock_data:
                continue
            
            # 当日停牌则不能存入
            open_price = float(stock_data[4])
            close_price = float(stock_data[5])
            if open_price == 0.0 or close_price == 0.0:
                continue

            item = StockDataItem()

            item['code'] = stock_code[2:]
            item['sid'] = self.code2id[stock_code]
            item['day'] = int(self.day)
            item['open_price'] = stock_data[4]
            item['high_price'] = stock_data[5]
            item['low_price'] = stock_data[6]
            item['close_price'] = stock_data[0]
            item['vary_price'] = stock_data[2]
            item['vary_portion'] = stock_data[3]
            # 成交量转化为手
            item['volume'] = int(int(stock_data[9]) / 100)
            # 成交额转化为万元
            item['amount'] = int(float(stock_data[10]) / 10000)

            yield item
