#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 抓取数据的工作线程
#date: 20134/06/23

import sys, re, json, random, os
import datetime, time, threading, weakref
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log

class FetchWorker(threading.Thread):
    def __init__(self, worker_config, config_info, datamap):
        threading.Thread.__init__(self)

        self.worker_config = worker_config
        self.name = self.worker_config['name']
        self.object_name = self.worker_config['object']
        self.interval = self.worker_config['interval']
        self.state = 1
        self.config_info = config_info
        self.datamap = datamap

    # 设置state控制worker的运行状态
    def control(self, state):
        self.state = state

    def run(self):
        # 根据call_name导入对应module获取运行函数
        (module_name, class_name) = self.object_name.split(".")
        module = __import__(module_name)
        object_creator = getattr(module, class_name)
        parrel_object = None

        # 运行次数
        run_count = 0
        day =  int("{0:%Y%m%d}".format(datetime.date.today()))

        if not hasattr(threading.current_thread(), "_children"):
            threading.current_thread()._children = weakref.WeakKeyDictionary()

        while True:
            try:
                #print "worker state=" + str(self.state)
                if 0 == self.state:
                    # 已经运行结束时需要保存中间状态
                    if run_count > 0:
                        parrel_object.save()
                    break
                elif 2 == self.state:
                    if run_count > 0:
                        parrel_object.save()

                    time.sleep(self.interval)
                    continue

                run_count += 1
                if run_count == 1:
                    parrel_object = object_creator(day, self.config_info, self.datamap, self.worker_config)
                    parrel_object.load()

                cost_time = 0
                if parrel_object:
                    before_timestamp = time.time()
                    parrel_object.run()
                    cost_time = round(time.time() - before_timestamp, 1)

                print format_log("fetch_worker", {'name':self.name, 'object':self.object_name, 'interval':self.interval, 'day': day, 'run_count':run_count, 'cost_time': cost_time})
                time.sleep(self.interval)
            except (KeyboardInterrupt, SystemExit):
                print "op=user_exit"
                return

        print format_log("worker_run_done", {'name':self.name, 'object':self.object_name, 'interval':self.interval, 'day': day, 'run_count':run_count})


