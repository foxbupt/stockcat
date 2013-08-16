#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取sina上的股票重大公告
#date: 2013-08-06

import sys, re, json, datetime 
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from stock.items import StockEventItem

class EventSpider(BaseSpider):
    name = "event"
    allowed_domains = ["sina.com.cn", "finance.sina.com.cn"]

    def __init__(self, code, day, interval):
        self.day = day
        self.code = code
        self.interval = int(interval)

        url = "http://vip.stock.finance.sina.com.cn/api/jsonp.php/var%20noticeData=/CB_AllService.getMemordlistbysymbol?num=8&PaperCode=" + code
        #print url
        self.start_urls = [url]

    def parse(self, response):
        match_notice = re.search(r'(\[[^\]]+\])', response.body)
        if not match_notice:
            return

        content = match_notice.group(1)
        #尼玛: title不带""导致json无法解析
        content = content.replace('title', '"title"').replace('date', '"date"').replace('id', '"id"')
        #print content.decode('gbk').encode('utf-8')

        notice_list = json.loads(content, encoding='gbk')
        #print notice_list

        for notice_info in notice_list:
            event_date = notice_info['date'].replace("-", "")
            if not self.check(event_date):
                continue

            item = StockEventItem()
            item['code'] = self.code
            item['title'] = notice_info['title']
            #item['event_id'] = int(notice_info['id'])
            item['event_date'] = event_date

            yield item
            
    # 检查事件日期是否满足区间的需求
    def check(self, event_date):
        if self.interval == 0:
            return True

        current_time = datetime.datetime(int(self.day[0:4]), int(self.day[4:6]), int(self.day[6:8]))     
        end_time = current_time + datetime.timedelta(days = self.interval)
        start_time = current_time + datetime.timedelta(days = -1 * self.interval)

        end_day = int("{:%Y%m%d}".format(end_time))
        start_day = int("{:%Y%m%d}".format(start_time))
        event_day = int(event_date)

        #print start_day, end_day, event_day

        return start_day <= event_day and event_day <= end_day 
