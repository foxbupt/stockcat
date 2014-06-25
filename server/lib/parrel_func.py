#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取数据的实际逻辑
#date: 2014-06-24

import os, sys, random, json
import datetime, urllib2
from multiprocessing.dummy import Pool as ThreadPool
#sys.path.append('../../../server')
sys.path.append('../../../../server')
from pyutil.util import Util, safestr, format_log
from stock_util import get_predict_volume
import redis

# 并行处理的基类
class ParrelFunc(object):
    item_list = []

    def __init__(self, day, config_info, datamap, item_per_thread = 30):
        self.day = day
        self.config_info = config_info
        self.datamap = datamap
        self.item_per_thread = item_per_thread
        # 连接REDIS
        self.conn = redis.StrictRedis(self.config_info['REDIS']['host'], int(self.config_info['REDIS']['port']))

    def run(self):
        self.item_list = self.get_data()
        count = max(int(round(len(self.item_list) / self.item_per_thread)), 1)
        print len(self.item_list), count

        if count > 1:
            pool = ThreadPool(count)
            pool.map(self.core, self.item_list)
            pool.close()
            pool.join()
        else:
            map(self.core, self.item_list)

    # 获取输入的数据列表
    def get_data(self):
        return []

    # 单个item的处理函数
    def core(self, item):
        return

    # 启动/恢复运行时加载中间状态
    def load(self):
        pass

    # 中间暂停/结束运行时保存中间状态
    def save(self):
        pass

# 并行抓取当日总览数据
class ParrelDaily(ParrelFunc):

    def run(self):
        super(ParrelDaily, self).run()

    def get_data(self):
        item_list = []
        scode_list = self.datamap['id2scode'].values()
        stock_count = len(scode_list)

        offset = 0
        percount = 20
        while offset < stock_count:
            item_list.append(",".join(scode_list[offset : min(offset + percount, stock_count)]))
            offset += percount

        return item_list

    # 获取股票当前价格及成交量等信息
    def core(self, item):
        scode = item
        url = "http://qt.gtimg.cn/r=" + str(random.random()) + "q=" + scode
        print scode, url

        try:
            response = urllib2.urlopen(url, timeout=1)
            content = response.read()
        except Exception as e:
            print "err=get_stock_daily scode=" + scode
            return

        if content:
            lines = content.strip("\n").split(";")

            for line in lines:
                if 0 == len(line):
                    continue

                daily_item = self.parse_stock_daily(line)
                if daily_item is None:
                    continue

                # 追加到redis队列中
                if self.conn:
                    self.conn.rpush("daily-queue", json.dumps(daily_item))
                print format_log("fetch_daily", daily_item)

    # 解析单个股票行情数据
    def parse_stock_daily(self, line):
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
            return None

        item = dict()

        item['code'] = stock_code = fields[2]
        item['sid'] = int(self.datamap['code2id'][stock_code])
        item['day'] = self.day
        item['last_close_price'] = float(fields[4])
        item['open_price'] = open_price
        item['high_price'] = float(fields[33])
        item['low_price'] = float(fields[34])
        item['close_price'] = close_price
        # 当前时刻, 格式为YYYYmmddHHMMSS
        item['time'] = fields[30]
        item['vary_price'] = float(fields[31])
        item['vary_portion'] = float(fields[32])
        # 成交量转化为手
        item['volume'] = int(fields[36])
        item['predict_volume'] = get_predict_volume(item['volume'], item['time'][8:])
        # 成交额转化为万元
        item['amount'] = int(fields[37])
        item['exchange_portion'] = fields[38]
        item['swing'] = fields[43]

        return item

