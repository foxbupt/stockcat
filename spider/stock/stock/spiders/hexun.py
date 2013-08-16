#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取和讯上的每日研报
#date: 2013-08-05

import sys, re, json
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockReportItem

class HexunSpider(BaseSpider):
    name = "hexun"
    allowed_domains = ["hexun.com", "stock.hexun.com"]
    base_url = "http://yanbao.stock.hexun.com/"
    start_urls = ["http://yanbao.stock.hexun.com/listnews.aspx?type=1"]

    def __init__(self, day):
        self.day = int(day)

    def parse(self, response):
        hxs = HtmlXPathSelector(response)

        for tr_node in hxs.select('//div[@class="fxx_table"]/table/tr'):
            if not tr_node.select('.//td'): 
                continue

            item = StockReportItem()
            url = ""
            report_day = 0

            for index, td_node in enumerate(tr_node.select('.//td')):
                text = ""

                if td_node.select('.//a').extract():
                    text_content = td_node.select('.//a/text()').extract()
                    if len(text_content) > 0:
                        text = text_content[0]
                else:
                    text = td_node.select('.//text()').extract()[0]

                #print text
                if index == 0:
                    item['title'] = text
                    url = td_node.select('.//a/@href').extract()[0]
                    url = self.base_url + url
                elif index == 1:
                    item['agency'] = text
                elif index == 3:
                    item['rank'] = text
                elif index == 4:
                    report_day = int(text.replace("-", ""))

            #print report_day
            if report_day != self.day:
                continue

            parts = item['title'].encode('utf-8').split("：")
            item['name'] = parts[0].strip()
            #print item['name']
            item['day'] = report_day
            print "op=fetch_report day=" + str(self.day) + " name=" + item['name'] + " title=" + item['title'].encode('utf-8')

            request = Request(url, callback=self.parse_article)
            request.meta['item'] = item
            yield request

        # 获取分页列表, 只取第2页
        if response.url == self.start_urls[0]:
            for page_node in hxs.select('//div[@class="hx_paging"]/ul/li/a'):
                text = page_node.select('.//text()').extract()[0]
                if re.search(r'\d+', text):
                    page_number = int(text)
                    if 2 == page_number:
                        yield Request(self.base_url + page_node.select('.//@href').extract()[0], callback=self.parse)
                        break

    # 获取研报具体信息
    def parse_article(self, response):
        hxs = HtmlXPathSelector(response)
        item = response.meta['item']

        content_list = []
        for text in hxs.select('//div[@class="yj_bglc"]/p[@class="txt_02"]/text()').extract():
            text = text.strip()
            if text:
                content_list.append("<p>" + text + "</p>")

        item['content'] = "\n".join(content_list)
        item['goal_price'] = 0.0

        match_goal = re.search(r'目标价(\d+\.\d+)元', item['content'].encode('utf-8'))
        #print match_goal
        if match_goal:
            item['goal_price'] = float(match_goal.group(1))

        return item
