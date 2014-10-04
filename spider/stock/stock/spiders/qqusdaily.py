#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取qq上每股的每日总览数据
#date: 2014/10/04

import sys, re, json, random
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from stock.items import StockDataItem

class QQUsDailySpider(BaseSpider):
    name = "qqusdaily"
    allowed_domains = ["qt.gtimg.cn", "qtimg.cn"]
    code2id = dict()
    start_urls = []

    # 传入列表文件, 批量请求
    def __init__(self, filename, request_count, day):
        self.day = day
        code_list = []
        file = open(filename)

        while True:
            line = file.readline().strip("\r\n")
            if not line:
                break

            fields = line.split()
            #print fields, fields[1:]
            self.code2id[fields[1][2:]] = fields[0]
            code_list.append(fields[1])
        file.close()
        
        count = len(code_list)
        offset = 0
        while offset < count:
            end = min(offset + int(request_count), count)
            code_str = ",".join(code_list[offset:end])
            offset = end

            url = "http://qt.gtimg.cn/r=" + str(random.random()) + "q=" + code_str
            print url
            self.start_urls.append(url)

    def parse(self, response):
        body = response.body
        lines = body.strip("\n").split(";")

        for line in lines:
            if len(line) == 0:
                continue

            parts = line.split("=")
            #print line, parts
            content = parts[1].strip('"')
            #print content

            fields = content.split("~")
            #print fields

            # 当日停牌则不能存入
            open_price = float(fields[5])
            close_price = float(fields[3])
            if open_price == 0.0 or close_price == 0.0:
                continue

            item = StockDataItem()

            show_code = fields[2]
            rindex = show_code.rfind(".")
            if -1 == rindex:
                continue

            item['code'] = stock_code = fields[2][0:rindex]
            print stock_code
            item['sid'] = self.code2id[stock_code]
            item['day'] = int(self.day)
            item['last_close_price'] = fields[4]
            item['open_price'] = open_price
            item['high_price'] = fields[33]
            item['low_price'] = fields[34]
            item['close_price'] = close_price
            item['vary_price'] = fields[31]
            item['vary_portion'] = fields[32]
            # 成交量转化为手
            item['volume'] = int(fields[36])
            # 美股缺少成交额的数据, 统一为0
            item['amount'] = int(fields[37])
            # 美股缺少换手率
            #item['exchange_portion'] = fields[38]
            # 计算振幅
            item['swing'] = (float(item['high_price']) - float(item['low_price'])) / float(item['last_close_price']) * 100

            #print item
            yield item