# 并行抓取股票盘中每分钟的实时价格和成交量
class ParrelRealtime(ParrelFunc):
    def run(self):
        super(ParrelRealtime, self).run()

    def get_data(self):
        item_list = []

        for sid in self.datamap['pool_list']:
            item_list.append((sid, self.datamap['id2scode'][sid]))
        #for sid, scode in self.datamap['id2scode'].items():
        #    item_list.append((sid, scode))
        return item_list

    def core(self, item):
        sid = item[0]
        scode = item[1]
        url = "http://data.gtimg.cn/flashdata/hushen/minute/" + scode + ".js?maxage=10&" + str(random.random())
        #print scode, url

        try:
            response = urllib2.urlopen(url, timeout=1)
            content = response.read()
        except Exception as e:
            print "err=get_stock_realtime sid=" + str(sid) + " exception=" + e
            return None

        content = content.strip(' ;"\n').replace("\\n\\", "")
        lines = content.split("\n")
        #print lines

        date_info = lines[1].split(":")
        data_day = int("20" + date_info[1])
        hq_item = list()

        for line in lines[2:]:
            fields = line.split(" ")
            # 直接用小时+分组成的时间, 格式为HHMM
            time = int(fields[0])

            data_item = dict()
            data_item['time'] = time
            data_item['price'] = float(fields[1])
            data_item['volume'] = int(fields[2])

            if data_item['volume'] <= 0:
                continue

            hq_item.append(data_item)

        # 表示当天所有的成交量都为0, 当天停牌
        if len(hq_item) == 0:
            return

        self.conn.rpush("realtime-queue", json.dumps({'sid': sid, 'day': data_day, 'items': hq_item}))
        print format_log("fetch_realtime", {'sid': sid, 'scode': scode, 'time': hq_item[len(hq_item) - 1]['time'], 'price': hq_item[len(hq_item) - 1]['price']})

 # 并行抓取股票盘成交明细
class ParrelTransaction(ParrelFunc):
    pos_map = dict()
    ignore_set = set()

    def run(self):
        super(ParrelTransaction, self).run()

    def load(self):
        key = "ts-overview-" + str(self.day)
        value = self.conn.get(key)
        if value: 
            self.pos_map = json.loads(value)

    def save(self):
        if len(self.pos_map) > 0:
            key = "ts-overview-" + str(self.day)
            self.conn.set(key, json.dumps(self.pos_map), 86400)

    def get_data(self):
        item_list = []

        #print self.datamap['pool_list']
        for sid in self.datamap['pool_list']:
            item_list.append((sid, self.datamap['id2scode'][sid]))
        return item_list

    def core(self, item):
        sid = item[0]
        scode = item[1]
        if sid in self.ignore_set:
            return

        if sid in self.pos_map:
            (pno, last_time) = self.pos_map[sid]
        else:
            pno = last_time = 0

        url = "http://stock.gtimg.cn/data/index.php?appn=detail&action=data&c=" + scode + "&p=" + str(pno)
        #print scode, url

        try:
            response = urllib2.urlopen(url, timeout=1)
            content = response.read()
        except Exception as e:
            print "err=get_stock_transaction sid=" + str(sid) + " pno=" + str(pno) + " exception=" + str(e.reason)
            return None

        # 拉取内容为空, 表明股票当天停牌, TODO: 加入公共的停牌列表中
        if 0 == len(content.strip()):
            self.ignore_set.add(sid)
            return

        lines = content.split('"')
        if len(lines) < 2:
            print format_log("invalid_resp", {'sid': sid, 'scode': scode, 'content': content})
            return
        #print lines

        elements = lines[1].split("|")
        transaction_list = []

        for element in elements:
            field_list = element.split("/")
            #print field_list
            transaction = dict()

            data_time = int(field_list[1].replace(":", ""))
            if data_time <= last_time:
                continue

            transaction['time'] = data_time
            transaction['price'] = float(field_list[2])
            transaction['vary_price'] = float(field_list[3])
            transaction['volume'] = int(field_list[4])
            transaction['amount'] = int(field_list[5])
            # 类型为B/S/M, 分别代表买盘/卖盘/中性盘
            transaction['type'] = field_list[6]

            transaction_list.append(transaction)

        transaction_count = len(transaction_list)
        #print transaction_list, transaction_count
        print format_log("fetch_transaction", {'sid': sid, 'scode': scode, 'p': pno, 'last_time': last_time, 'detail_count': transaction_count})

        if transaction_count > 0:
            # 每个时间段达到70笔成交记录时, p需要加1
            if transaction_count == 70:
                pno += 1

            #更新pno和last_time的值
            last_time = transaction_list[-1]['time']
            self.pos_map[sid] = (pno, last_time)

            self.conn.rpush("ts-queue", json.dumps({'sid': sid, 'day': self.day, 'items': transaction_list}))
