#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 运行策略分析器分析的工作线程
#date: 2014/06/27

import sys, re, json, os, traceback, logging
import datetime, time
import multiprocessing as mp
import redis
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log

class PolicyWorker():
    loop = True

    def __init__(self, name, worker_config, config_info, datamap):
        self.name = name
        self.worker_config = worker_config
        self.config_info = config_info
        self.queue = worker_config['queue']
        self.datamap = datamap

        self.process = None

    # 终止运行
    def stop(self):
        self.loop = False

    # 创建进程
    def start(self):
        self.process = mp.Process(target=self.run, group=None)
        self.process.start()

    def join(self):
        self.process.join()

    def get_pid(self):
        return 0 if self.process is None else self.process.pid 

    # 进程运行函数
    def run(self):
        # 根据object导入对应module获取运行函数
        (module_name, object_name) = self.worker_config['object'].split(".")
        module = __import__(module_name)
        object_creator = getattr(module, object_name)
        policy_object = object_creator(self.config_info, self.datamap)

        item_count = 0
        day = int("{0:%Y%m%d}".format(datetime.date.today()))
        redis_config = self.config_info['REDIS']
        conn = redis.StrictRedis(redis_config['host'], redis_config['port'])

        while self.loop:
            try:
                pop_data = conn.blpop(self.queue, 1)
                if pop_data is None:
                    cur_time = datetime.datetime.now().time()
                    cur_timenumber = cur_time.hour * 10000 + cur_time.minute * 100 + cur_time.second
                    #print "policy timenumber=" + str(cur_timenumber)

                    if cur_timenumber >= int(self.config_info['POLICY']['close_time']):
                        logging.getLogger("policy").critical("op=market_closed time=%d", cur_timenumber)
                        break
                    else:
                        continue

                data = pop_data[1]
                item = json.loads(data)
                #print item
                if item is None:
                    continue

                if 'day' in item and item['day'] != day:
                    logging.getLogger("policy").error("err=ignore_expired_item item_day=%d day=%d", item['day'], day)
                    continue

                item_count = item_count + 1
                if item_count % 20 == 0: # 抽样输出日志便于线下测试
                    logging.getLogger("policy").debug("desc=item_info count=%d processor=%s|%s", item_count, func_name, data); 

                for func_name in self.worker_config['processor_list']:
                    try:
                        getattr(policy_object, func_name)(item)
                    except Exception as e:
                        logging.getLogger("policy").exception("err=policy_call name=%s processor=%s", self.name, func_name)
                    else:
                        #print format_log("policy_processor", {'name': self.name, 'processor': func_name, 'sid': item['sid'], 'day': item['day']})
                        logging.getLogger("policy").debug("desc=policy_processor name=%s processor=%s sid=%d day=%d", self.name, func_name, item['sid'], item['day'])
            except Exception as e:
                logging.getLogger("policy").exception("err=pop_item name=%s", self.name)

        logging.getLogger("policy").critical("op=policy_worker_exit name=%s pid=%u", self.name, self.process.pid)

