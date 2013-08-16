#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取同花顺上的概念板块
#date: 2013-08-13

import sys, re, json
#sys.path.append('../../../server')  
#from pyutil.util import safestr
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockTagItem

class TagSpider(BaseSpider):
    name = "tag"
    allowed_domains = ["10jqka.com", "10jqka.com.cn"]
    base_url = "http://www.10jqka.com"

    def __init__(self, url, category):
        self.url = url
        self.category = category
        self.start_urls = [url]

    # 解析行业页面
    def parse(self, response):
        hxs = HtmlXPathSelector(response)

        url_fields = self.url.rstrip("/").split("/")
        self.slug = url_fields[-1]

        # 解析页码
        page_list = []
        for page_no_str in hxs.select('//div[@class="m_page main_page"]/a/text()').extract():
            if page_no_str.isdigit():
                page_list.append(int(page_no_str))
        page_count = max(page_list)
         
        prefix = "http://q.10jqka.com.cn/interface/stock/" + self.slug + "/zdf/desc/"
        for page_index in range(1, page_count+1):
            api_url = prefix + str(page_index) + "/quote/quote"
            print api_url
            yield Request(api_url, callback=self.parse_data)
            
    # 解析api的请求应答        
    def parse_data(self, response):
        resp = json.loads(response.body)

        for tag_info in resp['data']:
            item = StockTagItem()
            item['name'] = tag_info['platename']
            item['category'] = self.category
            item['slug'] = tag_info['hycode']

            count = int(tag_info['num'])
            page_count = int(count / 10)
            if count % 10 > 0:
                page_count += 1

            for page_index in range(1, page_count+1):
                url = "http://q.10jqka.com.cn/interface/stock/detail/zdf/desc/" + str(page_index) + "/3/" + item['slug']
                print url

                request = Request(url, callback = self.parse_tag)
                request.meta['item'] = item
                yield request

    def parse_tag(self, response):
        load_json = True
        try:
            resp = json.loads(response.body)
        except ValueError as e:
            load_json = False

        metaitem = response.meta['item']
        if load_json:
            for stock_info in resp['data']:
                item = StockTagItem(metaitem)
                item['stock_code'] = stock_info['stockcode']
                yield item
        else: #json解析失败, 用正则表达式查找
            list = re.findall(r'"stockcode"\s*:\s*"(\d+)"', response.body) 
            print list
            for stock_code in list:
                item = StockTagItem(metaitem)
                item['stock_code'] = stock_code
                yield item
                
