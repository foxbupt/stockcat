#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 运行策略分析器分析的工作线程
#date: 2014/06/27

import sys, re, json, os,traceback
import datetime, time, threading, Queue
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log

class PolicyWorker(threading.Thread):
    loop = True

    def __init__(self, name, index, worker_config, config_info, datamap):
        threading.Thread.__init__(self)

        self.name = name
        self.index = index
        self.worker_config = worker_config
        self.config_info = config_info
        self.datamap = datamap
        self.queue = Queue.Queue(0)

    # 入队列操作
    def enqueue(self, item):
        self.queue.put(item)

    # 终止运行
    def stop(self):
        self.loop = False

    def run(self):
        # 根据object导入对应module获取运行函数
        (module_name, object_name) = self.worker_config['object'].split(".")
        module = __import__(module_name)
        object_creator = getattr(module, object_name)
        policy_object = object_creator(self.config_info, self.datamap)

        while True:
            #try:
            #    item = self.queue.get(False, 2)
            #except Queue.Empty:
            #    print "1111"
            #    if self.loop:
            #        continue
            #    else:
            #        break

            item = self.queue.get()
            if item is None:
                continue

            #print item
            for func_name in self.worker_config['processor_list']:
                try:
                    getattr(policy_object, func_name)(item)
                except Exception as e:
                    print traceback.format_exc()
                else:
                    print format_log("policy_processor", {'name': self.name, 'processor': func_name, 'sid': item['sid'], 'day': item['day']})

            #self.queue.task_done()
        print format_log("worker_exit", {'name':self.name, 'index': self.index})


