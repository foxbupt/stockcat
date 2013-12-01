#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取sina上的业绩预测
#date: 2013-11-11

import sys, re, json, random
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from stock.items import StockEarningItem

class PredictSpider(BaseSpider):
    name = "predict"
    allowed_domains = ["vip.stock.finance.sina.com.cn", "stock.finance.sina.com.cn", "finance.sina.com.cn"]
    start_urls = []

    def __init__(self, url, day):
        self.day = int(day)
        self.url = url
        self.start_urls.append(url)

    def parse(self, response):
        hxs = HtmlXPathSelector(response)
        fetch_next_page = True

        for tr_node in hxs.select('//div[@id="divContainer"]/table[@class="list_table"]/tr'):
            if not tr_node.select(".//td"): 
                continue

            item = StockFinancePredict()
            url = ""
            publish_day = 0

            for index, td_node in enumerate(tr_node.select('.//td')):
                text = ""

                if td_node.select('.//a').extract():
                    text_content = td_node.select('.//a/text()').extract()
                    if len(text_content) > 0:
                        text = text_content[0]
                else:
                    text = td_node.select('.//text()').extract()[0]

                if index == 0:
                    item["code"] = text.encode("utf-8")
                elif index == 1:
                    item["name"] = text.encode("utf-8")
                elif index == 2:
                    item['predict'] = text.encode("utf-8")
                elif index == 3:
                    item['publish_day'] = publish_day = int(text.replace("-", ""))
                elif index == 4:
                    item["report_day"] = int(text.replace("-", ""))
                elif index == 5:
                    item['digest'] = text.encode("utf-8")
                elif index == 7 and text != "--":
                    text = text.replace("%", "")
                    parts = text.split("~")
                    print text, parts
                    item['vary_low'] = float(parts[0])

                    if len(parts) == 1:
                        item['vary_type'] = 1
                    else:
                        item['vary_type'] = 2
                        item['vary_high'] = float(parts[1])

                elif index == 8:
                    url = td_node.select('.//a/@href').extract()[0]
            
            #print item
            #print publish_day, self.day
            # 忽略过去发布的业绩预测
            if publish_day < self.day:
                fetch_next_page = False
                continue

            #print url
            print "op=fetch_predict day=" + str(self.day) + " publish_day=" + str(publish_day) + " code=" + item['code'] + " name=" + item['name'] + " digest=" + item['digest']

            request = Request(url, callback=self.parse_content)
            request.meta['item'] = item
            yield request
        
        # 获取分页列表
        if response.url == self.start_urls[0] and fetch_next_page:
            for page_node in hxs.select('//div[@class="pages"]/a'):
                text = page_node.select('.//text()').extract()[0]
                if re.search(r'\d+', text):
                    page_no = int(text)
                    if 1 == page_no:
                        continue
                    yield Request(self.url + "?p=" + str(page_no), callback=self.parse)

    # 解析摘要
    def parse_content(self, response):
        item = response.meta["item"]
        # body是页面编码GBK
        body = response.body

        start_pos = body.find("arr2[0][2]")
        if start_pos != -1:
            end_pos = body.find(";", start_pos)
            line = body[start_pos : end_pos]
            parts = line.split("'")

            content = parts[1].strip().decode('gbk').encode('utf-8')
            #print content
            item["content"] = content
            print item

            return item


