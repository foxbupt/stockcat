#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取股票的实时交易数据
#date: 2013-08-01

import sys, re, json, random
import redis
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockDataItem, StockDetailItem

class QqSpider(BaseSpider):
    name = "qq"
    allowed_domains = ["qq.com", "gtimg.cn"]

    def __init__(self, id, code, start, redis_host, redis_port):
        self.id = id
        self.code = code
        self.start = int(start)
        self.redis_host = redis_host
        self.redis_port = int(redis_port)

        url = "http://data.gtimg.cn/flashdata/hushen/minute/" + self.code + ".js?maxage=10&" + str(random.random())
        self.start_urls = [url]

    def parse(self, response):
        match_content = re.search(r'"([^"]+)"', response.body)
        if not match_content:
            return

        text = match_content.group(1).strip(" \n").replace("\\n\\", "")
        #print text
        lines = text.split("\n")
        print lines

        date_info = lines[1].split(":")
        day = int("20" + date_info[1])

        hq_time = list()
        hq_price = list()
        hq_volume = list()

        for line in lines[2:]:
            fields = line.split(" ")
            # 直接用小时+分组成的时间, 格式为HHMM
            time = int(fields[0])

            if self.start > 0 and time < self.start:
                continue

            item = StockDetailItem()
            item['sid'] = self.id
            item['code'] = self.code
            item['day'] = day
            item['time'] = time
            item['price'] = float(fields[1])
            item['volume'] = int(fields[2])

            if item['volume'] <= 0:
                continue

            hq_time.append(time)
            hq_price.append(item['price'])
            hq_volume.append(item['volume'])
            yield item

        hq_dict = {'time': hq_time, 'price': hq_price, 'volume': hq_volume}
        print hq_dict

        if len(self.redis_host) > 0:
            key = "daily-" + str(self.id) + "-" + str(day)
            conn = redis.StrictRedis(self.redis_host, self.redis_port)
            conn.set(key, json.dumps(hq_dict), 86400)

        return
