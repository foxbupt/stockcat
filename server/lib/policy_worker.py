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
        self.queue = config_info['queue']
        self.datamap = datamap
        self.process = None

    # 终止运行
    def stop(self):
        self.loop = False

    # 创建进程
    def start(self):
        self.process = mp.Process(self.run)
        self.process.start()

    def join(self):
        self.process.join()

    # 进程运行函数
    def run(self):
        # 根据object导入对应module获取运行函数
        (module_name, object_name) = self.worker_config['object'].split(".")
        module = __import__(module_name)
        object_creator = getattr(module, object_name)
        policy_object = object_creator(self.config_info, self.datamap)

        item_count = 0
        redis_config = self.worker_config['REDIS']
        conn = redis.StrictRedis(self.redis_config['host'], self.redis_config['port'])

        while self.loop:
            try:
                data = conn.blpop(self.queue, 1)
                if data is None:
                    cur_time = datetime.datetime.now().time()
                    cur_timenumber = cur_time.hour * 10000 + cur_time.minute * 100 + cur_time.second
                    #print "policy timenumber=" + str(cur_timenumber)

                    if cur_timenumber >= int(self.config_info['POLICY']['close_time']):
                        logging.getLogger("policy").critical("op=market_closed time=%d", cur_timenumber)
                        break
                    else:
                        continue

                item = json.loads(data)
                #print item
                if item is None:
                    continue

                item_count = item_count + 1
                if item_count % 20 == 0: # 抽样输出日志便于线下测试
                    logging.getLogger("policy").debug("desc=item_info processor=%s|%s", func_name, data); 

                for func_name in self.worker_config['processor_list']:
                    try:
                        getattr(policy_object, func_name)(item)
                    except Exception as e:
                        logging.getLogger("policy").exception("err=policy_call name=%s processor=%s", self.name, func_name)
                    else:
                        #print format_log("policy_processor", {'name': self.name, 'processor': func_name, 'sid': item['sid'], 'day': item['day']})
                        logging.getLogger("policy").debug("desc=policy_processor name=%s processor=%s sid=%d day=%d", self.name, func_name, item['sid'], item['day'])
            except Exception as e:
                logging.getLogger("policy").critical("err=pop_item name=%s", self.name)
            #self.queue.task_done()
        logging.getLogger("policy").critical("op=policy_worker_exit name=%s pid=%u", self.name, self.process.pid)

