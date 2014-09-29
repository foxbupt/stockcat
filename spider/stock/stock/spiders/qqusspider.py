#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取qq上的美股股票列表
#date: 2014/09/29

import sys, re, json, random
sys.path.append('../../../server')  
from pyutil.util import safestr
from scrapy.spider import BaseSpider
from scrapy.selector import HtmlXPathSelector
from scrapy.http import Request
from scrapy import log
from stock.items import StockItem

class QQUsSpider(BaseSpider):
    name = "qqus"
    allowed_domains = ["gtimg.cn", "finance.qq.com", "stockhtm.finance.qq.com", "stock.finance.qq.com]"
    base_url = "http://www.10jqka.com"
    industry = ""

    def __init__(self, category, page_count):
        for i in range(1, int(page_count) + 1):
            url = "http://stock.gtimg.cn/data/index.php?appn=usRank&t=" + category + "/volume&p=" + str(i) + "&o=0&l=80&v=list_data&_du_r_t=" + str(random.random())
            print url
            self.start_urls.append(url)

    # 解析列表页的应答包
    def parse(self, response):
        parts = response.body.split("=")
        content = safestr(parts[1].decode('gbk'))
        print content

        data = json.loads(content)
        item_list = data['data']['result']
        print len(item_list)

        for info in item_list:
            item = StockItem()
            item['location'] = 3

            code = info[0]   
            code_parts = code.split(".")
            if len(code_parts) == 2:
                ecode = code_parts[1]
                if "N" == ecode:
                    item['ecode'] = "NYSE"
                elif "OQ" == ecode:
                    item['ecode'] = "NASDAQ" 

            item['name'] = info[2]
            item['code'] = info[1]

            stock_url = "http://stockhtm.finance.qq.com/astock/ggcx/" + cpde + ".htm"
            print stock_url
            yield Request(stock_url, callback=self.parse_data)
            
    # 解析api的请求应答        
    def parse_data(self, response):
        hxs = HtmlXPathSelector(response)
        item = response.meta['item']

        # 获取股息
        col25_list = hxs.select('//span[contains(@class, "col-2-5")]/text()').extract()
        for text in col25_list:
            if text == "--":
                continue
            else:
                item['dividend'] = float(text)

        col24_list = hxs.select('//span[contains(@class, "col-2-4")]/text()').extract()
        for text in col24_list:
            if text == "--":
                continue
            else:
                text = text.replace(",", "")
                print text
                m = re.match(r"(\d+)[^0-9]*", text)
                if m:
                    item['captial'] = int(m.group(1)) / 10000

        print item

        ecode = "N"
        if item['ecode'] == "NASDAQ":
            ecode = "OQ"
        info_url = "http://stock.finance.qq.com/astock/list/view/info.php?c=" + item['code'] + "." + ecode
        print info_url

        request = Request(info_url, callback=self.parse_company)
        request.meta['item'] = item
        yield request

    # 解析公司详情
    def parse_company(self, response):
        hxs = HtmlXPathSelector(response)
        item = response.meta['item']

        for index, td_text in enumerate(hxs.select('//table/tbody/tr/td/text()').extract()):
            if 1 == index:
                item['alias'] = safestr(td_text.decode('gbk'))
            elif 8 == index:
                item['business'] = safestr(td_text.decode('gbk'))

        return item

