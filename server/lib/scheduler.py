#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取数据的调度框架类
#date: 20134/06/23

import sys, re, json, random, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from fetch_worker import FetchWorker
from stock_util import get_stock_list, get_past_openday

class Scheduler(object):
    worker_list = []
    datamap = {}
    interval = 60
    # 市场状态: 0 闭市  1 开市  2 午间休息
    state = 0

    def __init__(self, config_info):
        self.config_info = config_info
        #print self.config_info
        self.interval = int(config_info['FETCH']['interval'])
        self.db_config = config_info['DB']
        self.redis_config = config_info['REDIS']

        self.day = int("{0:%Y%m%d}".format(datetime.date.today()))

    # 核心运行函数, 每隔interval秒检测当前时间是否处于开市区间内.
    def core(self):
        hushen_content = open(self.config_info['FETCH']['hushen']).read()
        hushen_config = json.loads(hushen_content)
        print hushen_config

        while True:
            try:
                cur_time = datetime.datetime.now().time()
                cur_timenumber = cur_time.hour * 10000 + cur_time.minute * 100 + cur_time.second
                print "scheduler timenumber=" + str(cur_timenumber)
                
                if cur_timenumber < hushen_config['am_open']:
                    time.sleep(min(hushen_config['am_open'] - cur_timenumber, self.interval))
                    continue

                if cur_timenumber > hushen_config['am_close'] + int(hushen_config['close_delay']/60) * 100 + hushen_config['close_delay']%60 and cur_timenumber < hushen_config['pm_open']:
                    if self.state == 1:
                        self.pause()
                    time.sleep( min(hushen_config['pm_open'] - cur_timenumber, self.interval) )
                    continue

                # 下午收盘后需要把抓取数据的线程结束掉
                if cur_timenumber > hushen_config['pm_close'] + int(hushen_config['close_delay']/60) * 100 + hushen_config['close_delay']%60:
                    if self.state == 1:
                        self.terminate()
                        return
                    time.sleep(self.interval)
                    continue

                # 早上开盘, 需要初始化工作
                if self.state == 0:
                    self.start(hushen_config, cur_timenumber)
                elif self.state == 2:
                   self.resume()

                time.sleep(self.interval)
            # 捕获键盘输入的Ctrl+C 终止线程运行
            except (KeyboardInterrupt, SystemExit):
                self.terminate()
                return

    # 启动
    def start(self, hushen_config, cur_timenumber):
        self.prepare_data()

        for worker_config in hushen_config['fetch_list']:
            worker = FetchWorker(worker_config, self.config_info, self.datamap)
            worker.start()
            self.worker_list.append(worker)

        self.state = 1

    # 午间盘中休息
    def pause(self):
        self.state = 2
        for worker in self.worker_list:
            worker.control(self.state)

    # 下午开盘恢复
    def resume(self):
         self.state = 1
         for worker in self.worker_list:
             worker.control(self.state)

    # 闭市时的清理工作
    def terminate(self):
        self.state = 0

        for worker in self.worker_list:
            worker.control(self.state)
            if worker.isAlive():
                worker.join()

        del self.worker_list[0:-1]

    # 计算HHMMSS格式时间数值之间的差异, 返回差额的秒
    def time_diff(self, t1, t2):
        hour_diff = int(t1/10000) - int(t2/10000)
        min_diff = int(t1%10000/100) - int(t2%10000/100)
        second_diff = int(t1%100) - int(t2%100)

        return hour_diff * 3600 + min_diff * 60 + second_diff

    # 准备公共数据集
    def prepare_data(self):
        # 获取所有的股票列表
        stock_list = get_stock_list(self.db_config, 1)
        self.datamap['stock_list'] = stock_list
        #print len(self.datamap['stock_list'])

        code2id_map = dict()
        id2scode_map = dict()
        for sid, stock_info in stock_list.items():
            scode = stock_info['ecode'].lower() + stock_info['code']
            code2id_map[stock_info['code']] = sid
            id2scode_map[sid] = scode

        self.datamap['code2id'] = code2id_map
        self.datamap['id2scode'] = id2scode_map

        last_open_day = get_past_openday(str(self.day), 1)
        #print last_open_day
        cont_list = []
        try:
            db_conn = SqlUtil.get_db(self.db_config)
            sql = "select sid from t_stock_cont where day = " + last_open_day + " and status = 'Y'"
            #print sql
            record_list = db_conn.query_sql(sql)
        except Exception as e:
            print e
            return

        for stock_data in record_list:
            cont_list.append(int(stock_data['sid']))
        self.datamap['cont_list'] = cont_list
        #print len(self.datamap['cont_list'])

        threshold_list = []
        try:
            db_conn = SqlUtil.get_db(self.db_config)
            sql = "select sid from t_stock_price_threshold where day = " + last_open_day + " and (high_type = 1 or high_type = 2 or low_type = 1 or low_type = 2) and status = 'Y'"
            price_record_list = db_conn.query_sql(sql)
        except Exception as e:
            print e
            return

        for stock_data in price_record_list:
            threshold_list.append(int(stock_data['sid']))
        self.datamap['threshold_list'] = threshold_list
        #print len(self.datamap['threshold_list'])

        self.datamap['pool_list'] = list(set(cont_list + threshold_list))
        #print self.datamap['pool_list']
        return

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf>"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

    scheduler = Scheduler(config_info)
    scheduler.core()
    
