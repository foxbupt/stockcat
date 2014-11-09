#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取sina上每股的每日总览数据
#date: 2014/11/09

import sys, re, json, random
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from stock.items import StockDataItem

class SinaUsDailySpider(BaseSpider):
    name = "sinausdaily"
    allowed_domains = ["hq.sinajs.cn", "sinajs.cn"]
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
            stock_code = fields[1][2:]
            self.code2id[stock_code] = int(fields[0])
            code_list.append("gb_" + stock_code.lower())
        file.close()
        
        count = len(code_list)
        offset = 0
        while offset < count:
            end = min(offset + int(request_count), count)
            code_str = ",".join(code_list[offset:end])
            offset = end

            url = "http://hq.sinajs.cn/?_=" + str(random.random()) + "&list=" + code_str
            print url
            self.start_urls.append(url)

    def parse(self, response):
        body = response.body
        lines = body.strip("\n").split(";")

        for line in lines:
            line = line.strip("\r\n")
            if len(line) == 0:
                continue

            parts = line.split("=")
            #print line, parts
            stock_code = parts[0].replace("var hq_str_gb_", "").upper()  
            sid = self.code2id[stock_code]
            content = parts[1].strip('"')
            print stock_code, sid, content

            fields = content.split(",")
            #print fields
            if len(fields) < 28:
                #line_str = safestr(line)
                print "err=daily_lack_fields sid=" + str(sid) + " code=" + stock_code + " line={" + line + "}"
                continue

            # 当日停牌则不能存入
            open_price = float(fields[5])
            close_price = float(fields[1])
            if open_price == 0.0 or close_price == 0.0:
                print "err=daily_stopped sid=" + str(sid) + " code=" + stock_code + " open_price=" + str(open_price) + " close_price=" + str(close_price)
                continue

            item = StockDataItem()

            try:
                #item['name'] = safestr(fields[0])
                item['code'] = stock_code
                item['sid'] = sid
                item['day'] = int(self.day)
                item['last_close_price'] = float(fields[26])
                item['open_price'] = open_price
                item['high_price'] = float(fields[6])
                item['low_price'] = float(fields[7])
                item['close_price'] = close_price
                item['vary_price'] = float(fields[4])
                item['vary_portion'] = float(fields[2])
                # 成交量单位为股
                item['volume'] = int(fields[10])
                # 成交额转化为万元
                item['amount'] = 0
                # 总股本
                capital = float(fields[19])
                # 总市值
                #item['cap'] = float(fields[12])
                # 计算换手率
                if capital > 0:
                    item['exchange_portion'] = item['volume'] / capital * 100
                item['swing'] = (item['high_price'] - item['low_price']) / item['last_close_price'] * 10
            except Exception as e:
                continue

            if capital > 0:
                capital = capital / 10000
                print 'op=update_sql sql=[update t_stock set capital={0:.2f}, out_capital={1:.2f} where id = {2:d};]'.format(capital, capital, item['sid'])

            print item
            yield item
