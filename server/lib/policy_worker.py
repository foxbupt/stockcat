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
from stock_util import *

class PolicyWorker():
    loop = True

    def __init__(self, location, day, name, worker_config, config_info, datamap):
        self.location = location
        self.day = day
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
        redis_config = self.config_info['REDIS']
        conn = redis.StrictRedis(redis_config['host'], redis_config['port'])
        # 默认调用loop间隔, 单位为s
        loop_interval = self.worker_config['loop_interval'] if 'loop_interval' in self.worker_config else 10

        while self.loop:
            last_timenumber = get_timenumber(self.location)
            try:
                pop_data = conn.blpop(self.queue, 1)
                if pop_data is None:
                    cur_timenumber = get_timenumber(self.location)
                    #print "policy timenumber=" + str(cur_timenumber)

                    if cur_timenumber >= int(self.config_info[get_location_name(self.location).upper()]['close_time']):
                        logging.getLogger("policy").critical("op=market_closed time=%d", cur_timenumber)
                        break
                    # 周期性调用loop函数进行任务处理
                    elif cur_timenumber - last_timenumber >= loop_interval and 'loop_function' in self.worker_config:
                        last_timenumber = cur_timenumber
                        try:
                            getattr(policy_object, self.worker_config['loop_function'])(self.location, self.day, int(cur_timenumber/100))
                        except Exception as e:
                            logging.getLogger("policy").exception("err=policy_call_loop name=%s processor=%s curtime=%d", self.name, self.worker_config['loop_function'], cur_timenumber)
                            continue
                    else:
                        continue

                data = pop_data[1]
                item = json.loads(data)
                #print item
                if item is None:
                    continue

                if 'day' in item and item['day'] != self.day:
                    logging.getLogger("policy").error("err=ignore_expired_item item_day=%d day=%d", item['day'], self.day)
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
                        logging.getLogger("policy").debug("desc=policy_processor name=%s processor=%s sid=%d", self.name, func_name, item['sid'])
            except Exception as e:
                logging.getLogger("policy").exception("err=pop_item name=%s", self.name)

        logging.getLogger("policy").critical("op=policy_worker_exit name=%s pid=%u", self.name, self.process.pid)

