#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取ifeng上股票的历史数据
#date: 2013-08-01

import sys, re, json
sys.path.append('../../../../server')  
from pyutil.util import safestr
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockDataItem

class IFengSpider(BaseSpider):
    name = "ifeng"
    allowed_domains = ["ifeng.com", "finance.ifeng.com"]
    base_url = "http://finance.ifeng.com"

    def __init__(self, id, code, start_date, end_date):
        self.id = id
        self.code = code
        self.start_date = start_date
        self.end_date = end_date

        url = "http://app.finance.ifeng.com/hq/stock_daily.php?code=" + code + "&begin_day=" + start_date + "&end_day=" + end_date
        print url
        self.start_urls = [url]

    def parse(self, response):
        hxs = HtmlXPathSelector(response)
        #print len(response.body)

        #print hxs.select("//div[@class='tab01']")
        #print hxs.select("//div[@class='tab01']/table")
        #print hxs.select("//div[@class='tab01']/table/tr")
        for tr_node in hxs.select("//div[@class='tab01']/table/tr"):
            td_node_list = tr_node.select('.//td')
            if not td_node_list:
                continue

            item = StockDataItem()
            item['sid'] = self.id
            item['code'] = self.code[2:]
            
            value_list = []
            for index, td_node in enumerate(td_node_list):
                text = ""
                if td_node.select('.//span'):
                    text = td_node.select('.//span/text()').extract()[0]
                else: # 存在a标签时, 列表会有2项, 第2项为日期
                    #print td_node.select('.//text()').extract()
                    text = td_node.select('.//text()').extract()[-1]

                #print safestr(text)
                # 第1列为日期
                value = text
                if 0 == index:
                    value = text.replace("&nbsp;", "").replace("-", "")
                else:
                    match_text = re.search(r"(-?\d+(\.\d+)?)", text)
                    if match_text:
                        value = match_text.group(0)
                value_list.append(value)
                #print value_list

            item['day'] = int(value_list[0])
            item['open_price'] = float(value_list[1])
            item['high_price'] = float(value_list[2])
            item['low_price'] = float(value_list[3])
            item['close_price'] = float(value_list[4])
            item['volume'] = int(value_list[5])
            item['amount'] = int(value_list[6])
            item['vary_price'] = float(value_list[7])
            item['vary_portion'] = float(value_list[8])
        
            yield item
