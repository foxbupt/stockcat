#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取同花顺上最近上市的股票数据
#date: 2014-02-23

import sys, re, json
sys.path.append('../../../../server')  
from pyutil.util import safestr
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockItem

class NewSpider(BaseSpider):
    name = "new"
    allowed_domains = ["10jqka.com", "10jqka.com.cn"]
    base_url = "http://www.10jqka.com"
    item_count = 0

    def __init__(self, start_date, end_date, page_no):
        url = "http://data.10jqka.com.cn/ipo/xgsgyzq/"
        self.url = url
        self.start_urls = [url]

        self.start_day = int(start_date)
        self.end_day = int(end_date)
        self.page_no = int(page_no)

    # 解析新股列表页面
    def parse(self, response):
        hxs = HtmlXPathSelector(response)

        # 解析页码
        page_list = []
        for page_no_str in hxs.select('//div[@class="m_page main_page"]/a/text()').extract():
            if page_no_str.isdigit():
                page_list.append(int(page_no_str))
        page_count = max(page_list)
        #print "industry=" + self.industry + " page_count=" + str(page_count)
         
        prefix = "http://data.10jqka.com.cn/ipo/xgsgyzq/page/"
        for page_index in range(1, page_count+1):
            if page_index > self.page_no:
                continue

            api_url = prefix + str(page_index) + "/ajax/1/"
            #print api_url
            yield Request(api_url, callback=self.parse_data)
            
    # 解析api的请求应答        
    def parse_data(self, response):
        hxs = HtmlXPathSelector(response)
        stock_url = "http://stockpage.10jqka.com.cn/"

        for tr_node in hxs.select("//div/table/tbody/tr"):
            td_node_list = tr_node.select(".//td")
            value_list = []
            href = ""

            for index, td_node in enumerate(td_node_list):
                text = ""
                if td_node.select('.//a'):
                    text = td_node.select(".//a/text()").extract()[0]
                    href = td_node.select(".//a/@href").extract()[0]
                else:
                    text = td_node.select(".//text()").extract()[0]
                value_list.append(text)

            print value_list
            stock_code = value_list[1]
            stock_name = value_list[2]
            publish_date_str = safestr(value_list[11].replace("-", "")).strip()
            if publish_date_str == "":
                continue

            publish_date = int(publish_date_str)
            print publish_date
            if publish_date < self.start_day or publish_date >= self.end_day:
                continue

            print safestr(stock_code), safestr(stock_name), safestr(href)
            yield Request(href, callback=self.parse_stock)

    # 解析个股详情页面的数据
    def parse_stock(self, response):
        hxs = HtmlXPathSelector(response)
        #log.msg(response.body)
        item = StockItem()

        #print hxs.select('//div/h1')
        #print hxs.select('//div/h1/a/text()').extract()
        #print hxs.select('//div/h1/a/strong/text()').extract()
        item['name'] = hxs.select('//div[@class="m_header"]/h1/a/strong/text()').extract()[0] 
        item['code'] = hxs.select('//div[@class="m_header"]/h1/a/text()').extract()[1].strip(" \t\r\n")
        print item
      
        company_node = hxs.select('//dl[contains(@class, "company_details")]')
        strong_list = company_node.select('.//dd/strong/text()').extract()
        #print strong_list

        item['captial'] = float(strong_list[0]) 
        item['out_captial'] = float(strong_list[1])
        item['profit'] = float(strong_list[4])
        item['assets'] = float(strong_list[5])
        print item
    
        company_url = "http://stockpage.10jqka.com.cn/" + item['code'] + "/company/"
        request = Request(company_url, callback=self.parse_company)  
        request.meta['item'] = item
        yield request

    # 解析公司详情
    def parse_company(self, response):
        hxs = HtmlXPathSelector(response)
        item = response.meta['item']

        span_list = hxs.select('//div[@class="bd"]/table[@class="m_table"]/tbody/tr/td/span/text()').extract()
        #print span_list
        item['company'] = span_list[0]
        item['location'] = span_list[1]

        tab2_span_list = hxs.select('//div[@class="m_tab_content2"]/table/tbody/tr/td/span/text()').extract()
        #print tab2_span_list
        item['business'] = tab2_span_list[0]

        print item
        return item

